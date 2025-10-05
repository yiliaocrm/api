<?php

namespace App\Repositorys;


use App\Models\Goods;
use App\Models\Cashier;
use App\Models\Integral;
use App\Models\CashierPay;
use App\Models\CashierDetail;

/**
 * 处方收费
 * 缺少，物品购买后积分处理
 */

class PrescriptionChargeRepository
{
	/**
	 * 收费
	 */
	public function charge()
	{
        // 账户付款明细
        $this->createPayRecord();

        // 营收明细
        $this->createCashierDetail();

        // [更新]收费通知单状态 和 {处方单}状态
        $this->updateCashierableStatus();

        // 消费物品获得积分
        $this->createIntegral();

        // 更新顾客信息
        $this->updateCustomerInfo();
	}

	/**
	 * 业务退单
	 */
	public function cancel()
	{
		
	}

	/**
	 * 账户收款明细
	 */
    public function createPayRecord()
    {
        $cashier = Cashier::getInfo(request('id'));
        $data    = [];
        $pay     = request('pay');

        foreach ($pay as $p)
        {
            $data[] = new CashierPay([
                'customer_id' => $cashier->customer_id,
                'accounts_id' => $p['accounts_id'],
                'income'      => $p['income'],
                'remark'      => $v['remark'] ?? null
            ]);
        }

        $cashier->pay()->saveMany($data);
    }

    /**
     * 处方收费时,写入营收明细
     */
    public function createCashierDetail()
    {
        $cashier  = Cashier::getInfo(request('id'));
        $pay      = request('pay');                                              // 付款明细
        $detail   = collect(request('detail'))->sortBy('goods_id');              // 按项目排序
        $paycount = collect($pay)->where('accounts_id', '<>', 1)->sum('income');  // 实收金额(不包括余额支付)
        $balance  = collect($pay)->where('accounts_id', 1)->sum('income');        // 余额支付费用
        $amount   = $paycount + $balance;                                        // 合计支付费用
        $department_id = $cashier->cashierable->department_id;
        $cd       = [];

        foreach ($detail as $k => $v)
        {
            // 费用摊到各个项目上
            $income    = 0; // 本单实收金额
            $deposit   = 0; // 本单余额支付
            $arrearage = 0; // 本单欠款金额

            if ($amount)
            {
                if ($amount >= $v['amount'])
                {
                    if ($paycount && $paycount >= $v['amount'])
                    {
                        $income  = $v['amount'];
                        $deposit = 0;
                    }
                    // 实收 && 实收 < 项目价格
                    elseif ($paycount && $paycount < $v['amount'])
                    {
                        $income  = $paycount;
                        $deposit = $v['amount'] - $paycount;
                    }
                    else
                    {
                        $income  = 0;
                        $deposit = $v['amount'];
                    }
                }
                else
                {
                    $income  = $paycount ? $paycount : 0;
                    $deposit = $balance ? $balance : 0;
                }
                $arrearage = $amount > $v['amount'] ? 0 : $v['amount'] - $amount;
            }
            else 
            {
                $income    = 0;
                $deposit   = 0;
                $arrearage = $v['amount']; 
            }
            // 扣减
            $paycount -= $income;
            $balance  -= $deposit;
            $amount   -= ($income + $deposit);

            // 创建实例
            $cd[] = new CashierDetail([
                'customer_id'   => $cashier->customer_id,
                'order_id'      => $v['id'],
                'department_id' => $department_id,
                'type_id'       => $v['goods_id'],
                'type'          => $cashier->cashierable_type,
                'payable'       => $v['amount'],
                'user_id'       => user()->id,
                'income'        => $income,
                'deposit'       => $deposit,
                'arrearage'     => $arrearage,
            ]);
        }

        $cashier->details()->saveMany($cd);
    }

    /**
     * 更新[收费通知单]和[处方单]
     */
    public function updateCashierableStatus()
    {
        $cashier   = Cashier::getInfo(request('id'));
        $pay       = request('pay');
        $income    = collect($pay)->where('accounts_id', '<>', 1)->sum('income');
        $deposit   = collect($pay)->where('accounts_id', 1)->sum('income');
        $arrearage = $cashier->payable - $income - $deposit;
                
        $cashier->update([
            'status'    => 2,          // 已收费
            'income'    => $income,    // 实收金额
            'deposit'   => $deposit,   // 余额支付
            'arrearage' => $arrearage, // 欠款金额
        ]);

        // 更新处方单
        $cashier->cashierable()->update([
        	'status' => 2	// 收款未发药
        ]);
    }

    /**
     * 根据营收明细(处方明细)生成积分
     */
    public function createIntegral()
    {
        // 系统关闭积分功能
        if (!parameter('cywebos_integral_enable'))
        {
            return false;
        }

        $cashier = Cashier::getInfo(request('id'));
        $rate    = parameter('cywebos_integral_rate');

        // 循环营收明细
        foreach ($cashier->details as $detail)
        {

            $integral = ($detail->income + $detail->deposit) * $rate; // 计算当前项目积分
            $goods    = Goods::getInfo($detail->type_id);
            $customer = $cashier->customer;

            // 开启积分
            if ($goods->integral)
            {
                Integral::create([
                    'customer_id' => $cashier->customer_id,
                    'type'        => 3,                                 // 积分类:处方
                    'type_id'     => $detail->id,                       // 营收明细id
                    'before'      => $customer->integral,               // 原有积分
                    'integral'    => $integral,                         // 变动积分
                    'after'       => $customer->integral + $integral,   // 现有积分
                    'remark'      => "消费物品：{$goods->name}\r\n实收金额:{$detail->income}\r\n余额支付：{$detail->deposit}",
                    'data'        => $detail
                ]);

                $customer->update([
                    'integral' => $customer->integral + $integral
                ]);
            }
        }
    }

    /**
     * 更新顾客信息
     * 1、累计付款金额
     * 2、累计消费金额
     */
    public function updateCustomerInfo()
    {
        $cashier  = Cashier::getInfo(request('id'));
        $customer = $cashier->customer;

        // 1、本次收费,总付款金额
        $income = $cashier->pay()->where('accounts_id', '<>', 1)->sum('income');

        // 2、本次项目消费金额
        $payable = $cashier->details()->where(function($query){
            $query->where('type', 'App\Models\Reception')->where('type_id', '<>', 1);
        })->sum('payable');

        $customer->update([
            'total_payment' => $customer->total_payment + $income,
            'amount'        => $customer->amount + $payable,
        ]);
    }
}
<?php

namespace Database\Seeders\Tenant;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParametersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $parameters = [
            [
                'name'   => 'customer_phone_unique',
                'value'  => 'true',
                'type'   => 'boolean',
                'remark' => '顾客电话不能重复使用(请谨慎修改！！)',
            ],
            [
                'name'   => 'customer_phone_max',
                'value'  => '0',
                'type'   => 'number',
                'remark' => '限制客户多少个联系电话,0不限制',
            ],
            [
                'name'   => 'customer_phone_click2show',
                'value'  => 'true',
                'type'   => 'boolean',
                'remark' => '点击显示顾客电话(防止电话外泄)',
            ],
            [
                'name'   => 'customer_tags_required',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '顾客标签必填',
            ],
            [
                'name'   => 'customer_allow_modify_medium',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '允许修改首次来源',
            ],
            [
                'name'   => 'customer_create_selection_ascription',
                'value'  => 'true',
                'type'   => 'boolean',
                'remark' => '创建客户时允许自定义选择开发人员',
            ],
            [
                'name'   => 'cywebos_integral_enable',
                'value'  => 'true',
                'type'   => 'boolean',
                'remark' => '开启积分功能',
            ],
            [
                'name'   => 'cywebos_integral_rate',
                'value'  => '1',
                'type'   => 'number',
                'remark' => '每消费1元赠送多少积分',
            ],
            [
                'name'   => 'reservation_only_self_create',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '只允许[开发人员]可以挂自己的顾客。',
            ],
            [
                'name'   => 'reservation_only_create_once',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '一个顾客只能挂一次',
            ],
            [
                'name'   => 'reservation_allow_update_arrive',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '允许修改(到院的)咨询信息。',
            ],
            [
                'name'   => 'reservation_allow_multiple_item',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '允许录入多个咨询项目(默认关闭,多个项目会引起报表数据不准!)',
            ],
            [
                'name'   => 'reservation_allow_delete_arrive',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '允许删除(到院的)咨询信息。',
            ],
            [
                'name'   => 'reservation_allow_modify_medium',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '允许修改网电媒介来源',
            ],
            [
                'name'   => 'consultant_only_self_create',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '开启首诊制(无法挂其他咨询的顾客)',
            ],
            [
                'name'   => 'consultant_allow_reception',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '允许咨询师挂号(不经过前台)',
            ],
            [
                'name'   => 'consultant_allow_modify_previous_record',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '允许修改之前的咨询记录',
            ],
            [
                'name'   => 'consultant_allow_multiple_item',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '允许录入多个咨询项目',
            ],
            [
                'name'   => 'consultant_enable_item_product_type_sync',
                'value'  => '0',
                'type'   => 'number',
                'remark' => '开启[咨询项目]与[收费项目分类]关联验证(一致/包含)',
            ],
            [
                'name'   => 'outpatient_allow_reception',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '允许医生挂号(不经过前台)',
            ],
            [
                'name'   => 'cashier_allow_modify',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '允许收银员修改收费单据',
            ],
            [
                'name'   => 'cywebos_hospital_name',
                'value'  => 'XXX医院',
                'type'   => 'string',
                'remark' => '医院名称',
            ],
            [
                'name'   => 'watermark_enable',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '水印设置',
            ],
            [
                'name'   => 'cywebos_phone_rule',
                'value'  => '/^(?:(?:0\d{2,3}[\- ]?[1-9]\d{6,7})|(?:1[3-9]\d{9})|(?:[48]00[\- ]?[1-9]\d{6}))$/',
                'type'   => 'string',
                'remark' => '联系电话验证规则(正则表达式)',
            ],
            [
                'name'   => 'cywebos_force_enable_google_authenticator',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '强制启用登录动态口令（没有动态口令无法登录！）',
            ],
            [
                'name'   => 'cywebos_enable_whitelist',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '开启IP白名单,在白名单内的IP才可以访问系统',
            ],
            [
                'name'   => 'cywebos_enable_item_product_type_sync',
                'value'  => 'true',
                'type'   => 'boolean',
                'remark' => '开启[咨询项目]与[收费项目分类]同步(按收费分类维度)',
            ],
            [
                'name'   => 'cywebos_call_center_enable',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '是否开启呼叫中心功能',
            ],
            [
                'name'   => 'cywebos_call_center_api_url',
                'value'  => '',
                'type'   => 'string',
                'remark' => '呼叫中心接口地址',
            ],
            [
                'name'   => 'cywebos_call_center_username',
                'value'  => '',
                'type'   => 'string',
                'remark' => 'API用户名',
            ],
            [
                'name'   => 'cywebos_call_center_password',
                'value'  => '',
                'type'   => 'string',
                'remark' => 'API密码',
            ],
            [
                'name'   => 'cywebos_sncode_unique',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => 'sn码不能重复',
            ],
            [
                'name'   => 'cywebos_warehouse_permission_enable',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '开启仓库权限控制(开启后,仓库管理员只能看到自己的仓库)',
            ],
            [
                'name'   => 'cywebos_sms_enable',
                'value'  => 'false',
                'type'   => 'boolean',
                'remark' => '是否开启短信功能',
            ],
            [
                'name'   => 'cywebos_sms_default_gateway',
                'value'  => 'aliyun',
                'type'   => 'string',
                'remark' => '短信默认发送网关',
            ],
            [
                'name'   => 'cywebos_sms_aliyun_access_key_id',
                'value'  => '',
                'type'   => 'string',
                'remark' => '阿里云:access_key_id',
            ],
            [
                'name'   => 'cywebos_sms_aliyun_access_key_secret',
                'value'  => '',
                'type'   => 'string',
                'remark' => '阿里云:access_key_secret',
            ],
            [
                'name'   => 'cywebos_sms_aliyun_sign_name',
                'value'  => '',
                'type'   => 'string',
                'remark' => '阿里云:短信签名',
            ],
            [
                'name'   => 'wechat_mini_app_appid',
                'value'  => '',
                'type'   => 'string',
                'remark' => '小程序app_id',
            ],
            [
                'name'   => 'wechat_mini_app_secret',
                'value'  => '',
                'type'   => 'string',
                'remark' => '小程序secret',
            ],
            [
                'name'   => 'wechat_mini_app_token',
                'value'  => '',
                'type'   => 'string',
                'remark' => '小程序token',
            ],
            [
                'name'   => 'wechat_mini_app_aes_key',
                'value'  => '',
                'type'   => 'string',
                'remark' => '小程序aes_key',
            ],
            [
                'name'   => 'rfm_recency',
                'value'  => '[90,180]',
                'type'   => 'string',
                'remark' => 'RFM指标:最近一次消费时间',
            ],
            [
                'name'   => 'rfm_frequency',
                'value'  => '[1,3]',
                'type'   => 'string',
                'remark' => 'RFM指标:消费频率',
            ],
            [
                'name'   => 'rfm_monetary',
                'value'  => '[2000,10000]',
                'type'   => 'string',
                'remark' => 'RFM指标:消费金额',
            ],
        ];
        // 添加或更新参数
        foreach ($parameters as $parameter) {
            Parameter::query()->firstOrCreate(['name' => $parameter['name']], $parameter);
        }
        // 删除多余的参数
        Parameter::query()->whereNotIn('name', array_column($parameters, 'name'))->delete();
    }
}

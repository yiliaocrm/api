<?php
namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TestImport extends BaseImport
{
    /**
     * 实际导入的数据
     *
     * 需要自己实现导入
     *
     * @param Collection $collection
     * @return mixed
     */
    protected function handle(Collection $collection): mixed
    {
        $collection->each(function ($item) {
            // 自己处理实际导入业务
             Log::info('导入数据成功 ' . json_encode($item, JSON_UNESCAPED_UNICODE));
        });

        return true;
    }

    public function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            '项目名称' => 'required',
            '项目类别' => 'required',
            '项目次数' => 'required|numeric',
            '项目原价' => 'required|numeric',
            '执行价格' => 'required|numeric',
            '项目规格' => 'nullable',
            '使用期限' => 'required|numeric',
            '费用类别' => 'required',
            '结算科室' => 'required',
            '划扣科室' => 'required',
            '需要划扣' => 'required|in:"是","否a"',
            '开单提成' => 'required|in:"是","否"',
            '统计成交' => 'required|in:"是","否"',
            '消费积分' => 'required|in:"是","否"',
        ];
    }
}

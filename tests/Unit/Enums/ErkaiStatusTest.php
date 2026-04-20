<?php

namespace Tests\Unit\Enums;

use App\Enums\ErkaiStatus;
use Tests\TestCase;

class ErkaiStatusTest extends TestCase
{
    public function test_get_label_returns_expected_text(): void
    {
        $this->assertSame('未保存', ErkaiStatus::UNSAVED->getLabel());
        $this->assertSame('待收费', ErkaiStatus::PENDING_CHARGE->getLabel());
        $this->assertSame('已成交', ErkaiStatus::DEAL->getLabel());
        $this->assertSame('已取消', ErkaiStatus::CANCELLED->getLabel());
    }

    public function test_options_returns_all_status_options(): void
    {
        $this->assertSame([
            0 => '未保存',
            1 => '待收费',
            2 => '已成交',
            3 => '已取消',
        ], ErkaiStatus::options());
    }
}

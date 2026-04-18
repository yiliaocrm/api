<?php

namespace Tests\Unit\Enums;

use App\Enums\CashierRetailStatus;
use Tests\TestCase;

class CashierRetailStatusTest extends TestCase
{
    public function test_get_label_returns_expected_text(): void
    {
        $this->assertSame('挂单', CashierRetailStatus::PENDING->getLabel());
        $this->assertSame('成交', CashierRetailStatus::DEAL->getLabel());
    }

    public function test_options_returns_value_label_map(): void
    {
        $this->assertSame([
            1 => '挂单',
            2 => '成交',
        ], CashierRetailStatus::options());
    }
}

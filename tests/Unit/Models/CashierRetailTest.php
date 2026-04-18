<?php

namespace Tests\Unit\Models;

use App\Enums\CashierRetailStatus;
use App\Models\CashierRetail;
use Tests\TestCase;

class CashierRetailTest extends TestCase
{
    public function test_status_is_cast_to_enum_and_status_text_uses_label(): void
    {
        $model = new CashierRetail([
            'status' => 1,
            'detail' => [],
        ]);

        $this->assertSame(CashierRetailStatus::PENDING, $model->status);
        $this->assertSame('挂单', $model->status_text);
    }
}

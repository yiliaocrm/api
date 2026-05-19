<?php

namespace Tests\Unit\Models;

use App\Enums\ErkaiStatus;
use App\Models\Erkai;
use Tests\TestCase;

class ErkaiTest extends TestCase
{
    public function test_status_is_cast_to_enum_and_status_text_uses_label(): void
    {
        $model = new Erkai([
            'status' => 1,
        ]);

        $this->assertSame(ErkaiStatus::PENDING_CHARGE, $model->status);
        $this->assertSame('待收费', $model->status_text);
    }
}

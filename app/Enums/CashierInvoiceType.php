<?php

namespace App\Enums;

enum CashierInvoiceType: string
{
    /**
     * 收据
     */
    case RECEIPT = 'receipt';

    /**
     * 发票
     */
    case INVOICE = 'invoice';

    public function getLabel(): string
    {
        return match ($this) {
            self::RECEIPT => '收据',
            self::INVOICE => '发票',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}

<?php

namespace App\Services\CustomerLogRemark;

use App\Models\Customer;
use App\Models\Reservation;
use App\Services\CustomerLogRemark\Contracts\RemarkProfile;
use App\Services\CustomerLogRemark\Profiles\CustomerRemarkProfile;
use App\Services\CustomerLogRemark\Profiles\DefaultRemarkProfile;
use App\Services\CustomerLogRemark\Profiles\ReservationRemarkProfile;

class CustomerLogRemarkRegistry
{
    public function profileFor(?string $logableType): RemarkProfile
    {
        return match ($logableType) {
            Customer::class => app(CustomerRemarkProfile::class),
            Reservation::class => app(ReservationRemarkProfile::class),
            default => app(DefaultRemarkProfile::class),
        };
    }
}

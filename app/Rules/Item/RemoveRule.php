<?php

namespace App\Rules\Item;

use App\Models\Item;
use App\Models\ReceptionItems;
use App\Models\ReservationItems;
use Illuminate\Contracts\Validation\Rule;

class RemoveRule implements Rule
{
    protected $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $id)
    {
        $item = Item::query()->find($id)->getAllChild()->pluck('id');

        // 网电咨询项目表
        if (ReservationItems::query()->whereIn('item_id', $item)->count('reservation_id')) {
            $this->message = '【网电咨询】已经使用了该数据，无法直接删除！';
            return false;
        }

        // 分诊接待
        if (ReceptionItems::query()->whereIn('item_id', $item)->count('reception_id')) {
            $this->message = '【分诊接待】已经使用了该数据，无法直接删除！';
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}

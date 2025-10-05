<?php

namespace App\Rules\Distributor;

use App\Models\Distributor;
use Illuminate\Contracts\Validation\Rule;

class UpdateRule implements Rule
{
    protected $message;
    protected $id;
    protected $parentid;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($id, $parentid)
    {
        $this->id       = $id;
        $this->parentid = $parentid;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $ids = Distributor::find($this->id)->getAllChild()->pluck('user_id')->toArray();

        if (in_array($this->parentid, $ids))
        {
            $this->message = '不能修改到自己的下线里面!';
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

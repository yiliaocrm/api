<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRfmRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match (request()->route()->getActionMethod()) {
            'store' => $this->getStoreRules(),
            default => [],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'store' => $this->getStoreMessages(),
            default => [],
        };
    }

    public function formData(): array
    {
        return [
            'name'        => $this->input('name'),
            'description' => $this->input('description'),
            'r_operator'  => $this->input('r_operator'),
            'r_value'     => $this->input('r_value'),
            'f_operator'  => $this->input('f_operator'),
            'f_value'     => $this->input('f_value'),
            'm_operator'  => $this->input('m_operator'),
            'm_value'     => $this->input('m_value'),
        ];
    }

    private function getStoreRules(): array
    {
        return [
            'recency'     => 'required|array|size:2',
            'recency.*'   => 'integer|min:1|max:365',
            'frequency'   => 'required|array|size:2',
            'frequency.*' => 'integer|min:1|max:100',
            'monetary'    => 'required|array|size:2',
            'monetary.*'  => 'numeric|min:0',
        ];
    }

    private function getStoreMessages(): array
    {
        return [
            'recency.required'    => 'Recency is required',
            'recency.array'       => 'Recency must be an array',
            'recency.size'        => 'Recency must have 2 items',
            'recency.*.integer'   => 'Recency must be an integer',
            'recency.*.min'       => 'Recency must be at least 1',
            'recency.*.max'       => 'Recency may not be greater than 365',
            'frequency.required'  => 'Frequency is required',
            'frequency.array'     => 'Frequency must be an array',
            'frequency.size'      => 'Frequency must have 2 items',
            'frequency.*.integer' => 'Frequency must be an integer',
            'frequency.*.min'     => 'Frequency must be at least 1',
            'frequency.*.max'     => 'Frequency may not be greater than 100',
            'monetary.required'   => 'Monetary is required',
            'monetary.array'      => 'Monetary must be an array',
            'monetary.size'       => 'Monetary must have 2 items',
            'monetary.*.numeric'  => 'Monetary must be a number',
            'monetary.*.min'      => 'Monetary must be at least 0',
        ];
    }
}

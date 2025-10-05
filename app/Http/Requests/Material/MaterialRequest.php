<?php

namespace App\Http\Requests\Material;

use App\Models\Material;
use App\Models\MaterialCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaterialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
        $typeMap = array_keys(Material::TYPE_MAP);

        return match (request()->route()->getActionMethod()) {
            default => [],
            // 素材分类
            'indexCategory' => [
                'material_type' => ['nullable', 'integer', Rule::in(array_keys(Material::TYPE_MAP))],
            ],
            'createCategory' => [
                'material_type' => ['nullable', 'integer', Rule::in(array_keys(Material::TYPE_MAP))],
                'parent_id'     => ['nullable', 'integer'],
                'name'          => ['required', 'string'],
                'description'   => ['nullable', 'string'],
                'ranking'       => ['nullable', 'integer'],
                'is_enabled'    => ['nullable', 'boolean', 'in:0,1'],
            ],
            'updateCategory' => [
                'id'          => ['required', 'integer', 'exists:material_categories'],
                'parent_id'   => ['nullable', 'integer'],
                'name'        => ['required', 'string'],
                'description' => ['nullable', 'string'],
                'ranking'     => ['nullable', 'integer'],
                'is_enabled'  => ['nullable', 'boolean', 'in:0,1'],
            ],
            'sortCategory' => [
                'id1' => ['required', 'integer', 'exists:material_categories,id'],
                'id2' => ['required', 'integer', 'exists:material_categories,id'],
            ],
            'disableCategory' => [
                'id' => ['required', 'integer', 'exists:material_categories'],
            ],
            'enableCategory' => [
                'id' => ['required', 'integer', 'exists:material_categories'],
            ],
            'removeCategory' => [
                'id' => ['required', 'integer', 'exists:material_categories'],
            ],

            // 素材
            'index' => [
                'keyword' => ['nullable', 'string'],
                'type'    => ['nullable', 'integer'],
                'status'  => ['nullable', 'string', Rule::in('active', 'deactive')],
            ],
            'store' => [
                'material_category_id'  => ['required', 'integer'],
                'type'                  => ['required', 'integer', Rule::in($typeMap)],
                'title'                 => ['required', 'string'],
                'cover_image'           => ['nullable', 'file'],
                'cover_video'           => ['nullable', 'file'],
                'summary'               => ['nullable', 'string'],
                'content'               => ['nullable', 'string'],
                'ranking'               => ['nullable', 'integer'],
                'is_share_disabled'     => ['nullable', 'boolean', 'in:0,1'],
                'is_enabled_share_link' => ['nullable', 'boolean', 'in:0,1'],
                'is_enabled'            => ['nullable', 'boolean', 'in:0,1'],
            ],
            'info' => [
                'id' => ['required', 'integer', 'exists:materials'],
            ],
            'update' => [
                'id' => ['required', 'integer', 'exists:materials'],
            ],
            'remove' => [
                'id' => ['required', 'integer', 'exists:materials'],
            ],
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            default => [],
            'index' => [],
            'store' => [],
        };
    }

    public function toCategory(): array
    {
        return [
            'material_type' => request('material_type', 0),
            'parent_id'     => request('parent_id', 0),
            'name'          => request('name'),
            'description'   => request('description'),
            'ranking'       => request('ranking', 0),
            'is_enabled'    => request('is_enabled', true),
        ];
    }

    public function category()
    {
        return MaterialCategory::query()->find(request('id'));
    }

    public function toMaterial(): array
    {
        return [
            'material_category_id'  => request('material_category_id', 0),
            'type'                  => request('type'),
            'title'                 => request('title'),
            'cover_image'           => request('cover_image'),
            'cover_video'           => request('cover_video'),
            'summary'               => request('summary'),
            'content'               => request('content'),
            'ranking'               => request('ranking', 0),
            'is_share_disabled'     => request('is_share_disabled', false),
            'is_enabled_share_link' => request('is_enabled_share_link', false),
            'is_enabled'            => request('is_enabled', true),
            'creator_id'            => user()->id,
        ];
    }

    public function material()
    {
        return Material::query()->where('id', request('id'))->first();
    }
}

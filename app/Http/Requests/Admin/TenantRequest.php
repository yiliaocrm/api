<?php

namespace App\Http\Requests\Admin;

use App\Models\Tenant;
use App\Jobs\SyncMenusToTenantJob;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class TenantRequest extends FormRequest
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
            'index' => $this->getIndexRules(),
            'create' => $this->getCreateRules(),
            'update' => $this->getUpdateRules(),
            'remove', 'info', 'pause', 'run', 'login' => $this->getInfoRules(),
            default => []
        };
    }

    public function messages(): array
    {
        return match (request()->route()->getActionMethod()) {
            'index' => $this->getIndexMessages(),
            'create' => $this->getCreateMessages(),
            'update' => $this->getUpdateMessages(),
            'remove', 'info', 'pause', 'run', 'login' => $this->getInfoMessages(),
            default => []
        };
    }

    private function getIndexRules(): array
    {
        return [
            'keyword'     => 'nullable|string|max:255',
            'expire_date' => 'nullable|array|size:2',
            'created_at'  => 'nullable|array|size:2',
        ];
    }

    private function getIndexMessages(): array
    {
        return [
            'keyword.string'    => '[关键字]格式错误!',
            'keyword.max'       => '[关键字]长度不能超过255个字符!',
            'expire_date.array' => '[过期时间]格式错误!',
            'expire_date.size'  => '[过期时间]格式错误!',
            'created_at.array'  => '[创建时间]格式错误!',
            'created_at.size'   => '[创建时间]格式错误!'
        ];
    }

    private function getCreateRules(): array
    {
        return [
            'id'          => 'required|regex:/^[\w-]*$/|unique:tenants',
            'name'        => 'required',
            'expire_date' => 'required|date_format:Y-m-d',
            'domains.*'   => [
                'required',
                'distinct',
                'unique:domains,domain',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/', $value) && !filter_var($value, FILTER_VALIDATE_IP)) {
                        $fail("域名[{$value}]错误!");
                    }
                }
            ]
        ];
    }

    private function getCreateMessages(): array
    {
        return [
            'id.required'             => '[机构代码]不能为空!',
            'id.unique'               => '[机构代码]已被使用!',
            'id.regex'                => '[机构代码]格式错误!',
            'name.required'           => '[机构名称]不能为空!',
            'expire_date.required'    => '[过期时间]不能为空!',
            'expire_date.date_format' => '[过期时间]格式错误!',
            'domains.*.distinct'      => '[绑定域]名重复!',
            'domains.*.unique'        => '域名已经被使用了!'
        ];
    }

    private function getUpdateRules(): array
    {
        return [
            'original_id' => 'required|exists:tenants,id',
            'id'          => [
                'required',
                'regex:/^[\w-]*$/',
                Rule::unique('tenants')->ignore($this->input('original_id'))
            ],
            'name'        => 'required',
            'expire_date' => 'required|date_format:Y-m-d',
            'domains.*'   => [
                'required',
                'distinct',
                'unique:domains,domain,' . $this->input('original_id') . ',tenant_id',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/', $value) && !filter_var($value, FILTER_VALIDATE_IP)) {
                        $fail("{$value}错误!");
                    }
                }
            ]
        ];
    }

    private function getUpdateMessages(): array
    {
        return [
            'original_id.required'    => '机构代码不能为空!',
            'original_id.exists'      => '机构代码不存在!',
            'id.required'             => '[机构代码]不能为空!',
            'id.unique'               => '[机构代码]已被使用!',
            'id.regex'                => '[机构代码]格式错误!',
            'name.required'           => '[机构名称]不能为空!',
            'expire_date.required'    => '[过期时间]不能为空!',
            'expire_date.date_format' => '[过期时间]格式错误!',
            'domains.*.distinct'      => '[绑定域]名重复!',
            'domains.*.unique'        => '域名已经被使用了!'
        ];
    }

    private function getInfoRules(): array
    {
        return [
            'id' => 'required|exists:tenants'
        ];
    }

    private function getInfoMessages(): array
    {
        return [
            'id.required' => '机构代码不能为空!',
            'id.exists'   => '机构代码不存在!'
        ];
    }

    /**
     * 构造租户主表信息
     * @return array
     */
    public function createData(): array
    {
        return [
            'id'          => $this->input('id'),
            'status'      => 'run',
            'name'        => $this->input('name'),
            'version'     => admin_parameter('his_version'),
            'expire_date' => $this->input('expire_date'),
            'remark'      => $this->input('remark')
        ];
    }

    /**
     * 创建租户域名
     * @return array
     */
    public function domainData(): array
    {
        $data    = [];
        $domains = $this->input('domains');

        foreach ($domains as $domain) {
            $data[] = [
                'domain' => $domain
            ];
        }

        return $data;
    }

    /**
     * 构造租户主表信息
     * @return array
     */
    public function updateData(): array
    {
        return [
            'id'          => $this->input('id'),
            'name'        => $this->input('name'),
            'expire_date' => $this->input('expire_date'),
            'remark'      => $this->input('remark')
        ];
    }

    /**
     * 同步菜单到租户
     * @param Tenant $tenant
     * @return void
     */
    public function syncMenus(Tenant $tenant): void
    {
        $menus                  = DB::table('menus')->get()->map(fn($item) => (array)$item)->toArray();
        $menu_permission_scopes = DB::table('menu_permission_scopes')->get()->map(fn($item) => (array)$item)->toArray();
        dispatch(new SyncMenusToTenantJob($tenant->id, $menus, $menu_permission_scopes));
    }
}

<?php

namespace App\Imports;

use App\Enums\ImportTaskDetailStatus;
use App\Models\Department;
use App\Models\ImportTaskDetail;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserImport extends BaseImport
{
    /**
     * 实际业务导入逻辑
     */
    protected function handle(Collection $collection): mixed
    {
        // 预加载部门和角色数据，避免 N+1 查询
        $departmentNames = $collection->pluck('row_data')->pluck('归属部门')->unique()->toArray();
        $roleNames = $collection->pluck('row_data')->pluck('角色')->unique()->toArray();

        $departments = Department::query()->whereIn('name', $departmentNames)->get();
        $roles = Role::query()->whereIn('name', $roleNames)->get();

        $now = Carbon::now()->toDateTimeString();
        $roleUsers = [];
        $processedEmails = []; // 用于检测同一批次内的重复账号

        foreach ($collection as $item) {
            $row = $item->row_data;
            $detailId = $item->id;

            try {
                // 查找部门
                $department = $departments->where('name', $row['归属部门'])->first();
                if (! $department) {
                    throw new \Exception("部门 '{$row['归属部门']}' 不存在");
                }

                // 查找角色
                $role = $roles->where('name', $row['角色'])->first();
                if (! $role) {
                    throw new \Exception("角色 '{$row['角色']}' 不存在");
                }

                // 检查同一批次内是否有重复账号
                if (in_array($row['账号'], $processedEmails)) {
                    throw new \Exception("账号 '{$row['账号']}' 在导入文件中重复");
                }
                $processedEmails[] = $row['账号'];

                // 转换参与排班字段
                $scheduleable = ($row['参与排班'] === '是') ? 1 : 0;

                // 使用模型创建用户，触发 saving 事件自动生成 keyword
                $user = User::create([
                    'email' => (string) $row['账号'],
                    'password' => Hash::make((string) $row['密码']),
                    'name' => $row['姓名'],
                    'remark' => $row['备注'] ?? null,
                    'department_id' => $department->id,
                    'scheduleable' => $scheduleable,
                ]);

                $userId = $user->id;

                // 准备角色关联数据
                $roleUsers[] = [
                    'user_id' => $userId,
                    'role_id' => $role->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // 更新详情记录为成功
                ImportTaskDetail::query()->where('id', $detailId)->update([
                    'status' => ImportTaskDetailStatus::SUCCESS,
                    'updated_at' => $now,
                ]);
            } catch (\Throwable $e) {
                // 记录错误信息到 import_task_details 表
                ImportTaskDetail::query()->where('id', $detailId)->update([
                    'status' => ImportTaskDetailStatus::FAILED,
                    'validate_error_msg' => json_encode([$e->getMessage()], JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                ]);

                Log::error('用户导入失败', [
                    'row' => $row,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 批量插入角色关联数据
        if (! empty($roleUsers)) {
            DB::table('role_users')->insert($roleUsers);
        }

        return true;
    }

    /**
     * 导入行验证规则
     */
    public function rules(): array
    {
        return [
            '姓名' => 'required|string|max:255',
            '账号' => 'required|max:255|unique:users,email|regex:/^[a-zA-Z0-9_-]+$/',
            '密码' => 'required|min:6',
            '归属部门' => 'required|string|exists:department,name',
            '角色' => 'required|string|exists:roles,name',
            '参与排班' => 'required|in:是,否',
            '备注' => 'nullable|string|max:255',
        ];
    }

    /**
     * 自定义验证错误消息
     */
    public function messages(): array
    {
        return [
            '姓名.required' => '姓名不能为空',
            '姓名.max' => '姓名不能超过 255 个字符',
            '账号.required' => '账号不能为空',
            '账号.max' => '账号不能超过 255 个字符',
            '账号.unique' => '该账号已存在',
            '账号.regex' => '账号格式不正确，只能包含字母、数字、下划线和横线',
            '密码.required' => '密码不能为空',
            '密码.min' => '密码最少需要 6 个字符',
            '归属部门.required' => '归属部门不能为空',
            '归属部门.exists' => '归属部门不存在',
            '角色.required' => '角色不能为空',
            '角色.exists' => '角色不存在',
            '参与排班.required' => '参与排班不能为空',
            '参与排班.in' => '参与排班必须是"是"或"否"',
            '备注.max' => '备注不能超过 255 个字符',
        ];
    }

    /**
     * 字段名称映射
     */
    public function attributes(): array
    {
        return [
            '姓名' => 'name',
            '账号' => 'email',
            '密码' => 'password',
            '归属部门' => 'department',
            '角色' => 'role',
            '参与排班' => 'scheduleable',
            '备注' => 'remark',
        ];
    }
}

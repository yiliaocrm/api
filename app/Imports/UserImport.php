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
}

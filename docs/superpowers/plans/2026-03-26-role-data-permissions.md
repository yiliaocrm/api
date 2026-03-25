# Role Data Permissions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Split role data permissions out of Sentinel menu permissions, remove user private permissions, and enforce single-role users across the backend, upgrade path, and Vue frontend.

**Architecture:** Keep Sentinel responsible only for menu and feature permissions via `roles.permissions`. Introduce `roles.data_permissions` for data-scope rules, refactor all backend data-scope readers to use the current role explicitly, and delete all user-private permission read/write paths. Preserve the existing `role_users` pivot table, but enforce one role per user in both UI and backend save logic, then normalize legacy data in `Version107`.

**Tech Stack:** Laravel 12, PHP 8.4, stancl/tenancy 3.9, Cartalyst Sentinel, PHPUnit, Vue 3, Element Plus, Vite, Vitest

---

## Working Rules

- Use the backend repo at `D:\laragon\www\his` for the plan document, backend code, upgrade script, and backend tests.
- Modify the tenant bootstrap migration in place: `database/migrations/tenant/2014_07_02_230147_migration_cartalyst_sentinel.php`.
- Frontend changes belong in `D:\laragon\www\his-frontend-vue3`; do not add UI code to the Laravel repo.
- Follow TDD for backend behavior changes and Vitest + build verification for frontend utility/UI changes.
- Keep the API route shape stable unless the spec explicitly says to remove old endpoints.
- Use short Chinese imperative commit messages.

## File Map

### Backend repo: `D:\laragon\www\his`

- Modify: `database/migrations/tenant/2014_07_02_230147_migration_cartalyst_sentinel.php`
  - Add `roles.data_permissions` for fresh tenant installs
- Modify: `app/Models/Role.php`
  - Add fillable + cast for `data_permissions`
- Modify: `app/Models/User.php`
  - Stop merging `users.permissions`
  - Add explicit role / data-permission helpers
  - Refactor all data-scope methods to read `data_permissions`
- Modify: `app/Http/Controllers/Web/UserController.php`
  - Remove user-private permission actions
  - Change create/edit role sync to single-role input
- Modify: `app/Http/Controllers/Web/RoleController.php`
  - Save both `permissions` and `data_permissions`
- Modify: `app/Http/Controllers/Web/PermissionQueryController.php`
  - Remove user-private query paths
  - Let role queries target menu or data permissions explicitly
- Modify: `app/Http/Requests/Web/UserRequest.php`
  - Drop private-permission validation branches
  - Validate single role input
- Modify: `app/Http/Requests/Web/RoleRequest.php`
  - Accept `data_permissions`
- Modify: `app/Http/Requests/Web/PermissionQueryRequest.php`
  - Remove user-private helpers
  - Route removals to `permissions` or `data_permissions`
- Modify: `routes/web.php`
  - Remove `/user/permission*`
  - Adjust `/permission-query/*` contract if needed
- Modify: `app/Exports/UserExport.php`
  - Remove the “个人权限” export column
- Create: `app/Upgrades/Versions/Version107.php`
  - Migrate role data permissions, clear user private permissions, normalize single role
- Create: `tests/Feature/Permissions/RoleDataPermissionTest.php`
  - Cover permission split behavior, single-role user save behavior, route removals, and permission-query contract
- Create: `tests/Unit/Upgrades/Version107Test.php`
  - Cover `tenantUp()` migration rules and idempotency

### Frontend repo: `D:\laragon\www\his-frontend-vue3`

- Modify: `src/views/role/permission.vue`
  - Split menu permissions from data permissions in both load and save flows
- Modify: `src/views/role/drawer.vue`
  - Save the two permission payloads separately
- Modify: `src/api/model/role.js`
  - Keep endpoint, update payload shape
- Modify: `src/views/user/index.vue`
  - Remove personal-permission column and actions
- Delete: `src/views/user/permission.vue`
  - Remove the private-permission drawer
- Modify: `src/api/model/user.js`
  - Remove private-permission API methods
- Modify: `src/views/user/form.vue`
  - Change role control from multi-select to single-select
- Modify: `src/views/permission-query/index.vue`
  - Remove “私有权限” action
- Modify: `src/api/model/permission_query.js`
  - Remove `/permission-query/user`
- Create: `src/views/role/permission-helper.js`
  - Pure helper for splitting / combining role permission payloads
- Create: `tests/unit/role-permission-helper.test.js`
  - Cover the permission helper contract

## Task 1: Write the backend red tests for the new permission model

**Files:**
- Create: `tests/Feature/Permissions/RoleDataPermissionTest.php`
- Check for reference only: `app/Models/User.php`
- Check for reference only: `app/Http/Controllers/Web/UserController.php`
- Check for reference only: `app/Http/Controllers/Web/RoleController.php`
- Check for reference only: `app/Http/Controllers/Web/PermissionQueryController.php`
- Check for reference only: `routes/web.php`

- [ ] **Step 1: Create the failing feature test file**

```php
<?php

namespace Tests\Feature\Permissions;

use Tests\TestCase;

class RoleDataPermissionTest extends TestCase
{
    public function test_user_merged_permissions_do_not_include_data_permissions(): void
    {
        $this->assertTrue(false, 'Write merged-permission split test');
    }

    public function test_role_permission_endpoint_persists_menu_and_data_permissions_separately(): void
    {
        $this->assertTrue(false, 'Write role save test');
    }

    public function test_user_create_and_edit_only_keep_one_role(): void
    {
        $this->assertTrue(false, 'Write single-role sync test');
    }

    public function test_user_private_permission_routes_are_removed(): void
    {
        $this->assertTrue(false, 'Write removed-route test');
    }

    public function test_permission_query_role_action_can_remove_data_permissions(): void
    {
        $this->assertTrue(false, 'Write permission-query removal test');
    }
}
```

- [ ] **Step 2: Run the focused backend test file to verify it fails**

Run: `php artisan test --filter=RoleDataPermissionTest`

Expected: FAIL with placeholder assertions.

- [ ] **Step 3: Replace placeholders with real integration tests**

Use lightweight schema setup inside the test file, similar to existing feature tests in this repo:

```php
public function test_user_merged_permissions_do_not_include_data_permissions(): void
{
    $role = Role::query()->create([
        'slug' => 'consultant',
        'name' => '咨询师',
        'permissions' => ['reservation.manage' => true],
        'data_permissions' => ['reservation.view.user.id.2' => true],
    ]);

    $user = User::query()->create([
        'email' => 'a001',
        'password' => bcrypt('secret'),
        'department_id' => 1,
        'name' => '张三',
        'keyword' => 'a001',
    ]);

    DB::table('role_users')->insert([
        'user_id' => $user->id,
        'role_id' => $role->id,
    ]);

    $this->assertSame(
        ['reservation.manage' => true],
        $user->fresh()->getMergedPermissions()
    );
    $this->assertSame(
        ['reservation.view.user.id.2' => true],
        $user->fresh()->getRoleDataPermissions()
    );
}
```

Also add:

- one test that posts to `/role/permission` and asserts `roles.permissions` / `roles.data_permissions` split correctly
- one test that posts to `/user/create` and `/user/edit` with a single role and asserts only one `role_users` row exists
- one test that asserts `/user/permission`, `/user/clear-permission` do not resolve anymore
- one test that removes a role data permission via `/permission-query/remove`

- [ ] **Step 4: Re-run the focused test file**

Run: `php artisan test --filter=RoleDataPermissionTest`

Expected: FAIL on missing code paths, not on syntax.

- [ ] **Step 5: Commit the red-test baseline**

```bash
git add tests/Feature/Permissions/RoleDataPermissionTest.php
git commit -m "补充角色数据权限测试"
```

## Task 2: Write the upgrade red tests for `Version107`

**Files:**
- Create: `tests/Unit/Upgrades/Version107Test.php`
- Check for reference only: `app/Upgrades/Versions/BaseVersion.php`
- Check for reference only: `app/Upgrades/Versions/Version106.php`

- [ ] **Step 1: Create the failing unit test file**

```php
<?php

namespace Tests\Unit\Upgrades;

use Tests\TestCase;

class Version107Test extends TestCase
{
    public function test_tenant_up_splits_role_permissions_and_clears_user_permissions(): void
    {
        $this->assertTrue(false, 'Write split-and-clear upgrade test');
    }

    public function test_tenant_up_keeps_only_the_earliest_role_relation_per_user(): void
    {
        $this->assertTrue(false, 'Write single-role normalization test');
    }
}
```

- [ ] **Step 2: Run the focused upgrade tests to verify they fail**

Run: `php artisan test --filter=Version107Test`

Expected: FAIL with placeholder assertions.

- [ ] **Step 3: Replace placeholders with real `tenantUp()` tests**

Create the minimal in-memory schema directly in the test:

```php
Schema::create('roles', function (Blueprint $table) {
    $table->increments('id');
    $table->string('slug');
    $table->string('name');
    $table->text('permissions')->nullable();
    $table->text('data_permissions')->nullable();
    $table->timestamps();
});

Schema::create('users', function (Blueprint $table) {
    $table->increments('id');
    $table->string('email');
    $table->string('password');
    $table->text('permissions')->nullable();
    $table->integer('department_id');
    $table->string('keyword');
    $table->timestamps();
});

Schema::create('role_users', function (Blueprint $table) {
    $table->integer('user_id');
    $table->integer('role_id');
    $table->timestamp('created_at')->nullable();
    $table->timestamp('updated_at')->nullable();
});
```

Test these behaviors:

- role `permissions` keep `reservation.manage`
- role `data_permissions` gets `reservation.view.user.id.2`
- `users.permissions` becomes empty / null-equivalent
- multi-role user keeps the earliest `role_users.created_at`
- rerunning `tenantUp()` is idempotent

- [ ] **Step 4: Re-run the focused upgrade test file**

Run: `php artisan test --filter=Version107Test`

Expected: FAIL because `Version107` does not exist yet.

- [ ] **Step 5: Commit the red-test baseline**

```bash
git add tests/Unit/Upgrades/Version107Test.php
git commit -m "补充107升级测试"
```

## Task 3: Implement the schema and model split

**Files:**
- Modify: `database/migrations/tenant/2014_07_02_230147_migration_cartalyst_sentinel.php`
- Modify: `app/Models/Role.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Permissions/RoleDataPermissionTest.php`

- [ ] **Step 1: Add `data_permissions` to the tenant bootstrap migration**

In the `roles` table definition, add:

```php
$table->text('data_permissions')->nullable();
```

Place it next to the existing `permissions` column so fresh tenant installs match the upgraded schema.

- [ ] **Step 2: Update the `Role` model**

Add the new attribute to `fillable` and casts:

```php
protected $fillable = [
    'name',
    'slug',
    'execution',
    'permissions',
    'data_permissions',
];

protected function casts(): array
{
    return [
        'execution' => 'boolean',
        'permissions' => 'array',
        'data_permissions' => 'array',
    ];
}
```

- [ ] **Step 3: Refactor the `User` model permission readers**

Add explicit helpers and stop merging `users.permissions`:

```php
public function getCurrentRole(): ?Role
{
    return $this->relationLoaded('roles')
        ? $this->roles->first()
        : $this->roles()->first();
}

public function getRoleDataPermissions(): array
{
    return $this->getCurrentRole()?->data_permissions ?? [];
}

public function getMergedPermissions(): array
{
    if (! $this->mergedPermissions) {
        $permissions = [];
        foreach ($this->roles as $role) {
            $permissions = array_merge($permissions, $role->permissions ?? []);
        }
        $this->mergedPermissions = $permissions;
    }

    return $this->mergedPermissions;
}
```

Then update all `get*View*Permission()` / `getUserIdsFor*()` methods to read from `collect($this->getRoleDataPermissions())`.

- [ ] **Step 4: Run the focused backend test file**

Run: `php artisan test --filter=RoleDataPermissionTest`

Expected: some tests still fail on controller / route behavior, but the merged-permission split assertions should now pass.

- [ ] **Step 5: Commit the model split**

```bash
git add database/migrations/tenant/2014_07_02_230147_migration_cartalyst_sentinel.php app/Models/Role.php app/Models/User.php
git commit -m "拆分角色数据权限模型"
```

## Task 4: Remove user-private permission endpoints and enforce single-role users

**Files:**
- Modify: `app/Http/Controllers/Web/UserController.php`
- Modify: `app/Http/Requests/Web/UserRequest.php`
- Modify: `routes/web.php`
- Modify: `app/Exports/UserExport.php`
- Test: `tests/Feature/Permissions/RoleDataPermissionTest.php`

- [ ] **Step 1: Remove the old user-private permission routes**

Delete these lines from the `/user` route group:

```php
Route::get('permission', 'getPermission');
Route::post('permission', 'postPermission');
Route::get('clear-permission', 'clearPermission');
```

- [ ] **Step 2: Remove the matching controller actions**

Delete `getPermission()`, `postPermission()`, and `clearPermission()` from `UserController`.

- [ ] **Step 3: Change user create/edit to a single role input**

Update `UserController`:

```php
$roleId = $request->input('role_id');
$user->roles()->sync($roleId ? [$roleId] : []);
```

Use that in both `create()` and `edit()`. Keep the “user id 1 role cannot be changed” guard intact.

- [ ] **Step 4: Update request validation and form data expectations**

In `UserRequest`, replace the multi-role semantics:

```php
'role_id' => 'required|exists:roles,id',
```

Remove the deleted actions from `rules()` / `messages()`, keep `create` and `edit` aligned with the single `role_id` input.

- [ ] **Step 5: Remove the export column that still exposes private permissions**

In `UserExport`, drop the header and row cell for “个人权限”:

```php
$headers = [
    'ID',
    '姓名',
    '账号',
    '角色',
    '归属部门',
    '分机号码',
    '动态口令',
    '在职状态',
    '参与排班',
    '备注信息',
    '创建时间',
    '最后登陆时间',
    '更新时间',
];
```

Also shift the column width map so it still matches the final sheet.

- [ ] **Step 6: Run the focused backend tests**

Run: `php artisan test --filter=RoleDataPermissionTest`

Expected: user-route removal and single-role tests pass; role-permission and upgrade tests still fail.

- [ ] **Step 7: Commit the user cleanup**

```bash
git add app/Http/Controllers/Web/UserController.php app/Http/Requests/Web/UserRequest.php routes/web.php app/Exports/UserExport.php
git commit -m "移除用户私有权限入口"
```

## Task 5: Implement role permission saving and permission-query backend

**Files:**
- Modify: `app/Http/Controllers/Web/RoleController.php`
- Modify: `app/Http/Controllers/Web/PermissionQueryController.php`
- Modify: `app/Http/Requests/Web/RoleRequest.php`
- Modify: `app/Http/Requests/Web/PermissionQueryRequest.php`
- Test: `tests/Feature/Permissions/RoleDataPermissionTest.php`

- [ ] **Step 1: Accept and persist `data_permissions` on the role save endpoint**

In `RoleRequest::formData()`:

```php
return [
    'name' => $this->input('name'),
    'slug' => $this->input('slug'),
    'execution' => $this->input('execution', false),
    'permissions' => $this->input('permissions', []),
    'data_permissions' => $this->input('data_permissions', []),
];
```

In `RoleController::permission()`:

```php
$role->update([
    'permissions' => $request->input('permissions', []),
    'data_permissions' => $request->input('data_permissions', []),
]);
```

- [ ] **Step 2: Remove user-private permission-query support**

Delete or stop exposing:

- `PermissionQueryController::user()`
- `PermissionQueryRequest::removeUserPermissions()`

If route compatibility matters short-term, return an explicit business error instead of silently doing nothing. Prefer deleting the route from `routes/web.php` if the frontend is being updated in the same change set.

- [ ] **Step 3: Let role permission-query removal choose the correct storage column**

Add a small classifier in `PermissionQueryRequest`:

```php
private function isDataPermission(string $permission): bool
{
    return str_contains($permission, '.view.');
}

public function removeRolePermissions(string $permission, int $roleId): void
{
    $role = Role::query()->find($roleId);
    if (! $role) {
        return;
    }

    $column = $this->isDataPermission($permission) ? 'data_permissions' : 'permissions';
    $permissions = $role->{$column} ?? [];
    unset($permissions[$permission]);
    $role->update([$column => $permissions]);
}
```

- [ ] **Step 4: Keep the role query endpoint aware of both permission types**

Either split by request parameter or default by classifier. The simplest contract is:

```php
$column = str_contains($menu->permission, '.view.') ? 'data_permissions' : 'permissions';
$query->whereJsonContains($column, [$menu->permission => true]);
```

That keeps the frontend payload minimal while still using one role listing endpoint.

- [ ] **Step 5: Run the focused backend tests**

Run: `php artisan test --filter=RoleDataPermissionTest`

Expected: the full `RoleDataPermissionTest` file passes.

- [ ] **Step 6: Commit the role-permission backend**

```bash
git add app/Http/Controllers/Web/RoleController.php app/Http/Controllers/Web/PermissionQueryController.php app/Http/Requests/Web/RoleRequest.php app/Http/Requests/Web/PermissionQueryRequest.php
git commit -m "拆分角色权限存储"
```

## Task 6: Implement and verify `Version107`

**Files:**
- Create: `app/Upgrades/Versions/Version107.php`
- Test: `tests/Unit/Upgrades/Version107Test.php`

- [ ] **Step 1: Create the new upgrade class**

Start from `Version106.php` and implement:

```php
class Version107 extends BaseVersion
{
    public function version(): string
    {
        return '1.0.7';
    }

    public function tenantUp(): void
    {
        $this->addColumnIfNotExists('roles', 'data_permissions', function (Blueprint $table) {
            $table->text('data_permissions')->nullable()->after('permissions');
        });

        $this->migrateRolePermissions();
        $this->clearUserPrivatePermissions();
        $this->normalizeUserRoles();
    }
}
```

- [ ] **Step 2: Implement role permission splitting**

Inside `migrateRolePermissions()`:

```php
foreach (Role::query()->get() as $role) {
    $menuPermissions = [];
    $dataPermissions = $role->data_permissions ?? [];

    foreach (($role->permissions ?? []) as $key => $value) {
        if (! $value) {
            $menuPermissions[$key] = false;
            continue;
        }

        if (str_contains($key, '.view.')) {
            $dataPermissions[$key] = true;
            continue;
        }

        $menuPermissions[$key] = true;
    }

    $role->update([
        'permissions' => $menuPermissions,
        'data_permissions' => $dataPermissions,
    ]);
}
```

- [ ] **Step 3: Implement user-private cleanup and single-role normalization**

Use query builder updates for safety and speed:

```php
DB::table('users')
    ->whereNotNull('permissions')
    ->update(['permissions' => json_encode([], JSON_THROW_ON_ERROR)]);

$grouped = DB::table('role_users')
    ->select('user_id')
    ->groupBy('user_id')
    ->havingRaw('COUNT(*) > 1')
    ->pluck('user_id');
```

For each affected `user_id`, keep the earliest row by `created_at`, then `role_id`, delete the rest, and write tenant-prefixed logs via `$this->tenantInfo(...)`.

- [ ] **Step 4: Run the focused upgrade tests**

Run: `php artisan test --filter=Version107Test`

Expected: PASS.

- [ ] **Step 5: Commit the upgrade implementation**

```bash
git add app/Upgrades/Versions/Version107.php tests/Unit/Upgrades/Version107Test.php
git commit -m "新增107权限升级脚本"
```

## Task 7: Split the frontend role permission payload and add unit coverage

**Files:**
- Create: `D:\laragon\www\his-frontend-vue3\src\views\role\permission-helper.js`
- Create: `D:\laragon\www\his-frontend-vue3\tests\unit\role-permission-helper.test.js`
- Modify: `D:\laragon\www\his-frontend-vue3\src\views\role\permission.vue`
- Modify: `D:\laragon\www\his-frontend-vue3\src\views\role\drawer.vue`
- Modify: `D:\laragon\www\his-frontend-vue3\src\api\model\role.js`

- [ ] **Step 1: Write the failing frontend helper test**

Create a pure helper test first:

```js
import { describe, expect, it } from 'vitest'
import { splitRolePermissions } from '@/views/role/permission-helper'

describe('splitRolePermissions', () => {
  it('separates menu permissions from data permissions', () => {
    expect(
      splitRolePermissions({
        'reservation.manage': true,
        'reservation.view.user.id.2': true,
        'followup.view.department.id.4': true
      })
    ).toEqual({
      permissions: { 'reservation.manage': true },
      data_permissions: {
        'reservation.view.user.id.2': true,
        'followup.view.department.id.4': true
      }
    })
  })
})
```

- [ ] **Step 2: Run the targeted frontend test to verify it fails**

Run: `npm run test -- tests/unit/role-permission-helper.test.js`

Expected: FAIL because the helper does not exist yet.

- [ ] **Step 3: Implement the helper and wire the role permission component to it**

Create `permission-helper.js`:

```js
export function isDataPermission(key) {
  return key.includes('.view.')
}

export function splitRolePermissions(payload = {}) {
  const permissions = {}
  const data_permissions = {}

  Object.entries(payload).forEach(([key, value]) => {
    if (!value) return
    if (isDataPermission(key)) {
      data_permissions[key] = true
      return
    }
    permissions[key] = true
  })

  return { permissions, data_permissions }
}
```

In `role/permission.vue`:

- `setPermission` should accept `{ permissions, data_permissions }`
- menu tree checks only use `permissions`
- data-scope radio / id parsing only use `data_permissions`
- `getPermissions()` should return the same two-key object

In `role/drawer.vue`:

```js
const { permissions, data_permissions } = await permissionRef.value.getPermissions()

const param = {
  id: grid.value.params.role_id,
  permissions,
  data_permissions
}
```

- [ ] **Step 4: Re-run the targeted frontend test**

Run: `npm run test -- tests/unit/role-permission-helper.test.js`

Expected: PASS.

- [ ] **Step 5: Run a frontend build smoke test**

Run: `npm run build`

Expected: PASS.

- [ ] **Step 6: Commit the frontend role split**

```bash
git add src/views/role/permission-helper.js tests/unit/role-permission-helper.test.js src/views/role/permission.vue src/views/role/drawer.vue src/api/model/role.js
git commit -m "拆分前端角色数据权限"
```

## Task 8: Remove frontend user-private permission UI and enforce single-role selection

**Files:**
- Modify: `D:\laragon\www\his-frontend-vue3\src\views\user\index.vue`
- Delete: `D:\laragon\www\his-frontend-vue3\src\views\user\permission.vue`
- Modify: `D:\laragon\www\his-frontend-vue3\src\api\model\user.js`
- Modify: `D:\laragon\www\his-frontend-vue3\src\views\user\form.vue`
- Modify: `D:\laragon\www\his-frontend-vue3\src\views\permission-query\index.vue`
- Modify: `D:\laragon\www\his-frontend-vue3\src\api\model\permission_query.js`

- [ ] **Step 1: Remove the user-private permission drawer and API methods**

Delete the drawer component and remove these API entries:

```js
fetchPermission: { ... },
updatePermission: { ... },
clearPermission: { ... }
```

- [ ] **Step 2: Remove the personal-permission UI from the user list**

Delete from `user/index.vue`:

- the `permissions` grid column
- the imported `UserPermission`
- the `permissionRef`
- `handlePermission`
- `handleClearPermission`
- the corresponding dropdown items

- [ ] **Step 3: Change the user form to a single role**

In `user/form.vue`, switch to:

```vue
<el-select v-model="form.role_id" placeholder="请选择角色" filterable clearable>
  <el-option
    v-for="item in roles"
    :key="item.id"
    :label="item.name"
    :value="item.id"
  />
</el-select>
```

Update `initForm`, `edit`, and `copy`:

```js
role_id: row.roles?.[0]?.id ?? null
```

Also update the validation rule from `roles` to `role_id`.

- [ ] **Step 4: Remove the “私有权限” action from the permission-query page**

In `permission-query/index.vue`:

- remove `PrivateDrawer`
- remove `privateDrawerRef`
- remove the “私有权限” button
- keep only “角色权限”

In `permission_query.js`, remove:

```js
user: {
  url: `${config.API_URL}/permission-query/user`,
  name: '私有权限',
  get: async function (params) {
    return await http.get(this.url, params)
  }
},
```

- [ ] **Step 5: Run frontend verification**

Run: `npm run build`

Expected: PASS.

Then run:

Run: `npm run test -- tests/unit/role-permission-helper.test.js`

Expected: PASS.

- [ ] **Step 6: Commit the frontend cleanup**

```bash
git add src/views/user/index.vue src/api/model/user.js src/views/user/form.vue src/views/permission-query/index.vue src/api/model/permission_query.js
git add -u src/views/user/permission.vue
git commit -m "移除前端个人权限"
```

## Task 9: Run full verification and prepare the integration checkpoint

**Files:**
- Modify only if verification reveals defects
- Test: `tests/Feature/Permissions/RoleDataPermissionTest.php`
- Test: `tests/Unit/Upgrades/Version107Test.php`
- Check manually: `D:\laragon\www\his-frontend-vue3`

- [ ] **Step 1: Run backend focused tests together**

Run: `php artisan test --filter=RoleDataPermissionTest`

Expected: PASS.

Run: `php artisan test --filter=Version107Test`

Expected: PASS.

- [ ] **Step 2: Run broader backend regression on affected areas**

Run: `php artisan test --filter=UserCenterTest`

Expected: PASS, ensuring the existing auth/self-service paths still work after permission-model refactors.

- [ ] **Step 3: Format the backend code**

Run: `./vendor/bin/pint`

Expected: PASS with no remaining formatting diff.

- [ ] **Step 4: Run frontend build and targeted unit test**

In `D:\laragon\www\his-frontend-vue3`:

Run: `npm run test -- tests/unit/role-permission-helper.test.js`

Expected: PASS.

Run: `npm run build`

Expected: PASS.

- [ ] **Step 5: Perform manual smoke checks**

Verify in the browser:

- user create/edit only allows a single role
- user list no longer shows “个人权限”
- role permission drawer saves `permissions` and `data_permissions` correctly
- permission-query page only shows role-based management
- an affected report page still filters by role data permissions

- [ ] **Step 6: Commit any final fixes**

```bash
git add .
git commit -m "完成角色数据权限改造"
```

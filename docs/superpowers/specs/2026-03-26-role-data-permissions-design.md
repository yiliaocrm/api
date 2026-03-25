# 角色数据权限拆分设计

## 基本信息

- 主题：`role-data-permissions`
- 日期：`2026-03-26`
- 范围：将角色数据权限从 Sentinel 菜单权限中拆分，移除用户私有权限，并将员工角色收敛为单角色
- 关联仓库：
  - 后端：`D:\laragon\www\his`
  - 前端：`D:\laragon\www\his-frontend-vue3`

## 背景

当前系统将两类完全不同的授权数据混放在 Sentinel 的 `permissions` 结构里：

- 菜单/功能权限，例如 `reservation.manage`
- 数据权限，例如 `reservation.view.user.id.2`

同时，角色和用户两侧都可以写权限：

- `roles.permissions` 保存角色权限
- `users.permissions` 保存用户私有权限，并通过 `getMergedPermissions()` 覆盖角色结果

这套模型带来三个问题：

1. Sentinel 语义被混用，菜单权限和数据权限没有清晰边界
2. 用户私有权限会覆盖角色权限，导致权限来源难以追踪
3. 当前员工支持多角色，而数据权限解析逻辑默认按合并权限工作，增加了升级和维护复杂度

现状示例：

- `reservation.manage: true` 是菜单权限
- `reservation.view.user.id.2: true` 是数据权限

但它们目前都可能被塞进同一个 `permissions` JSON 中。

## 目标

本阶段完成以下目标：

1. `roles.permissions` 只保留菜单/功能权限
2. 角色数据权限独立存储为 `roles.data_permissions`
3. `users.permissions` 彻底退场，不再参与任何授权逻辑
4. 删除用户私有权限相关后端接口与前端页面能力
5. 员工角色从多角色收敛为单角色
6. 现有预约、回访、顾客、收费、业绩等数据过滤逻辑继续可用，但底层改为读取角色数据权限
7. 提供 `Version107` 升级脚本，完成现有租户数据迁移

## 非目标

以下内容不纳入本阶段：

- 重写整套授权系统或替换 Sentinel
- 将数据权限进一步拆成独立明细表
- 为用户保留“例外覆盖角色”的新机制
- 重做前端权限交互样式或页面结构
- 改造中央端或安装端前端仓库

## 现状分析

### 后端现状

- 租户 `users` 表包含 `permissions` 字段
- 租户 `roles` 表包含 `permissions` 字段
- [User.php](D:/laragon/www/his/app/Models/User.php) 中 `getMergedPermissions()` 会将角色权限与用户私有权限合并
- 多个数据权限解析方法直接依赖合并后的权限结果，例如：
  - `getReservationViewUsersPermission()`
  - `getCustomerViewUsersPermission()`
  - `getUserIdsForCashier()`
  - `getUserIdsForSalesPerformance()`
- [ReportPerformanceController.php](D:/laragon/www/his/app/Http/Controllers/Web/ReportPerformanceController.php) 等业务控制器已通过这些方法做数据过滤
- [UserController.php](D:/laragon/www/his/app/Http/Controllers/Web/UserController.php) 仍提供用户私有权限接口：
  - `getPermission()`
  - `postPermission()`
  - `clearPermission()`
- [PermissionQueryRequest.php](D:/laragon/www/his/app/Http/Requests/Web/PermissionQueryRequest.php) 里仍存在对用户私有权限的删除逻辑

### 前端现状

- [role/permission.vue](D:/laragon/www/his-frontend-vue3/src/views/role/permission.vue) 将电脑端权限、移动端权限、数据权限混合输出为单个 `permissions` 对象
- [user/permission.vue](D:/laragon/www/his-frontend-vue3/src/views/user/permission.vue) 直接复用角色权限组件写入用户私有权限
- [user/index.vue](D:/laragon/www/his-frontend-vue3/src/views/user/index.vue) 提供：
  - 个人权限列
  - 设置个人权限
  - 清空个人权限
- [user/form.vue](D:/laragon/www/his-frontend-vue3/src/views/user/form.vue) 的角色字段是多选

## 推荐方案

推荐采用“角色菜单权限与角色数据权限双轨存储，用户私有权限彻底移除”的方案。

具体做法：

1. 保留 `roles.permissions` 作为 Sentinel 菜单/功能权限载体
2. 在 `roles` 表新增 `data_permissions` 字段，专门存储角色数据权限
3. `users.permissions` 不再写入、不再读取、不再暴露接口
4. 数据权限解析从“合并权限”切换为“读取当前用户所属角色的 `data_permissions`”
5. 员工改为单角色，后端和前端同时收紧
6. 升级脚本将旧角色中的数据权限拆出，并清空用户私有权限

不推荐本次直接新增独立 `role_data_permissions` 明细表，因为当前系统已经稳定使用 `xxx.view.xxx` 的键式结构，若改成明细表，需要额外重做映射、回填和筛选逻辑，超出本次需求。

## 数据模型设计

### 1. 角色表

修改租户 `roles` 表：

- 保留 `permissions`
- 新增 `data_permissions`

对于新安装租户，需要同步修改原始租户迁移文件 [2014_07_02_230147_migration_cartalyst_sentinel.php](D:/laragon/www/his/database/migrations/tenant/2014_07_02_230147_migration_cartalyst_sentinel.php)，确保新库初始化时直接带上该字段，而不是只依赖升级脚本补齐。

字段建议：

- 类型：`text`
- 允许空值
- 模型层 cast 为 `array`

之所以延续 JSON 文本字段而不是单独建表，是因为：

- 与现有权限键结构兼容
- 前端可继续按键值对象读写
- 升级脚本迁移成本最低

### 2. 用户表

保留 `users.permissions` 字段以兼容历史库结构，但在业务上退役：

- 不再由任何接口写入
- 不再参与权限合并
- 升级时清空存量数据

这样可以减少本次对旧表结构的破坏性调整，也避免已有代码或导出逻辑在短期内因字段消失而报错。

### 3. 用户角色关系

继续沿用 `role_users` 中间表，但业务规则改为单角色：

- 创建员工时只能选择一个角色
- 编辑员工时只能保存一个角色
- 后端保存时覆盖为一条关系
- 升级脚本会清理历史多角色数据

## 权限模型设计

### 1. 功能权限

功能权限继续由 Sentinel 处理：

- 登录后菜单树、按钮权限、`hasAccess()` 等逻辑仍依赖 `roles.permissions`
- `getMergedPermissions()` 只合并角色的菜单/功能权限
- 结果中不再包含任何 `*.view.*` 数据权限键

### 2. 数据权限

数据权限由业务层单独处理：

- 当前用户从其所属角色读取 `data_permissions`
- 业务代码显式解析数据权限
- 不再通过 Sentinel 的 `hasAccess()` 或用户私有权限覆盖

建议保留现有业务方法名，修改其内部来源，以减小控制器和查询层改动范围：

- `getReservationViewUsersPermission()`
- `getReceptionViewUsersPermission()`
- `getConsultantViewUsersPermission()`
- `getCustomerViewUsersPermission()`
- `getFollowupViewUsersPermission()`
- `getTreatmentViewDepartmentsPermission()`
- `getUserIdsForCashier()`
- `getUserIdsForSalesPerformance()`

这些方法以后都从角色 `data_permissions` 解析，而不是从 `getMergedPermissions()` 解析。

### 3. 单角色规则

从本次改造起，用户只允许存在一个角色。

该规则需要同时在以下层面落实：

- 前端表单改单选
- 请求验证收紧
- 保存逻辑覆盖旧关联
- 升级脚本清理历史多角色记录

## 后端设计

### 1. 模型层

#### Role 模型

[Role.php](D:/laragon/www/his/app/Models/Role.php) 需要：

- 将 `data_permissions` 加入可填充字段
- 为 `data_permissions` 增加 `array` cast

#### User 模型

[User.php](D:/laragon/www/his/app/Models/User.php) 需要：

- `getMergedPermissions()` 不再合并用户私有权限
- 增加统一的数据权限读取方法，例如：
  - `getRoleDataPermissions()`
  - `getCurrentRole()`
- 将现有数据权限解析方法统一改为读取角色 `data_permissions`
- 逐步移除对 `getPermissions()` 或用户私有权限的依赖

### 2. 控制器与路由

#### UserController

[UserController.php](D:/laragon/www/his/app/Http/Controllers/Web/UserController.php) 删除：

- `getPermission()`
- `postPermission()`
- `clearPermission()`

[routes/web.php](D:/laragon/www/his/routes/web.php) 同步删除：

- `GET /user/permission`
- `POST /user/permission`
- `GET /user/clear-permission`

#### RoleController

[RoleController.php](D:/laragon/www/his/app/Http/Controllers/Web/RoleController.php) 的 `permission()` 接口改为接收并保存两类字段：

- `permissions`
- `data_permissions`

保留原接口路径：

- `POST /role/permission`

避免本次做无关 API 风格调整。

### 3. 请求验证

#### RoleRequest

[RoleRequest.php](D:/laragon/www/his/app/Http/Requests/Web/RoleRequest.php) 需要：

- 为 `permission` 动作增加 `data_permissions` 参数支持
- `formData()` 增加 `data_permissions`

#### UserRequest

[UserRequest.php](D:/laragon/www/his/app/Http/Requests/Web/UserRequest.php) 需要：

- 删除 `getPermission`、`postPermission`、`clearPermission` 三个 action 的规则分派和提示信息
- 将创建和编辑时的角色参数从多选改为单值校验

### 4. 权限查询管理

[PermissionQueryController.php](D:/laragon/www/his/app/Http/Controllers/Web/PermissionQueryController.php) 与 [PermissionQueryRequest.php](D:/laragon/www/his/app/Http/Requests/Web/PermissionQueryRequest.php) 需要同步收口。

调整原则：

- 查询角色菜单权限对象时，只看 `roles.permissions`
- 查询角色数据权限对象时，只看 `roles.data_permissions`
- 删除用户私有权限相关逻辑

[PermissionQueryRequest.php](D:/laragon/www/his/app/Http/Requests/Web/PermissionQueryRequest.php) 中：

- `removeUserPermissions()` 应删除
- `removeRolePermissions()` 需要根据目标权限类型决定修改 `permissions` 还是 `data_permissions`

### 5. 业务查询逻辑

现有调用方式尽量不改，仍让控制器通过用户模型方法拿到可见范围。

例如 [ReportPerformanceController.php](D:/laragon/www/his/app/Http/Controllers/Web/ReportPerformanceController.php) 仍然可以保留：

- `user()->getUserIdsForSalesPerformance()`

但方法内部改为从角色 `data_permissions` 解析，以确保控制器层变更最小。

### 6. 登录与菜单返回

[AuthController.php](D:/laragon/www/his/app/Http/Controllers/Web/AuthController.php) 与 API 侧登录资料返回逻辑需要保证：

- `user->permissions` 只包含菜单/功能权限
- 菜单树过滤不再受数据权限键影响

这一步是拆分权限语义的关键验收点。

## 前端设计

### 1. 角色权限页面

[role/permission.vue](D:/laragon/www/his-frontend-vue3/src/views/role/permission.vue) 保留三个标签页：

- 电脑端权限
- 移动端权限
- 数据权限

但组件对外暴露的数据结构需要拆分为两块：

- `permissions`
- `data_permissions`

建议将当前：

- `setPermission(permissions)`
- `getPermissions()`

改为更明确的结构化接口，例如：

- `setPermission({ permissions, data_permissions })`
- `getPermissions()` 返回 `{ permissions, data_permissions }`

这样可以继续复用同一个页面，不需要重做数据权限 UI。

### 2. 角色抽屉

[role/drawer.vue](D:/laragon/www/his-frontend-vue3/src/views/role/drawer.vue) 需要：

- 回填时将角色详情中的两类权限分别传入
- 保存时提交：
  - `id`
  - `permissions`
  - `data_permissions`

### 3. 用户页面

[user/index.vue](D:/laragon/www/his-frontend-vue3/src/views/user/index.vue) 删除：

- 个人权限列
- “设置个人权限”操作
- “清空个人权限”操作
- `UserPermission` 抽屉引用

[user/permission.vue](D:/laragon/www/his-frontend-vue3/src/views/user/permission.vue) 整体删除。

[src/api/model/user.js](D:/laragon/www/his-frontend-vue3/src/api/model/user.js) 删除：

- `fetchPermission`
- `updatePermission`
- `clearPermission`

### 4. 用户表单

[user/form.vue](D:/laragon/www/his-frontend-vue3/src/views/user/form.vue) 需要：

- 将角色选择从 `multiple` 改为单选
- 表单字段从 `roles: []` 收敛为单值字段
- 编辑与复制时只回填一个角色 ID

### 5. 用户列表展示

用户列表中的角色列建议同步收敛为单角色展示。

如果后端过渡期仍返回数组：

- 前端可临时显示第一项

但目标状态应是：

- 后端返回单角色语义
- 前端不再按多角色渲染

## 升级设计

### Version107

新增 [Version107.php](D:/laragon/www/his/app/Upgrades/Versions/Version107.php)，版本号为 `1.0.7`。

升级脚本需要在租户上下文完成以下操作：

1. 确保 `roles.data_permissions` 字段存在
2. 遍历所有角色，将旧 `permissions` 中的数据权限键迁移到 `data_permissions`
3. 保留角色原有菜单/功能权限在 `permissions`
4. 清空所有用户的 `permissions`
5. 清理 `role_users` 中一个用户多角色的数据

### 数据权限识别规则

升级脚本需要能区分“菜单权限”和“数据权限”。

建议规则：

- 以 `.view.` 为核心特征识别数据权限
- 例如：
  - `reservation.view.self`
  - `reservation.view.all`
  - `reservation.view.department.id.2`
  - `sales_performance.view.user.id.3`

其余如 `reservation.manage`、`reservation.sms` 视为菜单/功能权限。

### 多角色用户处理

当发现同一用户存在多条角色关系时：

- 保留最早一条角色关系
- 删除其余关系

“最早”判定规则：

1. 优先按 `role_users.created_at` 升序
2. 如果时间为空或无法稳定比较，则按 `role_id` 升序兜底

升级日志应记录：

- 被清理的用户数量
- 被删除的多余角色关系数量

### 用户私有权限处理

本次升级不迁移用户私有权限到角色。

处理方式：

- 直接清空 `users.permissions`
- 在升级日志中记录存在用户私有权限的用户数量

原因：

- 用户私有权限语义已被产品规则废弃
- 自动迁移到角色会造成角色权限污染
- 多用户共用角色时，无法安全推断该私有权限应归属到哪个角色

## 测试设计

本阶段建议至少覆盖以下测试：

1. 角色保存时，`permissions` 与 `data_permissions` 可分别持久化
2. `getMergedPermissions()` 不再返回数据权限键
3. 各业务数据权限解析方法能从 `data_permissions` 正确计算可见用户或部门
4. 用户私有权限接口已不可访问
5. 用户创建和编辑只允许单角色
6. `Version107` 能正确拆分角色旧权限并清空用户私有权限
7. `Version107` 能正确处理历史多角色用户，只保留最早角色

## 风险与兼容性

### 1. 遗漏直接读取合并权限的业务点

如果某些业务代码仍直接从 `getMergedPermissions()` 判断数据权限，会导致拆分后权限失效或误判。

应在实现阶段全量检索：

- `.view.`
- `getMergedPermissions()`
- `permissions`

确认所有数据权限使用点都已切换。

### 2. 前端角色权限组件拆分不完整

如果页面仍把数据权限混进 `permissions` 提交，会出现：

- 后端菜单权限被污染
- 数据权限保存丢失
- 编辑回填异常

因此角色权限组件的“回填结构”和“保存结构”必须一起调整。

### 3. 升级后历史用户权限丢失认知差异

由于用户私有权限会被直接清空，升级前需要明确告知：

- 新版本不再支持用户私有权限
- 如有特殊数据范围需求，应改由角色数据权限配置承担

## 验收标准

满足以下条件即可认为本次设计落地完成：

1. 角色权限保存后，菜单权限与数据权限在存储层清晰分离
2. 登录返回的用户权限只包含菜单/功能权限
3. 依赖数据权限的业务列表仍能按角色配置正确过滤
4. 用户侧不再存在任何“个人权限”入口与接口
5. 员工创建与编辑只能选择一个角色
6. 升级脚本可将旧租户数据平滑迁移到新模型

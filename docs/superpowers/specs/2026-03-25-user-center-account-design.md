# 账号中心设计

## 基本信息

- 主题：`user-center-account`
- 日期：`2026-03-25`
- 范围：机构端右上角“帐号信息”入口对应的账号中心页面与后端自助接口
- 关联仓库：
  - 后端：`D:\laragon\www\his`
  - 前端：`D:\laragon\www\his-frontend-vue3`

## 背景

当前机构端右上角用户下拉菜单中存在“帐号信息”入口，但点击后会跳转到 `/usercenter`，前端路由中并没有对应页面，因此用户会直接进入 404。

系统现状并不是缺少账号能力，而是缺少“当前登录用户自助维护账号”的聚合入口。现有系统已经具备以下基础：

- 前端右上角入口已存在，位于 `src/layout/components/userbar.vue`
- 后端已有当前登录用户资料接口：`GET /auth/profile`
- 后端已有当前登录用户修改密码接口：`POST /auth/reset-password`
- `users` 表已有账号中心所需的大部分基础字段：
  - `email`
  - `name`
  - `avatar`
  - `remark`
  - `extension`
  - `last_login`
  - `secret`
- 登录成功时系统会写入 `users_login` 登录日志，并更新 `users.last_login`
- 系统已有动态口令底层能力，但当前接口主要挂在“员工管理”语义下的 `user/*`

因此本次工作不应再做一个“员工管理页的另一个入口”，而应补齐一个仅服务当前登录用户的账号中心。

## 目标

本阶段要完成以下目标：

1. 点击右上角“帐号信息”不再进入 404
2. `usercenter` 页面作为前端静态隐藏路由存在，只从右上角入口进入，不出现在左侧菜单
3. 页面提供当前登录用户的账号中心能力，而不是管理员管理员工的能力
4. 账号中心包含三个区块：
   - 基础资料
   - 安全设置
   - 最近登录
5. 用户可自助修改以下字段：
   - 头像
   - 姓名
   - 分机
   - 个人备注
   - 登录密码
   - 动态口令绑定状态
6. 用户可查看以下只读信息：
   - 账号
   - 所属部门
   - 角色
   - 在职状态
   - 创建时间
   - 最后登录时间
   - 最近登录记录

## 非目标

本阶段不包含以下内容：

- 将账号中心加入后端菜单树或左侧导航
- 复用或改造“员工管理”列表页面作为账号中心入口
- 支持当前用户修改所属部门、角色、在职状态等管理员字段
- 引入跨账号查询、代管他人账号、管理员替别人改密码等能力
- 扩展扫码登录、第三方登录等新的登录方式
- 修改租户用户表结构

## 现状分析

### 前端现状

- `src/layout/components/userbar.vue` 中：
  - 用户下拉点击 `uc` 时固定执行 `router.push({ path: '/usercenter' })`
  - 该路径目前没有对应路由与页面
- `src/router/index.js` 会将两类路由合并：
  - 后端返回菜单生成的动态路由
  - `src/config/route.js` 中的静态路由
- `src/config/route.js` 当前为空，适合承载这种“不出现在菜单中，但前端需要固定可达”的页面

结论：

- `usercenter` 不需要后端返回菜单
- 直接写入 `src/config/route.js` 是符合当前项目路由机制的

### 后端现状

- `Web\AuthController::profile()` 能返回当前登录用户、菜单和门店信息
- `Web\AuthController::resetPassword()` 已支持当前登录用户修改密码，并在成功后删除全部 token
- `User` 模型已具备：
  - 角色关系
  - 部门关系
  - 登录日志关系 `loginLog()`
- `UsersLogin` 模型已记录：
  - 登录时间
  - 登录方式
  - IP
  - 地区
  - 浏览器
  - 操作系统
  - 备注
- `LogController::login()` 已有成熟的登录日志查询结构
- `UserController` 已有动态口令相关接口：
  - `GET /user/secret`
  - `POST /user/secret`
  - `GET /user/clear-secret`

问题在于：

- 这些接口都处于“管理员工账号”的语义下
- 需要传入 `id`
- 不适合直接暴露给“当前用户自助维护自己账号”的页面

## 推荐方案

推荐采用“前端静态隐藏路由 + 后端 auth 自助接口”的方案。

具体做法：

1. 前端在 `src/config/route.js` 中新增 `/usercenter` 静态隐藏路由
2. 前端新增独立页面 `src/views/usercenter/index.vue`
3. 后端在 `auth/*` 下新增账号中心自助接口，只面向当前登录用户
4. 底层尽量复用现有密码修改、动态口令、登录日志模型和逻辑
5. 明确禁止账号中心传入任意 `user_id`

不推荐直接复用 `user/edit`、`user/secret` 等管理员接口作为账号中心接线方式，因为：

- 语义混乱：管理员工和维护自己不是同一件事
- 参数风险：容易让前端透传 `id`，产生越权面
- 后续扩展困难：账号中心会不断出现“只对自己开放”的例外规则

## 信息架构

账号中心页面采用单页三卡片结构：

1. 基础资料
2. 安全设置
3. 最近登录

### 1. 基础资料

展示字段：

- 头像
- 姓名
- 账号
- 所属部门
- 角色
- 分机
- 个人备注
- 创建时间
- 最后登录时间
- 在职状态

编辑规则：

- 可编辑：头像、姓名、分机、个人备注
- 只读：账号、所属部门、角色、创建时间、最后登录时间、在职状态

### 2. 安全设置

展示与操作：

- 修改密码
- 动态口令状态：`未绑定 / 已绑定`
- 绑定动态口令
- 更换动态口令
- 解绑动态口令

规则：

- 绑定流程采用“生成 secret 与二维码 -> 输入验证码确认 -> 保存”
- 若系统开启 `cywebos_force_enable_google_authenticator`
  - 页面需明确提示当前账号必须绑定动态口令
  - 不允许解绑已有动态口令

### 3. 最近登录

展示内容：

- 最近一次登录时间摘要
- 最近登录记录列表

建议字段：

- 登录时间
- 登录方式
- IP
- 地区
- 浏览器
- 操作系统
- 备注

范围规则：

- 只显示当前登录用户自己的记录
- 默认展示最近 10 到 20 条
- 支持分页，不提供跨账号筛选

## 前端设计

### 1. 路由设计

在 `D:\laragon\www\his-frontend-vue3\src\config\route.js` 中新增静态路由：

- 路径：`/usercenter`
- 组件：`usercenter/index`
- 不加入左侧菜单树
- 不依赖后端菜单返回

原因：

- 该页面是固定入口
- 只从右上角用户下拉进入
- 不属于业务菜单导航的一部分

### 2. 页面结构

建议新增：

- `src/views/usercenter/index.vue`

如需拆分，可进一步分成：

- `components/profile-card.vue`
- `components/security-card.vue`
- `components/login-log-card.vue`

页面初始化时：

1. 调用账号中心资料接口
2. 渲染三块内容
3. 最近登录列表独立请求，避免首页接口过重

### 3. 与全局状态的关系

账号中心资料更新成功后，需要同步刷新前端登录态缓存：

- Vuex `auth.profile`
- `Tool.session` 中的 `USER_INFO`

这样可以确保右上角用户名、头像等信息即时刷新，而不需要用户手动刷新页面。

### 4. 交互要求

基础资料：

- 点击“编辑”进入表单态
- 保存成功后提示成功信息
- 保存失败时展示字段级错误

修改密码：

- 输入旧密码、新密码、确认密码
- 成功后提示“密码已修改，请重新登录”
- 随后执行退出登录流程

动态口令：

- 未绑定时显示“立即绑定”
- 已绑定时显示“更换动态口令”“解绑”
- 绑定与更换都需要先生成新的二维码和 secret，再输入验证码确认
- 当系统强制开启动态口令时：
  - 页面展示安全提示
  - 不显示或禁用“解绑”按钮

最近登录：

- 支持表格分页
- 查询失败不影响页面其他模块使用

## 后端设计

### 1. 路由设计

在 `routes/web.php` 的 `auth` 路由组下新增账号中心相关接口。

建议接口如下：

- `GET /auth/user-center`
- `POST /auth/update-profile`
- `POST /auth/reset-password`
- `GET /auth/secret`
- `POST /auth/secret`
- `GET /auth/clear-secret`
- `POST /auth/login-logs`

说明：

- `reset-password` 继续沿用现有接口
- 新接口全部以“当前登录用户”为作用对象
- 所有接口都不接收 `id`

### 2. 账号中心资料接口

建议新增 `AuthController::userCenter()`。

返回内容建议包含：

- 当前用户基础字段：
  - `id`
  - `email`
  - `name`
  - `avatar`
  - `remark`
  - `extension`
  - `last_login`
  - `created_at`
  - `banned`
  - `secret`
- 部门信息
- 角色信息
- 动态口令状态布尔值
- 系统安全配置：
  - `cywebos_force_enable_google_authenticator`

其中：

- 真实 `secret` 不建议直接回给前端
- 用 `has_secret` 或等价字段表示是否已绑定即可

### 3. 更新基础资料接口

建议新增 `AuthController::updateProfile()`。

仅允许修改以下字段：

- `avatar`
- `name`
- `extension`
- `remark`

不允许修改：

- `email`
- `department_id`
- `roles`
- `banned`
- `password`
- `secret`

理由：

- 这些字段属于管理员控制范围
- 账号中心只处理个人资料维护

### 4. 修改密码接口

继续复用 `AuthController::resetPassword()`。

保留现有规则：

- 校验旧密码
- 校验新密码与确认密码一致
- 修改成功后清空当前用户全部 token

这样可以保持安全行为一致，不需要另造密码策略。

### 5. 动态口令接口

建议将“当前用户自助绑定动态口令”能力放入 `AuthController`。

新增或调整逻辑：

- `GET /auth/secret`
  - 为当前用户生成新的 `secret` 与二维码
- `POST /auth/secret`
  - 校验用户输入的验证码
  - 校验成功后将 secret 保存到当前用户
- `GET /auth/clear-secret`
  - 清除当前用户的 secret
  - 若系统强制启用动态口令，则直接拒绝

实现建议：

- 复用 `UserController` 中现有 Google2FA 生成与校验逻辑
- 但生成二维码时应使用当前用户 `user()->email`
- 不再使用管理员上下文中的 `admin()->email`

### 6. 最近登录接口

建议新增 `AuthController::loginLogs()`。

查询逻辑复用 `LogController::login()` 的结构，但固定加上：

- `where('user_id', user()->id)`

返回字段保留：

- `type`
- `type_text`
- `ip`
- `country`
- `province`
- `city`
- `browser`
- `platform`
- `remark`
- `created_at`

可支持分页参数：

- `page`
- `rows`

但不支持传入 `user_id`

## 请求验证设计

建议在 `App\Http\Requests\Web\AuthRequest` 中扩展账号中心相关规则，而不是复用 `UserRequest`。

建议新增方法对应规则：

- `userCenter`
- `updateProfile`
- `secret`
- `clearSecret`
- `loginLogs`

推荐验证规则：

### updateProfile

- `name`: `required`
- `avatar`: `nullable|string`
- `extension`: `nullable|unique:users,extension,<current_user_id>`
- `remark`: `nullable|string`

### secret

- `secret`: `required`
- `code`: `required|google-authenticator-valid`

### loginLogs

- `rows`: `nullable|integer`
- `page`: `nullable|integer`

## 数据流

### 1. 打开账号中心

1. 用户点击右上角“帐号信息”
2. 前端跳转到 `/usercenter`
3. 页面调用 `GET /auth/user-center`
4. 页面展示资料、安全设置与最近登录摘要
5. 最近登录表格再调用 `POST /auth/login-logs`

### 2. 更新基础资料

1. 用户编辑姓名、头像、分机或备注
2. 前端提交 `POST /auth/update-profile`
3. 后端只更新允许修改字段
4. 成功后前端刷新账号中心数据
5. 同步更新 Vuex 与会话缓存中的当前用户信息

### 3. 修改密码

1. 用户输入旧密码、新密码、确认密码
2. 前端提交 `POST /auth/reset-password`
3. 后端验证并更新密码
4. 后端删除该用户全部 token
5. 前端提示成功并调用退出登录流程

### 4. 绑定或更换动态口令

1. 用户点击“绑定动态口令”或“更换动态口令”
2. 前端调用 `GET /auth/secret`
3. 后端生成新的 secret 与二维码
4. 用户扫码并输入验证码
5. 前端提交 `POST /auth/secret`
6. 后端验证验证码并保存 secret
7. 前端刷新安全设置状态

### 5. 解绑动态口令

1. 用户点击“解绑动态口令”
2. 前端调用 `GET /auth/clear-secret`
3. 若系统强制动态口令开启，则后端返回错误
4. 否则清除当前用户 secret
5. 前端刷新状态

## 错误处理

### 基础资料

- 获取资料失败：展示加载失败态与重试入口
- 表单校验失败：展示字段级错误信息

### 修改密码

- 旧密码错误：明确提示
- 两次密码不一致：明确提示
- 修改成功后必须重新登录

### 动态口令

- 验证码错误或过期：明确提示
- 系统强制启用动态口令时：
  - 明确告知当前租户必须保持已绑定状态
  - 拒绝解绑请求

### 最近登录

- 列表接口失败时，只影响日志区块，不影响资料与安全设置使用

## 安全与边界

1. 所有账号中心接口都只作用于当前登录用户，不接收外部 `id`
2. 禁止账号中心修改角色、部门、在职状态等管理员字段
3. 修改密码后删除 token，避免旧设备继续持有有效会话
4. 解绑动态口令要受系统配置约束
5. 动态口令二维码生成和绑定必须使用当前租户当前用户上下文，避免串账号

## 测试设计

本阶段以后端测试为主，前端先做手工联调。

建议补充以下后端测试：

1. 当前用户可以获取账号中心资料
2. 账号中心资料接口不会泄露其他用户信息
3. 当前用户只能更新允许修改的资料字段
4. 分机唯一性校验对当前用户更新生效
5. 修改密码成功后旧密码失效且 token 被清空
6. 当前用户可生成并绑定动态口令
7. 系统强制动态口令开启时不允许解绑
8. 最近登录接口仅返回当前用户自己的登录记录

前端手工回归清单：

1. 点击右上角“帐号信息”不再 404
2. `usercenter` 页面不出现在左侧菜单
3. 资料修改成功后右上角名称与头像同步刷新
4. 修改密码后被要求重新登录
5. 动态口令绑定、替换、解绑流程可用
6. 最近登录记录展示正确

## 风险与注意点

### 1. 现有动态口令接口上下文不一致

`UserController::getSecret()` 当前使用的是管理员上下文邮箱，不可直接拿到账号中心复用，需要改成当前用户上下文的实现。

### 2. 头像字段可能已有历史值但前端顶部未展示

当前 `userbar.vue` 只显示姓名首字，并未使用头像字段。账号中心即使支持头像上传，也需要评估是否同步改右上角展示规则。

### 3. 资料缓存同步

若账号中心修改后只更新页面本地状态，不同步更新 Vuex / session，则顶部用户栏仍会显示旧值，体验会割裂。

### 4. 登录日志查询结构复用

现有 `LogController::login()` 支持更多筛选条件，但账号中心必须收紧范围，避免暴露不必要的检索能力。

## 实施顺序建议

1. 前端新增 `/usercenter` 静态隐藏路由与空页面，先消除 404
2. 后端新增账号中心资料与自助资料更新接口
3. 后端迁移动态口令自助接口到 `auth` 语义
4. 后端新增最近登录自助查询接口
5. 补后端测试
6. 前端完成账号中心三块页面接线
7. 联调资料更新、密码修改、动态口令、最近登录

## 分支约定

按当前用户要求，使用普通 Git 分支，不使用 worktree：

- 后端分支：`feature/user-center-account-backend`
- 前端分支：`feature/user-center-account-frontend`

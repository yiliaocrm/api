# 欠款管理 Vue3 重建设计

## 基本信息

- 主题：`cashier-arrearage-vue3`
- 日期：`2026-03-27`
- 范围：重建机构端 Vue3 版欠款管理页面，并将后端列表查询切换为场景化搜索模式
- 关联仓库：
  - 后端：`D:\laragon\www\his`
  - 前端：`D:\laragon\www\his-frontend-vue3`
  - 旧版前端参考：`D:\wwwroot\his-frontend-vue2`

## 背景

当前 Vue3 仓库中的欠款管理页面 [index.vue](D:/laragon/www/his-frontend-vue3/src/views/cashier-arrearage/index.vue) 仍是空模板，尚未迁移旧版功能。

旧版 Vue2 页面 [index.vue](D:/wwwroot/his-frontend-vue2/src/views/cashier-arrearage/index.vue) 已具备以下能力：

- 按录单日期、顾客信息、单据状态查询
- 查看欠款主列表
- 展开行查看还款明细
- 执行还款
- 执行免单

当前后端 [CashierArrearageController.php](D:/laragon/www/his/app/Http/Controllers/Web/CashierArrearageController.php) 也仍沿用旧查询方式：

- `created_at_start`
- `created_at_end`
- `keyword`
- `status`

而 Vue3 项目中同类业务页已经统一采用：

- `cy-scene-filter`
- `cy-table`
- `keyword + filters + date` 混合查询模型
- 抽屉或弹窗承载详情与编辑动作

本次目标不是简单把旧页面“翻译”为 Vue3，而是按照现有 Vue3 仓库的页面模式，重建一个可维护、可扩展、与现有业务页一致的欠款管理页面。

## 目标

本阶段完成以下目标：

1. 在 Vue3 仓库实现可用的欠款管理主页面
2. 主列表使用 `cy-table`
3. 搜索区使用 `cy-scene-filter`
4. 页面顶部显式保留：
   - 录单日期
   - 顾客信息
   - 查询
5. 操作列提供：
   - `查看`
   - `还款`
   - `免单`
6. “查看”改为抽屉展示还款明细表，不再使用展开子表格
7. “还款”沿用旧版完整弹窗逻辑
8. “免单”沿用旧版确认与提交逻辑
9. 后端 `CashierArrearageController::manage()` 改为兼容 Vue3 场景化搜索参数
10. 为 `CashierArrearageIndex` 补充租户侧场景字段配置，使后端支持按单据状态等字段做场景筛选

## 非目标

以下内容不纳入本阶段：

- 重做欠款业务规则
- 修改 `repayment` 与 `free` 的核心业务处理逻辑
- 新增独立欠款明细接口
- 改造安装端或总后台前端仓库
- 顺带重构与欠款管理无关的收银模块页面

## 现状分析

### 前端现状

- [index.vue](D:/laragon/www/his-frontend-vue3/src/views/cashier-arrearage/index.vue) 当前只有占位内容
- Vue3 业务页中，`cashier-pay`、`cashier-detail` 等页面已经形成统一写法：
  - `cy-scene-filter`
  - `cy-table`
  - 顶部 `daterange`
  - `keyword + filters`
  - 抽屉/弹窗组件拆分
- 旧版 Vue2 页面已完整覆盖欠款管理业务，但交互形式包含：
  - 展开行查看明细
  - EasyUI 风格工具栏和表单

### 后端现状

- [CashierArrearageController.php](D:/laragon/www/his/app/Http/Controllers/Web/CashierArrearageController.php) 已提供：
  - `manage`
  - `repayment`
  - `free`
- 其中 `manage` 当前仍按旧参数过滤，不支持 `filters`
- 项目内已存在通用场景化搜索能力：
  - [ParseFilter.php](D:/laragon/www/his/app/Helpers/ParseFilter.php)
  - [QueryConditionsTrait.php](D:/laragon/www/his/app/Traits/QueryConditionsTrait.php)
  - `scene_fields` 租户侧配置
- `CashierPayIndex`、`CashierDetailIndex` 等页面已经通过 `queryConditions()` 接入场景化搜索

### 场景字段配置现状

- 租户侧场景字段由 [SceneFieldTableSeeder.php](D:/laragon/www/his/database/seeders/Tenant/SceneFieldTableSeeder.php) 汇总
- 每个页面对应一个 `database/seeders/Tenant/SceneFields/*Seeder.php`
- 当前已存在：
  - `CashierPayIndex`
  - `CashierDetailIndex`
- 当前不存在 `CashierArrearageIndex` 对应的场景字段配置

## 推荐方案

推荐采用“按 Vue3 现有业务页模式重建”的方案。

核心做法：

1. 前端使用 `cy-scene-filter + cy-table + 本页抽屉/弹窗组件` 结构重建页面
2. 后端继续复用现有 `manage / repayment / free` 路由，不新增无必要接口
3. `manage` 查询参数切换为 Vue3 风格：
   - `date`
   - `keyword`
   - `filters`
   - `rows / page / sort / order`
4. 租户侧补充 `CashierArrearageIndex` 场景字段配置

不推荐采用“直接把旧版字段和布局平移到 Vue3 页面”的方案，因为那会导致：

- 与现有 Vue3 业务页风格不一致
- 场景化搜索和列筛选能力缺失
- 后续维护仍要再次整理

## 页面设计

### 1. 页面结构

新版 `CashierArrearageIndex` 建议拆为以下三个单元：

1. 主页面 `index.vue`
2. 还款弹窗 `form.vue`
3. 明细抽屉 `detail-drawer.vue`

职责划分：

- `index.vue`
  - 管理搜索条件
  - 管理主表格
  - 承载操作列
  - 打开还款弹窗与明细抽屉
  - 处理免单确认和刷新
- `form.vue`
  - 只负责还款
  - 成功后向父组件派发刷新事件
- `detail-drawer.vue`
  - 只负责展示当前欠款单的还款明细列表

### 2. 搜索区设计

页面顶部使用 `cy-scene-filter`，页面标识为 `CashierArrearageIndex`。

显式展示内容仅保留：

- 录单日期
- 顾客信息搜索框
- 查询

其中：

- 录单日期使用 `daterange`
- 顾客信息继续使用 `keyword`
- 高级筛选由 `cy-scene-filter` 承载

用户确认的规则是：

- 页面顶部不再显式提供“单据状态”筛选控件
- 但后端场景字段中仍需配置“单据状态”，供 `cy-scene-filter` 进行高级筛选

### 3. 主表格设计

主列表使用 `cy-table`。

建议保留旧版核心字段，但按 Vue3 页面密度进行适度收敛：

- 顾客姓名
- 顾客卡号
- 类别
- 项目名称/物品名称
- 套餐名称
- 次数/数量
- 应收金额
- 实收金额
- 欠款金额
- 累计还款
- 尚欠金额
- 单据状态
- 销售人员
- 结算科室
- 结单人员
- 最近还款
- 录单时间
- 操作

展示约定：

- 顾客姓名支持打开现有顾客档案抽屉
- `欠款金额`、`尚欠金额` 使用强调色
- `单据状态` 使用现有状态映射进行标签化展示

### 4. 操作列设计

操作列固定提供三个入口：

- `查看`
- `还款`
- `免单`

交互规则：

- `查看`：打开抽屉
- `还款`：打开还款弹窗
- `免单`：先确认，再提交

不再使用双击整行或展开行承载明细。

### 5. 查看抽屉设计

“查看”使用抽屉展示，内容仅为旧版展开子表格中的还款明细列表，不额外展示欠款单头部信息。

明细字段为：

- 收费单号
- 实收金额
- 还款备注
- 结算科室
- 销售人员
- 结单人员
- 还款时间

抽屉只承担展示职责，不在其中放编辑动作。

### 6. 还款弹窗设计

还款弹窗保留旧版完整逻辑，字段包括：

- 项目名称或物品名称
- 尚欠金额
- 本次还款
- 还款方式
- 还款方式金额分摊
- 销售人员
- 结算科室
- 还款备注

行为规则：

- 支持多支付方式组合
- 根据支付方式分摊自动汇总本次还款金额
- 提交成功后关闭弹窗并刷新主列表

### 7. 免单交互设计

免单继续复用旧接口和旧业务规则，仅将交互改为 Vue3 页面方式。

前置校验：

- 未选择记录时不可操作
- 单据已清讫时不可免单
- 单据已免单时不可免单

交互方式：

- 点击后弹确认框
- 成功后刷新主列表

## 前端实现设计

### 1. API 模型

Vue3 仓库当前尚无独立的 `cashier-arrearage` API 模块，需要补充对应接口封装。

建议封装：

- `manage`
- `repayment`
- `free`

分别对应：

- `POST /cashier-arrearage/manage`
- `POST /cashier-arrearage/repayment`
- `GET /cashier-arrearage/free`

### 2. 页面参数模型

主列表参数统一使用 Vue3 现有业务页模式：

- `date: [start, end]`
- `keyword`
- `filters`
- `rows`
- `page`
- `sort`
- `order`

### 3. 场景过滤交互

`cy-scene-filter` 负责：

- 搜索关键字回传
- 场景模板加载
- 场景字段加载
- 列过滤入口联动

主表列中，只要存在对应场景字段配置，就可标记为 `filterable`，允许从列表列头进入高级筛选。

### 4. 组件复用原则

优先复用 Vue3 仓库现有能力：

- 顾客档案抽屉
- 缓存类接口
- `useMsgbox` 或项目当前消息提示封装
- 现有 `cy-table`、`cy-drawer`、`cy-dialog` 模式

不额外引入新的页面级状态管理方式。

## 后端实现设计

### 1. 路由设计

不新增后端路由，继续复用现有：

- `POST /cashier-arrearage/manage`
- `POST /cashier-arrearage/repayment`
- `GET /cashier-arrearage/free`

这样可以减少接口变更范围，重点聚焦在 Vue3 页面接入和 `manage` 查询重构。

### 2. manage 查询模型调整

[CashierArrearageController.php](D:/laragon/www/his/app/Http/Controllers/Web/CashierArrearageController.php) 中的 `manage()` 需要改为与 Vue3 页面参数对齐。

建议查询流程：

1. 读取 `rows / sort / order`
2. 基于 `CashierArrearage::query()` 构建列表查询
3. `with` 预加载：
   - `customer`
   - `details`
4. `leftJoin customer`
5. 使用 `date` 处理录单日期范围
6. 使用 `keyword` 处理顾客信息搜索
7. 使用 `queryConditions('CashierArrearageIndex')` 处理场景筛选
8. 支持排序与分页

返回结构继续保持：

- `rows`
- `total`

不要求本次为明细抽屉拆分独立接口，因为当前 `details` 已可通过预加载返回，足够支撑页面使用。

### 3. repayment 与 free

`repayment()` 与 `free()` 的核心业务逻辑本次不改，仅确保 Vue3 前端可以按旧规则正确调用。

也就是说：

- 不调整事务结构
- 不改业务数据写入规则
- 不在本次顺带重构还款业务

## 场景字段设计

### 1. 页面标识

新增页面标识：

- `CashierArrearageIndex`

### 2. 配置位置

在租户侧场景字段配置中新增对应 seeder：

- `database/seeders/Tenant/SceneFields/CashierArrearageSeeder.php`

并由 [SceneFieldTableSeeder.php](D:/laragon/www/his/database/seeders/Tenant/SceneFieldTableSeeder.php) 自动汇总加载。

### 3. 首批字段建议

虽然页面顶部显式只保留录单日期和顾客信息，但 `cy-scene-filter` 对应字段建议至少包含：

- 单据状态

如实现时确认有必要，也可补充与当前列表列强相关的其他筛选字段，但不应超出本次最小范围太多。

“单据状态”配置建议：

- `page`: `CashierArrearageIndex`
- `table`: `cashier_arrearage`
- `field`: `status`
- `component`: `select`
- `component_params`: 读取现有欠款状态配置
- `operators`: 至少支持 `=` / `<>` 或按当前场景组件约定的单选等于模式

## 分支设计

按用户要求，前后端分别新开普通 Git 分支开发，不使用 worktree。

建议分支名：

- 后端：`feat/cashier-arrearage-vue3`
- 前端：`feat/cashier-arrearage-vue3`

## 测试与验证设计

### 后端测试

建议至少补充以下测试：

1. `manage` 在 `date` 条件下能正确筛选
2. `manage` 在 `keyword` 条件下能正确筛选
3. `manage` 在 `filters` 中使用 `status` 时能正确筛选
4. `manage` 返回结构满足 `rows + total`
5. `repayment` 与 `free` 现有行为不回归

### 前端自测

建议至少完成以下联调验证：

1. 页面初始加载正常
2. 录单日期切换后列表刷新正常
3. 顾客信息搜索正常
4. 场景筛选中的单据状态筛选正常
5. 点击“查看”可打开抽屉并看到还款明细
6. 点击“还款”可打开弹窗并成功提交
7. 点击“免单”可确认并成功提交
8. 还款或免单后主列表正确刷新
9. 已清讫、已免单、尚欠为 0 等限制条件提示正确

## 风险与注意点

### 1. 租户场景字段配置不是纯代码逻辑

`scene_fields` 为租户侧数据配置，不能只改控制器。

这意味着实现时除了代码变更，还必须同步补上 `CashierArrearageIndex` 的场景字段 seeder，并评估既有租户如何更新配置。

### 2. 还款弹窗依赖缓存数据

旧版还款弹窗依赖：

- 用户
- 账户
- 科室

Vue3 版也应继续通过现有缓存接口加载，不应在页面中写死选项。

### 3. 列表与明细同接口返回的取舍

当前明细抽屉可直接复用主列表返回中的 `details` 数据，优点是实现简单、联调快。

如果后续出现性能问题，再考虑拆分为独立明细接口；本阶段不提前复杂化。

## 验收标准

满足以下条件即可认为本次设计完成：

1. Vue3 欠款管理页面可正常使用
2. 主表使用 `cy-table`
3. 搜索区使用 `cy-scene-filter`
4. 页面顶部显式仅保留录单日期与顾客信息搜索
5. 操作列存在 `查看 / 还款 / 免单`
6. 查看明细改为抽屉展示还款明细表
7. 还款弹窗保留旧版完整业务字段与行为
8. 免单交互沿用旧版逻辑
9. 后端 `manage` 已切换为支持 `date + keyword + filters`
10. `CashierArrearageIndex` 已具备对应场景字段配置

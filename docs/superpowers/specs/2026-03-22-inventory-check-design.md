# 库存盘点功能设计

## 1. 背景与目标

当前系统已经具备以下库存相关能力：

- 进货入库：采购单审核后生成库存批次和库存变动明细
- 库存报损：报损单审核后扣减库存批次并生成库存变动明细
- 库存报溢：报溢单审核后新增库存批次并生成库存变动明细

本次需要补齐“库存盘点”能力，用于按仓库对当前库存商品进行实盘登记，并在盘点审核后自动生成后续处理单据。

本次设计目标：

- 新增独立的库存盘点单据
- 按仓库加载当前有库存的商品作为默认盘点明细
- 录入实盘数量并计算账实差异
- 审核盘点单时，按差异自动生成报损单和报溢单草稿
- 不在盘点审核时直接变更库存

## 2. 范围与边界

本期范围：

- 按商品维度盘点
- 支持草稿保存、编辑、删除、审核
- 审核后生成草稿状态的报损单和报溢单
- 在盘点单上保留生成结果的关联关系

明确不做：

- 不做按批次维度盘点
- 不做盘点审核即直接调整库存
- 不做反审核
- 不在本期引入额外的复杂审批流

## 3. 用户流程

### 3.1 新建盘点单

1. 用户进入库存盘点页面
2. 选择盘点仓库、盘点日期、经办人并填写备注
3. 通过“加载库存商品”操作，读取该仓库当前有库存的商品
4. 系统生成默认明细，带出账面数量与成本单价
5. 用户录入每个商品的实盘数量
6. 前端实时计算差异数量与差异金额
7. 用户保存为草稿

### 3.2 审核盘点单

1. 用户在草稿状态下发起审核
2. 系统按明细差异拆分数据：
   - `diff_number < 0`：生成报损草稿单
   - `diff_number > 0`：生成报溢草稿单
   - `diff_number = 0`：不生成后续单据
3. 若某一侧没有差异，不生成对应单据
4. 盘点单状态更新为已审核，并记录生成的报损单/报溢单 ID
5. 后续库存变更仍由报损单或报溢单各自审核时完成

## 4. 业务规则

### 4.1 盘点明细来源

- 默认明细来源于所选仓库当前 `inventory` 表中存在库存的商品
- 页面允许用户删减明细
- 页面允许补充商品，便于处理账面缺失但实盘存在的情况

### 4.2 差异计算

- `book_number`：账面数量，来源于当前库存
- `actual_number`：实盘数量，由用户录入
- `diff_number = actual_number - book_number`
- `price`：成本单价，建议取当前库存成本单价
- `diff_amount = diff_number * price`

### 4.3 审核生成规则

- 审核盘点单时不直接更新 `inventory`
- 审核时根据差异自动生成后续单据：
  - 盘亏生成一张报损草稿单
  - 盘盈生成一张报溢草稿单
- 单据生成方式为“按当前盘点单汇总生成”，每张盘点单最多生成一张报损单和一张报溢单
- 若某一侧没有明细，不生成空单据
- 审核操作必须具备幂等保护，避免重复生成后续单据

### 4.4 删除限制

- 仅允许删除草稿状态盘点单
- 已审核盘点单禁止删除
- 若后续需要加强约束，可进一步禁止删除已生成关联单据的草稿盘点单，但本期核心约束以“仅草稿可删”为准

## 5. 数据设计

### 5.1 主表：`inventory_checks`

建议字段：

- `id`
- `key`：盘点单号
- `date`：盘点日期
- `warehouse_id`
- `department_id`
- `user_id`：经办人
- `remark`
- `status`：`1=草稿, 2=已审核`
- `check_user`
- `check_time`
- `inventory_loss_id`：关联生成的报损单 ID，可空
- `inventory_overflow_id`：关联生成的报溢单 ID，可空
- `create_user_id`
- `created_at`
- `updated_at`

说明：

- `department_id` 预留以对齐现有库存报损、报溢单据结构
- `inventory_loss_id` 与 `inventory_overflow_id` 用于从盘点单跳转到后续处理单据

### 5.2 明细表：`inventory_check_details`

建议字段：

- `id`
- `inventory_check_id`
- `key`
- `date`
- `warehouse_id`
- `goods_id`
- `goods_name`
- `specs`
- `manufacturer_id`
- `manufacturer_name`
- `unit_id`
- `unit_name`
- `book_number`：账面数量
- `actual_number`：实盘数量
- `diff_number`：差异数量
- `price`：成本单价
- `diff_amount`：差异金额
- `remark`
- `status`
- `created_at`
- `updated_at`

说明：

- 本期不引入 `inventory_batchs_id`
- 明细保留商品快照字段，避免后续商品资料变更影响盘点结果

## 6. 后端设计

### 6.1 模型与关系

新增模型：

- `App\Models\InventoryCheck`
- `App\Models\InventoryCheckDetail`

建议关系：

- `InventoryCheck`:
  - `details()`
  - `warehouse()`
  - `department()`
  - `user()`
  - `checkUser()`
  - `createUser()`
  - `inventoryLoss()`
  - `inventoryOverflow()`
- `InventoryCheckDetail`:
  - `inventoryCheck()`
  - `goods()`

### 6.2 控制器与请求类

新增控制器：

- `App\Http\Controllers\Web\InventoryCheckController`

建议接口：

- `GET /inventoryCheck/manage`
- `POST /inventoryCheck/create`
- `POST /inventoryCheck/update`
- `POST /inventoryCheck/check`
- `POST /inventoryCheck/remove`
- `GET /inventoryCheck/loadInventory`

新增请求类：

- `App\Http\Requests\Web\InventoryCheckRequest`
- `App\Http\Requests\InventoryCheck\CreateRequest`
- `App\Http\Requests\InventoryCheck\UpdateRequest`
- `App\Http\Requests\InventoryCheck\CheckRequest`
- `App\Http\Requests\InventoryCheck\RemoveRequest`

### 6.3 服务层

建议新增：

- `App\Services\InventoryCheckService`

职责：

- 按仓库读取当前库存商品并组装盘点明细
- 在审核时拆分盘亏/盘盈明细
- 创建报损草稿单及其明细
- 创建报溢草稿单及其明细
- 更新盘点单关联关系与审核状态

原因：

- 审核盘点单会跨多个单据模型写入，放在控制器中会使控制器过重
- 服务层更便于编写测试与复用生成逻辑

### 6.4 与现有模块衔接

- 盘点单审核时复用现有 `InventoryLoss`、`InventoryLossDetail`、`InventoryOverflow`、`InventoryOverflowDetail` 数据结构
- 生成的报损单、报溢单状态均为草稿
- 报损单、报溢单后续仍由其自身审核流程控制库存增减

## 7. 前端设计

前端参考空模板文件：

- `D:\laragon\www\his-frontend-vue3\src\views\inventory-check\index.vue`

### 7.1 页面结构

建议延续现有单据型页面风格：

- 顶部筛选区
- 列表表格区
- 新建/编辑单据表单
- 明细表编辑区

### 7.2 列表页能力

- 按仓库、状态、关键字、日期筛选
- 展示单号、盘点日期、仓库、经办人、状态、录单人、审核人、审核时间
- 审核后展示报损单号、报溢单号并支持跳转

### 7.3 编辑页能力

表头字段：

- 仓库
- 盘点日期
- 经办人
- 备注

明细字段：

- 商品
- 规格
- 单位
- 账面数
- 实盘数
- 差异数
- 成本单价
- 差异金额
- 备注

关键交互：

- 仓库选定后可执行“加载库存商品”
- 实盘数编辑后即时回算差异数量与差异金额
- 支持删除明细
- 支持手动补充商品
- 审核成功后展示生成结果

## 8. 状态与约束

### 8.1 盘点单状态

- `1`：草稿
- `2`：已审核

### 8.2 操作约束

- 草稿状态允许编辑、删除、审核
- 已审核状态只允许查看，不允许再次审核
- 审核接口需加事务与并发保护，防止重复生成后续单据

## 9. 测试设计

建议至少覆盖以下测试：

- 创建草稿盘点单成功
- 按仓库加载库存商品成功
- 更新草稿盘点单成功
- 审核时仅生成报损草稿单
- 审核时仅生成报溢草稿单
- 审核时同时生成报损草稿单和报溢草稿单
- 审核时无差异则不生成后续单据
- 已审核盘点单不能重复审核
- 草稿盘点单可以删除
- 已审核盘点单不能删除

## 10. 实施要求与协作约束

- 后端与前端分别创建独立分支进行开发
- 本次不使用 git worktree
- 若需要执行数据库操作，例如 migration、seeder 或数据修复，需要先通知用户

## 11. 风险与后续扩展

本期主要风险：

- 当前 `inventory` 为商品级库存，不含批次级盘点能力，需避免在本期误扩范围
- 盘点补充商品场景会带来“账面数为 0、实盘数大于 0”的报溢路径，需在校验与生成逻辑中明确支持
- 审核生成后续单据时需要保持事务一致性，避免只生成一半

后续可扩展方向：

- 按批号盘点
- 盘点差异原因分类
- 盘点单导入
- 盘点任务分配与多人协同
- 反审核与撤销生成后续单据能力

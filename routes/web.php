<?php

use App\Http\Controllers\Web as Web;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [Web\AuthController::class, 'home'])->name('login')->withoutMiddleware('auth:sanctum');
Route::get('/new', [Web\AuthController::class, 'new'])->withoutMiddleware('auth:sanctum');

Route::controller(Web\AuthController::class)->prefix('auth')->group(function () {
    Route::get('config', 'getConfig')->withoutMiddleware('auth:sanctum');
    Route::get('qrcode', 'qrcode')->withoutMiddleware('auth:sanctum');
    Route::post('login', 'login')->withoutMiddleware('auth:sanctum');
    Route::get('profile', 'profile');
    Route::post('logout', 'logout');
    Route::post('reset-password', 'resetPassword');
});

Route::controller(Web\MessageController::class)->prefix('message')->group(function () {
    Route::get('export', 'export');
    Route::get('import', 'import');
});

Route::controller(Web\DownloadController::class)->prefix('download')->group(function () {
    Route::get('export', 'export');
});

Route::controller(Web\WorkbenchController::class)->prefix('workbench')->group(function () {
    Route::get('menu', 'menu');
    Route::get('today', 'today');
    Route::post('followup', 'followup');
    Route::post('birthday', 'birthday');
    Route::post('reception', 'reception');
    Route::post('appointment', 'appointment');
    Route::post('inventory-alarm', 'inventoryAlarm');
    Route::post('inventory-expiry', 'inventoryExpiry');
});

Route::controller(Web\FieldController::class)->prefix('field')->group(function () {
    Route::get('reset', 'reset');
    Route::post('save', 'save');
});

Route::controller(Web\ParameterController::class)->prefix('parameter')->group(function () {
    Route::get('info', 'info');
    Route::get('index', 'index');
    Route::post('update', 'update');
});

Route::controller(Web\StoreController::class)->prefix('store')->group(function () {
    Route::get('index', 'index');
    Route::get('remove', 'remove');
    Route::post('create', 'create');
    Route::post('update', 'update');
});

Route::controller(Web\AddressController::class)->prefix('address')->group(function () {
    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\BedController::class)->prefix('bed')->group(function () {
    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\RoomController::class)->prefix('room')->group(function () {
    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\TagsController::class)->prefix('tags')->group(function () {
    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::get('move', 'move');
});

Route::controller(Web\FailureController::class)->prefix('failure')->group(function () {
    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\ExpenseCategoryController::class)->prefix('expense-category')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\DepartmentController::class)->prefix('department')->group(function () {
    Route::post('manage', 'manage');
    Route::post('update', 'update');
    Route::post('create', 'create');
    Route::get('remove', 'remove');
    Route::get('disable', 'disable');
    Route::get('enable', 'enable');
});

Route::controller(Web\MediumController::class)->prefix('medium')->group(function () {
    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::get('swap', 'swap');
});

Route::controller(Web\PrintTemplateController::class)->prefix('print-template')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::post('copy', 'copy');
    Route::get('remove', 'remove');
    Route::get('info', 'info');
    Route::post('default', 'default');
});

Route::controller(Web\PrintController::class)->prefix('print')->group(function () {
    Route::get('cashier', 'cashier');
    Route::get('purchase-detail', 'purchaseDetail');
    Route::get('purchase-return', 'purchaseReturn');
    Route::get('cashier-invoice', 'cashierInvoice');
    Route::get('cashier-collection', 'cashierCollection');
    Route::get('department-picking', 'departmentPicking');
    Route::get('inventory-transfer', 'inventoryTransfer');
    Route::get('inventory-loss', 'inventoryLoss');
    Route::get('inventory-overflow', 'inventoryOverflow');
});

Route::controller(Web\ItemController::class)->prefix('item')->group(function () {
    Route::post('manage', 'manage');
    Route::get('info', 'info');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::get('move', 'move');
});

Route::controller(Web\LogController::class)->prefix('log')->group(function () {
    Route::post('login', 'login');
    Route::post('phone', 'phone');
    Route::post('export', 'export');
    Route::post('customer', 'customer');
});

Route::controller(Web\UnitController::class)->prefix('unit')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\AccountsController::class)->prefix('accounts')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::get('info', 'info');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\WarehouseController::class)->prefix('warehouse')->group(function () {
    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::get('enable', 'enable');
    Route::get('disable', 'disable');
});

Route::controller(Web\ManufacturerController::class)->prefix('manufacturer')->group(function () {
    Route::post('manage', 'manage');
    Route::get('info', 'info');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::get('enable', 'enable');
    Route::get('disable', 'disable');
    Route::get('export', 'export');
    Route::post('combogrid', 'combogrid');
});

Route::controller(Web\SupplierController::class)->prefix('supplier')->group(function () {
    Route::post('manage', 'manage');
    Route::get('info', 'info');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::post('query', 'query');
    Route::get('remove', 'remove');
    Route::get('enable', 'enable');
    Route::get('disable', 'disable');
    Route::get('export', 'export');
});

Route::controller(Web\ProductTypeController::class)->prefix('product-type')->group(function () {
    Route::get('all', 'all');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::get('move', 'move');
});

Route::controller(Web\ProductController::class)->prefix('product')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::post('remove', 'remove');
    Route::post('import', 'import');
    Route::post('batch', 'batch');
    Route::get('export', 'export');
    Route::post('query', 'query');
    Route::post('combogrid', 'combogrid');
    Route::get('enable', 'enable');
    Route::get('disable', 'disable');
});

Route::controller(Web\ProductPackageTypeController::class)->prefix('product-package-type')->group(function () {
    Route::get('all', 'all');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::get('sort', 'sort');
});

Route::controller(Web\ProductPackageController::class)->prefix('product-package')->group(function () {
    Route::post('manage', 'manage');
    Route::post('choose', 'choose');
    Route::get('remove', 'remove');
    Route::post('create', 'create');
    Route::post('update', 'update');
});

Route::controller(Web\UserController::class)->prefix('user')->group(function () {
    Route::post('manage', 'manage');
    Route::get('info', 'info');
    Route::post('edit', 'edit');
    Route::post('create', 'create');
    Route::get('permission', 'getPermission');
    Route::post('permission', 'postPermission');
    Route::get('clear-permission', 'clearPermission');
    Route::get('ban', 'ban');
    Route::get('unban', 'unban');
    Route::get('secret', 'getSecret');
    Route::post('secret', 'postSecret');
    Route::get('clear-secret', 'clearSecret');
    Route::get('code', 'loginCode');
});

Route::controller(Web\UserGroupController::class)->prefix('user-group')->group(function () {
    Route::get('index', 'index');
    Route::get('remove', 'remove');
    Route::post('create', 'create');
    Route::post('update', 'update');
});

Route::controller(Web\DepartmentGroupController::class)->prefix('department-group')->group(function () {
    Route::get('index', 'index');
    Route::get('remove', 'remove');
    Route::post('create', 'create');
    Route::post('update', 'update');
});

Route::controller(Web\RoleController::class)->prefix('role')->group(function () {
    Route::get('all', 'all');
    Route::get('copy', 'copy');
    Route::get('info', 'info');
    Route::post('users', 'users');
    Route::get('remove', 'remove');
    Route::post('create', 'create');
    Route::post('manage', 'manage');
    Route::post('update', 'update');
    Route::post('permission', 'permission');
});

Route::controller(Web\CacheController::class)->prefix('cache')->group(function () {
    Route::post('dependency', 'dependency');
    Route::get('rooms', 'rooms');
    Route::get('qufriend', 'qufriend');
    Route::get('accounts', 'accounts');
    Route::get('stores', 'stores');
    Route::get('menu', 'menu');
    Route::get('webmenu', 'webmenu');
    Route::get('tags', 'tags');
    Route::get('unit', 'unit');
    Route::get('users', 'users');
    Route::get('roles', 'roles');
    Route::get('items', 'items');
    Route::get('address', 'address');
    Route::get('mediums', 'mediums');
    Route::get('suppliers', 'suppliers');
    Route::get('failures', 'failures');
    Route::get('positions', 'positions');
    Route::get('warehouse', 'warehouse');
    Route::get('departments', 'departments');
    Route::get('goods-type', 'goodsType');
    Route::get('purchase-type', 'purchaseType');
    Route::get('product-type', 'productType');
    Route::get('followup-role', 'followupRole');
    Route::get('followup-tool', 'followupTool');
    Route::get('followup-type', 'followupType');
    Route::get('customer-job', 'customerJob');
    Route::get('reception-type', 'receptionType');
    Route::get('customer-group', 'customerGroup');
    Route::get('expense-category', 'expenseCategory');
    Route::get('reservation-type', 'reservationType');
    Route::get('customer-economic', 'customerEconomic');
    Route::get('phone-relationship', 'phoneRelationship');
    Route::get('product-package-type', 'productPackageType');
    Route::get('followup-template-type', 'followupTemplateType');
    Route::get('department-picking-type', 'departmentPickingType');
});

Route::controller(Web\MenuController::class)->prefix('menu')->group(function () {
    Route::post('manage', 'manage');
});

Route::controller(Web\CustomerController::class)->prefix('customer')->group(function () {
    Route::post('index', 'index');
    Route::post('create', 'create');
    Route::get('remove', 'remove');
    Route::post('merge', 'merge');
    Route::post('update', 'update');
    Route::post('query', 'query');
    Route::post('import', 'import');
    Route::get('fill', 'fill');
    Route::get('groups', 'groups');
});

Route::controller(Web\CustomerProfileController::class)->prefix('customer-profile')->group(function () {
    Route::get('info', 'info');
    Route::get('phone', 'phone');
    Route::post('log', 'log');
    Route::post('sms', 'sms');
    Route::post('talk', 'talk');
    Route::get('erkai', 'erkai');
    Route::get('cashier', 'cashier');
    Route::post('goods', 'goods');
    Route::post('photo', 'photo');
    Route::get('coupons', 'coupons');
    Route::get('qufriend', 'qufriend');
    Route::get('overview', 'overview');
    Route::post('product', 'product');
    Route::post('followup', 'followup');
    Route::post('treatment', 'treatment');
    Route::post('consultant', 'consultant');
    Route::post('reservation', 'reservation');
    Route::post('appointment', 'appointment');
});

Route::controller(Web\CustomerQufriendController::class)->prefix('customer-qufriend')->group(function () {
    Route::get('info', 'info');
    Route::get('remove', 'remove');
    Route::post('create', 'create');
    Route::post('update', 'update');
});

Route::controller(Web\CustomerBatchController::class)->prefix('customer-batch')->group(function () {
    Route::post('sms', 'sms');
    Route::post('tags', 'tags');
    Route::post('doctor', 'doctor');
    Route::post('service', 'service');
    Route::post('followup', 'followup');
    Route::post('ascription', 'ascription');
    Route::post('consultant', 'consultant');
    Route::post('join-group', 'joinGroup');
    Route::post('change-group', 'changeGroup');
    Route::post('remove-group', 'removeGroup');
});

Route::controller(Web\CustomerJobController::class)->prefix('customer-job')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\CustomerLevelController::class)->prefix('customer-level')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\CustomerEconomicController::class)->prefix('customer-economic')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::get('info', 'info');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\ReservationController::class)->prefix('reservation')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::get('remove', 'remove');
    Route::post('update', 'update');
    Route::get('info', 'info');
    Route::post('search', 'search');
    Route::post('reception', 'reception');
    Route::get('reminder', 'reminder');
});

Route::controller(Web\ReservationTypeController::class)->prefix('reservation-type')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::get('info', 'info');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\ReservationRemarkTemplateController::class)->prefix('reservation-remark-template')->group(function () {
    Route::post('manage', 'manage');
    Route::post('update', 'update');
    Route::post('create', 'create');
    Route::get('remove', 'remove');
});

Route::controller(Web\ConsultantRemarkTemplateController::class)->prefix('consultant-remark-template')->group(function () {
    Route::post('manage', 'manage');
    Route::post('update', 'update');
    Route::post('create', 'create');
    Route::get('remove', 'remove');
});

Route::controller(Web\ReceptionController::class)->prefix('reception')->group(function () {
    Route::get('info', 'info');
    Route::get('fill', 'fill');
    Route::get('remove', 'remove');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::post('dispatch-consultant', 'dispatchConsultant');
    Route::post('dispatch-doctor', 'dispatchDoctor');
});

Route::controller(Web\ReceptionTypeController::class)->prefix('reception-type')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::get('info', 'info');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\ConsultantController::class)->prefix('consultant')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('fill', 'fill');
    Route::get('info', 'info');
    Route::get('cancel', 'cancel');
});

Route::controller(Web\QuotationController::class)->prefix('quotation')->group(function () {
    Route::get('product-type', 'productType');
    Route::post('product-list', 'productList');
    Route::get('product-package-type', 'productPackageType');
    Route::post('product-package-list', 'productPackageList');
    Route::get('goods-type', 'goodsType');
    Route::post('goods-list', 'goodsList');
});

Route::controller(Web\ErkaiController::class)->prefix('erkai')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::get('info', 'info');
    Route::post('update', 'update');
});

Route::controller(Web\CashierController::class)->prefix('cashier')->group(function () {
    Route::get('info', 'info');
//    Route::get('dashboard', 'dashboard');
    Route::post('manage', 'manage');
    Route::post('consultant-charge', 'consultantCharge');
    Route::post('erkai-charge', 'erkaiCharge');
    Route::post('refund-charge', 'refundCharge');
    Route::post('charge', 'charge');
    Route::get('cancel', 'cancel');
    Route::post('recharge', 'recharge');
    Route::post('details', 'details');
});

Route::controller(Web\CashierPayController::class)->prefix('cashier-pay')->group(function () {
    Route::post('index', 'index');
    Route::post('update', 'update');
});

Route::controller(Web\CashierDetailController::class)->prefix('cashier-detail')->group(function () {
    Route::post('index', 'index');
});

Route::controller(Web\CashierRefundController::class)->prefix('cashier-refund')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::get('remove', 'remove');
});

Route::controller(Web\CashierArrearageController::class)->prefix('cashier-arrearage')->group(function () {
    Route::post('manage', 'manage');
    Route::post('repayment', 'repayment');
    Route::get('free', 'free');
});

Route::controller(Web\CashierRetailController::class)->prefix('cashier-retail')->group(function () {
    Route::get('info', 'info');
    Route::get('fill', 'fill');
    Route::post('manage', 'manage');
    Route::post('charge', 'charge');
    Route::post('pending', 'pending');
    Route::get('remove', 'remove');
});

Route::controller(Web\CashierCouponController::class)->prefix('cashier-coupon')->group(function () {
    Route::get('index', 'index');
});

Route::controller(Web\CashierInvoiceController::class)->prefix('cashier-invoice')->group(function () {
    Route::get('info', 'info');
    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('customer-goods', 'customerGoods');
    Route::get('customer-product', 'customerProduct');
});

Route::controller(Web\FollowupController::class)->prefix('followup')->group(function () {
    Route::get('info', 'info');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::post('execute', 'execute');
    Route::get('originate', 'originate');
    Route::post('batch-insert', 'batchInsert');
});

Route::controller(Web\FollowupToolController::class)->prefix('followup-tool')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\FollowupRoleController::class)->prefix('followup-role')->group(function () {
    Route::get('manage', 'manage');
    Route::get('create', 'create');
    Route::get('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\FollowupTypeController::class)->prefix('followup-type')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\FollowupTemplateController::class)->prefix('followup-template')->group(function () {
    Route::get('type', 'type');
    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::post('create-type', 'createType');
    Route::post('update-type', 'updateType');
    Route::get('remove-type', 'removeType');
});

Route::controller(Web\ScheduleController::class)->prefix('schedule')->group(function () {
    Route::post('scheduling', 'scheduling');
    Route::post('scheduling-create', 'createScheduling');
    Route::post('scheduling-clear', 'clearScheduling');
    Route::get('rule', 'rule');
    Route::post('rule-create', 'createRule');
    Route::get('rule-remove', 'removeRule');
    Route::post('rule-update', 'updateRule');
});

Route::controller(Web\AppointmentController::class)->prefix('appointment')->group(function () {
    Route::get('info', 'info');
    Route::get('drag', 'drag');
    Route::get('config', 'getConfig');
    Route::post('create', 'create');
    Route::post('config', 'saveConfig');
    Route::post('events', 'events');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::get('export', 'export');
    Route::get('history', 'history');
    Route::get('arrival', 'arrival');
    Route::get('schedule', 'getSchedule');
});

Route::controller(Web\GoodsController::class)->prefix('goods')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::post('upload', 'upload');
    Route::get('remove', 'remove');
    Route::post('enable', 'enable');
    Route::post('disable', 'disable');
    Route::post('inventory-batch', 'inventoryBatch');
    Route::post('inventory-detail', 'inventoryDetail');
    Route::post('query', 'query');
    Route::get('query-batchs', 'queryBatchs');
    Route::get('inventory', 'inventory');
});

Route::controller(Web\GoodsTypeController::class)->prefix('goods-type')->group(function () {
    Route::get('all', 'all');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\DrugController::class)->prefix('drug')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::post('upload', 'upload');
    Route::post('enable', 'enable');
    Route::post('disable', 'disable');
    Route::post('inventory-batch', 'inventoryBatch');
    Route::post('inventory-detail', 'inventoryDetail');
});

Route::controller(Web\DrugTypeController::class)->prefix('drug-type')->group(function () {
    Route::get('all', 'all');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\PurchaseController::class)->prefix('purchase')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('check', 'check');
    Route::get('remove', 'remove');
});

Route::controller(Web\PurchaseTypeController::class)->prefix('purchase-type')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::get('info', 'info');
    Route::get('remove', 'remove');
    Route::post('update', 'update');
});

Route::controller(Web\DepartmentPickingTypeController::class)->prefix('department-picking-type')->group(function () {
    Route::post('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\PurchaseReturnController::class)->prefix('purchase-return')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::get('check', 'check');
});

Route::controller(Web\InventoryController::class)->prefix('inventory')->group(function () {
    Route::post('index', 'index');
    Route::post('batch', 'batch');
});

Route::controller(Web\InventoryBatchsController::class)->prefix('inventory-batchs')->group(function () {
    Route::post('index', 'index');
    Route::get('detail', 'detail');
});

Route::controller(Web\InventoryTransferController::class)->prefix('inventory-transfer')->group(function () {
    Route::post('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('check', 'check');
    Route::get('remove', 'remove');
});

Route::controller(Web\InventoryCheckController::class)->prefix('inventory-check')->group(function () {
    Route::post('manage', 'manage');
});

Route::controller(Web\InventoryLossController::class)->prefix('inventory-loss')->group(function () {
    Route::post('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('check', 'check');
    Route::get('remove', 'remove');
});

Route::controller(Web\InventoryOverflowController::class)->prefix('inventory-overflow')->group(function () {
    Route::post('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('check', 'check');
    Route::get('remove', 'remove');
});

Route::controller(Web\RetailOutboundController::class)->prefix('retail-outbound')->group(function () {
    Route::post('manage', 'manage');
    Route::post('query-customer-goods', 'queryCustomerGoods');
    Route::post('create', 'create');
});

Route::controller(Web\DepartmentPickingController::class)->prefix('department-picking')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('check', 'check');
    Route::get('remove', 'remove');
});

Route::controller(Web\ConsumableController::class)->prefix('consumable')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('customer-product', 'customerProduct');
});

Route::controller(Web\DistributorController::class)->prefix('distributor')->group(function () {
    Route::post('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::get('update-number', 'updateNumber');
});

Route::controller(Web\TreatmentController::class)->prefix('treatment')->group(function () {
    Route::post('index', 'index');
    Route::post('record', 'record');
    Route::post('create', 'create');
    Route::post('history', 'history');
    Route::get('undo', 'undo');
});

Route::controller(Web\PrescriptionFrequencyController::class)->prefix('prescription-frequency')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('info', 'info');
    Route::get('remove', 'remove');
});

Route::controller(Web\PrescriptionWaysController::class)->prefix('prescription-ways')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('info', 'info');
    Route::get('remove', 'remove');
});

Route::controller(Web\PrescriptionUnitController::class)->prefix('prescription-unit')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('info', 'info');
    Route::get('remove', 'remove');
});

Route::controller(Web\PharmacyController::class)->prefix('pharmacy')->group(function () {
    Route::post('index', 'index');
    Route::get('detail', 'detail');
});

Route::controller(Web\DiagnosisController::class)->prefix('diagnosis')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
    Route::post('search', 'search');
});

Route::controller(Web\DiagnosisCategoryController::class)->prefix('diagnosis-category')->group(function () {
    Route::get('all', 'all');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\IntegralController::class)->prefix('integral')->group(function () {
    Route::post('index', 'index');
    Route::post('adjust', 'adjust');
});

Route::controller(Web\OutpatientController::class)->prefix('outpatient')->group(function () {
    Route::post('manage', 'manage');
    Route::get('fill', 'fill');
    Route::post('create', 'create');
    Route::post('update', 'update');
});

Route::controller(Web\PrescriptionController::class)->prefix('prescription')->group(function () {
    Route::post('manage', 'manage');
});

Route::prefix('report')->group(function () {
    Route::post('performance/sales', [Web\ReportPerformanceController::class, 'index']);
    Route::post('customer/product', [Web\ReportController::class, 'customerProduct']);
    Route::post('customer/goods', [Web\ReportController::class, 'customerGoods']);
    Route::post('department/cashier', [Web\ReportCashierController::class, 'department']);
    Route::post('customer/refund', [Web\ReportController::class, 'customerRefund']);
    Route::post('consultant/detail', [Web\ReportController::class, 'consultantDetail']);
    Route::get('consultant/order', [Web\ReportController::class, 'consultantOrder']);
    Route::get('followup/statistics', [Web\ReportController::class, 'followupStatistics']);
    Route::post('purchase/detail', [Web\ReportPurchaseController::class, 'detail']);
    Route::post('inventory/detail', [Web\ReportController::class, 'inventoryDetail']);
    Route::post('cashier/list', [Web\ReportController::class, 'cashierList']);
    Route::get('cashier/collect', [Web\ReportCashierController::class, 'collect']);
    Route::post('cashier/deposit-received', [Web\ReportCashierController::class, 'depositReceived']);
    Route::post('cashier/deposit-received-detail', [Web\ReportCashierController::class, 'depositReceivedDetail']);
    Route::post('retail-outbound/detail', [Web\ReportController::class, 'retailOutboundDetail']);
    Route::post('treatment/detail', [Web\ReportController::class, 'treatmentDetail']);
    Route::post('erkai/detail', [Web\ReportController::class, 'erkaiDetail']);
    Route::post('consumable/detail', [Web\ReportConsumableController::class, 'detail']);
    Route::post('product/ranking', [Web\ReportCustomerProductController::class, 'ranking']);
    Route::get('reception/product-analysis', [Web\ReportController::class, 'receptionProductAnalysis']);
    Route::post('department-picking/detail', [Web\ReportDepartmentPickingController::class, 'detail']);
    Route::get('reception/product-analysis-detail', [Web\ReportController::class, 'receptionProductAnalysisDetail']);
});

Route::controller(Web\ExportController::class)->prefix('export')->group(function () {
    Route::get('user', 'user');
    Route::post('goods', 'goods');
    Route::post('customer', 'customer');
    Route::post('appointment', 'appointment');
    Route::post('goods/inventory', 'inventory');
    Route::post('customer/goods', 'customerGoods');
    Route::post('customer/product', 'customerProduct');
    Route::get('customer/log', 'customerLog');
    Route::get('customer/integral', 'customerIntegral');
    Route::get('customer-deposit/detail', 'customerDepositDetail');
    Route::post('consultant/detail', 'consultantDetail');
    Route::get('consultant/order', 'consultantOrder');
    Route::post('performance/sales', 'salesPerformance');
    Route::post('treatment/record', 'treatmentRecord');
    Route::post('cashier/pay', 'cashierPay');
    Route::post('cashier/index', 'cashierIndex');
    Route::post('cashier/detail', 'cashierDetail');
    Route::post('cashier/refund', 'cashierRefund');
    Route::post('cashier/list', 'cashierList');
    Route::post('coupon/detail', 'couponDetail');
    Route::get('erkai/detail', 'erkaiDetail');
    Route::post('product/ranking', 'productRanking');
    Route::get('inventory/detail', 'inventoryDetail');
    Route::get('inventory/batch', 'inventoryBatch');
    Route::post('inventory/alarm', 'inventoryAlarm');
    Route::post('inventory/expiry', 'inventoryExpiry');
    Route::get('purchase/detail', 'purchaseDetail');
    Route::get('followup/statistic', 'followupStatistic');
    Route::post('consumable/detail', 'consumableDetail');
    Route::post('department-picking/detail', 'departmentPickingDetail');
});

Route::controller(Web\CouponController::class)->prefix('coupon')->group(function () {
    Route::post('manage', 'manage');
    Route::post('create', 'create');
    Route::post('issue', 'issue');
    Route::get('remove', 'remove');
    Route::get('detail', 'detail');
    Route::get('cashier', 'cashier');
});

Route::controller(Web\CouponDetailController::class)->prefix('coupon-detail')->group(function () {
    Route::get('manage', 'manage');
    Route::get('histories', 'histories');
});

Route::controller(Web\CustomerGroupController::class)->prefix('customer-group')->group(function () {
    Route::get('fields', 'fields');
    Route::get('manage', 'index');
    Route::get('remove', 'remove');
    Route::get('copy', 'copy');
    Route::get('preview', 'preview');
    Route::get('compute', 'compute');
    Route::post('update', 'update');
    Route::post('create', 'create');
    Route::post('import', 'import');
    Route::get('categories', 'categories');
    Route::post('add-category', 'addCategory');
    Route::get('swap-category', 'swapCategory');
    Route::get('get-category', 'getCategory');
    Route::post('update-category', 'updateCategory');
    Route::get('remove-category', 'removeCategory');
    Route::post('remove-customer', 'removeCustomer');
});

Route::controller(Web\CustomerRfmController::class)->prefix('customer-rfm')->group(function () {
    Route::get('index', 'index');
    Route::get('config', 'getConfig');
    Route::post('store', 'store');
});

Route::controller(Web\ExecutionController::class)->prefix('execution')->group(function () {
    Route::get('participants', 'participants');
});

Route::controller(Web\DataMaintenanceController::class)->prefix('data-maintenance')->group(function () {
    Route::get('index', 'index');
    Route::get('receptions', 'receptions');
    Route::get('customer-product', 'customerProduct');
    Route::get('remove-customer-product', 'removeCustomerProduct');
    Route::post('create-customer-product', 'createCustomerProduct');
});

Route::controller(Web\CustomerPhotoController::class)->prefix('customer-photo')->group(function () {
    Route::post('index', 'index');
    Route::post('create', 'create');
    Route::post('upload', 'upload');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\CustomerPhotoDetailController::class)->prefix('customer-photo-detail')->group(function () {
    Route::post('rename', 'rename');
    Route::get('remove', 'remove');
    Route::get('download', 'download');
});

Route::controller(Web\MarketChannelController::class)->prefix('marketing-channel')->group(function () {
    Route::post('index', 'index');
    Route::get('tree', 'tree');
    Route::get('info', 'info');
    Route::post('upload', 'upload');
    Route::get('remove', 'remove');
    Route::post('create', 'create');
    Route::post('update', 'update');
});

Route::controller(Web\TokenController::class)->prefix('token')->group(function () {
    Route::post('index', 'index');
    Route::get('remove', 'remove');
});

Route::controller(Web\MiniappController::class)->prefix('miniapp')->group(function () {
    Route::post('user/index', 'getUserList');
    Route::post('user/change', 'change');
});

Route::controller(Web\WhitelistController::class)->prefix('whitelist')->group(function () {
    Route::post('index', 'index');
    Route::get('remove', 'remove');
    Route::get('status', 'status');
    Route::get('enable', 'enable');
    Route::get('disable', 'disable');
    Route::post('create', 'create');
    Route::post('update', 'update');
});

Route::controller(Web\SceneController::class)->prefix('scene')->group(function () {
    Route::get('lists', 'lists');
    Route::get('fields', 'fields');
    Route::post('format', 'format');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\MaterialController::class)->prefix('material')->group(function () {
    Route::get('categories/index', 'indexCategory');
    Route::post('categories/create', 'createCategory');
    Route::post('categories/update', 'updateCategory');
    Route::post('categories/sort', 'sortCategory');
    Route::post('categories/disable', 'disableCategory');
    Route::post('categories/enable', 'enableCategory');
    Route::get('categories/remove', 'removeCategory');

    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::get('info', 'info');
    Route::post('update', 'update');
    Route::get('remove', 'remove');
});

Route::controller(Web\QufriendController::class)->prefix('qufriend')->group(function () {
    Route::get('index', 'index');
    Route::get('remove', 'remove');
    Route::post('update', 'update');
    Route::post('create', 'create');
});

Route::controller(Web\CustomerSopController::class)->prefix('customer-sop')->group(function () {
    Route::get('categories', 'categories');
    Route::post('add-category', 'addCategory');
    Route::get('swap-category', 'swapCategory');
    Route::get('remove-category', 'removeCategory');
    Route::post('update-category', 'updateCategory');
    Route::get('index', 'index');
    Route::get('template-list', 'templateList');
    Route::get('template-category', 'templateCategory');
});

Route::controller(Web\PermissionQueryController::class)->prefix('permission-query')->group(function () {
    Route::get('index', 'index');
    Route::get('user', 'user');
    Route::get('role', 'role');
    Route::get('remove', 'remove');
    Route::get('role-user', 'roleUser');
});

Route::controller(Web\SmsController::class)->prefix('sms')->group(function () {
    Route::post('send', 'send');
    Route::get('index', 'index');
    Route::get('dashboard', 'dashboard');
    Route::get('categories', 'categories');
});

Route::controller(Web\SmsTemplateController::class)->prefix('sms-template')->group(function () {
    Route::get('scenarios', 'scenarios');
    Route::get('categories', 'categories');
    Route::post('add-category', 'addCategory');
    Route::post('update-category', 'updateCategory');
    Route::get('remove-category', 'removeCategory');
    Route::get('swap-category', 'swapCategory');
    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::post('update', 'update');
    Route::get('enable', 'enable');
    Route::get('disable', 'disable');
    Route::get('remove', 'remove');
});

// 导入任务
Route::controller(Web\ImportTaskController::class)->prefix('import-task')->group(function () {
    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::get('details', 'details');
    Route::get('import', 'import');
    Route::get('export', 'export');
});

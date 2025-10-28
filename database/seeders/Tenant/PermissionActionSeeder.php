<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionActionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permission_actions')->truncate();
        $permissions = [
            [
                'permission' => 'app.appointment',
                'controller' => 'App\Http\Controllers\Api\AppointmentController',
                'action'     => '*',
            ],
            [
                'permission' => 'app.appointment.create',
                'controller' => 'App\Http\Controllers\Api\AppointmentController',
                'action'     => 'create',
            ],
            [
                'permission' => 'app.customer',
                'controller' => 'App\Http\Controllers\Api\CustomerController',
                'action'     => '*',
            ],
            [
                'permission' => 'app.customer.create',
                'controller' => 'App\Http\Controllers\Api\CustomerController',
                'action'     => 'create',
            ],
            [
                'permission' => 'app.customer.photo',
                'controller' => 'App\Http\Controllers\Api\CustomerController',
                'action'     => 'photo',
            ],
            [
                'permission' => 'app.customer.followup',
                'controller' => 'App\Http\Controllers\Api\CustomerController',
                'action'     => 'followup',
            ],
            [
                'permission' => 'app.photo',
                'controller' => 'App\Http\Controllers\Api\CustomerPhotoController',
                'action'     => '*',
            ],
            [
                'permission' => 'app.photo.create',
                'controller' => 'App\Http\Controllers\Api\CustomerPhotoController',
                'action'     => 'create',
            ],
            [
                'permission' => 'app.followup',
                'controller' => 'App\Http\Controllers\Api\FollowupController',
                'action'     => '*',
            ],
            [
                'permission' => 'app.followup.create',
                'controller' => 'App\Http\Controllers\Api\FollowupController',
                'action'     => 'create',
            ],
            [
                'permission' => 'app.followup.execute',
                'controller' => 'App\Http\Controllers\Api\FollowupController',
                'action'     => 'execute',
            ],
            [
                'permission' => 'workbench.today.index',
                'controller' => 'App\Http\Controllers\Web\WorkbenchController',
                'action'     => 'today',
            ],
            [
                'permission' => 'workbench.today.reception',
                'controller' => 'App\Http\Controllers\Web\ReceptionController',
                'action'     => 'create',
            ],
            [
                'permission' => 'workbench.today.arrival',
                'controller' => 'App\Http\Controllers\Web\AppointmentController',
                'action'     => 'arrival',
            ],
            [
                'permission' => 'workbench.alarm',
                'controller' => 'App\Http\Controllers\Web\WorkbenchController',
                'action'     => 'inventoryAlarm',
            ],
            [
                'permission' => 'workbench.alarm.export',
                'controller' => 'App\Http\Controllers\Web\ExportController',
                'action'     => 'inventoryAlarm',
            ],
            [
                'permission' => 'workbench.expiry',
                'controller' => 'App\Http\Controllers\Web\WorkbenchController',
                'action'     => 'inventoryExpiry',
            ],
            [
                'permission' => 'reservation.manage',
                'controller' => 'App\Http\Controllers\Api\ReservationController',
                'action'     => 'manage',
            ],
            [
                'permission' => 'accounts.manage',
                'controller' => 'App\Http\Controllers\Web\AccountsController',
                'action'     => '*',
            ],
            [
                'permission' => 'address.manage',
                'controller' => 'App\Http\Controllers\Web\AddressController',
                'action'     => '*',
            ],
            [
                'permission' => 'appointment.dashboard',
                'controller' => 'App\Http\Controllers\Web\AppointmentController',
                'action'     => '*',
            ],
            [
                'permission' => 'bed.manage',
                'controller' => 'App\Http\Controllers\Web\BedController',
                'action'     => '*',
            ],
            [
                'permission' => 'cashier.pay',
                'controller' => 'App\Http\Controllers\Web\CashierPayController',
                'action'     => '*',
            ],
            [
                'permission' => 'cashier.arrearage',
                'controller' => 'App\Http\Controllers\Web\CashierArrearageController',
                'action'     => '*',
            ],
            [
                'permission' => 'consumable.manage',
                'controller' => 'App\Http\Controllers\Web\ConsumableController',
                'action'     => '*',
            ],
            [
                'permission' => 'cashier.coupon',
                'controller' => 'App\Http\Controllers\Web\CashierCouponController',
                'action'     => '*',
            ],
            [
                'permission' => 'cashier.refund',
                'controller' => 'App\Http\Controllers\Web\CashierRefundController',
                'action'     => '*',
            ],
            [
                'permission' => 'consultant.remark.manage',
                'controller' => 'App\Http\Controllers\Web\ConsultantRemarkTemplateController',
                'action'     => '*',
                'except'     => 'manage',
            ],
            [
                'permission' => 'cashier.invoice',
                'controller' => 'App\Http\Controllers\Web\CashierInvoiceController',
                'action'     => '*',
            ],
            [
                'permission' => 'cashier.retail',
                'controller' => 'App\Http\Controllers\Web\CashierRetailController',
                'action'     => '*',
            ],
            [
                'permission' => 'cashier.detail',
                'controller' => 'App\Http\Controllers\Web\CashierDetailController',
                'action'     => '*',
            ],
            [
                'permission' => 'cashier.list',
                'controller' => 'App\Http\Controllers\Web\CashierController',
                'action'     => '*',
            ],
            [
                'permission' => 'consultant.manage',
                'controller' => 'App\Http\Controllers\Web\ConsultantController',
                'action'     => '*',
            ],
            [
                'permission' => 'consultant.cancel.reception',
                'controller' => 'App\Http\Controllers\Web\ConsultantController',
                'action'     => 'cancel',
            ],
            [
                'permission' => 'customer.update.qufriend',
                'controller' => 'App\Http\Controllers\Web\CustomerQufriendController',
                'action'     => '*',
            ],
            [
                'permission' => 'customer.group',
                'controller' => 'App\Http\Controllers\Web\CustomerGroupController',
                'action'     => '*',
            ],
            [
                'permission' => 'customer.group',
                'controller' => 'App\Http\Controllers\Web\CustomerRfmController',
                'action'     => '*',
            ],
            [
                'permission' => 'customer.photo.manage',
                'controller' => 'App\Http\Controllers\Web\CustomerPhotoController',
                'action'     => '*',
            ],
            [
                'permission' => 'coupon.detail',
                'controller' => 'App\Http\Controllers\Web\CouponDetailController',
                'action'     => '*',
            ],
            [
                'permission' => 'customer.info',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'info,overview,qufriend,phone',
            ],
            [
                'permission' => 'customer.log',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'log',
            ],
            [
                'permission' => 'customer.sms',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'sms',
            ],
            [
                'permission' => 'customer.talk',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'talk',
            ],
            [
                'permission' => 'customer.goods',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'goods',
            ],
            [
                'permission' => 'customer.erkai',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'erkai',
            ],
            [
                'permission' => 'customer.photo',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'photo',
            ],
            [
                'permission' => 'customer.coupons',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'coupons',
            ],
            [
                'permission' => 'customer.product',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'product',
            ],
            [
                'permission' => 'customer.followup',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'followup',
            ],
            [
                'permission' => 'customer.treatment',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'treatment',
            ],
            [
                'permission' => 'customer.consultant',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'consultant',
            ],
            [
                'permission' => 'customer.reservation',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'reservation',
            ],
            [
                'permission' => 'customer.appointment',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'appointment',
            ],
            [
                'permission' => 'customer.cashier',
                'controller' => 'App\Http\Controllers\Web\CustomerProfileController',
                'action'     => 'cashier',
            ],
            [
                'permission' => 'data.maintenance',
                'controller' => 'App\Http\Controllers\Web\DataMaintenanceController',
                'action'     => '*',
            ],
            [
                'permission' => 'customer.manage',
                'controller' => 'App\Http\Controllers\Web\CustomerController',
                'action'     => 'index,groups',
            ],
            [
                'permission' => 'customer.create',
                'controller' => 'App\Http\Controllers\Web\CustomerController',
                'action'     => 'create',
            ],
            [
                'permission' => 'customer.update',
                'controller' => 'App\Http\Controllers\Web\CustomerController',
                'action'     => 'update',
            ],
            [
                'permission' => 'customer.merge',
                'controller' => 'App\Http\Controllers\Web\CustomerController',
                'action'     => 'merge',
            ],
            [
                'permission' => 'customer.export',
                'controller' => 'App\Http\Controllers\Web\ExportController',
                'action'     => 'customer',
            ],
            [
                'permission' => 'customer.import',
                'controller' => 'App\Http\Controllers\Web\CustomerController',
                'action'     => 'import',
            ],
            [
                'permission' => 'customer.job.manage',
                'controller' => 'App\Http\Controllers\Web\CustomerJobController',
                'action'     => '*',
            ],
            [
                'permission' => 'customer.economic.manage',
                'controller' => 'App\Http\Controllers\Web\CustomerEconomicController',
                'action'     => '*',
            ],
            [
                'permission' => 'customer.photo.manage',
                'controller' => 'App\Http\Controllers\Web\CustomerPhotoDetailController',
                'action'     => '*',
            ],
            [
                'permission' => 'coupon.manage',
                'controller' => 'App\Http\Controllers\Web\CouponController',
                'action'     => '*',
            ],
            [
                'permission' => 'customer.batch.tags',
                'controller' => 'App\Http\Controllers\Web\CustomerBatchController',
                'action'     => 'tags',
            ],
            [
                'permission' => 'customer.batch.doctor',
                'controller' => 'App\Http\Controllers\Web\CustomerBatchController',
                'action'     => 'doctor',
            ],
            [
                'permission' => 'customer.batch.service',
                'controller' => 'App\Http\Controllers\Web\CustomerBatchController',
                'action'     => 'service',
            ],
            [
                'permission' => 'customer.batch.followup',
                'controller' => 'App\Http\Controllers\Web\CustomerBatchController',
                'action'     => 'followup',
            ],
            [
                'permission' => 'customer.batch.ascription',
                'controller' => 'App\Http\Controllers\Web\CustomerBatchController',
                'action'     => 'ascription',
            ],
            [
                'permission' => 'customer.batch.consultant',
                'controller' => 'App\Http\Controllers\Web\CustomerBatchController',
                'action'     => 'consultant',
            ],
            [
                'permission' => 'customer.batch.group.join',
                'controller' => 'App\Http\Controllers\Web\CustomerBatchController',
                'action'     => 'joinGroup',
            ],
            [
                'permission' => 'customer.batch.group.change',
                'controller' => 'App\Http\Controllers\Web\CustomerBatchController',
                'action'     => 'changeGroup',
            ],
            [
                'permission' => 'customer.batch.group.remove',
                'controller' => 'App\Http\Controllers\Web\CustomerBatchController',
                'action'     => 'removeGroup',
            ],
            [
                'permission' => 'customer.batch.sms',
                'controller' => 'App\Http\Controllers\Web\CustomerBatchController',
                'action'     => 'sms',
            ],
            [
                'permission' => 'followup.manage',
                'controller' => 'App\Http\Controllers\Web\FollowupController',
                'action'     => '*',
            ],
            [
                'permission' => 'followup.remove',
                'controller' => 'App\Http\Controllers\Web\FollowupController',
                'action'     => 'remove',
            ],
            [
                'permission' => 'failure.manage',
                'controller' => 'App\Http\Controllers\Web\FailureController',
                'action'     => '*',
            ],
            [
                'permission' => 'department.picking',
                'controller' => 'App\Http\Controllers\Web\DepartmentPickingController',
                'action'     => '*',
            ],
            [
                'permission' => 'department.picking.type',
                'controller' => 'App\Http\Controllers\Web\DepartmentPickingTypeController',
                'action'     => '*',
            ],
            [
                'permission' => 'followup.tool.manage',
                'controller' => 'App\Http\Controllers\Web\FollowupToolController',
                'action'     => '*',
            ],
            [
                'permission' => 'expense.category.manage',
                'controller' => 'App\Http\Controllers\Web\ExpenseCategoryController',
                'action'     => '*',
            ],
            [
                'permission' => 'followup.type.manage',
                'controller' => 'App\Http\Controllers\Web\FollowupTypeController',
                'action'     => '*',
            ],
            [
                'permission' => 'department.manage',
                'controller' => 'App\Http\Controllers\Web\DepartmentController',
                'action'     => '*',
            ],
            [
                'permission' => 'distributor.manage',
                'controller' => 'App\Http\Controllers\Web\DistributorController',
                'action'     => '*',
            ],
            [
                'permission' => 'diagnosis.manage',
                'controller' => 'App\Http\Controllers\Web\DiagnosisCategoryController',
                'action'     => '*',
            ],
            [
                'permission' => 'erkai.manage',
                'controller' => 'App\Http\Controllers\Web\ErkaiController',
                'action'     => '*',
            ],
            [
                'permission' => 'followup.template.manage',
                'controller' => 'App\Http\Controllers\Web\FollowupTemplateController',
                'action'     => '*',
            ],
            [
                'permission' => 'drug.manage',
                'controller' => 'App\Http\Controllers\Web\DrugTypeController',
                'action'     => '*',
            ],
            [
                'permission' => 'drug.manage',
                'controller' => 'App\Http\Controllers\Web\DrugController',
                'action'     => '*',
                'except'     => 'query',
            ],
            [
                'permission' => 'diagnosis.manage',
                'controller' => 'App\Http\Controllers\Web\DiagnosisController',
                'action'     => '*',
                'except'     => 'search',
            ],
            [
                'permission' => 'inventory.check',
                'controller' => 'App\Http\Controllers\Web\InventoryCheckController',
                'action'     => '*',
            ],
            [
                'permission' => 'inventory.transfer',
                'controller' => 'App\Http\Controllers\Web\InventoryTransferController',
                'action'     => '*',
            ],
            [
                'permission' => 'inventory.batchs',
                'controller' => 'App\Http\Controllers\Web\InventoryBatchsController',
                'action'     => '*',
            ],
            [
                'permission' => 'integral.manage',
                'controller' => 'App\Http\Controllers\Web\IntegralController',
                'action'     => '*',
            ],
            [
                'permission' => 'inventory.loss',
                'controller' => 'App\Http\Controllers\Web\InventoryLossController',
                'action'     => '*',
            ],
            [
                'permission' => 'goods.manage',
                'controller' => 'App\Http\Controllers\Web\GoodsController',
                'action'     => '*',
                'except'     => 'query',
            ],
            [
                'permission' => 'item.manage',
                'controller' => 'App\Http\Controllers\Web\ItemController',
                'action'     => '*',
            ],
            [
                'permission' => 'goods.manage',
                'controller' => 'App\Http\Controllers\Web\GoodsTypeController',
                'action'     => '*',
            ],
            [
                'permission' => 'store.manage',
                'controller' => 'App\Http\Controllers\Web\StoreController',
                'action'     => '*',
            ],
            [
                'permission' => 'inventory.manage',
                'controller' => 'App\Http\Controllers\Web\InventoryController',
                'action'     => '*',
            ],
            [
                'permission' => 'inventory.loss',
                'controller' => 'App\Http\Controllers\Web\InventoryOverflowController',
                'action'     => '*',
            ],
            [
                'permission' => 'app.outpatient',
                'controller' => 'App\Http\Controllers\Web\OutpatientController',
                'action'     => '*',
            ],
            [
                'permission' => 'prescription.manage',
                'controller' => 'App\Http\Controllers\Web\PrescriptionController',
                'action'     => '*',
            ],
            [
                'permission' => 'medium.manage',
                'controller' => 'App\Http\Controllers\Web\MediumController',
                'action'     => '*',
            ],
            [
                'permission' => 'manufacturer.manage',
                'controller' => 'App\Http\Controllers\Web\ManufacturerController',
                'action'     => '*',
            ],
            [
                'permission' => 'materials.index',
                'controller' => 'App\Http\Controllers\Web\MaterialController',
                'action'     => '*',
            ],
            [
                'permission' => 'parameter.index',
                'controller' => 'App\Http\Controllers\Web\ParameterController',
                'action'     => '*',
                'except'     => 'info',
            ],
            [
                'permission' => 'prescription.frequency.manage',
                'controller' => 'App\Http\Controllers\Web\PrescriptionFrequencyController',
                'action'     => '*',
            ],
            [
                'permission' => 'pharmacy.manage',
                'controller' => 'App\Http\Controllers\Web\PharmacyController',
                'action'     => '*',
            ],
            [
                'permission' => 'prescription.ways.manage',
                'controller' => 'App\Http\Controllers\Web\PrescriptionWaysController',
                'action'     => '*',
            ],
            [
                'permission' => 'miniapp.user.index',
                'controller' => 'App\Http\Controllers\Web\MiniappController',
                'action'     => 'getUserList',
            ],
            [
                'permission' => 'market.channel.index',
                'controller' => 'App\Http\Controllers\Web\MarketChannelController',
                'action'     => '*',
            ],
            [
                'permission' => 'prescription.unit.manage',
                'controller' => 'App\Http\Controllers\Web\PrescriptionUnitController',
                'action'     => '*',
            ],
            [
                'permission' => 'purchase.type.manage',
                'controller' => 'App\Http\Controllers\Web\PurchaseTypeController',
                'action'     => '*',
            ],
            [
                'permission' => 'reception.create',
                'controller' => 'App\Http\Controllers\Web\ReceptionController',
                'action'     => 'create',
            ],
            [
                'permission' => 'reception.update',
                'controller' => 'App\Http\Controllers\Web\ReceptionController',
                'action'     => 'update',
            ],
            [
                'permission' => 'reception.remove',
                'controller' => 'App\Http\Controllers\Web\ReceptionController',
                'action'     => 'remove',
            ],
            [
                'permission' => 'reception.dispatch.doctor',
                'controller' => 'App\Http\Controllers\Web\ReceptionController',
                'action'     => 'dispatchDoctor',
            ],
            [
                'permission' => 'reception.dispatch.consultant',
                'controller' => 'App\Http\Controllers\Web\ReceptionController',
                'action'     => 'dispatchConsultant',
            ],
            [
                'permission' => 'product.manage',
                'controller' => 'App\Http\Controllers\Web\ProductController',
                'action'     => '*',
                'except'     => 'query,combogrid',
            ],
            [
                'permission' => 'retail.outbound',
                'controller' => 'App\Http\Controllers\Web\RetailOutboundController',
                'action'     => '*',
            ],
            [
                'permission' => 'print.template.manage',
                'controller' => 'App\Http\Controllers\Web\PrintTemplateController',
                'action'     => '*',
            ],
            [
                'permission' => 'reservation.manage',
                'controller' => 'App\Http\Controllers\Web\ReservationController',
                'action'     => 'manage',
            ],
            [
                'permission' => 'reservation.reception',
                'controller' => 'App\Http\Controllers\Web\ReservationController',
                'action'     => 'reception',
            ],
            [
                'permission' => 'reservation.reminder',
                'controller' => 'App\Http\Controllers\Web\ReservationController',
                'action'     => 'reminder',
            ],
            [
                'permission' => 'reservation.create',
                'controller' => 'App\Http\Controllers\Web\ReservationController',
                'action'     => 'create',
            ],
            [
                'permission' => 'reservation.remove',
                'controller' => 'App\Http\Controllers\Web\ReservationController',
                'action'     => 'remove',
            ],
            [
                'permission' => 'product.package.manage',
                'controller' => 'App\Http\Controllers\Web\ProductPackageController',
                'action'     => '*',
                'except'     => 'choose',
            ],
            [
                'permission' => 'reminder.inventory.alarm',
                'controller' => 'App\Http\Controllers\Web\ReminderController',
                'action'     => 'inventoryAlarm',
            ],
            [
                'permission' => 'reminder.inventory.expiry',
                'controller' => 'App\Http\Controllers\Web\ReminderController',
                'action'     => 'inventoryExpiry',
            ],
            [
                'permission' => 'product.manage',
                'controller' => 'App\Http\Controllers\Web\ProductTypeController',
                'action'     => '*',
            ],
            [
                'permission' => 'reception.type.manage',
                'controller' => 'App\Http\Controllers\Web\ReceptionTypeController',
                'action'     => '*',
            ],
            [
                'permission' => 'purchase.return.manage',
                'controller' => 'App\Http\Controllers\Web\PurchaseReturnController',
                'action'     => '*',
            ],
            [
                'permission' => 'purchase.manage',
                'controller' => 'App\Http\Controllers\Web\PurchaseController',
                'action'     => '*',
            ],
            [
                'permission' => 'qufriend.manage',
                'controller' => 'App\Http\Controllers\Web\QufriendController',
                'action'     => 'index',
            ],
            [
                'permission' => 'product.manage',
                'controller' => 'App\Http\Controllers\Web\ProductPackageTypeController',
                'action'     => '*',
                'except'     => 'all',
            ],
            [
                'permission' => 'reservation.type.manage',
                'controller' => 'App\Http\Controllers\Web\ReservationTypeController',
                'action'     => '*',
            ],
            [
                'permission' => 'reservation.remark.manage',
                'controller' => 'App\Http\Controllers\Web\ReservationRemarkTemplateController',
                'action'     => '*',
                'except'     => 'manage',
            ],
            [
                'permission' => 'token.index',
                'controller' => 'App\Http\Controllers\Web\TokenController',
                'action'     => '*',
            ],
            [
                'permission' => 'role.manage',
                'controller' => 'App\Http\Controllers\Web\RoleController',
                'action'     => '*',
            ],
            [
                'permission' => 'room.manage',
                'controller' => 'App\Http\Controllers\Web\RoomController',
                'action'     => '*',
            ],
            [
                'permission' => 'whitelist.index',
                'controller' => 'App\Http\Controllers\Web\WhitelistController',
                'action'     => '*',
            ],
            [
                'permission' => 'unit.manage',
                'controller' => 'App\Http\Controllers\Web\UnitController',
                'action'     => '*',
            ],
            [
                'permission' => 'sms.template.manage',
                'controller' => 'App\Http\Controllers\Web\SmsTemplateController',
                'action'     => '*',
            ],
            [
                'permission' => 'sms.dashboard',
                'controller' => 'App\Http\Controllers\Web\SmsController',
                'action'     => 'dashboard',
            ],
            [
                'permission' => 'sms.index',
                'controller' => 'App\Http\Controllers\Web\SmsController',
                'action'     => 'index',
            ],
            [
                'permission' => 'warehouse.manage',
                'controller' => 'App\Http\Controllers\Web\WarehouseController',
                'action'     => '*',
            ],
            [
                'permission' => 'tags.manage',
                'controller' => 'App\Http\Controllers\Web\TagsController',
                'action'     => '*',
            ],
            [
                'permission' => 'supplier.manage',
                'controller' => 'App\Http\Controllers\Web\SupplierController',
                'action'     => '*',
                'except'     => 'query',
            ],
            [
                'permission' => 'treatment.manage',
                'controller' => 'App\Http\Controllers\Web\TreatmentController',
                'action'     => 'index,create',
            ],
            [
                'permission' => 'treatment.record',
                'controller' => 'App\Http\Controllers\Web\TreatmentController',
                'action'     => 'record',
            ],
            [
                'permission' => 'treatment.undo',
                'controller' => 'App\Http\Controllers\Web\TreatmentController',
                'action'     => 'undo',
            ],
            [
                'permission' => 'user.manage',
                'controller' => 'App\Http\Controllers\Web\UserController',
                'action'     => '*',
            ],
            [
                'permission' => 'user.group.manage',
                'controller' => 'App\Http\Controllers\Web\UserGroupController',
                'action'     => '*',
            ],
            [
                'permission' => 'department.group.manage',
                'controller' => 'App\Http\Controllers\Web\DepartmentGroupController',
                'action'     => '*',
            ],
            [
                'permission' => 'report.performance.sales',
                'controller' => 'App\Http\Controllers\Web\ReportPerformanceController',
                'action'     => 'index',
            ],
            [
                'permission' => 'permission.query.index',
                'controller' => 'App\Http\Controllers\Web\PermissionQueryController',
                'action'     => '*',
            ],
            [
                'permission' => 'system.log.customer',
                'controller' => 'App\Http\Controllers\Web\LogController',
                'action'     => 'customer',
            ],
            [
                'permission' => 'system.log.login',
                'controller' => 'App\Http\Controllers\Web\LogController',
                'action'     => 'login',
            ],
            [
                'permission' => 'system.log.export',
                'controller' => 'App\Http\Controllers\Web\LogController',
                'action'     => 'export',
            ],
        ];
        // 补全数据
        foreach ($permissions as $key => $permission) {
            $permissions[$key]['except']     = $permission['except'] ?? null;
            $permissions[$key]['created_at'] = now();
            $permissions[$key]['updated_at'] = now();
        }
        DB::table('permission_actions')->insert($permissions);
    }
}

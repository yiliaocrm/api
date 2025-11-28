<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Tenant as Tenant;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            Tenant\PositionsSeeder::class,
            Tenant\PermissionActionSeeder::class,
            Tenant\StoreSeeder::class,
            Tenant\WebMenuTableSeeder::class,
            Tenant\RolesTableSeeder::class,
            Tenant\UsersTableSeeder::class,
            Tenant\ParametersTableSeeder::class,
            Tenant\AccountsTableSeeder::class,
            Tenant\ReceptionTypeTableSeeder::class,
            Tenant\TagsTableSeeder::class,
            Tenant\AddressTableSeeder::class,
            Tenant\MediumsTableSeeder::class,
            Tenant\ProductsTableSeeder::class,
            Tenant\ProductTypesTableSeeder::class,
            Tenant\ProductPackageTypesTableSeeder::class,
            Tenant\FollowupRoleTableSeeder::class,
            Tenant\FollowupTemplateTypesTableSeeder::class,
            Tenant\ItemsTableSeeder::class,
            Tenant\FailuresTableSeeder::class,
            Tenant\DepartmentsTableSeeder::class,
            Tenant\ReservationTypesTableSeeder::class,
            Tenant\CustomerJobsTableSeeder::class,
            Tenant\CustomerEconomicsTableSeeder::class,
            Tenant\PrescriptionFrequencysTableSeeder::class,
            Tenant\PrescriptionWaysTableSeeder::class,
            Tenant\PrescriptionUnitsTableSeeder::class,
            Tenant\FollowupTypesTableSeeder::class,
            Tenant\FollowupToolsTableSeeder::class,
            Tenant\GoodsTypesTableSeeder::class,
            Tenant\UnitsTableSeeder::class,
            Tenant\WarehousesTableSeeder::class,
            Tenant\PurchaseTypesTableSeeder::class,
            Tenant\ExpenseCategorysTableSeeder::class,
            Tenant\CustomerLevelsTableSeeder::class,
            Tenant\BedsTableSeeder::class,
            Tenant\RoomsTableSeeder::class,
            Tenant\DiagnosisCategorysTableSeeder::class,
            Tenant\ScheduleRulesTableSeeder::class,
            Tenant\PrintTemplatesTableSeeder::class,
            Tenant\DepartmentPickingTypeSeeder::class,
            Tenant\SceneFieldTableSeeder::class,
            Tenant\CustomerGroupFieldsTableSeeder::class,
            Tenant\CustomerGroupCategoryTableSeeder::class,
            Tenant\QufriendSeeder::class,
            Tenant\CustomerSopCategorySeeder::class,
            Tenant\CustomerPhoneRelationshipSeeder::class,
            Tenant\SmsCategorySeeder::class,
            Tenant\SmsScenarioSeeder::class,
            Tenant\ImportTemplateSeeder::class,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Agreement;
use App\Models\Consumable;
use App\Models\Developer;
use App\Models\Official;
use App\Models\Payment;
use App\Models\Task;
use App\Models\User;
use App\Policies\AgreementPolicy;
use App\Policies\AssetPolicy;
use App\Policies\ConsumablePolicy;
use App\Policies\DeveloperPolicy;
use App\Policies\OfficialPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use PHPUnit\Framework\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    private function makeUserWithRole(UserRole $role): User
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'user@test.local',
            'role' => $role,
        ]);
        $user->role = $role;
        return $user;
    }

    /**
     * Test User model role enum casting and role checks.
     */
    public function test_user_model_casts_and_role_helpers(): void
    {
        $admin = $this->makeUserWithRole(UserRole::ADMIN);
        $this->assertSame(UserRole::ADMIN, $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isViewer());
        $this->assertTrue($admin->canManageUsers());
        $this->assertTrue($admin->canManageInventory());
        $this->assertTrue($admin->canAssignAssets());
        $this->assertTrue($admin->canPostStockEntries());
        $this->assertTrue($admin->canManageAgreements());
        $this->assertTrue($admin->canManagePayments());
        $this->assertTrue($admin->canViewInventory());
        $this->assertTrue($admin->canExportData());
        $this->assertTrue($admin->canViewSensitivePersonnel());
        $this->assertTrue($admin->canViewAuditHistory());

        $manager = $this->makeUserWithRole(UserRole::INVENTORY_MANAGER);
        $this->assertTrue($manager->isInventoryManager());
        $this->assertFalse($manager->isAdmin());
        $this->assertFalse($manager->canManageUsers());
        $this->assertTrue($manager->canManageInventory());
        $this->assertTrue($manager->canAssignAssets());
        $this->assertTrue($manager->canPostStockEntries());
        $this->assertTrue($manager->canManageAgreements());
        $this->assertFalse($manager->canManagePayments());
        $this->assertTrue($manager->canViewInventory());
        $this->assertTrue($manager->canExportData());

        $stockOp = $this->makeUserWithRole(UserRole::STOCK_OPERATOR);
        $this->assertTrue($stockOp->isStockOperator());
        $this->assertFalse($stockOp->canManageInventory());
        $this->assertFalse($stockOp->canAssignAssets());
        $this->assertTrue($stockOp->canPostStockEntries());
        $this->assertFalse($stockOp->canManageAgreements());
        $this->assertFalse($stockOp->canManagePayments());
        $this->assertTrue($stockOp->canViewInventory());
        $this->assertTrue($stockOp->canExportData());

        $finOp = $this->makeUserWithRole(UserRole::FINANCE_OPERATOR);
        $this->assertTrue($finOp->isFinanceOperator());
        $this->assertFalse($finOp->canManageInventory());
        $this->assertFalse($finOp->canPostStockEntries());
        $this->assertTrue($finOp->canManageAgreements());
        $this->assertTrue($finOp->canManagePayments());
        $this->assertTrue($finOp->canViewInventory());
        $this->assertTrue($finOp->canExportData());

        $viewer = $this->makeUserWithRole(UserRole::VIEWER);
        $this->assertTrue($viewer->isViewer());
        $this->assertFalse($viewer->canManageUsers());
        $this->assertFalse($viewer->canManageInventory());
        $this->assertFalse($viewer->canAssignAssets());
        $this->assertFalse($viewer->canPostStockEntries());
        $this->assertFalse($viewer->canManageAgreements());
        $this->assertFalse($viewer->canManagePayments());
        $this->assertTrue($viewer->canViewInventory());
        $this->assertFalse($viewer->canExportData());
        $this->assertFalse($viewer->canViewSensitivePersonnel());

        $auditor = $this->makeUserWithRole(UserRole::AUDITOR);
        $this->assertTrue($auditor->isAuditor());
        $this->assertFalse($auditor->canManageInventory());
        $this->assertFalse($auditor->canAssignAssets());
        $this->assertFalse($auditor->canPostStockEntries());
        $this->assertFalse($auditor->canManageAgreements());
        $this->assertFalse($auditor->canManagePayments());
        $this->assertTrue($auditor->canViewInventory());
        $this->assertTrue($auditor->canExportData());
        $this->assertTrue($auditor->canViewSensitivePersonnel());
        $this->assertTrue($auditor->canViewAuditHistory());
    }

    /**
     * Test User ActivityLog options config.
     */
    public function test_user_activitylog_options_configured(): void
    {
        $user = new User();
        $options = $user->getActivitylogOptions();

        $this->assertInstanceOf(\Spatie\Activitylog\LogOptions::class, $options);
    }

    /**
     * Test AssetPolicy authorization across all 6 roles.
     */
    public function test_asset_policy_enforces_matrix_permissions(): void
    {
        $policy = new AssetPolicy();

        $admin = $this->makeUserWithRole(UserRole::ADMIN);
        $manager = $this->makeUserWithRole(UserRole::INVENTORY_MANAGER);
        $stockOp = $this->makeUserWithRole(UserRole::STOCK_OPERATOR);
        $finOp = $this->makeUserWithRole(UserRole::FINANCE_OPERATOR);
        $viewer = $this->makeUserWithRole(UserRole::VIEWER);
        $auditor = $this->makeUserWithRole(UserRole::AUDITOR);

        // View: all roles can view
        foreach ([$admin, $manager, $stockOp, $finOp, $viewer, $auditor] as $user) {
            $this->assertTrue($policy->viewAny($user));
            $this->assertTrue($policy->view($user));
        }

        // Create / Update / Delete / Assign / Return / Decommission: Admin & Inventory Manager only
        foreach ([$admin, $manager] as $authorizedUser) {
            $this->assertTrue($policy->create($authorizedUser));
            $this->assertTrue($policy->update($authorizedUser));
            $this->assertTrue($policy->delete($authorizedUser));
            $this->assertTrue($policy->assign($authorizedUser));
            $this->assertTrue($policy->return($authorizedUser));
            $this->assertTrue($policy->decommission($authorizedUser));
            $this->assertTrue($policy->export($authorizedUser));
        }

        foreach ([$stockOp, $finOp, $viewer] as $unauthorizedUser) {
            $this->assertFalse($policy->create($unauthorizedUser));
            $this->assertFalse($policy->update($unauthorizedUser));
            $this->assertFalse($policy->delete($unauthorizedUser));
            $this->assertFalse($policy->assign($unauthorizedUser));
            $this->assertFalse($policy->return($unauthorizedUser));
            $this->assertFalse($policy->decommission($unauthorizedUser));
            $this->assertFalse($policy->export($unauthorizedUser));
        }

        // Auditor: can export audit data, but cannot create/modify/assign
        $this->assertTrue($policy->export($auditor));
        $this->assertFalse($policy->create($auditor));
        $this->assertFalse($policy->update($auditor));
        $this->assertFalse($policy->assign($auditor));
        $this->assertFalse($policy->decommission($auditor));
    }

    /**
     * Test ConsumablePolicy authorization.
     */
    public function test_consumable_policy_enforces_matrix_permissions(): void
    {
        $policy = new ConsumablePolicy();

        $admin = $this->makeUserWithRole(UserRole::ADMIN);
        $manager = $this->makeUserWithRole(UserRole::INVENTORY_MANAGER);
        $stockOp = $this->makeUserWithRole(UserRole::STOCK_OPERATOR);
        $finOp = $this->makeUserWithRole(UserRole::FINANCE_OPERATOR);
        $viewer = $this->makeUserWithRole(UserRole::VIEWER);
        $auditor = $this->makeUserWithRole(UserRole::AUDITOR);

        // View: all roles can view
        foreach ([$admin, $manager, $stockOp, $finOp, $viewer, $auditor] as $user) {
            $this->assertTrue($policy->viewAny($user));
            $this->assertTrue($policy->view($user));
        }

        // Create / Update / Delete: Admin and Inventory Manager only
        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->create($manager));
        $this->assertFalse($policy->create($stockOp));
        $this->assertFalse($policy->create($finOp));
        $this->assertFalse($policy->create($viewer));
        $this->assertFalse($policy->create($auditor));

        // Post Entry / Purchase / Issue: Admin, Inventory Manager, and Stock Operator
        $this->assertTrue($policy->postEntry($admin));
        $this->assertTrue($policy->postEntry($manager));
        $this->assertTrue($policy->postEntry($stockOp));
        $this->assertFalse($policy->postEntry($finOp));
        $this->assertFalse($policy->postEntry($viewer));
        $this->assertFalse($policy->postEntry($auditor));

        $this->assertTrue($policy->purchase($stockOp));
        $this->assertTrue($policy->issue($stockOp));
        $this->assertFalse($policy->purchase($finOp));
        $this->assertFalse($policy->issue($viewer));
    }

    /**
     * Test AgreementPolicy authorization.
     */
    public function test_agreement_policy_enforces_matrix_permissions(): void
    {
        $policy = new AgreementPolicy();

        $admin = $this->makeUserWithRole(UserRole::ADMIN);
        $manager = $this->makeUserWithRole(UserRole::INVENTORY_MANAGER);
        $finOp = $this->makeUserWithRole(UserRole::FINANCE_OPERATOR);
        $stockOp = $this->makeUserWithRole(UserRole::STOCK_OPERATOR);
        $viewer = $this->makeUserWithRole(UserRole::VIEWER);
        $auditor = $this->makeUserWithRole(UserRole::AUDITOR);

        // View: all roles
        foreach ([$admin, $manager, $finOp, $stockOp, $viewer, $auditor] as $user) {
            $this->assertTrue($policy->viewAny($user));
            $this->assertTrue($policy->view($user));
        }

        // Create / Update / Delete: Admin, Inventory Manager, Finance Operator
        foreach ([$admin, $manager, $finOp] as $authorizedUser) {
            $this->assertTrue($policy->create($authorizedUser));
            $this->assertTrue($policy->update($authorizedUser));
            $this->assertTrue($policy->delete($authorizedUser));
            $this->assertTrue($policy->export($authorizedUser));
        }

        foreach ([$stockOp, $viewer] as $unauthorizedUser) {
            $this->assertFalse($policy->create($unauthorizedUser));
            $this->assertFalse($policy->update($unauthorizedUser));
            $this->assertFalse($policy->delete($unauthorizedUser));
            $this->assertFalse($policy->export($unauthorizedUser));
        }

        // Auditor can export
        $this->assertTrue($policy->export($auditor));
        $this->assertFalse($policy->create($auditor));
    }

    /**
     * Test PaymentPolicy authorization.
     */
    public function test_payment_policy_enforces_matrix_permissions(): void
    {
        $policy = new PaymentPolicy();

        $admin = $this->makeUserWithRole(UserRole::ADMIN);
        $finOp = $this->makeUserWithRole(UserRole::FINANCE_OPERATOR);
        $manager = $this->makeUserWithRole(UserRole::INVENTORY_MANAGER);
        $stockOp = $this->makeUserWithRole(UserRole::STOCK_OPERATOR);
        $viewer = $this->makeUserWithRole(UserRole::VIEWER);
        $auditor = $this->makeUserWithRole(UserRole::AUDITOR);

        // View: all roles
        foreach ([$admin, $finOp, $manager, $stockOp, $viewer, $auditor] as $user) {
            $this->assertTrue($policy->viewAny($user));
            $this->assertTrue($policy->view($user));
        }

        // Complete / Cancel payments: Admin & Finance Operator only
        $this->assertTrue($policy->complete($admin));
        $this->assertTrue($policy->cancel($admin));
        $this->assertTrue($policy->complete($finOp));
        $this->assertTrue($policy->cancel($finOp));

        $this->assertFalse($policy->complete($manager));
        $this->assertFalse($policy->cancel($manager));
        $this->assertFalse($policy->complete($stockOp));
        $this->assertFalse($policy->complete($viewer));
        $this->assertFalse($policy->complete($auditor));
    }

    /**
     * Test OfficialPolicy and DeveloperPolicy sensitive data viewing.
     */
    public function test_official_and_developer_policy_sensitive_access(): void
    {
        $officialPolicy = new OfficialPolicy();
        $developerPolicy = new DeveloperPolicy();

        $admin = $this->makeUserWithRole(UserRole::ADMIN);
        $auditor = $this->makeUserWithRole(UserRole::AUDITOR);
        $manager = $this->makeUserWithRole(UserRole::INVENTORY_MANAGER);
        $stockOp = $this->makeUserWithRole(UserRole::STOCK_OPERATOR);
        $finOp = $this->makeUserWithRole(UserRole::FINANCE_OPERATOR);
        $viewer = $this->makeUserWithRole(UserRole::VIEWER);

        // Sensitive access: Admin and Auditor only
        $this->assertTrue($officialPolicy->viewSensitive($admin));
        $this->assertTrue($officialPolicy->viewSensitive($auditor));
        $this->assertFalse($officialPolicy->viewSensitive($manager));
        $this->assertFalse($officialPolicy->viewSensitive($stockOp));
        $this->assertFalse($officialPolicy->viewSensitive($finOp));
        $this->assertFalse($officialPolicy->viewSensitive($viewer));

        $this->assertTrue($developerPolicy->viewSensitive($admin));
        $this->assertTrue($developerPolicy->viewSensitive($auditor));
        $this->assertFalse($developerPolicy->viewSensitive($manager));
        $this->assertFalse($developerPolicy->viewSensitive($stockOp));
        $this->assertFalse($developerPolicy->viewSensitive($finOp));
        $this->assertFalse($developerPolicy->viewSensitive($viewer));
    }

    /**
     * Test TaskPolicy permissions.
     */
    public function test_task_policy_enforces_matrix_permissions(): void
    {
        $policy = new TaskPolicy();

        $admin = $this->makeUserWithRole(UserRole::ADMIN);
        $manager = $this->makeUserWithRole(UserRole::INVENTORY_MANAGER);
        $stockOp = $this->makeUserWithRole(UserRole::STOCK_OPERATOR);
        $finOp = $this->makeUserWithRole(UserRole::FINANCE_OPERATOR);
        $viewer = $this->makeUserWithRole(UserRole::VIEWER);
        $auditor = $this->makeUserWithRole(UserRole::AUDITOR);

        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->create($manager));
        $this->assertTrue($policy->create($stockOp));
        $this->assertFalse($policy->create($finOp));
        $this->assertFalse($policy->create($viewer));
        $this->assertFalse($policy->create($auditor));
    }

    /**
     * Test UserPolicy admin-only user management.
     */
    public function test_user_policy_admin_only_management(): void
    {
        $policy = new UserPolicy();

        $admin = $this->makeUserWithRole(UserRole::ADMIN);
        $manager = $this->makeUserWithRole(UserRole::INVENTORY_MANAGER);
        $auditor = $this->makeUserWithRole(UserRole::AUDITOR);

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->manageRoles($admin));

        $this->assertFalse($policy->viewAny($manager));
        $this->assertFalse($policy->create($manager));
        $this->assertFalse($policy->manageRoles($manager));

        $this->assertFalse($policy->viewAny($auditor));
        $this->assertFalse($policy->create($auditor));
        $this->assertFalse($policy->manageRoles($auditor));
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_dashboard',
            'manage_accounts',
            'manage_contacts',
            'manage_products',
            'manage_wallets',
            'manage_tax_rules',
            'manage_periods',
            'manage_invoices',
            'manage_payments',
            'manage_purchases',
            'manage_supplier_payments',
            'manage_expenses',
            'manage_journals',
            'view_reports',
            'manage_settings',
            'manage_users',
            'manage_opening_balance',
            'close_period',
            'reopen_period',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        $accountant->givePermissionTo([
            'view_dashboard',
            'manage_accounts',
            'manage_contacts',
            'manage_products',
            'manage_wallets',
            'manage_tax_rules',
            'manage_periods',
            'manage_invoices',
            'manage_payments',
            'manage_purchases',
            'manage_supplier_payments',
            'manage_expenses',
            'manage_journals',
            'view_reports',
            'manage_opening_balance',
            'close_period',
            'reopen_period',
        ]);

        $cashier = Role::firstOrCreate(['name' => 'cashier']);
        $cashier->givePermissionTo([
            'view_dashboard',
            'manage_contacts',
            'manage_invoices',
            'manage_payments',
            'manage_expenses',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->givePermissionTo([
            'view_dashboard',
            'view_reports',
        ]);

        $admin = User::where('email', 'admin@example.com')->first();
        if ($admin) {
            $admin->assignRole($superAdmin);
        }
    }
}

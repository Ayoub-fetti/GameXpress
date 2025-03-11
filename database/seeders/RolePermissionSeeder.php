<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Liste des permissions
        $permissions = [
            'view_dashboard',
            'view_products', 'create_products', 'edit_products', 'delete_products',
            'view_categories', 'create_categories', 'edit_categories', 'delete_categories',
            'view_users', 'create_users', 'edit_users', 'delete_users',
        ];

        // Création des permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Création des rôles et assignation des permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $productManager = Role::firstOrCreate(['name' => 'product_manager']);
        $productManager->givePermissionTo([
            'view_products', 'create_products', 'edit_products', 'delete_products',
            'view_categories', 'create_categories', 'edit_categories', 'delete_categories',
        ]);

        $userManager = Role::firstOrCreate(['name' => 'user_manager']);
        $userManager->givePermissionTo([
            'view_users', 'create_users', 'edit_users', 'delete_users',
        ]);
    }
}
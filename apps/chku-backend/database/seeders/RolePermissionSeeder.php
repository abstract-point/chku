<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'club_members.create',
            'club_members.deactivate',
            'meetings.create',
            'meetings.update',
            'audit_logs.view',
            'developer_tools.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $memberRole = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $developerRole = Role::firstOrCreate(['name' => 'developer', 'guard_name' => 'web']);

        $adminRole->givePermissionTo([
            'club_members.create',
            'club_members.deactivate',
            'meetings.create',
            'meetings.update',
        ]);

        $developerRole->givePermissionTo([
            'club_members.create',
            'club_members.deactivate',
            'meetings.create',
            'meetings.update',
            'audit_logs.view',
            'developer_tools.view',
        ]);
    }
}

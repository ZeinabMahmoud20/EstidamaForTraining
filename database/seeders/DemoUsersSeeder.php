<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the main branch + the admin account (always) and one demo user per staff role (local only).
 * Password comes from SEED_PASSWORD in .env (default: "password") - CHANGE IT before going live.
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'استدامة للتدريب والتطوير - الفرع الرئيسي', 'is_active' => true]
        );

        $password = env('SEED_PASSWORD', 'password');

        $users = [
            ['admin', 'مدير النظام', 'super_admin'],
        ];

        if (app()->environment('local')) {
            $users = array_merge($users, [
                ['manager', 'مدير المركز', 'center_manager'],
                ['coordinator', 'منسق التدريب', 'training_coordinator'],
                ['admissions', 'مسؤول القبول', 'admissions_officer'],
                ['halls', 'مسؤول القاعات', 'hall_manager'],
                ['finance', 'المسؤول المالي', 'finance_officer'],
                ['quality', 'مسؤول الجودة', 'quality_officer'],
            ]);
        }

        foreach ($users as [$username, $name, $role]) {
            $user = User::firstOrCreate(
                ['username' => $username],
                ['full_name' => $name, 'password' => $password, 'branch_id' => $branch->id, 'is_active' => true]
            );
            $user->syncRoles([$role]);
        }
    }
}

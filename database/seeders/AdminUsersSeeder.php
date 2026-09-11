<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class AdminUsersSeeder extends Seeder
{
    private const PASSWORD = 'Admin@123456';

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admins = [
            ['name' => 'علی رادمنش', 'mobile' => '09120000001', 'email' => 'superadmin@mestroam.ir', 'role' => 'super_admin'],
            ['name' => 'سارا رضایی', 'mobile' => '09120000002', 'email' => 'admin@mestroam.ir', 'role' => 'admin'],
            ['name' => 'محمد حسینی', 'mobile' => '09120000003', 'email' => 'finance@mestroam.ir', 'role' => 'financial_manager'],
            ['name' => 'مهسا مرادی', 'mobile' => '09120000004', 'email' => 'operator@mestroam.ir', 'role' => 'operator'],
            ['name' => 'رضا کمالی', 'mobile' => '09120000005', 'email' => 'auditor@mestroam.ir', 'role' => 'auditor'],
        ];

        foreach ($admins as $adminData) {
            $role = Role::query()
                ->where('name', $adminData['role'])
                ->where('guard_name', 'web')
                ->firstOrFail();
            $user = User::updateOrCreate(
                ['mobile' => $adminData['mobile']],
                [
                    'name' => $adminData['name'],
                    'email' => $adminData['email'],
                    'password' => Hash::make(self::PASSWORD),
                    'is_verified' => true,
                ],
            );
            $user->syncRoles([$role]);

            Log::info('Admin test user synchronized', [
                'function' => __METHOD__, 'user_id' => $user->id,
                'payload' => ['mobile' => $user->mobile, 'role' => $role->name],
                'trace' => null,
            ]);
        }
    }
}
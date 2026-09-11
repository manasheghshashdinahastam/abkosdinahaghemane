<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Otp;
use App\Models\Advertisement;
use App\Models\UserVerification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\AdminUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_have_buyer_and_seller_roles_at_the_same_time(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['buyer', 'seller']);

        $this->assertTrue($user->hasRole('buyer'));
        $this->assertTrue($user->hasRole('seller'));
        $this->assertTrue($user->hasAnyRole(['buyer', 'operator']));
    }

    public function test_regular_user_receives_forbidden_on_admin_route(): void
    {
        $user = User::factory()->create();
        $user->assignRole('buyer');

        $this->actingAs($user, 'sanctum')->getJson('/api/admin/ads')->assertForbidden();
    }

    public function test_operator_cannot_access_finance_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        $this->actingAs($user, 'sanctum')->getJson('/api/admin/finance')->assertForbidden();
    }

    public function test_super_admin_can_access_all_admin_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $request = fn (string $uri) => $this->actingAs($user, 'sanctum')->getJson($uri)->assertSuccessful();

        foreach (['/api/admin/me', '/api/admin/dashboard', '/api/admin/ads', '/api/admin/finance', '/api/admin/users', '/api/admin/verifications'] as $uri) {
            $request($uri);
        }
    }

    public function test_admin_can_login_with_mobile_or_email_password(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, AdminUsersSeeder::class]);

        $this->postJson('/api/admin/login', [
            'identifier' => '09120000001',
            'password' => 'Admin@123456',
        ])->assertOk()->assertJsonPath('user.email', 'superadmin@mestroam.ir');

        $this->postJson('/api/admin/login', [
            'identifier' => 'auditor@mestroam.ir',
            'password' => 'Admin@123456',
        ])->assertOk()->assertJsonPath('user.mobile', '09120000005');
    }

    public function test_operator_dashboard_returns_live_queue_counts(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        Advertisement::factory()->create(['status' => Advertisement::STATUS_PENDING_APPROVAL]);
        UserVerification::create([
            'user_id' => User::factory()->create()->id,
            'home_phone' => '09121111111', 'postal_address' => 'آدرس تست',
            'residence_document_path' => 'test/residence.jpg', 'national_code' => '0012345678',
            'national_card_serial' => 'SERIAL', 'national_card_front_path' => 'test/front.jpg',
            'national_card_back_path' => 'test/back.jpg', 'birth_certificate_p1_path' => 'test/birth1.jpg',
            'birth_certificate_p2_path' => 'test/birth2.jpg', 'job_document_path' => 'test/job.jpg',
            'iban' => 'IR000000000000000000000000', 'status' => UserVerification::STATUS_PENDING,
        ]);

        $this->actingAs($operator, 'sanctum')
            ->getJson('/api/admin/dashboard/operator-stats')
            ->assertOk()
            ->assertJsonPath('pending_ads_count', 1)
            ->assertJsonPath('pending_kyc_count', 1)
            ->assertJsonStructure(['today_approved_ads', 'today_rejected_ads', 'recent_pending_ads']);
    }

    public function test_operator_can_approve_and_reject_pending_ads_with_reason(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $approved = Advertisement::factory()->create(['status' => Advertisement::STATUS_PENDING_APPROVAL]);
        $rejected = Advertisement::factory()->create(['status' => Advertisement::STATUS_PENDING_APPROVAL]);

        $this->actingAs($operator, 'sanctum')->postJson("/api/admin/ads/{$approved->id}/approve")->assertOk();
        $this->actingAs($operator, 'sanctum')->postJson("/api/admin/ads/{$rejected->id}/reject", ['rejection_reason' => 'مدرک کافی نیست'])->assertOk();

        $this->assertDatabaseHas('advertisements', ['id' => $approved->id, 'status' => Advertisement::STATUS_PUBLISHED]);
        $this->assertDatabaseHas('advertisements', ['id' => $rejected->id, 'status' => Advertisement::STATUS_REJECTED, 'rejection_reason' => 'مدرک کافی نیست']);
    }

    public function test_admin_can_search_and_toggle_user_block_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $target = User::factory()->create(['name' => 'کاربر صف', 'is_banned' => false]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/admin/users?search=کاربر صف')->assertOk()->assertJsonPath('data.0.id', $target->id);
        $this->actingAs($admin, 'sanctum')->patchJson("/api/admin/users/{$target->id}/status", ['is_banned' => true])->assertOk()->assertJsonPath('data.is_banned', true);
    }

    public function test_first_otp_registration_assigns_buyer_and_seller_roles(): void
    {
        Otp::create(['mobile' => '09123456789', 'code' => '12345', 'expires_at' => now()->addMinutes(2)]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'mobile' => '09123456789', 'code' => '12345',
        ])->assertJson(['is_new_user' => true]);
        $user = User::where('mobile', '09123456789')->firstOrFail();

        $response->assertOk()->assertJsonPath('user.roles.0.name', 'buyer');
        $this->assertTrue($user->hasRole('buyer'));
        $this->assertTrue($user->hasRole('seller'));
    }
}
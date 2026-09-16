<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_receives_persian_unauthorized_message(): void
    {
        $this->getJson('/api/user/profile')
            ->assertUnauthorized()
            ->assertJsonPath('message', trans('errors.unauthorized'));
    }

    public function test_insufficient_permission_receives_persian_forbidden_message(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator, 'sanctum')
            ->getJson('/api/admin/finance')
            ->assertForbidden()
            ->assertJsonPath('message', trans('errors.forbidden'));
    }

    public function test_missing_api_resource_receives_persian_not_found_message(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator, 'sanctum')
            ->getJson('/api/admin/ads/999999')
            ->assertNotFound()
            ->assertJsonPath('message', trans('errors.not_found'));
    }
}
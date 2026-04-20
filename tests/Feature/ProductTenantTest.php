<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_creates_a_tenant_link(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->tenant_id);
        $this->assertDatabaseHas('tenants', [
            'id' => $user->tenant_id,
        ]);
    }

    public function test_authenticated_user_creates_products_in_their_tenant(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/products', [
            'name' => 'Gaming Mouse',
            'sku' => 'MOUSE-001',
            'price' => 49.99,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('tenant_id', $user->tenant_id);

        $this->assertDatabaseHas('products', [
            'tenant_id' => $user->tenant_id,
            'name' => 'Gaming Mouse',
            'sku' => 'MOUSE-001',
        ]);
    }

    public function test_same_sku_can_exist_in_different_tenants(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        Sanctum::actingAs($firstUser);
        $this->postJson('/api/products', [
            'name' => 'Keyboard',
            'sku' => 'SHARED-SKU',
            'price' => 79.99,
        ])->assertCreated();

        Sanctum::actingAs($secondUser);
        $this->postJson('/api/products', [
            'name' => 'Keyboard Pro',
            'sku' => 'SHARED-SKU',
            'price' => 89.99,
        ])->assertCreated();

        $this->assertSame(
            2,
            Product::query()->where('sku', 'SHARED-SKU')->count()
        );
    }

    public function test_tenant_middleware_rejects_a_mismatched_tenant_header(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        DB::table('projects')->insert([
            'name' => 'Tenant Project',
            'tenant_id' => $user->tenant_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/projects', [
            'X-Tenant-Id' => (string) $otherUser->tenant_id,
        ]);

        $response
            ->assertForbidden()
            ->assertExactJson([
                'message' => 'You are not allowed to access this tenant.',
            ]);
    }

    public function test_tenant_middleware_defaults_to_the_authenticated_users_tenant(): void
    {
        $user = User::factory()->create();

        DB::table('projects')->insert([
            'name' => 'Scoped Project',
            'tenant_id' => $user->tenant_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/projects');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'tenant_id' => $user->tenant_id,
            ]);
    }
}

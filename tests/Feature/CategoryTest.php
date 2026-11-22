<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/categories', [
            'name' => 'Beverages',
            'description' => 'Drinks',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Beverages');

        $this->assertDatabaseHas('categories', ['name' => 'Beverages']);
    }

    public function test_cashier_cannot_create_category(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)
            ->postJson('/api/categories', ['name' => 'Beverages'])
            ->assertStatus(403);
    }

    public function test_manager_can_update_category(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $category = Category::create(['name' => 'Snacks']);

        $this->actingAs($manager)
            ->putJson("/api/categories/{$category->id}", ['name' => 'Snacks & More'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Snacks & More');
    }

    public function test_cashier_can_view_categories(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        Category::create(['name' => 'Beverages']);

        $this->actingAs($cashier)
            ->getJson('/api/categories')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_delete_empty_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Temporary']);

        $this->actingAs($admin)
            ->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Food']);
        $category->products()->create([
            'name' => 'Rice',
            'sku' => 'SKU-001',
            'selling_price' => 10,
            'stock_quantity' => 5,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(422);
    }
}

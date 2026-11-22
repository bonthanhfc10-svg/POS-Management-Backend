<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Beverages']);
    }

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/products', [
            'category_id' => $this->category->id,
            'name' => 'Cola 500ml',
            'sku' => 'SKU-COLA-500',
            'barcode' => '123456789',
            'cost_price' => 0.80,
            'selling_price' => 1.50,
            'stock_quantity' => 20,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.sku', 'SKU-COLA-500');

        $this->assertDatabaseHas('products', ['sku' => 'SKU-COLA-500']);
    }

    public function test_duplicate_sku_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Product::create([
            'category_id' => $this->category->id,
            'name' => 'Cola',
            'sku' => 'SKU-DUP',
            'selling_price' => 1.50,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/products', [
                'category_id' => $this->category->id,
                'name' => 'Cola 2',
                'sku' => 'SKU-DUP',
                'selling_price' => 2.00,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_cashier_cannot_create_product(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)
            ->postJson('/api/products', [
                'category_id' => $this->category->id,
                'name' => 'Cola',
                'sku' => 'SKU-X',
                'selling_price' => 1.50,
            ])
            ->assertStatus(403);
    }

    public function test_cashier_can_view_products(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        Product::create([
            'category_id' => $this->category->id,
            'name' => 'Cola',
            'sku' => 'SKU-VIEW',
            'selling_price' => 1.50,
        ]);

        $this->actingAs($cashier)
            ->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_product_search_filters_by_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Product::create([
            'category_id' => $this->category->id,
            'name' => 'Apple Juice',
            'sku' => 'SKU-APPLE',
            'selling_price' => 2.00,
        ]);
        Product::create([
            'category_id' => $this->category->id,
            'name' => 'Orange Juice',
            'sku' => 'SKU-ORANGE',
            'selling_price' => 2.00,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/products?search=Apple')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals('Apple Juice', $response->json('data.data.0.name'));
    }

    public function test_manager_can_update_product(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Cola',
            'sku' => 'SKU-UPD',
            'selling_price' => 1.50,
        ]);

        $this->actingAs($manager)
            ->putJson("/api/products/{$product->id}", [
                'category_id' => $this->category->id,
                'name' => 'Cola Large',
                'sku' => 'SKU-UPD',
                'cost_price' => 0.80,
                'selling_price' => 1.80,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Cola Large');
    }
}

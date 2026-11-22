<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::create(['name' => 'Beverages']);
        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Cola 500ml',
            'sku' => 'SKU-COLA-500',
            'cost_price' => 0.80,
            'selling_price' => 1.50,
            'stock_quantity' => 10,
        ]);
    }

    private function cashier(): User
    {
        return User::factory()->create(['role' => 'cashier']);
    }

    private function manager(): User
    {
        return User::factory()->create(['role' => 'manager']);
    }

    public function test_cashier_can_checkout_with_sufficient_stock(): void
    {
        $cashier = $this->cashier();

        $response = $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 3],
            ],
            'payment_method' => 'cash',
            'tendered' => 10.00,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['receipt_number', 'total', 'items', 'payment']]);

        $this->product->refresh();
        $this->assertEquals(7, $this->product->stock_quantity);

        $sale = Sale::first();
        $this->assertEquals(4.50, (float) $sale->total);
        $this->assertEquals('completed', $sale->status);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'sale',
            'quantity' => -3,
        ]);
    }

    public function test_checkout_fails_with_insufficient_stock(): void
    {
        $cashier = $this->cashier();

        $response = $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 99],
            ],
            'payment_method' => 'cash',
            'tendered' => 100.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->product->refresh();
        $this->assertEquals(10, $this->product->stock_quantity);
        $this->assertEquals(0, Sale::count());
    }

    public function test_checkout_rejects_insufficient_cash(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 5],
            ],
            'payment_method' => 'cash',
            'tendered' => 1.00,
        ])->assertStatus(422);

        $this->product->refresh();
        $this->assertEquals(10, $this->product->stock_quantity);
    }

    public function test_manager_can_void_sale_and_stock_is_restored(): void
    {
        $cashier = $this->cashier();
        $manager = $this->manager();

        $saleResponse = $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 4],
            ],
            'payment_method' => 'card',
        ]);

        $saleResponse->assertStatus(201);
        $saleId = $saleResponse->json('data.id');
        $this->assertEquals(6, $this->product->fresh()->stock_quantity);

        $this->actingAs($manager)
            ->postJson("/api/sales/{$saleId}/void", ['reason' => 'Customer requested cancellation'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'voided')
            ->assertJsonPath('data.void_reason', 'Customer requested cancellation');

        $this->assertEquals(10, $this->product->fresh()->stock_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'void',
            'quantity' => 4,
        ]);
    }

    public function test_cashier_cannot_void_sale(): void
    {
        $cashier = $this->cashier();

        $saleResponse = $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
            'payment_method' => 'cash',
            'tendered' => 2.00,
        ]);

        $saleId = $saleResponse->json('data.id');

        $this->actingAs($cashier)
            ->postJson("/api/sales/{$saleId}/void", ['reason' => 'Not authorized'])
            ->assertStatus(403);
    }

    public function test_cashier_only_sees_own_sales(): void
    {
        $cashier1 = $this->cashier();
        $cashier2 = $this->cashier();

        $this->actingAs($cashier1)->postJson('/api/sales', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'tendered' => 2.00,
        ])->assertStatus(201);

        $this->actingAs($cashier2)->postJson('/api/sales', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'tendered' => 2.00,
        ])->assertStatus(201);

        $response = $this->actingAs($cashier1)->getJson('/api/sales');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_sale_items_preserve_product_history(): void
    {
        $cashier = $this->cashier();

        $response = $this->actingAs($cashier)->postJson('/api/sales', [
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
            'payment_method' => 'cash',
            'tendered' => 5.00,
        ]);

        $itemId = $response->json('data.items.0.id');
        $this->assertDatabaseHas('sale_items', [
            'id' => $itemId,
            'product_name' => 'Cola 500ml',
            'product_sku' => 'SKU-COLA-500',
            'unit_price' => 1.50,
            'quantity' => 2,
        ]);
    }
}

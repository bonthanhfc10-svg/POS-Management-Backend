<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name', 200);
            $table->string('product_sku', 50);
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('unit_cost', 14, 4);
            $table->integer('warranty_months')->default(0);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales');
            $table->foreign('product_id')->references('id')->on('products');
            $table->unique(['sale_id', 'product_id']);

            $table->index('product_id', 'sale_items_product_idx');
        });

        DB::statement('ALTER TABLE sale_items ADD CONSTRAINT sale_items_quantity_ck CHECK (quantity > 0)');
        DB::statement('ALTER TABLE sale_items ADD CONSTRAINT sale_items_unit_price_ck CHECK (unit_price >= 0)');
        DB::statement('ALTER TABLE sale_items ADD CONSTRAINT sale_items_unit_cost_ck CHECK (unit_cost >= 0)');
        DB::statement('ALTER TABLE sale_items ADD CONSTRAINT sale_items_warranty_months_ck CHECK (warranty_months >= 0)');
        DB::statement('ALTER TABLE sale_items ADD CONSTRAINT sale_items_line_total_ck CHECK (line_total >= 0)');
        DB::statement('ALTER TABLE sale_items ADD CONSTRAINT sale_items_line_total_calc_ck CHECK (line_total = quantity * unit_price)');
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};

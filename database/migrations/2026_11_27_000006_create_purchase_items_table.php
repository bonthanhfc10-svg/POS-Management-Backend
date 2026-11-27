<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name', 200);
            $table->string('product_sku', 50);
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('purchases');
            $table->foreign('product_id')->references('id')->on('products');
            $table->unique(['purchase_id', 'product_id']);

            $table->index('product_id', 'purchase_items_product_idx');
        });

        DB::statement('ALTER TABLE purchase_items ADD CONSTRAINT purchase_items_quantity_ck CHECK (quantity > 0)');
        DB::statement('ALTER TABLE purchase_items ADD CONSTRAINT purchase_items_unit_cost_ck CHECK (unit_cost >= 0)');
        DB::statement('ALTER TABLE purchase_items ADD CONSTRAINT purchase_items_line_total_ck CHECK (line_total >= 0)');
        DB::statement('ALTER TABLE purchase_items ADD CONSTRAINT purchase_items_line_total_calc_ck CHECK (line_total = quantity * unit_cost)');
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};

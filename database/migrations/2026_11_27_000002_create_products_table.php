<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('sku', 50)->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('name', 200);
            $table->string('brand', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('cost_price', 14, 4);
            $table->decimal('selling_price', 12, 2);
            $table->integer('stock_qty')->default(0);
            $table->integer('min_stock_qty')->default(5);
            $table->integer('warranty_months')->default(0);
            $table->jsonb('specifications')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories');
            $table->index('category_id', 'products_category_idx');
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_cost_price_ck CHECK (cost_price >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_selling_price_ck CHECK (selling_price >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_stock_qty_ck CHECK (stock_qty >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_min_stock_qty_ck CHECK (min_stock_qty >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_warranty_months_ck CHECK (warranty_months >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

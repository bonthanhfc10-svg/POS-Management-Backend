<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('movement_type', 20);
            $table->integer('quantity');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->string('reason', 150);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('sale_id')->references('id')->on('sales');
            $table->foreign('purchase_id')->references('id')->on('purchases');

            $table->index(['product_id', 'created_at'], 'stock_movements_product_date_idx');
            $table->index('sale_id', 'stock_movements_sale_idx');
            $table->index('purchase_id', 'stock_movements_purchase_idx');
            $table->index('user_id', 'stock_movements_user_idx');
        });

        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_movement_type_ck CHECK (movement_type IN ('IN', 'OUT', 'ADJUSTMENT'))");
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_quantity_ck CHECK (quantity <> 0)');
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_stock_before_ck CHECK (stock_before >= 0)');
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_stock_after_ck CHECK (stock_after >= 0)');
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_ref_exclusive_ck CHECK (sale_id IS NULL OR purchase_id IS NULL)');
        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_purchase_type_ck CHECK (purchase_id IS NULL OR movement_type = 'IN')");
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_stock_calc_ck CHECK (stock_after = stock_before + quantity)');
        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_type_quantity_ck CHECK ((movement_type = 'IN' AND quantity > 0) OR (movement_type = 'OUT' AND quantity < 0) OR movement_type = 'ADJUSTMENT')");
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

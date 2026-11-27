<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('user_id');
            $table->string('purchase_no', 50)->unique();
            $table->string('supplier_invoice_no', 100)->nullable();
            $table->timestamp('purchase_date');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->string('status', 20)->default('PENDING');
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('received_by')->references('id')->on('users');
            $table->unique(['supplier_id', 'supplier_invoice_no']);

            $table->index(['supplier_id', 'purchase_date'], 'purchases_supplier_date_idx');
            $table->index('user_id', 'purchases_user_idx');
            $table->index('received_by', 'purchases_received_by_idx');
            $table->index(['status', 'purchase_date'], 'purchases_status_date_idx');
        });

        DB::statement("ALTER TABLE purchases ADD CONSTRAINT purchases_subtotal_ck CHECK (subtotal >= 0)");
        DB::statement("ALTER TABLE purchases ADD CONSTRAINT purchases_discount_amount_ck CHECK (discount_amount >= 0)");
        DB::statement("ALTER TABLE purchases ADD CONSTRAINT purchases_grand_total_ck CHECK (grand_total >= 0)");
        DB::statement("ALTER TABLE purchases ADD CONSTRAINT purchases_status_ck CHECK (status IN ('PENDING', 'RECEIVED', 'CANCELLED'))");
        DB::statement('ALTER TABLE purchases ADD CONSTRAINT purchases_discount_le_subtotal_ck CHECK (discount_amount <= subtotal)');
        DB::statement('ALTER TABLE purchases ADD CONSTRAINT purchases_grand_total_calc_ck CHECK (grand_total = subtotal - discount_amount)');
        DB::statement("ALTER TABLE purchases ADD CONSTRAINT purchases_received_state_ck CHECK ((status = 'RECEIVED' AND received_at IS NOT NULL AND received_by IS NOT NULL) OR (status <> 'RECEIVED' AND received_at IS NULL AND received_by IS NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};

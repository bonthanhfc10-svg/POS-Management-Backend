<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('invoice_no', 50)->unique();
            $table->string('checkout_key', 100)->unique();
            $table->timestamp('sale_date')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('status', 20)->default('PENDING');
            $table->timestamp('reservation_expires_at')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('customer_id')->references('id')->on('customers');
            $table->foreign('voided_by')->references('id')->on('users');

            $table->index(['user_id', 'sale_date'], 'sales_user_date_idx');
            $table->index(['status', 'sale_date'], 'sales_status_date_idx');
            $table->index(['customer_id', 'sale_date'], 'sales_customer_date_idx');
            $table->index('voided_by', 'sales_voided_by_idx');
        });

        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_subtotal_ck CHECK (subtotal >= 0)");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_discount_amount_ck CHECK (discount_amount >= 0)");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_tax_rate_ck CHECK (tax_rate >= 0 AND tax_rate <= 100)");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_tax_amount_ck CHECK (tax_amount >= 0)");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_grand_total_ck CHECK (grand_total >= 0)");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_currency_ck CHECK (currency = 'USD')");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_status_ck CHECK (status IN ('PENDING', 'PARTIALLY_PAID', 'COMPLETED', 'CANCELLED', 'VOIDED'))");
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_discount_le_subtotal_ck CHECK (discount_amount <= subtotal)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_grand_total_calc_ck CHECK (grand_total = subtotal - discount_amount + tax_amount)');
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_sale_date_required_ck CHECK (status NOT IN ('COMPLETED', 'VOIDED') OR sale_date IS NOT NULL)");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_void_state_ck CHECK (status <> 'VOIDED' OR (voided_by IS NOT NULL AND voided_at IS NOT NULL AND void_reason IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

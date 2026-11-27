<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->string('payment_method', 20);
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('cash_received', 12, 2)->nullable();
            $table->decimal('change_amount', 12, 2)->nullable();
            $table->string('payment_status', 20)->default('PENDING');
            $table->string('provider', 50)->nullable();
            $table->string('transaction_ref', 150)->unique();
            $table->string('provider_transaction_id', 150)->nullable();
            $table->text('qr_payload')->nullable();
            $table->string('qr_md5', 32)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('refund_ref', 150)->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales');
            $table->unique(['provider', 'provider_transaction_id']);

            $table->index('sale_id', 'payments_sale_idx');
        });

        DB::statement('CREATE UNIQUE INDEX payments_one_pending_per_sale ON payments (sale_id) WHERE payment_status = \'PENDING\'');

        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_amount_due_ck CHECK (amount_due >= 0)");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_amount_paid_ck CHECK (amount_paid >= 0)");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_cash_received_ck CHECK (cash_received >= 0)");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_change_amount_ck CHECK (change_amount >= 0)");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_method_ck CHECK (payment_method IN ('CASH', 'KHQR'))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_ck CHECK (payment_status IN ('PENDING', 'PAID', 'FAILED', 'EXPIRED', 'CANCELLED', 'REFUND_PENDING', 'REFUNDED', 'REVERSED', 'REVIEW'))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_unresolved_amount_ck CHECK (payment_status NOT IN ('PENDING', 'FAILED', 'EXPIRED', 'CANCELLED') OR amount_paid = 0)");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_review_is_khqr_ck CHECK (payment_status <> 'REVIEW' OR payment_method = 'KHQR')");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_refunded_at_ck CHECK (payment_status NOT IN ('REFUNDED', 'REVERSED') OR refunded_at IS NOT NULL)");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_khqr_no_cash_ck CHECK (payment_method <> 'KHQR' OR (provider IS NOT NULL AND cash_received IS NULL AND change_amount IS NULL))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_paid_state_ck CHECK (payment_status <> 'PAID' OR (paid_at IS NOT NULL AND amount_paid = amount_due))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_refund_needs_paid_at_ck CHECK (payment_status NOT IN ('REFUND_PENDING', 'REFUNDED', 'REVERSED') OR paid_at IS NOT NULL)");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_cash_balance_ck CHECK (payment_method <> 'CASH' OR payment_status NOT IN ('PAID', 'REFUND_PENDING', 'REFUNDED', 'REVERSED') OR (cash_received IS NOT NULL AND change_amount IS NOT NULL AND cash_received = amount_paid + change_amount))");
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

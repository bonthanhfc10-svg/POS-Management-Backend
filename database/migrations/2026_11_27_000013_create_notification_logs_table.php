<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('type', 50);
            $table->string('channel', 20)->default('TELEGRAM');
            $table->string('recipient', 100);
            $table->string('title', 200);
            $table->text('message');
            $table->string('status', 20)->default('PENDING');
            $table->string('event_key', 150);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('sale_id')->references('id')->on('sales');
            $table->foreign('purchase_id')->references('id')->on('purchases');
            $table->unique(['event_key', 'recipient']);

            $table->index(['status', 'created_at'], 'notification_logs_status_idx');
            $table->index('user_id', 'notification_logs_user_idx');
            $table->index('purchase_id', 'notification_logs_purchase_idx');
            $table->index('product_id', 'notification_logs_product_idx');
            $table->index('sale_id', 'notification_logs_sale_idx');
        });

        DB::statement("ALTER TABLE notification_logs ADD CONSTRAINT notification_logs_status_ck CHECK (status IN ('PENDING', 'SENT', 'FAILED'))");
        DB::statement("ALTER TABLE notification_logs ADD CONSTRAINT notification_logs_sent_at_ck CHECK (status <> 'SENT' OR sent_at IS NOT NULL)");
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};

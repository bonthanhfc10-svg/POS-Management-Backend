<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('telegram_chat_id', 50)->nullable();
            $table->boolean('telegram_enabled')->default(false);
            $table->boolean('low_stock_alert')->default(true);
            $table->boolean('out_of_stock_alert')->default(true);
            $table->boolean('daily_sales_report')->default(true);
            $table->boolean('payment_alert')->default(false);
            $table->boolean('void_sale_alert')->default(true);
            $table->boolean('stock_adjustment_alert')->default(true);
            $table->boolean('purchase_alert')->default(true);
            $table->boolean('stock_received_alert')->default(true);
            $table->boolean('daily_purchase_report')->default(false);
            $table->time('report_time')->default('21:00:00');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });

        DB::statement('ALTER TABLE notification_settings ADD CONSTRAINT notification_settings_telegram_ck CHECK (NOT telegram_enabled OR telegram_chat_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->default(1);
            $table->primary('id');
            $table->string('store_name', 150);
            $table->string('store_logo')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->boolean('tax_enabled')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->string('invoice_prefix', 20)->default('INV');
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->string('timezone', 100)->default('Asia/Phnom_Penh');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users');
            $table->index('updated_by', 'settings_updated_by_idx');
        });

        DB::statement('ALTER TABLE settings ADD CONSTRAINT settings_single_row_ck CHECK (id = 1)');
        DB::statement("ALTER TABLE settings ADD CONSTRAINT settings_currency_ck CHECK (currency = 'USD')");
        DB::statement('ALTER TABLE settings ADD CONSTRAINT settings_tax_rate_ck CHECK (tax_rate >= 0 AND tax_rate <= 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

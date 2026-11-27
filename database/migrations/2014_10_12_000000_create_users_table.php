<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('staff_id', 30)->unique();
            $table->string('full_name', 150);
            $table->string('username', 100)->unique();
            $table->string('email', 150)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('password');
            $table->string('profile_image')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles');
            $table->index('role_id', 'users_role_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

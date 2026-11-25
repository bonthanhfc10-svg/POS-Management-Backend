<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['admin' => 'Administrator', 'manager' => 'Manager', 'cashier' => 'Cashier'] as $code => $name) {
            DB::table('roles')->updateOrInsert(
                ['code' => $code],
                ['name' => $name, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        if (!Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->after('password')->constrained('roles')->nullOnDelete();
            });
        }
        if (!Schema::hasColumn('users', 'staff_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('staff_id')->nullable()->unique();
            });
        }
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->unique();
            });
        }

        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')->whereNull('role_id')->orderBy('id')->each(function ($user) {
                $roleId = DB::table('roles')->where('code', $user->role)->value('id');
                DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'staff_id', 'username']);
        });
    }
};

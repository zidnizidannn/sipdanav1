<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'wa_phone')) {
                $table->string('wa_phone', 25)->nullable()->after('email');
            }
            $table->string('email')->nullable()->change();
        });

        // Isi username otomatis untuk akun lama dari bagian depan email
        $users = DB::table('users')->select('id', 'email', 'username')->get();
        foreach ($users as $u) {
            if (!empty($u->username)) {
                continue;
            }

            $base = $u->email ? strtolower(explode('@', $u->email)[0]) : 'user_' . $u->id;
            $base = preg_replace('/[^a-z0-9_]/', '_', $base) ?: 'user_' . $u->id;

            $candidate = $base;
            $counter = 1;
            while (DB::table('users')->where('username', $candidate)->where('id', '!=', $u->id)->exists()) {
                $candidate = "{$base}_{$counter}";
                $counter++;
            }

            DB::table('users')->where('id', $u->id)->update(['username' => $candidate]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
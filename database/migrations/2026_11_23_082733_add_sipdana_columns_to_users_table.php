<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_code', 40)->index()->after('email');
            $table->string('entity_group', 30)->nullable()->after('role_code');
            $table->string('yayasan_mode', 20)->default('FUND_PROVIDER')->after('entity_group');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role_code', 'entity_group', 'yayasan_mode']);
        });
    }
};
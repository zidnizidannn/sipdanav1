<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_caps', function (Blueprint $table) {
            $table->id();
            $table->string('item_name')->unique();
            $table->string('unit', 30);
            $table->unsignedBigInteger('max_price');
            $table->string('scope', 20)->default('ALL');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_caps');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->foreignId('price_cap_id')->nullable()->constrained('price_caps')->nullOnDelete();
            $table->string('name', 200);
            $table->string('spec', 255)->nullable();
            $table->unsignedInteger('qty');
            $table->string('unit', 30);
            $table->unsignedBigInteger('unit_price');
            $table->boolean('is_checked')->default(true);
            $table->text('revision_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_items');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('title', 200);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('applicant_role_code', 40)->index();
            $table->string('applicant_group', 30);
            $table->string('recipient_type', 30)->index();
            $table->string('disbursement_account', 255);
            $table->date('submission_date');
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status', 40)->default('WAITING_EXAMINATION')->index();
            $table->unsignedBigInteger('escalated_from_id')->nullable()->index();
            $table->string('escalated_from_school', 60)->nullable();
            $table->unsignedBigInteger('escalated_to_id')->nullable()->index();
            $table->text('disbursement_note')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('generated_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('generation_status', 32)->default('pending');
            $table->string('lesson_script_path', 2048)->nullable();
            $table->text('video_url')->nullable();
            $table->string('video_public_id', 255)->nullable();
            $table->string('video_format', 32)->nullable();
            $table->decimal('video_duration', 10, 2)->nullable();
            $table->text('narration_url')->nullable();
            $table->string('narration_public_id', 255)->nullable();
            $table->json('generation_metadata')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['source_document_id', 'generation_status']);
            $table->index('generation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_lessons');
    }
};

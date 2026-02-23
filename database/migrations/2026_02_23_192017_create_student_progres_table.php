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
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->foreignId('level_id')
                ->constrained('levels')
                ->cascadeOnDelete();

            $table->integer('worksheets_completed')->default(0);
            $table->decimal('average_score', 5, 2)->default(0);
            $table->decimal('average_time', 8, 2)->default(0);

            $table->date('level_started_at')->nullable();
            $table->date('level_completed_at')->nullable();

            $table->boolean('is_level_complete')->default(false);

            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'level_id'], 'uq_student_subject_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_progres');
    }
};

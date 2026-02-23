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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')
                ->unique()
                ->constrained('assignments')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->string('submitted_file', 255)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->integer('time_taken_min')->nullable();
            $table->integer('error_count')->default(0);
            $table->text('teacher_feedback')->nullable();

            $table->foreignId('graded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('graded_at')->nullable();

            $table->enum('status', ['pending', 'graded'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};

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
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('center_id')
                ->constrained('centers')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('enrollment_no', 50)->unique()->nullable();

            $table->date('date_of_birth')->nullable();
            $table->string('grade', 20)->nullable();
            $table->date('enrollment_date')->nullable();

            $table->json('subjects')->nullable(); // ["Math","English"]

            $table->string('current_level', 20)->nullable();

            $table->enum('status', ['active', 'inactive', 'completed'])
                ->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

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
         Schema::create('fees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();

            $table->foreignId('center_id')
                  ->constrained('centers')
                  ->cascadeOnDelete();

            $table->string('month', 7); 
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->enum('status', ['unpaid','paid','overdue'])->default('unpaid');
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id', 100)->nullable();

            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};

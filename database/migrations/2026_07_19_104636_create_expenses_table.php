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
       Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // Staff Payment, Teacher Payment, Rent, Others
            $table->string('expense_type');

            // শুধুমাত্র Staff/Teacher হলে থাকবে
            $table->string('employee_name')->nullable();

            $table->decimal('salary_amount', 10, 2);

            $table->decimal('paid_amount', 10, 2);

            $table->decimal('due_amount', 10, 2)->default(0);

            $table->enum('payment_method', [
                'Cash',
                'Bkash',
                'Nagad',
                'Bank'
            ]);

            $table->date('payment_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // শিফটের নাম
            $table->time('start_time')->default('09:00:00');

            $table->timestamps();
        });

        // পিভট টেবিল (Teacher এবং Shift এর মেনি-টু-মেনি রিলেশনের জন্য)
        Schema::create('teacher_shift', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_shift');
        Schema::dropIfExists('shifts');
         Schema::dropIfExists('students');
    }
};

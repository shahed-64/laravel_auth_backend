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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('teacher_id')->unique();
            $table->string('full_name');
            $table->string('designation');
            $table->string('department');
            $table->string('qualification')->nullable(); // শিক্ষাগত যোগ্যতা অপশনাল হতে পারে
            $table->string('phone')->nullable();
            $table->string('email')->unique();
            $table->date('join_date');
            $table->decimal('salary', 10, 2);
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};

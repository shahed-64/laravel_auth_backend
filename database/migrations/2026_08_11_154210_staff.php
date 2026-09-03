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
        //
    schema::create('staff', function (blueprint $table){
        $table->id();
        $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
        $table->string('name');
        $table->string('user_name')->unique();
        $table->string('skill')->nullable();
        $table->string('role');
        $table->string('email')->unique();
        $table->string('image')->nullable();
        $table->decimal('salary', 10, 2);
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();

    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

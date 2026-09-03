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
        Schema::create('institute_infos', function (Blueprint $table) {
            $table->id();

            $table->string('institute_name');
            $table->string('established_year')->nullable();
            $table->string('location')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institute_infos');
    }
};

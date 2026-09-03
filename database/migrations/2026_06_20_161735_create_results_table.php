<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->string('exam_year');
            $table->string('exam_type');

            $table->timestamps();

            $table->unique([
                'student_id',
                'exam_year',
                'exam_type'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};

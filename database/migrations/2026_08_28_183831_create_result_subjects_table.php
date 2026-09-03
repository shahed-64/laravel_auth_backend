<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('result_id')
                ->constrained('results')
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->decimal('marks', 5, 2)->nullable();

            $table->timestamps();

            $table->unique([
                'result_id',
                'subject_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_subjects');
    }
};

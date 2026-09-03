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
        Schema::create('teachers_attendances', function (Blueprint $table) {

            $table->id();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->onDelete('cascade');

            // Shift name দিয়ে attendance match করা হবে
            $table->string('shift_name')
                ->default('General Shift');

            $table->date('date');

            // Attendance status
            $table->enum('status', [
                'Present',
                'Absent',
                'Late',
                'Leave'
            ])->default('Present');

            // Leave flag
            $table->boolean('leave')->default(false);

            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            // একই teacher + shift + date এ duplicate attendance হবে না
            $table->unique([
                'teacher_id',
                'shift_name',
                'date'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers_attendances');
    }
};

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
        Schema::create('staff_attendances', function (Blueprint $table) {

            $table->id();

            $table->foreignId('staff_id')
                ->constrained('staff')
                ->onDelete('cascade');

            $table->string('shift_name')
                ->default('General Shift');

            $table->date('date');

            $table->enum('status', [
                'Present',
                'Absent',
                'Late',
                'Leave'
            ])->default('Present');

            $table->time('in_time')->nullable();

            $table->time('out_time')->nullable();

            $table->text('note')->nullable();

            // Leave checkbox
            $table->boolean('leave')->default(false);

            $table->timestamps();

            // একই staff + shift + date এ duplicate attendance হবে না
            $table->unique([
                'staff_id',
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
        Schema::dropIfExists('staff_attendances');
    }
};

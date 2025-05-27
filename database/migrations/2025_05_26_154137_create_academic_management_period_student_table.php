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
        Schema::create('academic_management_period_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_management_period_id')
                ->constrained('academic_management_period', 'id')
                ->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students', 'id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_management_period_student');
    }
};

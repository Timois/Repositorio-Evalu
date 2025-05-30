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
        Schema::create('academic_management_period_bank_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_management_period_id')
                ->constrained('academic_management_period', 'id')
                ->onDelete('cascade');
            $table->foreignId('bank_question_id')->constrained('bank_questions', 'id')->onDelete('cascade');
            $table->timestamps();
        });
    }   

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_management_period_bank_question');
    }
};

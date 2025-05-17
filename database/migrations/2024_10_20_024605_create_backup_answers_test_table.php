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
        Schema::create('backup_answers_test', function (Blueprint $table) {
            $table->id(); // ID del backup de pregunta caso  
            $table->foreignId('student_test_id')->constrained('student_tests', 'id')->onDelete('cascade'); // Relación con la prueba
            $table->integer('question_id')->nullable();
            $table->integer('answer_id')->nullable(); // ID de la respuesta
            $table->time('time')->nullable(); // Hora de la respuesta
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('backup_answers_test');
    }
};

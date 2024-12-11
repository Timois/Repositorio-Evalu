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
        Schema::create('backup_case_question', function (Blueprint $table) {
            $table->id(); // ID del backup de pregunta caso
            $table->foreignId('results_student_test_id')->constrained('results_student_test', 'id')->onDelete('cascade'); // Relación con resultados de prueba del estudiante
            $table->foreignId('question_answer_id')->constrained('answer_question', 'id')->onDelete('cascade'); // Relación con pregunta_respuesta
            $table->string('student_answer'); // Respuesta proporcionada por el estudiante
            $table->longText('answer')->nullable(); // Respuesta corregida (opcional)
            $table->double('value')->nullable(); // Valor asignado
            $table->enum('status', ['evaluado', 'corregido'])->default('evaluado'); // Estado del backup
            $table->timestamps();
        });
        Schema::create('cases_detail_correction_question', function (Blueprint $table) {
            $table->id(); // ID del detalle de caso de corrección por pregunta
            $table->foreignId('cases_correction_id')->constrained('cases_correction', 'id')->onDelete('cascade'); // Relación con casos de corrección
            $table->foreignId('results_student_test_id')->constrained('results_student_test', 'id')->onDelete('cascade'); // Relación con resultados de prueba del estudiante
            $table->foreignId('backup_question_case_id')->constrained('backup_case_question', 'id')->onDelete('cascade'); // Relación con backup de pregunta caso
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('cases_detail_correction_question');
        Schema::dropIfExists('backup_case_question');
    }
};

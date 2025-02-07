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
            $table->foreignId('case_correction_id')->constrained('cases_correction', 'id')->onDelete('cascade'); // Relación con la pregunta
            $table->string('student_answer'); // Respuesta proporcionada por el estudiante
            $table->string('correct_answer')->nullable(); // Respuesta corregida (opcional)
            $table->double('value')->nullable(); // Valor asignado
            $table->enum('status', ['evaluado', 'corregido'])->default('evaluado'); // Estado del backup
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('backup_case_question');
    }
};

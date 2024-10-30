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
        Schema::create('cases_correction', function (Blueprint $table) {
            $table->id(); // ID del caso de corrección
            $table->foreignId('detail_evaluation_final_note_id')->constrained('detail_evaluation_final_note', 'id')->onDelete('cascade'); // Relación con el detalle de nota de evaluación final
            $table->longText('case_correction')->nullable(); // Detalle del caso de corrección
            $table->timestamps();
        });
        Schema::create('cases_detail_correction', function (Blueprint $table) {
            $table->id(); // ID del detalle de caso de corrección
            $table->foreignId('cases_correction_id')->constrained('cases_correction', 'id')->onDelete('cascade'); // Relación con casos de corrección
            $table->foreignId('student_test_id')->constrained('student_tests', 'id')->onDelete('cascade'); // Relación con la prueba del estudiante
            $table->enum('status', ['abierto', 'cerrado'])->default('abierto'); // Estado del caso
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases_detail_correction');
        Schema::dropIfExists('cases_correction');
    }
};

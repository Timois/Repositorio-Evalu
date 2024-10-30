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
        Schema::create('bank_questions', function (Blueprint $table) {
            $table->id(); // ID de la pregunta
            $table->json('book_id')->nullable(); // Referencia a múltiples libros
            $table->foreignId('evaluation_area_id')->constrained('evaluation_area', 'id')->onDelete('cascade'); // Relación con área de evaluación
            $table->foreignId('question_type_id')->constrained('question_type', 'id')->onDelete('cascade'); // Relación con type de pregunta
            $table->string('name')->nullable(); // Nombre de la pregunta
            $table->date('date')->nullable(); // date de creación
            $table->string('question')->nullable(); // Contenido de la pregunta
            $table->string('image')->nullable(); // Imagen asociada a la pregunta (si existe)
            $table->double('weight')->nullable(); // Ponderación de la pregunta
            $table->enum('status', ['activo', 'inactivo'])->default('inactivo'); // Estado de la pregunta
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_questions');
    }
};

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
            //$table->json('book_id')->nullable(); // Referencia a múltiples libros
            $table->foreignId('evaluation_area_id')->constrained('evaluation_area', 'id')->onDelete('cascade'); // Relación con l área de evaluación
            $table->foreignId('area_id')->constrained('areas', 'id')->onDelete('cascade'); 
            $table->string('description')->nullable(); // descripcion de la pregunta
            $table->string('question')->nullable(); // Contenido de la pregunta
            $table->string('image')->nullable(); // Imagen asociada a la pregunta (si existe)
            $table->double('total_weight')->nullable(); // Ponderación de la pregunta
            $table->enum("type", ['cerrada', 'selectiva']);
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

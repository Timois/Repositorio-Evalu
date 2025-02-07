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
            $table->foreignId('area_id')->constrained('areas', 'id')->onDelete('cascade'); 
            $table->foreignId('excel_import_id')->constrained('excel_imports','id')->onDelete('cascade');
            $table->text('description')->nullable(); // descripcion de la pregunta
            $table->text('question'); // Contenido de la pregunta
            $table->integer('total_weight')->nullable(); // Nota de la pregunta
            $table->string('image')->nullable(); // Imagen asociada a la pregunta (si existe)
            $table->enum("type", ['multiple', 'una opcion'])->default('multiple');
            $table->enum('status', ['activo', 'inactivo'])->default('activo'); // Estado de la pregunta
            $table->timestamps();
        });
        //php artisan migrate --path=/database/migrations/2024_10_20_020825_create_evaluations_table.php
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_evaluation');
        Schema::dropIfExists('bank_questions');
    }
};

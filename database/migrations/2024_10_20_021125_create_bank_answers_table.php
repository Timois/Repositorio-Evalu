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
        Schema::create('bank_answers', function (Blueprint $table) {
            $table->id(); // ID de la respuesta
            $table->foreignId('bank_question_id')->constrained('bank_questions', 'id')->onDelete('cascade'); // Relación con la pregunta
            $table->string('answer')->nullable(); // Contenido de la respuesta
            $table->boolean('is_correct')->default(false); // Indica si la respuesta es correcta
            // $table->double('weight')->nullable(); // Nota de la respuesta
            $table->enum('status', ['activo', 'inactivo'])->default('activo'); // Estado de la respuesta
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_answers');
    }
};

<?php

use App\Models\Student;
use Illuminate\Console\Scheduling\ScheduleWorkCommand;
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
        Schema::create('student_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations', 'id')->onDelete('cascade'); // Relación con la evaluaciones
            $table->foreignId('student_id')->constrained('students', 'id')->onDelete('cascade'); // Relación con la estudiantes
            $table->uuid('code')->unique(); // Codigo generado cuando entra a la prueba
            $table->time('start_time')->nullable(); // Hora de inicio de la prueba
            $table->time('end_time')->nullable();   // Hora de fin de la prueba
            $table->double('score_obtained')->nullable();   // Calificacion de la prueba
            $table->string('correct_answers')->nullable();  // respuestas correctas que hizo el estudiante
            $table->string('incorrect_answers')->nullable();   // respuestas incorrectas que hizo el estudiante
            $table->string('not_answered')->nullable();   // respuestas no respondidas que hizo el estudiante
            $table->json('questions_order')->nullable();
            $table->enum('status', ['evaluado', 'corregido'])->default('evaluado'); // Estado de la prueba
            $table->timestamps();
        });

        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_test_id')->constrained('student_tests', 'id')->onDelete('cascade'); // Relación con la prueba
            // $table->string('name')->nullable(); // Nombre para mostrar el resultado de la prueba
            $table->double('qualification')->nullable(); // Puntaje obtenido en la prueba
            $table->double('maximum_score')->nullable();   // Puntaje maximo de la prueba
            $table->integer('minimum_score')->nullable();   // Puntaje minimo de la prueba
            $table->string('exam_duration')->nullable();     // Duracion de la prueba
            $table->enum('status', ['admitido', 'no_admitido'])->default('evaluado');
            $table->timestamps();
        });
        Schema::create('student_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_test_id')->constrained('student_tests', 'id')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('bank_questions', 'id')->onDelete('cascade');
            $table->foreignId('answer_id')->constrained('bank_answers', 'id')->onDelete('cascade');
            $table->double('score')->nullable(); // opcional
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_answers');
        Schema::dropIfExists('results');
        Schema::dropIfExists('student_tests');
    }
};

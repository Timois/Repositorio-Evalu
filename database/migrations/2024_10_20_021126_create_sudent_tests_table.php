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
        Schema::create('student_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations', 'id')->onDelete('cascade'); // Relación con la evaluaciones
            $table->foreignId('student_id')->constrained('students', 'id')->onDelete('cascade')->unique(); // Relación con la estudiantes
            $table->string('code')->unique(); // Codigo de la prueba
            $table->time('start_time')->nullable(); // Hora de inicio de la prueba
            $table->time('end_time')->nullable();   // Hora de fin de la prueba
            $table->double('score_obtained')->nullable();   // Calificacion de la prueba
            $table->string('correct_answers')->nullable();  // respuestas correctas que hizo el estudiante
            $table->string('incorrect_answers')->nullable();   // respuestas incorrectas que hizo el estudiante
            $table->string('not_answered')->nullable();   // respuestas no respondidas que hizo el estudiante
            $table->json('questions_order')->nullable();
            $table->enum('status', ['pendiente', 'completado'])->default('pendiente'); // Estado de la prueba
            $table->string('session_token')->nullable()->unique(); // Token de sesión para la prueba
            $table->timestamps();
        });
        Schema::create('student_test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_test_id')->constrained('student_tests')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('bank_questions')->onDelete('cascade');
            $table->double('score_assigned'); // Puntaje que vale esta pregunta
            $table->string('student_answer')->nullable(); // Respuesta que dio el estudiante
            $table->boolean('is_correct')->nullable(); // Si fue correcta o no
            $table->integer('question_order')->nullable(); // Para mantener el orden único de las preguntas
            $table->timestamps();
        });
        Schema::create('laboratories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable(); // Ubicación física (opcional)
            $table->integer('equipment_count'); // Cantidad de equipos disponibles
            $table->timestamps();
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations', 'id')->onDelete('cascade'); // Relación con evaluaciones
            $table->foreignId('laboratory_id')->constrained('laboratories', 'id')->onDelete('cascade'); // Relación con laboratorios
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('total_students')->nullable();
            $table->dateTime('start_time')->nullable(); // Fecha y hora de inicio del examen para el grupo
            $table->dateTime('end_time')->nullable();   // Fecha y hora de fin del examen para el grupo
            $table->boolean('is_restricted')->default(true); // Si solo los estudiantes del grupo pueden rendir
            $table->timestamps();
        });
        Schema::create('group_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->timestamps();
        });
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_test_id')->constrained('student_tests', 'id')->onDelete('cascade'); // Relación con la prueba
            $table->double('qualification'); // Puntaje obtenido en la prueba
            $table->double('maximum_score');   // Puntaje maximo de la prueba
            $table->integer('minimum_score');   // Puntaje minimo de la prueba
            $table->string('exam_duration');     // Duracion de la prueba
            $table->enum('status', ['admitido', 'no_admitido'])->default('evaluado');
            $table->timestamps();
        });

        Schema::table('logs_answers', function (Blueprint $table) {
            $table->foreignId('student_test_id')->constrained('student_tests', 'id')->onDelete('cascade'); // Relación con la prueba
            $table->integer('student_question_id')->default(0);
            $table->time('time')->default('00:00:00'); // Tiempo que el estudiante se demoró en responder
            $table->integer('answer_id')->nullable(); // Respuesta del estudiante
            $table->boolean('is_ultimate')->default(true); // Si es la ultima respuesta del estudiante
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('student_questions');
        Schema::dropIfExists('student_tests');
    }
};

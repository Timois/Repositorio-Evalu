<?php

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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations', 'id')->onDelete('cascade'); // Relación con la evaluaciones
            $table->uuid('code')->unique();
            $table->enum('range',['minutos','horas','jornada'])->default('minutos');  
            $table->integer('time')->unsigned();
            $table->enum('status',['evaluado','en_proceso','creado'])->default('creado');    
            $table->timestamps();
        });

        Schema::create('student_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests', 'id')->onDelete('cascade'); // Relación con la prueba
            $table->foreignId('student_id')->constrained('students', 'id')->onDelete('cascade'); // Relación con la estudiantes
            $table->uuid('code')->unique();
            $table->enum('status',['evaluado','en_proceso','creado'])->default('creado');    
            $table->timestamps();
        });
        Schema::create('results_student_test', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_test_id')->constrained('student_tests', 'id')->onDelete('cascade'); // Relación con la prueba
            $table->foreignId('bank_question_id')->constrained('bank_questions', 'id')->onDelete('cascade'); // Relación con la pregunta respuesta
            $table->foreignId('question_answer_select_id')->constrained('answer_question', 'id')->onDelete('cascade'); // Relación con la pregunta respuesta  seleccionada por el estudiante
            $table->foreignId('question_answer_correct_id')->constrained('answer_question', 'id')->onDelete('cascade'); // Relación con la pregunta respuesta  seleccionada po el estudiante
            $table->longText('optional_answer')->nullable();
            $table->double('value')->nullable();
            $table->enum('status',['evaluado','corregido'])->default('evaluado');
            $table->timestamps();
        });
        Schema::create('draft_evaluations_header_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests', 'id')->onDelete('cascade'); // Relación con la prueba
            $table->enum('status', ['abierto', 'cerrado']);
            $table->timestamps();
        });
        Schema::create('detail_draft_evaluations_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_evaluations_header_notes_id')->constrained('draft_evaluations_header_notes', 'id')->onDelete('cascade'); // Relación con la cabezera nota evaluacion borrador
            $table->foreignId('student_test_id')->constrained('student_tests', 'id')->onDelete('cascade'); // Relación con la prueba del estudiante
            $table->double('score')->nullable();
            $table->enum('status', ['admitido', 'no_admitido']);
            $table->timestamps();
        });
        Schema::create('detail_evaluation_final_note', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_test_id')->constrained('student_tests', 'id')->onDelete('cascade'); // Relación con la prueba estudiante
            $table->double('score')->nullable();
            $table->integer('detail_draft_evaluations_notes_id')->constrained('detail_draft_evaluations_notes', 'id')->onDelete('cascade'); // Relación con la detalle de notas en borrador
            $table->enum('status',['admitido','no_admitido']);      
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_evaluation_final_note');
        Schema::dropIfExists('detail_draft_evaluations_notes');
        Schema::dropIfExists('draft_evaluations_header_notes');
        Schema::dropIfExists('results_student_test');
        Schema::dropIfExists('student_tests');
        Schema::dropIfExists('tests');
    }
};

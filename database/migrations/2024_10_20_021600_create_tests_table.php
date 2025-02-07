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
        Schema::create('rules_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations', 'id')->onDelete('cascade'); // Relación con la evaluaciones
            $table->string('name');
            $table->uuid('code')->unique();
            $table->enum('range_time',['minutos','horas','jornada'])->default('minutos');  
            $table->integer('minimum_score')->nullable();
            $table->enum('status',['evaluado','en_proceso','creado'])->default('creado');    
            $table->timestamps();
        });

        Schema::create('student_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations', 'id')->onDelete('cascade'); // Relación con la prueba
            $table->foreignId('student_id')->constrained('students', 'id')->onDelete('cascade'); // Relación con la estudiantes
            $table->integer('number_of_places')->nullable();
            $table->uuid('code')->unique();
            $table->date('date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();    
            $table->timestamps();
        });
    
        
        Schema::create('draft_exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_test_id')->constrained('student_tests', 'id')->onDelete('cascade'); // Relación con la prueba
            $table->string('name')->nullable();
            $table->double('qualification')->nullable();
            $table->string('correct_answers')->nullable();
            $table->string('incorrect_answers')->nullable();
            $table->enum('status',['evaluado','corregido'])->default('evaluado');
            $table->timestamps();
        });
        
        Schema::create('cases_correction', function (Blueprint $table) {
            $table->id(); // ID del caso de corrección
            $table->foreignId('result_student_test_id')->constrained('draft_exam_results', 'id')->onDelete('cascade'); // Relación con resultados de prueba del estudiante
            $table->longText('detail')->nullable(); // Detalle del caso de corrección
            $table->double('corrected_score')->nullable(); // Puntaje corregido
            $table->dateTime('correction_date')->nullable(); // Fecha de correccion
            $table->timestamps();
        });

        Schema::create('final_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_result_id')->constrained('draft_exam_results', 'id')->onDelete('cascade'); // Relación con el resultado de la prueba
            $table->double('final_score');
            $table->double('corrected_final_score')->nullable();
            $table->enum('status',['admitido','no_admitido']);      
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_results');
        Schema::dropIfExists('cases_correction');
        Schema::dropIfExists('draft_exam_results');
        Schema::dropIfExists('student_tests');
        Schema::dropIfExists('tests');
    }
};

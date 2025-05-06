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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();
            $table->integer('total_score')->nullable();
            $table->double('passing_score')->nullable();
            $table->uuid('code')->unique();
            $table->date('date_of_realization')->nullable();  
            $table->integer('qualified_students')->nullable();
            $table->integer('disqualified_students')->nullable();
            $table->enum('status', ['activo', 'inactivo'])->default('inactivo');
            $table->enum('type', ['ocr', 'web', 'app'])->default('web');
            $table->foreignId('academic_management_period_id')->constrained('academic_management_period', 'id')->onDelete('cascade');
            // $table->foreignId('rules_test_id')->constrained('rules_tests', 'id')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('question_evaluation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations', 'id')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('bank_questions', 'id')->onDelete('cascade');
            $table->double('score')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_of_generated_questions', function (Blueprint $table) 
        {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations', 'id')->onDelete('cascade');
            $table->integer('areas_selected')->nullable();
            $table->integer('questions_generated')->nullable();
            $table->double('scores_asigned')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_of_generated_questions');
        Schema::dropIfExists('question_evaluation');
        Schema::dropIfExists('evaluations');
    }
};

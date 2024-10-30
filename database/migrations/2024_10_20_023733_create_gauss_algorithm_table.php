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
        Schema::create('gauss_algorithm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests', 'id')->onDelete('cascade'); // Relación con la evaluacion
            $table->double('passing_score')->nullable();
            $table->double('maximum_score')->nullable();
            $table->integer('approval_count')->nullable();
            $table->integer('failed_count')->nullable();
            $table->integer('abandoned_count')->nullable();
            $table->integer('total_count')->nullable();
            $table->double('approval_percentage')->nullable();
            $table->double('failed_percentage')->nullable();
            $table->double('abandoned_percentage')->nullable();
            $table->double('total_percentage')->nullable();
            $table->enum('status',['activo','inactivo','en_proceso'])->default('en_proceso');
            $table->timestamps();
        });
        Schema::create('evaluation_final_notes', function (Blueprint $table) {
            $table->id();
            $table->integer('gauss_algorithm_id')->unsigned(); //Relacion con Algoritmo de Gauss
            $table->integer('evaluation_id')->unsigned();
            $table->enum('status', ['abierto', 'cerrado']);  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_final_notes');
        Schema::dropIfExists('gauss_algorithm');
    }
};

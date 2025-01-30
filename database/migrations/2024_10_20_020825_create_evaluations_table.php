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
            $table->integer('number_questions');
            $table->integer('total_score')->nullable();
            $table->boolean('is_random')->nullable();
            $table->time('duration');
            $table->enum('status', ['activo', 'inactivo', 'efectuado'])->default('inactivo');
            $table->enum('type', ['ocr', 'web', 'app'])->default('web');
            $table->foreignId('academic_management_period_id')->constrained('academic_management_period', 'id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};

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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('unit_id')->unsigned(); // Relacion con la unit
            $table->string('period_id')->unsigned(); // Relacion con el periodo
            $table->string('name')->nullable();
            $table->date('date')->nullable();
            $table->string('autor')->nullable();
            $table->string('patch')->nullable();
            $table->enum('status',['activo','inactivo','efectuado'])->default('inactivo');
            $table->timestamps();
        });
        Schema::create('books_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('books_id')->constrained("books", 'id')->onDelete('cascade'); // Relacion con el libro
            $table->foreignId('evaluation_id')->constrained("evaluations", 'id')->onDelete('cascade'); // Relacion con las evaluaciones
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books_material');
        Schema::dropIfExists('books');
    }
};

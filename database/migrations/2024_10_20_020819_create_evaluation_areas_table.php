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
        Schema::create('evaluation_area', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained("evaluations", 'id')->onDelete('cascade'); // Relacion con las evaluaciones
            $table->string('name')->nullable();
            $table->integer('count')->nullable();
            $table->integer('assessment')->nullable();
            $table->longText('description')->nullable();
            $table->enum('status', ['activo', 'inactivo'])->default('inactivo');
            $table->enum('books', ['si', 'no'])->default('no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_area');
    }
};

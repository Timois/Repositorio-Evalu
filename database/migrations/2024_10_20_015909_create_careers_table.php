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
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('initials');
            $table->string('logo')->nullable();
            $table->enum('type', ['dependiente', 'mayor', 'carrera', 'facultad'])->default('mayor');
            $table->unsignedBigInteger('unit_id')->default(0);
            $table->timestamps();

            // Índice para mejorar el rendimiento de las búsquedas
            $table->index(['type', 'unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('careers');
    }
};

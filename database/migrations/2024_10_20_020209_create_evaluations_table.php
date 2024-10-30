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
            $table->string('name')->nullable();
            $table->double('approval_score')->nullable();
            $table->date('date')->nullable();
            $table->enum('status', ['activo', 'inactivo', 'efectuado'])->default('inactivo');
            $table->enum('type', ['ocr', 'web', 'app'])->default('web');
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

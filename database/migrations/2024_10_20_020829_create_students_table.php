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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('ci')->unique();
            $table->string('name');
            $table->string('paternal_surname')->nullable();
            $table->string('maternal_surname')->nullable();
            $table->string('phone_number')->unique();
            $table->date('birthdate');
            $table->string('password');
            $table->enum('status',['activo','inactivo'])->default('inactivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

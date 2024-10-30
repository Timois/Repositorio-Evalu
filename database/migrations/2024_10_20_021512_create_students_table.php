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
            $table->string('ci')->default('');
            $table->string('name')->default('');
            $table->string('last_name_parent')->default('');
            $table->string('last_name_mother')->default('');
            $table->string('phone_number')->default('');
            $table->string('birthdate')->default('');
            $table->string('password')->default('');
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

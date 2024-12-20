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
        Schema::create('academic_management', function (Blueprint $table) {
            $table->id();
            $table->string('year');
            $table->date('initial_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->string('period');
            $table->enum('level',['1','2','3','4','5']);
            $table->timestamps();
        });
        Schema::create('academic_management_career',function(Blueprint $table){
            $table->id();
            $table->foreignId('career_id')->constrained("careers", 'id')->onDelete('cascade');
            $table->foreignId('academic_management_id')->constrained("academic_management", 'id')->onDelete('cascade');
            $table->timestamps();
        });
        Schema::create('academic_management_period',function(Blueprint $table){
            $table->id();
            $table->dateTime('initial_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->foreignId('academic_management_career_id')->constrained("academic_management_career", 'id')->onDelete('cascade');
            $table->enum('status',['aperturado','finalizado']);
            $table->foreignId('period_id')->constrained("periods", 'id')->onDelete('cascade');
            $table->timestamps();
        });
        Schema::create('period_extensions', function (Blueprint $table) {
            $table->id();
            $table->dateTime('initial_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->foreignId('academic_management_period_id')->constrained("academic_management_period", 'id')->onDelete('cascade');
            $table->timestamps();
        });
        Schema::create('management_extensions', function (Blueprint $table) {
            $table->id();
            $table->date('date_extension')->nullable();
            $table->foreignId('academic_management_id')->constrained("academic_management", 'id')->onDelete('cascade');
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('management_extensions');
        Schema::dropIfExists('period_extensions');
        Schema::dropIfExists('academic_management_period');
        Schema::dropIfExists('academic_management_career');
        Schema::dropIfExists('periods');
        Schema::dropIfExists('academic_management');
    }
};

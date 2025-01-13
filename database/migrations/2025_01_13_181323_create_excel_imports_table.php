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
        Schema::create('excel_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('original_name')->nullable();
            $table->enum('status',['pendiente', 'procesando','completado','error']);
            $table->timestamps();
        });

        Schema::create('excel_imports_detail', function (Blueprint $table){
            $table->id();
            $table->foreignId('excel_import')->constrained('excel_imports','id')->onDelete('cascade');   
            $table->foreignId('question_bank_id')->constrained('bank_questions','id')->onDelete('cascade');
            $table->integer('row_number');
            $table->json('row_data');
            $table->enum('status',['pendiente', 'procesando', 'completado', 'error']);
            $table->string('error_message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excel_imports');
        Schema::dropIfExists('excel_imports_detail');
        
    }
};

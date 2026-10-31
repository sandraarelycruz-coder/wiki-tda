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
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            //se agrego de aca 
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_leader')->default(false);
            $table->unique(['project_id', 'student_id']);
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    //project_id con cascadeOnDelete si se borra el proyecto, no tiene sentido conservar sus filas 

    //tudent_id con cascadeOnDelete si se borra el estudiante, se elimina  todo lo de el 

    //El índice único evita agregar dos veces al mismo estudiante en un proyecto.




    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};

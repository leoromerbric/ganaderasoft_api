<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla: vacunacion_animals
     * Registro de qué animales específicos fueron vacunados en cada evento de vacunación.
     * Tabla pivote entre vacunacions y animals.
     */
    public function up(): void
    {
        Schema::create('vacunacion_animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacunacion_id')->constrained('vacunacions')->onDelete('cascade');
            $table->foreignId('animal_id')->constrained('animals')->onDelete('cascade');

            $table->unique(['vacunacion_id', 'animal_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacunacion_animals');
    }
};

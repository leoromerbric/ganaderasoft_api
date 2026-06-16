<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dosis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casa_comercial_vacuna_id')->constrained('casa_comercial_vacuna')->onDelete('cascade');
            $table->decimal('frecuencia', 3, 0);
            $table->decimal('costo', 20, 2)->nullable();
            $table->decimal('costo_frasco', 20, 2)->nullable();
            $table->date('fecha_uso_ini');
            $table->date('fecha_uso_fin')->nullable();
            $table->foreignId('animal_etapa_id')->constrained('animal_etapa')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dosis');
    }
};

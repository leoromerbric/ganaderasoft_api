<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historico_aplicacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosis_id')->constrained('dosis')->onDelete('cascade');
            $table->enum('origen_tipo', ['manual', 'dosis_animal', 'dosis_rebano', 'dosis_subgrupo'])->default('manual');
            $table->date('fecha_inyeccion');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_aplicacions');
    }
};

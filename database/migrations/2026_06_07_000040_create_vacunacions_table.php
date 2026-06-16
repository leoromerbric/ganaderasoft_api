<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla: vacunacions
     * Representa un evento de vacunación masiva aplicado a un rebaño o grupo de animales.
     * No confundir con 'vacunas' (catálogo de productos); esta es la APLICACIÓN del evento.
     */
    public function up(): void
    {
        Schema::create('vacunacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacuna_id')->constrained('vacunas')->onDelete('cascade');
            $table->foreignId('casa_comercial_id')->nullable()->constrained('casa_comercials')->onDelete('set null');
            $table->foreignId('rebano_id')->nullable()->constrained('rebanos')->onDelete('set null');
            $table->enum('modo_seleccion', ['todos_rebano', 'lista_animales', 'filtros']);
            $table->json('filtros')->nullable();
            $table->date('fecha');
            $table->decimal('costo_dosis', 20, 2)->default(0);
            $table->unsignedInteger('total_animales')->default(0);
            $table->decimal('monto_total', 20, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacunacions');
    }
};

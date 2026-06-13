<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        if (Schema::hasTable('dosis') && !Schema::hasTable('dosis_borrar_vacunacion')) {
            Schema::rename('dosis', 'dosis_borrar_vacunacion');
        }

        if (Schema::hasTable('historico_aplicacion') && !Schema::hasTable('historico_aplicacion_borrar_vacunacion')) {
            Schema::rename('historico_aplicacion', 'historico_aplicacion_borrar_vacunacion');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        if (!Schema::hasTable('vacunacion')) {
            Schema::create('vacunacion', function (Blueprint $table) {
                $table->increments('vacunacion_id');
                $table->unsignedInteger('vacunacion_vacuna_id');
                $table->unsignedInteger('vacunacion_casa_id')->nullable();
                $table->integer('vacunacion_rebano_id')->nullable();
                $table->enum('vacunacion_modo_seleccion', ['todos_rebano', 'lista_animales', 'filtros']);
                $table->json('vacunacion_filtros')->nullable();
                $table->date('vacunacion_fecha');
                $table->decimal('vacunacion_costo_dosis', 20, 2)->default(0);
                $table->unsignedInteger('vacunacion_total_animales')->default(0);
                $table->decimal('vacunacion_monto_total', 20, 2)->default(0);
                $table->text('vacunacion_observacion')->nullable();
                $table->timestamps();

                $table->foreign('vacunacion_vacuna_id', 'fk_vacunacion_vacuna')->references('vacuna_id')->on('vacuna');
                $table->foreign('vacunacion_casa_id', 'fk_vacunacion_casa')->references('casa_id')->on('casa_comercial');
                $table->foreign('vacunacion_rebano_id', 'fk_vacunacion_rebano')->references('id_Rebano')->on('rebano');

                $table->index(['vacunacion_fecha'], 'idx_vacunacion_fecha');
                $table->index(['vacunacion_vacuna_id'], 'idx_vacunacion_vacuna');
                $table->index(['vacunacion_rebano_id'], 'idx_vacunacion_rebano');
            });
        }

        if (!Schema::hasTable('vacunacion_animal')) {
            Schema::create('vacunacion_animal', function (Blueprint $table) {
                $table->increments('va_id');
                $table->unsignedInteger('va_vacunacion_id');
                $table->integer('va_animal_id');
                $table->timestamps();

                $table->foreign('va_vacunacion_id', 'fk_va_vacunacion')->references('vacunacion_id')->on('vacunacion')->cascadeOnDelete();
                $table->foreign('va_animal_id', 'fk_va_animal')->references('id_Animal')->on('animal');
                $table->unique(['va_vacunacion_id', 'va_animal_id'], 'uq_va_vacunacion_animal');
                $table->index(['va_animal_id'], 'idx_va_animal');
            });
        }
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::dropIfExists('vacunacion_animal');
        Schema::dropIfExists('vacunacion');

        if (Schema::hasTable('dosis_borrar_vacunacion') && !Schema::hasTable('dosis')) {
            Schema::rename('dosis_borrar_vacunacion', 'dosis');
        }

        if (Schema::hasTable('historico_aplicacion_borrar_vacunacion') && !Schema::hasTable('historico_aplicacion')) {
            Schema::rename('historico_aplicacion_borrar_vacunacion', 'historico_aplicacion');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};

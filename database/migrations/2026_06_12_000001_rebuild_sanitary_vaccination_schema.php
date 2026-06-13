<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $legacyTables = [
            'historico_aplicacion',
            'dosis',
            'vacuna_casa',
            'vacuna',
            'casa_comercial',
        ];

        foreach ($legacyTables as $table) {
            $backupTable = $table . '_borrar';
            if (Schema::hasTable($table) && !Schema::hasTable($backupTable)) {
                Schema::rename($table, $backupTable);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        if (!Schema::hasTable('vacuna')) {
            Schema::create('vacuna', function (Blueprint $table) {
                $table->increments('vacuna_id');
                $table->string('vacuna_nombre', 80);
                $table->text('vacuna_descripcion')->nullable();
                $table->boolean('activa')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('casa_comercial')) {
            Schema::create('casa_comercial', function (Blueprint $table) {
                $table->increments('casa_id');
                $table->string('laboratorio', 60);
                $table->string('marca_comercial', 60);
                $table->boolean('activa')->default(true);
                $table->timestamps();
                $table->unique(['laboratorio', 'marca_comercial'], 'uq_casa_laboratorio_marca');
            });
        }

        if (!Schema::hasTable('vacuna_casa')) {
            Schema::create('vacuna_casa', function (Blueprint $table) {
                $table->unsignedInteger('vc_vacuna_id');
                $table->unsignedInteger('vc_casa_id');
                $table->decimal('dosis_cantidad', 10, 2)->nullable();
                $table->primary(['vc_vacuna_id', 'vc_casa_id']);
                $table->foreign('vc_vacuna_id', 'fk_vacuna_casa_vacuna')->references('vacuna_id')->on('vacuna');
                $table->foreign('vc_casa_id', 'fk_vacuna_casa_casa')->references('casa_id')->on('casa_comercial');
            });
        }

        if (!Schema::hasTable('dosis')) {
            Schema::create('dosis', function (Blueprint $table) {
                $table->increments('dosis_id');
                $table->unsignedInteger('dosis_vacuna_id');
                $table->unsignedInteger('dosis_casa_id');
                $table->enum('dosis_objetivo_tipo', ['animal', 'rebano', 'subgrupo']);
                $table->unsignedInteger('dosis_objetivo_animal_id')->nullable();
                $table->unsignedInteger('dosis_objetivo_rebano_id')->nullable();
                $table->json('dosis_objetivo_filtros')->nullable();
                $table->unsignedInteger('dosis_frecuencia');
                $table->decimal('dosis_costo', 20, 2)->nullable();
                $table->decimal('dosis_costo_frasco', 20, 2)->nullable();
                $table->date('dosis_fecha_uso_ini');
                $table->date('dosis_fecha_uso_fin')->nullable();
                $table->text('dosis_observacion')->nullable();
                $table->timestamps();

                $table->foreign('dosis_vacuna_id', 'fk_dosis_vacuna')->references('vacuna_id')->on('vacuna');
                $table->foreign('dosis_casa_id', 'fk_dosis_casa')->references('casa_id')->on('casa_comercial');
                $table->foreign('dosis_objetivo_animal_id', 'fk_dosis_obj_animal')->references('id_Animal')->on('animal');
                $table->foreign('dosis_objetivo_rebano_id', 'fk_dosis_obj_rebano')->references('id_Rebano')->on('rebano');

                $table->index(['dosis_vacuna_id'], 'idx_dosis_vacuna');
                $table->index(['dosis_objetivo_tipo'], 'idx_dosis_obj_tipo');
                $table->index(['dosis_fecha_uso_ini', 'dosis_fecha_uso_fin'], 'idx_dosis_vigencia');
            });
        }

        if (!Schema::hasTable('historico_aplicacion')) {
            Schema::create('historico_aplicacion', function (Blueprint $table) {
                $table->increments('id_ha');
                $table->unsignedInteger('ha_vacuna_id');
                $table->unsignedInteger('ha_casa_id');
                $table->unsignedInteger('ha_dosis_id')->nullable();
                $table->unsignedInteger('ha_animal_id');
                $table->enum('ha_origen_tipo', ['manual', 'dosis_animal', 'dosis_rebano', 'dosis_subgrupo'])->default('manual');
                $table->date('fecha_inyeccion');
                $table->text('observacion')->nullable();
                $table->timestamps();

                $table->foreign('ha_vacuna_id', 'fk_ha_vacuna')->references('vacuna_id')->on('vacuna');
                $table->foreign('ha_casa_id', 'fk_ha_casa')->references('casa_id')->on('casa_comercial');
                $table->foreign('ha_dosis_id', 'fk_ha_dosis')->references('dosis_id')->on('dosis');
                $table->foreign('ha_animal_id', 'fk_ha_animal')->references('id_Animal')->on('animal');

                $table->index(['fecha_inyeccion'], 'idx_ha_fecha');
                $table->index(['ha_vacuna_id'], 'idx_ha_vacuna');
                $table->index(['ha_animal_id'], 'idx_ha_animal');
            });
        }
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::dropIfExists('historico_aplicacion');
        Schema::dropIfExists('dosis');
        Schema::dropIfExists('vacuna_casa');
        Schema::dropIfExists('vacuna');
        Schema::dropIfExists('casa_comercial');

        $legacyTables = [
            'historico_aplicacion',
            'dosis',
            'vacuna_casa',
            'vacuna',
            'casa_comercial',
        ];

        foreach ($legacyTables as $table) {
            $backupTable = $table . '_borrar';
            if (Schema::hasTable($backupTable) && !Schema::hasTable($table)) {
                Schema::rename($backupTable, $table);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};

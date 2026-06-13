<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Alters arbol_gen to:
 * 1. Add a surrogate auto-increment PK (id_arbol) for easy CRUD operations.
 * 2. Replace the composite PK (id_hijo, id_padre) with a unique constraint on
 *    (id_hijo, tipo) — ensuring each animal can have at most ONE Padre and ONE Madre.
 * 3. Keep the existing FK constraints.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: drop existing primary key and add surrogate PK + unique constraint
        DB::statement('ALTER TABLE arbol_gen DROP PRIMARY KEY');

        DB::statement('ALTER TABLE arbol_gen ADD COLUMN id_arbol INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');

        DB::statement('ALTER TABLE arbol_gen ADD UNIQUE KEY uq_hijo_tipo (id_hijo, tipo)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE arbol_gen DROP COLUMN id_arbol');
        DB::statement('ALTER TABLE arbol_gen DROP INDEX uq_hijo_tipo');
        DB::statement('ALTER TABLE arbol_gen ADD PRIMARY KEY (id_hijo, id_padre)');
    }
};

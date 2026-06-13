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
        // Step 1: drop FK constraints that depend on the composite PK
        DB::statement('ALTER TABLE arbol_gen DROP FOREIGN KEY fk_hijo');
        DB::statement('ALTER TABLE arbol_gen DROP FOREIGN KEY fk_padre');

        // Step 2: drop the composite primary key
        DB::statement('ALTER TABLE arbol_gen DROP PRIMARY KEY');

        // Step 3: add surrogate auto-increment PK
        DB::statement('ALTER TABLE arbol_gen ADD COLUMN id_arbol INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');

        // Step 4: add unique constraint (one Padre, one Madre per animal)
        DB::statement('ALTER TABLE arbol_gen ADD UNIQUE KEY uq_hijo_tipo (id_hijo, tipo)');

        // Step 5: re-add FK constraints
        DB::statement('ALTER TABLE arbol_gen ADD CONSTRAINT fk_hijo FOREIGN KEY (id_hijo) REFERENCES animal (id_Animal)');
        DB::statement('ALTER TABLE arbol_gen ADD CONSTRAINT fk_padre FOREIGN KEY (id_padre) REFERENCES animal (id_Animal)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE arbol_gen DROP FOREIGN KEY fk_hijo');
        DB::statement('ALTER TABLE arbol_gen DROP FOREIGN KEY fk_padre');
        DB::statement('ALTER TABLE arbol_gen DROP INDEX uq_hijo_tipo');
        DB::statement('ALTER TABLE arbol_gen DROP COLUMN id_arbol');
        DB::statement('ALTER TABLE arbol_gen ADD PRIMARY KEY (id_hijo, id_padre)');
        DB::statement('ALTER TABLE arbol_gen ADD CONSTRAINT fk_hijo FOREIGN KEY (id_hijo) REFERENCES animal (id_Animal)');
        DB::statement('ALTER TABLE arbol_gen ADD CONSTRAINT fk_padre FOREIGN KEY (id_padre) REFERENCES animal (id_Animal)');
    }
};

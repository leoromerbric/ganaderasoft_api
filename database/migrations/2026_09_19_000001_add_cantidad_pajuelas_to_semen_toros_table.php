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
        Schema::table('semen_toros', function (Blueprint $table) {
            if (!Schema::hasColumn('semen_toros', 'cantidad_pajuelas')) {
                $table->unsignedInteger('cantidad_pajuelas')->nullable()->default(1)->after('fecha');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('semen_toros', function (Blueprint $table) {
            if (Schema::hasColumn('semen_toros', 'cantidad_pajuelas')) {
                $table->dropColumn('cantidad_pajuelas');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE formations MODIFY niveau VARCHAR(180) NOT NULL');
        DB::statement('ALTER TABLE bilan_lignes MODIFY niveau VARCHAR(180) NOT NULL');

        Schema::table('formations', function (Blueprint $t) {
            $t->unsignedSmallInteger('ordre')->default(0)->after('semestre');
        });
        Schema::table('bilan_lignes', function (Blueprint $t) {
            $t->unsignedSmallInteger('ordre')->default(0)->after('semestre');
        });
    }

    public function down(): void
    {
        Schema::table('formations', function (Blueprint $t) {
            $t->dropColumn('ordre');
        });
        Schema::table('bilan_lignes', function (Blueprint $t) {
            $t->dropColumn('ordre');
        });
    }
};

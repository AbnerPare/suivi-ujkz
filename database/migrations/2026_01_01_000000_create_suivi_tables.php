<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departements', function (Blueprint $t) {
            $t->id();
            $t->string('nom')->unique();
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('enseignant'); // admin | responsable | enseignant
            $t->foreignId('departement_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('cours', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('intitule');
            $t->foreignId('departement_id')->constrained()->cascadeOnDelete();
            $t->foreignId('enseignant_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('filiere')->nullable();
            $t->string('niveau')->nullable();
            $t->unsignedTinyInteger('semestre')->default(1);
            $t->unsignedSmallInteger('vh_cm')->default(0);
            $t->unsignedSmallInteger('vh_td')->default(0);
            $t->unsignedSmallInteger('vh_tp')->default(0);
            $t->timestamps();
        });

        Schema::create('suivis', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cours_id')->constrained('cours')->cascadeOnDelete();
            $t->unsignedTinyInteger('mois');
            $t->unsignedSmallInteger('annee');
            $t->unsignedSmallInteger('cm_realise')->default(0);
            $t->unsignedSmallInteger('td_realise')->default(0);
            $t->unsignedSmallInteger('tp_realise')->default(0);
            $t->text('observations')->nullable();
            $t->string('statut')->default('brouillon'); // brouillon | soumis | valide
            $t->timestamps();
            $t->unique(['cours_id', 'mois', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suivis');
        Schema::dropIfExists('cours');
        Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('departement_id'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
        Schema::dropIfExists('departements');
    }
};

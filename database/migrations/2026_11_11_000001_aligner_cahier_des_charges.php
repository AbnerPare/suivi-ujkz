<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('suivis');
        Schema::dropIfExists('cours');
        if (Schema::hasColumn('users', 'departement_id')) {
            Schema::table('users', function (Blueprint $t) {
                $t->dropForeign(['departement_id']);
                $t->dropColumn('departement_id');
            });
        }
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
        }
        Schema::dropIfExists('departements');
        Schema::enableForeignKeyConstraints();

        Schema::create('universites', function (Blueprint $t) {
            $t->id();
            $t->string('nom');
            $t->string('sigle', 20);
            $t->string('ville')->nullable();
            $t->string('email')->nullable();
            $t->string('telephone')->nullable();
            $t->boolean('actif')->default(true);
            $t->timestamps();
        });

        Schema::create('ufrs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('universite_id')->constrained()->cascadeOnDelete();
            $t->string('type')->default('ufr'); // ufr | institut
            $t->string('nom');
            $t->string('sigle', 20)->nullable();
            $t->timestamps();
        });

        Schema::create('departements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ufr_id')->constrained('ufrs')->cascadeOnDelete();
            $t->string('nom');
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('chef');
            $t->foreignId('universite_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('ufr_id')->nullable()->constrained('ufrs')->nullOnDelete();
            $t->foreignId('departement_id')->nullable()->constrained()->nullOnDelete();
            $t->boolean('actif')->default(true);
        });

        Schema::table('ufrs', function (Blueprint $t) {
            $t->foreignId('directeur_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('directeur_adjoint_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('departements', function (Blueprint $t) {
            $t->foreignId('chef_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('adjoint_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('formations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('departement_id')->constrained()->cascadeOnDelete();
            $t->string('filiere');
            $t->string('parcours')->nullable();
            $t->string('niveau', 10); // L1 L2 L3 M1 M2
            $t->string('semestre', 4); // S1..S6
            $t->timestamps();
        });

        Schema::create('annees_academiques', function (Blueprint $t) {
            $t->id();
            $t->foreignId('universite_id')->constrained()->cascadeOnDelete();
            $t->string('libelle');
            $t->date('date_debut');
            $t->date('date_fin');
            $t->string('statut')->default('ouverte'); // ouverte | cloturee | archivee
            $t->timestamps();
            $t->unique(['universite_id', 'libelle']);
        });

        Schema::create('campagnes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $t->string('libelle');
            $t->date('date_debut')->nullable();
            $t->date('date_fin')->nullable();
            $t->string('statut')->default('ouverte');
            $t->timestamps();
        });

        Schema::create('periodes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('campagne_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('mois');
            $t->unsignedSmallInteger('annee');
            $t->string('libelle');
            $t->boolean('ouverte')->default(true);
            $t->timestamps();
            $t->unique(['campagne_id', 'mois', 'annee']);
        });

        Schema::create('bilans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('universite_id')->constrained()->cascadeOnDelete();
            $t->foreignId('departement_id')->constrained()->cascadeOnDelete();
            $t->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $t->foreignId('campagne_id')->constrained()->cascadeOnDelete();
            $t->foreignId('periode_id')->constrained()->cascadeOnDelete();
            $t->string('statut')->default('brouillon');
            $t->text('commentaire_chef')->nullable();
            $t->unsignedSmallInteger('version')->default(1);
            $t->text('motif_reouverture')->nullable();
            $t->timestamp('transmis_at')->nullable();
            $t->timestamps();
            $t->unique(['departement_id', 'periode_id']);
        });

        Schema::create('bilan_lignes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bilan_id')->constrained()->cascadeOnDelete();
            $t->foreignId('formation_id')->nullable()->constrained()->nullOnDelete();
            $t->string('filiere');
            $t->string('parcours')->nullable();
            $t->string('niveau', 10);
            $t->string('semestre', 4);
            $t->unsignedSmallInteger('ec_total')->default(0);
            $t->unsignedSmallInteger('ec_effectues')->default(0);
            $t->unsignedSmallInteger('ec_a_evaluer')->default(0);
            $t->unsignedSmallInteger('ec_evalues')->default(0);
            $t->unsignedTinyInteger('deliberation')->default(0); // 0 aucune, 1 session 1, 2 session annuelle
            $t->text('observations')->nullable();
            $t->timestamps();
        });

        Schema::create('bilan_observations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bilan_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('role');
            $t->text('contenu');
            $t->timestamps();
        });

        Schema::create('bilan_historiques', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bilan_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('de')->nullable();
            $t->string('vers');
            $t->string('commentaire')->nullable();
            $t->timestamps();
        });

        Schema::create('bilan_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bilan_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedSmallInteger('version');
            $t->string('motif')->nullable();
            $t->json('donnees');
            $t->timestamps();
        });

        Schema::create('avis', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('universite_id')->nullable()->constrained()->nullOnDelete();
            $t->string('titre');
            $t->text('message');
            $t->string('lien')->nullable();
            $t->timestamp('lu_at')->nullable();
            $t->timestamps();
        });

        Schema::create('reunions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('universite_id')->constrained()->cascadeOnDelete();
            $t->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('titre');
            $t->dateTime('date_seance');
            $t->string('lieu')->nullable();
            $t->text('ordre_du_jour')->nullable();
            $t->text('pv')->nullable();
            $t->boolean('pv_publie')->default(false);
            $t->string('statut')->default('programmee'); // programmee | tenue | annulee
            $t->timestamps();
        });

        Schema::create('reunion_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reunion_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->boolean('present')->default(false);
            $t->timestamp('emarge_at')->nullable();
            $t->unique(['reunion_id', 'user_id']);
        });

        Schema::create('decisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('universite_id')->constrained()->cascadeOnDelete();
            $t->foreignId('reunion_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('ufr_id')->nullable()->constrained('ufrs')->nullOnDelete();
            $t->string('type'); // decision | recommandation | urgence
            $t->string('titre');
            $t->text('contenu');
            $t->date('echeance')->nullable();
            $t->string('statut')->default('a_faire'); // a_faire | en_cours | executee
            $t->timestamps();
        });

        Schema::create('journaux', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('universite_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('cible')->nullable();
            $t->text('details')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['journaux', 'decisions', 'reunion_user', 'reunions', 'avis', 'bilan_snapshots', 'bilan_historiques', 'bilan_observations', 'bilan_lignes', 'bilans', 'periodes', 'campagnes', 'annees_academiques', 'formations'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('departements', function (Blueprint $t) {
            if (Schema::hasColumn('departements', 'chef_id')) {
                $t->dropConstrainedForeignId('chef_id');
            }
        });
        Schema::dropIfExists('departements');
        Schema::dropIfExists('ufrs');
        Schema::dropIfExists('universites');
        Schema::enableForeignKeyConstraints();
    }
};

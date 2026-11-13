<?php

namespace Database\Seeders;

use App\Models\AnneeAcademique;
use App\Models\Bilan;
use App\Models\BilanHistorique;
use App\Models\BilanLigne;
use App\Models\Campagne;
use App\Models\Departement;
use App\Models\Formation;
use App\Models\Periode;
use App\Models\Ufr;
use App\Models\Universite;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $ujkz = Universite::firstOrCreate(['sigle' => 'UJKZ'], [
            'nom' => 'Université Joseph KI-ZERBO',
            'ville' => 'Ouagadougou',
            'email' => 'presidence@ujkz.bf',
        ]);

        $compte = function (string $email, array $attrs) {
            return User::updateOrCreate(['email' => $email], $attrs + ['password' => 'password', 'actif' => true]);
        };

        $compte('admin@ujkz.bf', ['name' => 'Administrateur système', 'role' => Roles::ADMIN, 'universite_id' => null, 'ufr_id' => null, 'departement_id' => null]);
        $compte('admin.ujkz@ujkz.bf', ['name' => 'Administrateur UJKZ', 'role' => Roles::ADMIN_UNIV, 'universite_id' => $ujkz->id, 'ufr_id' => null, 'departement_id' => null]);
        $compte('vp@ujkz.bf', ['name' => 'Vice-président', 'role' => Roles::VICE, 'universite_id' => $ujkz->id]);
        $compte('dip@ujkz.bf', ['name' => 'Directeur des Innovations Pédagogiques', 'role' => Roles::DIP, 'universite_id' => $ujkz->id]);

        $sds = Ufr::firstOrCreate(['universite_id' => $ujkz->id, 'sigle' => 'SDS'], ['type' => 'ufr', 'nom' => 'Sciences de la Santé']);
        $sea = Ufr::firstOrCreate(['universite_id' => $ujkz->id, 'sigle' => 'SEA'], ['type' => 'ufr', 'nom' => 'Sciences Exactes et Appliquées']);

        $info = Departement::firstOrCreate(['ufr_id' => $sea->id, 'nom' => 'Informatique']);
        $maths = Departement::firstOrCreate(['ufr_id' => $sea->id, 'nom' => 'Mathématiques']);

        $adjoint = $compte('adjoint@ujkz.bf', ['name' => 'Directeur adjoint UFR SEA', 'role' => Roles::ADJOINT, 'universite_id' => $ujkz->id, 'ufr_id' => $sea->id]);
        $chef = $compte('chef@ujkz.bf', ['name' => 'Chef département Informatique', 'role' => Roles::CHEF, 'universite_id' => $ujkz->id, 'ufr_id' => $sea->id, 'departement_id' => $info->id]);
        $chefMaths = $compte('maths@ujkz.bf', ['name' => 'Chef département Mathématiques', 'role' => Roles::CHEF, 'universite_id' => $ujkz->id, 'ufr_id' => $sea->id, 'departement_id' => $maths->id]);
        unset($sds);

        $sea->update(['directeur_id' => null, 'directeur_adjoint_id' => $adjoint->id]);
        $info->update(['chef_id' => $chef->id]);
        $maths->update(['chef_id' => $chefMaths->id]);

        $formations = [
            [$info->id, 'Informatique', 'MIAGE', 'L1', 'S1'],
            [$info->id, 'Informatique', 'MIAGE', 'L1', 'S2'],
            [$info->id, 'Informatique', 'RIMT', 'L2', 'S3'],
            [$maths->id, 'Mathématiques', 'Mathématiques appliquées', 'L1', 'S1'],
            [$maths->id, 'Mathématiques', 'Mathématiques appliquées', 'L2', 'S3'],
        ];
        foreach ($formations as $row) {
            Formation::firstOrCreate(
                ['departement_id' => $row[0], 'filiere' => $row[1], 'niveau' => $row[3], 'semestre' => $row[4]],
                ['parcours' => $row[2]]
            );
        }

        $annee = AnneeAcademique::firstOrCreate(
            ['universite_id' => $ujkz->id, 'libelle' => '2026-2027'],
            ['date_debut' => '2026-10-01', 'date_fin' => '2027-07-31', 'statut' => 'ouverte']
        );
        $campagne = Campagne::firstOrCreate(
            ['annee_academique_id' => $annee->id, 'libelle' => 'Suivi des enseignements 2026-2027'],
            ['date_debut' => '2026-10-01', 'date_fin' => '2027-06-30', 'statut' => 'ouverte']
        );
        $octobre = Periode::firstOrCreate(
            ['campagne_id' => $campagne->id, 'mois' => 10, 'annee' => 2026],
            ['libelle' => 'Octobre 2026', 'ouverte' => false]
        );
        $novembre = Periode::firstOrCreate(
            ['campagne_id' => $campagne->id, 'mois' => 11, 'annee' => 2026],
            ['libelle' => 'Novembre 2026', 'ouverte' => true]
        );

        $this->bilan($ujkz, $maths, $annee, $campagne, $octobre, 'officiel', [
            ['Mathématiques', 'Mathématiques appliquées', 'L1', 'S1', 12, 10, 4, 3, 1],
            ['Mathématiques', 'Mathématiques appliquées', 'L2', 'S3', 10, 8, 3, 2, 0],
        ]);
        $this->bilan($ujkz, $maths, $annee, $campagne, $novembre, 'officiel', [
            ['Mathématiques', 'Mathématiques appliquées', 'L1', 'S1', 12, 11, 4, 4, 1],
            ['Mathématiques', 'Mathématiques appliquées', 'L2', 'S3', 10, 9, 3, 2, 0],
        ]);
        $attente = $this->bilan($ujkz, $info, $annee, $campagne, $novembre, 'attente_adjoint', [
            ['Informatique', 'MIAGE', 'L1', 'S1', 14, 6, 5, 2, 0],
            ['Informatique', 'MIAGE', 'L1', 'S2', 14, 4, 5, 0, 0],
            ['Informatique', 'RIMT', 'L2', 'S3', 16, 7, 6, 1, 0],
        ]);
        $attente->update(['commentaire_chef' => 'Retard sur les TP de L1, salle informatique indisponible deux semaines.']);
    }

    private function bilan(Universite $u, Departement $dept, AnneeAcademique $annee, Campagne $campagne, Periode $periode, string $statut, array $lignes): Bilan
    {
        $formations = Formation::where('departement_id', $dept->id)->get();
        $existant = Bilan::where('departement_id', $dept->id)->where('periode_id', $periode->id)->first();
        if ($existant) {
            return $existant;
        }
        $bilan = Bilan::create([
            'universite_id' => $u->id,
            'departement_id' => $dept->id,
            'annee_academique_id' => $annee->id,
            'campagne_id' => $campagne->id,
            'periode_id' => $periode->id,
            'statut' => $statut,
            'transmis_at' => $statut === 'officiel' ? now() : null,
        ]);
        foreach ($lignes as $l) {
            $formation = $formations->first(fn ($f) => $f->filiere === $l[0] && $f->niveau === $l[2] && $f->semestre === $l[3]);
            BilanLigne::create([
                'bilan_id' => $bilan->id,
                'formation_id' => $formation?->id,
                'filiere' => $l[0],
                'parcours' => $l[1],
                'niveau' => $l[2],
                'semestre' => $l[3],
                'ec_total' => $l[4],
                'ec_effectues' => $l[5],
                'ec_a_evaluer' => $l[6],
                'ec_evalues' => $l[7],
                'deliberation' => $l[8],
            ]);
        }
        BilanHistorique::create([
            'bilan_id' => $bilan->id,
            'vers' => $statut,
            'commentaire' => 'Jeu de démonstration',
        ]);
        return $bilan;
    }
}

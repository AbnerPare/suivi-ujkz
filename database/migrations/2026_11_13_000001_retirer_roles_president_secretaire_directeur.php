<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $bilans = DB::table('bilans')->where('statut', 'attente_directeur')->pluck('id');
        DB::table('bilans')->whereIn('id', $bilans)->update(['statut' => 'retour_correction', 'updated_at' => now()]);
        foreach ($bilans as $id) {
            DB::table('bilan_historiques')->insert([
                'bilan_id' => $id,
                'user_id' => null,
                'de' => 'attente_directeur',
                'vers' => 'retour_correction',
                'commentaire' => 'Le rôle de directeur d\'UFR a été retiré. Le bilan revient au chef de département.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('ufrs')->update(['directeur_id' => null]);
        DB::table('users')->whereIn('role', ['president', 'secretaire', 'directeur_ufr'])->update(['actif' => false]);
    }

    public function down(): void
    {
        DB::table('users')->whereIn('role', ['president', 'secretaire', 'directeur_ufr'])->update(['actif' => true]);
    }
};

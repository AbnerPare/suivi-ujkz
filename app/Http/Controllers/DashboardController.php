<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\Avis;
use App\Models\Bilan;
use App\Models\BilanLigne;
use App\Models\Campagne;
use App\Models\Decision;
use App\Models\Departement;
use App\Models\Periode;
use App\Models\Reunion;
use App\Models\Ufr;
use App\Support\Roles;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $annees = AnneeAcademique::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('universite_id', $user->universite_id))
            ->orderByDesc('libelle')->get();
        $annee = $annees->firstWhere('id', (int) $request->query('annee_id')) ?? $annees->firstWhere('statut', 'ouverte') ?? $annees->first();

        $periodes = $annee
            ? Periode::whereHas('campagne', fn ($q) => $q->where('annee_academique_id', $annee->id))->orderBy('annee')->orderBy('mois')->get()
            : collect();
        $periode = $periodes->firstWhere('id', (int) $request->query('periode_id')) ?? $periodes->last();

        $base = Bilan::visibleTo($user)->when($annee, fn ($q) => $q->where('annee_academique_id', $annee->id));
        $duMois = (clone $base)->when($periode, fn ($q) => $q->where('periode_id', $periode->id))->with('lignes', 'departement.ufr')->get();
        $officiels = $duMois->where('statut', 'officiel');
        $source = $officiels->isNotEmpty() ? $officiels : $duMois->where('statut', '!=', 'brouillon');

        $taux = function ($bilans, string $kind) {
            $lignes = $bilans->flatMap->lignes;
            $den = $lignes->sum($kind === 'cours' ? 'ec_total' : 'ec_a_evaluer');
            $num = $lignes->sum($kind === 'cours' ? 'ec_effectues' : 'ec_evalues');
            return $den > 0 ? (int) round($num / $den * 100) : 0;
        };

        $stats = [
            'bilans' => $duMois->count(),
            'officiels' => $officiels->count(),
            'a_traiter' => $duMois->whereIn('statut', ['attente_adjoint', 'attente_directeur', 'retour_correction'])->count(),
            'taux_cours' => $taux($source, 'cours'),
            'taux_eval' => $taux($source, 'eval'),
            'retards' => $source->filter(fn ($b) => ($b->tauxCours() ?? 100) < 50)->count(),
        ];

        $parUfr = $source->groupBy(fn ($b) => $b->departement->ufr->sigle ?: $b->departement->ufr->nom)
            ->map(function ($group, $nom) use ($taux) {
                return ['nom' => $nom, 'cours' => $taux($group, 'cours'), 'eval' => $taux($group, 'eval')];
            })->values();

        $parMois = $periodes->map(function ($p) use ($user, $annee) {
            $bilans = Bilan::visibleTo($user)->where('annee_academique_id', $annee->id)->where('periode_id', $p->id)->where('statut', 'officiel')->with('lignes')->get();
            $lignes = $bilans->flatMap->lignes;
            $den = $lignes->sum('ec_total');
            return ['mois' => $p->libelle, 'taux' => $den > 0 ? (int) round($lignes->sum('ec_effectues') / $den * 100) : 0];
        });

        $file = Bilan::visibleTo($user)
            ->when($user->role === Roles::ADJOINT, fn ($q) => $q->where('statut', 'attente_adjoint'))
            ->when($user->role === Roles::DIRECTEUR, fn ($q) => $q->where('statut', 'attente_directeur'))
            ->when($user->role === Roles::CHEF, fn ($q) => $q->whereIn('statut', ['brouillon', 'retour_correction', 'corrige']))
            ->when($user->role === Roles::DIP, fn ($q) => $q->where('statut', 'officiel'))
            ->with('departement', 'periode')->latest('updated_at')->take(6)->get();

        $mesures = Decision::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('universite_id', $user->universite_id))
            ->where('type', 'urgence')->where('statut', '!=', 'executee')->latest()->take(4)->get();

        $nonLus = Avis::where('user_id', $user->id)->whereNull('lu_at')->count();
        $prochaine = Reunion::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('universite_id', $user->universite_id))
            ->where('statut', 'programmee')->where('date_seance', '>=', now())->orderBy('date_seance')->first();

        return view('dashboard', compact('stats', 'parUfr', 'parMois', 'file', 'mesures', 'annees', 'annee', 'periodes', 'periode', 'nonLus', 'prochaine', 'source'));
    }
}

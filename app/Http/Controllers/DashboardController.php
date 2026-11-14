<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\Avis;
use App\Models\Bilan;
use App\Models\BilanLigne;
use App\Models\Campagne;
use App\Models\Decision;
use App\Models\Departement;
use App\Models\Formation;
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
        $anneeBilans = (clone $base)->with('lignes', 'departement.ufr', 'periode')->get();
        $structure = $request->query('structure');
        if (! in_array($structure, ['ufr', 'institut'], true)) {
            $structure = '';
        }
        if ($structure !== '') {
            $anneeBilans = $anneeBilans
                ->filter(fn (Bilan $bilan) => $this->typeStructure($bilan) === $structure)
                ->values();
        }
        $duMois = $periode ? $anneeBilans->where('periode_id', $periode->id) : $anneeBilans;
        $officiels = $duMois->where('statut', 'officiel');
        $source = $officiels->isNotEmpty() ? $officiels : $duMois->where('statut', '!=', 'brouillon');
        $recents = $this->derniersBilans($anneeBilans);

        $taux = function ($bilans, string $kind) {
            $lignes = $bilans->flatMap->lignes;
            $den = $lignes->sum($kind === 'cours' ? 'ec_total' : 'ec_a_evaluer');
            $num = $lignes->sum($kind === 'cours' ? 'ec_effectues' : 'ec_evalues');
            return $den > 0 ? (int) round($num / $den * 100) : 0;
        };

        $stats = [
            'bilans' => $duMois->count(),
            'officiels' => $officiels->count(),
            'a_traiter' => $duMois->whereIn('statut', ['attente_adjoint', 'retour_correction'])->count(),
            'taux_cours' => $taux($source, 'cours'),
            'taux_eval' => $taux($source, 'eval'),
            'retards' => $source->filter(fn ($b) => ($b->tauxCours() ?? 100) < 50)->count(),
        ];

        $departementChef = $user->role === Roles::CHEF ? $user->departement : null;
        $cartesUfr = null;
        $ufrOuverte = null;
        if ($user->role === Roles::ADJOINT) {
            $graphiques = $recents->sortBy(fn ($b) => mb_strtolower($b->departement->nom))
                ->map(fn ($bilan) => $this->graphiqueDepartement($bilan, $taux))
                ->values();
        } elseif ($user->role === Roles::CHEF) {
            $graphiques = collect([[
                'titre' => trim(($departementChef?->nom ? 'Parcours du département '.$departementChef->nom : 'Comparaison des parcours').($recents->first()?->periode ? ' · '.$recents->first()->periode->libelle : '')),
                'barres' => $this->comparaisonParcours($recents, true),
            ]]);
        } elseif ($user->recoitBilansOfficiels()) {
            $cartesUfr = $recents->groupBy(fn ($b) => $b->departement->ufr_id)
                ->map(function ($groupe) use ($taux) {
                    $ufr = $groupe->first()->departement->ufr;

                    return [
                        'id' => $ufr->id,
                        'type' => $ufr->type === 'institut' ? 'institut' : 'ufr',
                        'nom' => trim(($ufr->sigle ? $ufr->sigle.' — ' : '').$ufr->nom),
                        'departements' => $groupe->count(),
                        'taux_cours' => $taux($groupe, 'cours'),
                        'taux_eval' => $taux($groupe, 'eval'),
                    ];
                })->sortBy([['type', 'desc'], ['nom', 'asc']])->values();
            $ufrOuverte = $request->filled('ufr_id')
                ? $cartesUfr->firstWhere('id', (int) $request->query('ufr_id'))
                : null;
            $graphiques = $ufrOuverte
                ? $recents->filter(fn ($b) => (int) $b->departement->ufr_id === (int) $ufrOuverte['id'])
                    ->sortBy(fn ($b) => mb_strtolower($b->departement->nom))
                    ->map(fn ($bilan) => $this->graphiqueDepartement($bilan, $taux))
                    ->values()
                : collect();
        } else {
            $graphiques = collect([[
                'titre' => 'Comparaison des UFR et instituts',
                'barres' => $recents->groupBy(fn ($b) => $b->departement->ufr->sigle ?: $b->departement->ufr->nom)
                    ->map(function ($group, $nom) use ($taux) {
                        return ['nom' => $nom, 'cours' => $taux($group, 'cours'), 'eval' => $taux($group, 'eval')];
                    })->values(),
            ]]);
        }

        $file = Bilan::visibleTo($user)
            ->when($user->role === Roles::ADJOINT, fn ($q) => $q->where('statut', 'attente_adjoint'))
            ->when($user->role === Roles::CHEF, fn ($q) => $q->whereIn('statut', ['brouillon', 'retour_correction', 'corrige']))
            ->when($user->recoitBilansOfficiels(), fn ($q) => $q->where('statut', 'officiel'))
            ->with('departement', 'periode')->latest('updated_at')->take(6)->get();

        $mesures = Decision::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('universite_id', $user->universite_id))
            ->where('type', 'urgence')->where('statut', '!=', 'executee')->latest()->take(4)->get();

        $etablissement = null;
        if ($user->universite_id && in_array($user->role, [Roles::VICE, Roles::DIP, Roles::ADMIN_UNIV], true)) {
            $structures = Ufr::withCount('departements')->where('universite_id', $user->universite_id)->get();
            $etablissement = [
                'nom' => $user->universite->nom,
                'sigle' => $user->universite->sigle,
                'ufrs' => $structures->where('type', 'ufr')->count(),
                'instituts' => $structures->where('type', 'institut')->count(),
                'departements' => (int) $structures->sum('departements_count'),
            ];
        }

        $nonLus = Avis::where('user_id', $user->id)->whereNull('lu_at')->count();
        $prochaine = Reunion::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('universite_id', $user->universite_id))
            ->where('statut', 'programmee')->where('date_seance', '>=', now())->orderBy('date_seance')->first();

        return view('dashboard', compact('stats', 'graphiques', 'cartesUfr', 'ufrOuverte', 'file', 'mesures', 'annees', 'annee', 'periodes', 'periode', 'nonLus', 'prochaine', 'source', 'departementChef', 'etablissement', 'structure'));
    }

    private function graphiqueDepartement(Bilan $bilan, callable $taux): array
    {
        $groupe = collect([$bilan]);

        return [
            'titre' => $bilan->departement->nom.' · '.$bilan->periode->libelle,
            'stats' => [
                'taux_cours' => $taux($groupe, 'cours'),
                'taux_eval' => $taux($groupe, 'eval'),
                'officiels' => $bilan->statut === 'officiel' ? 1 : 0,
                'bilans' => 1,
                'retards' => ($bilan->tauxCours() ?? 100) < 50 ? 1 : 0,
            ],
            'barres' => $this->comparaisonParcours($groupe, false),
        ];
    }

    private function derniersBilans($bilans)
    {
        return $bilans->groupBy('departement_id')->map(function ($groupe) {
            $transmis = $groupe->filter(fn (Bilan $bilan) => $bilan->statut !== 'brouillon');
            $pool = $transmis->isNotEmpty() ? $transmis : $groupe;

            return $pool->sortBy(fn (Bilan $bilan) => sprintf(
                '%04d%02d%010d',
                $bilan->periode->annee ?? 0,
                $bilan->periode->mois ?? 0,
                $bilan->id
            ))->last();
        })->filter()->values();
    }

    private function comparaisonParcours($bilans, bool $nommerDepartement = false)
    {
        $lignes = $bilans->flatMap(function (Bilan $bilan) {
            return $bilan->lignes->map(function (BilanLigne $ligne) use ($bilan) {
                $parcours = ($ligne->parcours && $ligne->niveau && $ligne->parcours !== $ligne->niveau)
                    ? $ligne->niveau
                    : ($ligne->parcours ?: $ligne->niveau);

                return [
                    'departement' => $bilan->departement->nom,
                    'filiere' => $ligne->filiere,
                    'parcours' => $parcours,
                    'deliberation' => (int) $ligne->deliberation,
                    'ordre' => $ligne->ordre,
                    'ec_total' => $ligne->ec_total,
                    'ec_effectues' => $ligne->ec_effectues,
                    'ec_a_evaluer' => $ligne->ec_a_evaluer,
                    'ec_evalues' => $ligne->ec_evalues,
                ];
            });
        });

        $groupes = $lignes->groupBy(fn (array $ligne) => $ligne['departement'].'|'.$ligne['filiere'].'|'.$ligne['parcours'])
            ->flatMap(function ($rows) {
                $premier = $rows->first();
                $semestres = Formation::semestresDuLibelle($premier['parcours']);

                return $rows->sortBy('ordre')->values()->map(function (array $row, int $index) use ($premier, $semestres) {
                    $cours = (int) $row['ec_total'];
                    $eval = (int) $row['ec_a_evaluer'];
                    $semestre = $semestres[$index] ?? ('S'.($index + 1));

                    return [
                        'departement' => $premier['departement'],
                        'filiere' => $premier['filiere'],
                        'parcours' => $premier['parcours'],
                        'semestre' => $semestre,
                        'ordre' => $row['ordre'],
                        'cours' => $cours > 0 ? (int) round($row['ec_effectues'] / $cours * 100) : 0,
                        'eval' => $eval > 0 ? (int) round($row['ec_evalues'] / $eval * 100) : 0,
                        'deliberations' => [[
                            'semestre' => $semestre,
                            'statut' => $this->statutDeliberation($row['deliberation']),
                        ]],
                    ];
                });
            })
            ->sortBy([['departement', 'asc'], ['ordre', 'asc']])
            ->values();

        $plusieursDepartements = $nommerDepartement || $groupes->pluck('departement')->unique()->count() > 1;
        $doublons = $groupes->groupBy(fn (array $ligne) => $ligne['departement'].'|'.$ligne['parcours'])
            ->filter(fn ($rows) => $rows->pluck('filiere')->unique()->count() > 1);

        return $groupes->map(function (array $ligne) use ($plusieursDepartements, $doublons) {
            $parties = [];
            if ($plusieursDepartements) {
                $parties[] = $ligne['departement'];
            }
            if ($doublons->has($ligne['departement'].'|'.$ligne['parcours'])) {
                $parties[] = $ligne['filiere'];
            }
            $parties[] = $ligne['parcours'];
            $parties[] = $ligne['semestre'];

            return [
                'nom' => $parties,
                'cours' => $ligne['cours'],
                'eval' => $ligne['eval'],
                'deliberations' => $ligne['deliberations'],
            ];
        })->values();
    }

    private function comparaisonDepartements($bilans)
    {
        return $bilans->map(function (Bilan $bilan) {
            $ufr = $bilan->departement->ufr->sigle ?: $bilan->departement->ufr->nom;

            return [
                'ordre' => $ufr.'|'.$bilan->departement->nom,
                'nom' => $ufr.' · '.$bilan->departement->nom,
                'cours' => $bilan->tauxCours() ?? 0,
                'eval' => $bilan->tauxEval() ?? 0,
            ];
        })->sortBy('ordre')->values()->map(fn (array $ligne) => [
            'nom' => $ligne['nom'],
            'cours' => $ligne['cours'],
            'eval' => $ligne['eval'],
        ])->values();
    }

    private function typeStructure(Bilan $bilan): string
    {
        return $bilan->departement->ufr->type === 'institut' ? 'institut' : 'ufr';
    }

    private function statutDeliberation(int $valeur): string
    {
        return match ($valeur) {
            1 => 'oui',
            2 => 's-an',
            default => 'non',
        };
    }
}

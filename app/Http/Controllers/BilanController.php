<?php

namespace App\Http\Controllers;

use App\Models\AnneeAcademique;
use App\Models\Bilan;
use App\Models\BilanHistorique;
use App\Models\BilanLigne;
use App\Models\BilanObservation;
use App\Models\BilanSnapshot;
use App\Models\Campagne;
use App\Models\Departement;
use App\Models\Periode;
use App\Models\User;
use App\Support\Notifier;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BilanController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->voitBilans(), 403);
        $user = $request->user();
        $bilans = Bilan::visibleTo($user)
            ->with(['departement.ufr.universite', 'periode', 'annee'])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('periode_id'), fn ($q) => $q->where('periode_id', $request->periode_id))
            ->when($user->recoitBilansOfficiels() && ! $request->filled('statut'), fn ($q) => $q->where('statut', 'officiel'));

        $periodes = $this->periodesVisibles($user);
        $groupes = null;
        if ($user->recoitBilansOfficiels() || $user->isAdmin() || $user->role === Roles::ADMIN_UNIV) {
            $groupes = $bilans->get()
                ->sortBy(fn ($bilan) => ($bilan->departement->ufr->sigle ?: $bilan->departement->ufr->nom).'|'.$bilan->departement->nom.'|'.$bilan->periode->annee.'|'.$bilan->periode->mois)
                ->groupBy(fn ($bilan) => $bilan->departement->ufr_id)
                ->map(function ($lignes) use ($user) {
                    $ufr = $lignes->first()->departement->ufr;
                    $nom = trim(($ufr->sigle ? $ufr->sigle.' — ' : '').$ufr->nom);
                    if ($user->isAdmin() && $ufr->universite) {
                        $nom = $ufr->universite->sigle.' · '.$nom;
                    }

                    return [
                        'id' => $ufr->id,
                        'ufr' => $nom,
                        'departements' => $lignes->pluck('departement_id')->unique()->count(),
                        'bilans' => $lignes->values(),
                    ];
                })->values();
        } else {
            $bilans = $bilans->latest('updated_at')->paginate(20)->withQueryString();
        }

        return view('bilans.index', compact('bilans', 'periodes', 'groupes'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === Roles::CHEF && $user->departement_id, 403, 'Seul un chef de département peut saisir un bilan.');
        $departement = Departement::with('formations', 'ufr')->findOrFail($user->departement_id);
        if ($departement->formations->isEmpty()) {
            return redirect()->route('formations.index')->with('ok', 'Dressez d\'abord le tableau de bilan de votre département : filières, niveaux et semestres.');
        }
        $periodes = Periode::where('ouverte', true)
            ->whereHas('campagne.annee', fn ($q) => $q->where('universite_id', $user->universite_id)->where('statut', 'ouverte'))
            ->whereHas('campagne', fn ($q) => $q->where('statut', 'ouverte'))
            ->orderByDesc('annee')->orderByDesc('mois')->get();

        return view('bilans.create', compact('departement', 'periodes'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === Roles::CHEF && $user->departement_id, 403);
        $data = $request->validate(['periode_id' => 'required|exists:periodes,id']);
        $periode = Periode::with('campagne.annee')->findOrFail($data['periode_id']);
        abort_unless($periode->ouverte && $periode->campagne->annee->universite_id === $user->universite_id, 403);
        abort_unless($periode->campagne->statut === 'ouverte' && $periode->campagne->annee->statut === 'ouverte', 422, 'La campagne ou l\'année académique est clôturée.');

        if (Bilan::where('departement_id', $user->departement_id)->where('periode_id', $periode->id)->exists()) {
            return back()->withErrors(['periode_id' => 'Un bilan existe déjà pour cette période.']);
        }

        $departement = Departement::with('formations')->findOrFail($user->departement_id);
        if ($departement->formations->isEmpty()) {
            return redirect()->route('formations.index')->with('ok', 'Dressez d\'abord le tableau de bilan de votre département.');
        }
        $bilan = DB::transaction(function () use ($user, $periode, $departement) {
            $bilan = Bilan::create([
                'universite_id' => $user->universite_id,
                'departement_id' => $departement->id,
                'annee_academique_id' => $periode->campagne->annee_academique_id,
                'campagne_id' => $periode->campagne_id,
                'periode_id' => $periode->id,
                'statut' => 'brouillon',
            ]);
            foreach ($departement->formations as $f) {
                $bilan->lignes()->create([
                    'formation_id' => $f->id,
                    'filiere' => $f->filiere,
                    'parcours' => $f->parcours,
                    'niveau' => $f->niveau,
                    'semestre' => $f->semestre,
                    'ordre' => $f->ordre,
                ]);
            }
            BilanHistorique::create(['bilan_id' => $bilan->id, 'user_id' => $user->id, 'de' => null, 'vers' => 'brouillon', 'commentaire' => 'Création du bilan']);
            return $bilan;
        });
        Notifier::journal($user, 'Création d\'un bilan', 'Bilan #'.$bilan->id, $departement->nom.' · '.$periode->libelle);

        return redirect()->route('bilans.edit', $bilan)->with('ok', 'Bilan créé. Renseignez le tableau de suivi.');
    }

    public function edit(Request $request, Bilan $bilan)
    {
        $this->authorizeView($request->user(), $bilan);
        $bilan->load(['lignes', 'observations.auteur', 'historiques.auteur', 'snapshots.auteur', 'departement.ufr', 'periode', 'annee']);
        $groupes = [];
        foreach ($bilan->lignes as $ligne) {
            $nom = $ligne->filiere;
            $label = ($ligne->parcours && $ligne->niveau && $ligne->parcours !== $ligne->niveau)
                ? $ligne->niveau
                : ($ligne->parcours ?: $ligne->niveau);
            if (! isset($groupes[$nom])) {
                $groupes[$nom] = ['nom' => $nom, 'rows' => 0, 'parcours' => []];
            }
            if (! isset($groupes[$nom]['parcours'][$label])) {
                $groupes[$nom]['parcours'][$label] = ['nom' => $label, 'lignes' => []];
            }
            $groupes[$nom]['parcours'][$label]['lignes'][] = $ligne;
            $groupes[$nom]['rows']++;
        }

        return view('bilans.edit', compact('bilan', 'groupes'));
    }

    public function update(Request $request, Bilan $bilan)
    {
        $user = $request->user();
        abort_unless($bilan->editablePar($user), 403, 'Ce bilan n\'est pas modifiable.');
        abort_unless($bilan->periode->ouverte, 422, 'La période de suivi est clôturée.');
        $data = $request->validate([
            'commentaire_chef' => 'nullable|string|max:5000',
            'lignes' => 'required|array',
            'lignes.*.ec_total' => 'required|integer|min:0|max:999',
            'lignes.*.ec_effectues' => 'required|integer|min:0|max:999',
            'lignes.*.ec_a_evaluer' => 'required|integer|min:0|max:999',
            'lignes.*.ec_evalues' => 'required|integer|min:0|max:999',
            'lignes.*.deliberation' => 'required|integer|in:0,1,2',
            'lignes.*.observations' => 'nullable|string|max:2000',
        ]);

        $erreurs = [];
        foreach ($data['lignes'] as $id => $row) {
            if ($row['ec_effectues'] > $row['ec_total']) {
                $erreurs["lignes.$id.ec_effectues"] = 'Les éléments effectués ne peuvent pas dépasser le total prévu.';
            }
            if ($row['ec_evalues'] > $row['ec_a_evaluer']) {
                $erreurs["lignes.$id.ec_evalues"] = 'Les éléments évalués ne peuvent pas dépasser le total à évaluer.';
            }
        }
        if ($erreurs !== []) {
            throw ValidationException::withMessages($erreurs);
        }

        foreach ($data['lignes'] as $id => $row) {
            $ligne = BilanLigne::where('bilan_id', $bilan->id)->find($id);
            if ($ligne) {
                $ligne->update($row);
            }
        }
        $statut = $bilan->statut === 'retour_correction' ? 'corrige' : $bilan->statut;
        $bilan->update(['commentaire_chef' => $data['commentaire_chef'] ?? null, 'statut' => $statut]);
        if ($bilan->statut !== $statut || $statut === 'corrige') {
            // historique only when entering corrige
        }
        if ($statut === 'corrige' && $bilan->wasChanged('statut')) {
            BilanHistorique::create(['bilan_id' => $bilan->id, 'user_id' => $user->id, 'de' => 'retour_correction', 'vers' => 'corrige', 'commentaire' => 'Corrections enregistrées']);
        }
        Notifier::journal($user, 'Mise à jour d\'un bilan', 'Bilan #'.$bilan->id);

        return back()->with('ok', 'Bilan enregistré.');
    }

    public function transmettre(Request $request, Bilan $bilan)
    {
        $user = $request->user();
        abort_unless($bilan->editablePar($user), 403);
        $bilan->load('departement.ufr', 'lignes', 'periode');

        if ($bilan->statut === 'brouillon') {
            $adjoint = User::where('role', Roles::ADJOINT)->where('ufr_id', $bilan->departement->ufr_id)->where('actif', true)->get();
            abort_if($adjoint->isEmpty(), 422, 'Aucun directeur adjoint n\'est affecté à cette UFR.');
            $this->changerStatut($bilan, $user, 'attente_adjoint', 'Transmission au directeur adjoint');
            Notifier::send($adjoint, 'Bilan à examiner', $bilan->departement->nom.' a transmis le bilan '.$bilan->periode->libelle.'.', route('bilans.edit', $bilan));
            return redirect()->route('bilans.edit', $bilan)->with('ok', 'Bilan transmis au directeur adjoint.');
        }

        if (in_array($bilan->statut, ['retour_correction', 'corrige'], true)) {
            $dip = User::where('role', Roles::DIP)->where('universite_id', $bilan->universite_id)->where('actif', true)->get();
            abort_if($dip->isEmpty(), 422, 'Aucun Directeur des Innovations Pédagogiques n\'est enregistré.');
            $destinataires = $dip->concat(
                User::where('role', Roles::VICE)->where('universite_id', $bilan->universite_id)->where('actif', true)->get()
            );
            DB::transaction(function () use ($bilan, $user) {
                BilanSnapshot::create([
                    'bilan_id' => $bilan->id,
                    'user_id' => $user->id,
                    'version' => $bilan->version,
                    'motif' => 'Transmission officielle',
                    'donnees' => $bilan->lignes->toArray(),
                ]);
                $de = $bilan->statut;
                $bilan->update(['statut' => 'officiel', 'transmis_at' => now()]);
                BilanHistorique::create(['bilan_id' => $bilan->id, 'user_id' => $user->id, 'de' => $de, 'vers' => 'officiel', 'commentaire' => 'Transmission officielle']);
            });
            Notifier::send($destinataires, 'Bilan officiel transmis', $bilan->departement->nom.' · '.$bilan->periode->libelle, route('bilans.edit', $bilan));
            Notifier::journal($user, 'Transmission officielle', 'Bilan #'.$bilan->id);
            return redirect()->route('bilans.edit', $bilan)->with('ok', 'Bilan officiel transmis au Directeur des Innovations Pédagogiques et au vice-président.');
        }

        abort(422, 'Ce bilan ne peut pas être transmis dans son état actuel.');
    }

    public function examiner(Request $request, Bilan $bilan)
    {
        $user = $request->user();
        $bilan->load('departement.ufr', 'periode');
        abort_unless($bilan->examinablePar($user), 403);
        $data = $request->validate(['contenu' => 'nullable|string|max:5000']);

        $chef = User::where('role', Roles::CHEF)->where('departement_id', $bilan->departement_id)->where('actif', true)->get();
        DB::transaction(function () use ($bilan, $user, $data) {
            if (filled($data['contenu'] ?? null)) {
                BilanObservation::create(['bilan_id' => $bilan->id, 'user_id' => $user->id, 'role' => $user->role, 'contenu' => $data['contenu']]);
            }
            $this->changerStatut($bilan, $user, 'retour_correction', 'Validation du directeur adjoint et retour au chef de département');
        });
        Notifier::send($chef, 'Bilan retourné avec les observations', 'Le directeur adjoint a examiné le bilan '.$bilan->periode->libelle.'.', route('bilans.edit', $bilan));
        return back()->with('ok', 'Bilan retourné au chef de département.');
    }

    public function rouvrir(Request $request, Bilan $bilan)
    {
        $user = $request->user();
        abort_unless($user->recoitBilansOfficiels() || $user->isAdmin(), 403);
        abort_unless($bilan->statut === 'officiel', 422, 'Seul un bilan officiel peut être rouvert.');
        if (! $user->isAdmin()) {
            abort_unless((int) $user->universite_id === (int) $bilan->universite_id, 403);
        }
        $data = $request->validate(['motif' => 'required|string|max:2000']);
        $chef = User::where('role', Roles::CHEF)->where('departement_id', $bilan->departement_id)->where('actif', true)->get();

        DB::transaction(function () use ($bilan, $user, $data) {
            $bilan->load('lignes');
            BilanSnapshot::create([
                'bilan_id' => $bilan->id,
                'user_id' => $user->id,
                'version' => $bilan->version,
                'motif' => 'Version officielle conservée avant réouverture : '.$data['motif'],
                'donnees' => $bilan->lignes->toArray(),
            ]);
            $bilan->update([
                'statut' => 'retour_correction',
                'motif_reouverture' => $data['motif'],
                'version' => $bilan->version + 1,
                'transmis_at' => null,
            ]);
            BilanHistorique::create(['bilan_id' => $bilan->id, 'user_id' => $user->id, 'de' => 'officiel', 'vers' => 'retour_correction', 'commentaire' => 'Réouverture : '.$data['motif']]);
        });
        Notifier::send($chef, 'Bilan rouvert', 'Le bilan officiel a été rouvert. Motif : '.$data['motif'], route('bilans.edit', $bilan));
        Notifier::journal($user, 'Réouverture d\'un bilan', 'Bilan #'.$bilan->id, $data['motif']);

        return back()->with('ok', 'Bilan rouvert. Le chef de département est notifié. La version officielle précédente est conservée.');
    }

    private function changerStatut(Bilan $bilan, User $user, string $vers, string $commentaire): void
    {
        $de = $bilan->statut;
        $bilan->update(['statut' => $vers]);
        BilanHistorique::create(['bilan_id' => $bilan->id, 'user_id' => $user->id, 'de' => $de, 'vers' => $vers, 'commentaire' => $commentaire]);
        Notifier::journal($user, $commentaire, 'Bilan #'.$bilan->id);
    }

    private function authorizeView(User $user, Bilan $bilan): void
    {
        abort_unless(Bilan::visibleTo($user)->whereKey($bilan->id)->exists(), 403);
    }

    private function periodesVisibles(User $user)
    {
        return Periode::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('campagne.annee', fn ($a) => $a->where('universite_id', $user->universite_id)))
            ->orderByDesc('annee')->orderByDesc('mois')->get();
    }
}

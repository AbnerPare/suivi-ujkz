<?php

namespace App\Http\Controllers;

use App\Models\Bilan;
use App\Models\BilanLigne;
use App\Models\Departement;
use App\Models\Formation;
use App\Models\Ufr;
use App\Models\Universite;
use App\Models\User;
use App\Support\Notifier;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StructureController extends Controller
{
    public function ufrs(Request $request)
    {
        $this->assert($request);
        if ($choix = $this->choixUniversite($request)) {
            return $choix;
        }
        $universiteId = $this->universiteId($request);
        $ufrs = Ufr::with(['directeur', 'adjoint', 'departements'])->where('universite_id', $universiteId)->orderBy('nom')->get();
        $personnes = User::where('universite_id', $universiteId)->where('actif', true)->orderBy('name')->get();
        return view('structures.ufrs', compact('ufrs', 'personnes', 'universiteId'));
    }

    public function storeUfr(Request $request)
    {
        $this->assert($request, true);
        $data = $request->validate([
            'nom' => 'required|string|max:180',
            'sigle' => 'nullable|string|max:20',
            'type' => 'required|in:ufr,institut',
            'directeur_adjoint_id' => 'nullable|exists:users,id',
        ]);
        $data['universite_id'] = $this->universiteId($request);
        $data['directeur_id'] = null;
        $ufr = Ufr::create($data);
        $this->syncResponsables($ufr);
        Notifier::journal($request->user(), 'Création d\'une UFR', $ufr->nom);
        return back()->with('ok', 'UFR ou institut enregistré.');
    }

    public function updateUfr(Request $request, Ufr $ufr)
    {
        $this->assert($request, true);
        abort_unless((int) $ufr->universite_id === $this->universiteId($request) || $request->user()->isAdmin(), 403);
        $data = $request->validate([
            'nom' => 'required|string|max:180',
            'sigle' => 'nullable|string|max:20',
            'type' => 'required|in:ufr,institut',
            'directeur_adjoint_id' => 'nullable|exists:users,id',
        ]);
        $data['directeur_id'] = null;
        $ufr->update($data);
        $this->syncResponsables($ufr);
        return back()->with('ok', 'UFR mise à jour.');
    }

    public function destroyUfr(Request $request, Ufr $ufr)
    {
        $this->assert($request, true);
        abort_unless((int) $ufr->universite_id === $this->universiteId($request) || $request->user()->isAdmin(), 403);
        $ufr->delete();
        return back()->with('ok', 'UFR supprimée.');
    }

    public function departements(Request $request)
    {
        $this->assert($request);
        if ($choix = $this->choixUniversite($request)) {
            return $choix;
        }
        $universiteId = $this->universiteId($request);
        $ufrs = Ufr::with('departements.chef')->where('universite_id', $universiteId)->orderBy('nom')->get();
        $personnes = User::where('universite_id', $universiteId)->where('actif', true)->orderBy('name')->get();
        return view('structures.departements', compact('ufrs', 'personnes'));
    }

    public function storeDepartement(Request $request)
    {
        $this->assert($request, true);
        $data = $request->validate([
            'ufr_id' => 'required|exists:ufrs,id',
            'nom' => 'required|string|max:180',
            'chef_id' => 'nullable|exists:users,id',
            'adjoint_id' => 'nullable|exists:users,id',
        ]);
        $ufr = Ufr::findOrFail($data['ufr_id']);
        abort_unless((int) $ufr->universite_id === $this->universiteId($request) || $request->user()->isAdmin(), 403);
        $dept = Departement::create($data);
        $this->syncChef($dept);
        Notifier::journal($request->user(), 'Création d\'un département', $dept->nom);
        return back()->with('ok', 'Département enregistré.');
    }

    public function updateDepartement(Request $request, Departement $departement)
    {
        $this->assert($request, true);
        abort_unless((int) $departement->ufr->universite_id === $this->universiteId($request) || $request->user()->isAdmin(), 403);
        $data = $request->validate([
            'nom' => 'required|string|max:180',
            'chef_id' => 'nullable|exists:users,id',
            'adjoint_id' => 'nullable|exists:users,id',
        ]);
        $departement->update($data);
        $this->syncChef($departement);
        return back()->with('ok', 'Département mis à jour.');
    }

    public function destroyDepartement(Request $request, Departement $departement)
    {
        $this->assert($request, true);
        abort_unless((int) $departement->ufr->universite_id === $this->universiteId($request) || $request->user()->isAdmin(), 403);
        $departement->delete();
        return back()->with('ok', 'Département supprimé.');
    }

    public function formations(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, [Roles::ADMIN, Roles::ADMIN_UNIV, Roles::CHEF, Roles::ADJOINT], true), 403);
        $departements = Departement::with('ufr')
            ->whereHas('ufr', fn ($q) => $user->isAdmin() ? $q : $q->where('universite_id', $user->universite_id))
            ->when($user->role === Roles::CHEF, fn ($q) => $q->where('id', $user->departement_id))
            ->when($user->role === Roles::ADJOINT, fn ($q) => $q->where('ufr_id', $user->ufr_id))
            ->orderBy('nom')->get();
        $filtre = $request->integer('departement_id') ?: $departements->first()?->id;
        $formations = Formation::where('departement_id', $filtre)->orderBy('ordre')->orderBy('id')->get();
        $structure = Formation::structureDepuis($formations);
        $peutDresser = in_array($user->role, [Roles::ADMIN, Roles::ADMIN_UNIV, Roles::CHEF], true) && (bool) $filtre;

        return view('structures.formations', compact('departements', 'formations', 'filtre', 'structure', 'peutDresser'));
    }

    public function storeTableau(Request $request)
    {
        $data = $request->validate([
            'departement_id' => 'required|exists:departements,id',
            'filieres' => 'required|array|min:1|max:30',
            'filieres.*.nom' => 'required|string|max:180',
            'filieres.*.niveaux' => 'required|array|min:1|max:8',
            'filieres.*.niveaux.*' => 'required|string|max:80',
        ]);

        $dept = Departement::with('ufr')->findOrFail($data['departement_id']);
        $this->assertFormation($request, $dept);

        $noms = [];
        foreach ($data['filieres'] as $i => $filiere) {
            $nom = trim($filiere['nom']);
            $clef = mb_strtolower($nom);
            if (isset($noms[$clef])) {
                throw ValidationException::withMessages([
                    "filieres.$i.nom" => 'Cette filière est déjà dans le tableau.',
                ]);
            }
            $noms[$clef] = true;
            $labels = [];
            foreach ($filiere['niveaux'] as $j => $label) {
                $label = trim($label);
                $clefLabel = mb_strtolower($label);
                if (isset($labels[$clefLabel])) {
                    throw ValidationException::withMessages([
                        "filieres.$i.niveaux.$j" => 'Ce libellé est déjà utilisé dans la filière.',
                    ]);
                }
                $labels[$clefLabel] = true;
                $data['filieres'][$i]['niveaux'][$j] = $label;
            }
            $data['filieres'][$i]['nom'] = $nom;
        }

        DB::transaction(function () use ($dept, $data) {
            Formation::where('departement_id', $dept->id)->delete();
            $ordre = 0;
            $creees = [];
            foreach ($data['filieres'] as $filiere) {
                foreach (array_values($filiere['niveaux']) as $rang => $label) {
                    foreach (Formation::semestresDuLibelle($label, $rang + 1) as $semestre) {
                        $creees[] = Formation::create([
                            'departement_id' => $dept->id,
                            'filiere' => $filiere['nom'],
                            'parcours' => $label,
                            'niveau' => $label,
                            'semestre' => $semestre,
                            'ordre' => $ordre++,
                        ]);
                    }
                }
            }
            $this->realignerBrouillons($dept, $creees);
        });

        Notifier::journal($request->user(), 'Tableau de bilan dressé', $dept->nom, count($data['filieres']).' filière(s)');

        return redirect()
            ->route('formations.index', ['departement_id' => $dept->id])
            ->with('ok', 'Tableau de bilan enregistré. Les semestres sont en place : vous pouvez renseigner le suivi.');
    }

    public function storeFormation(Request $request)
    {
        $data = $request->validate([
            'departement_id' => 'required|exists:departements,id',
            'filiere' => 'required|string|max:180',
            'parcours' => 'nullable|string|max:180',
            'niveau' => ['required', Rule::in(Formation::NIVEAUX)],
            'semestre' => 'required|string|max:4',
        ]);
        $dept = Departement::with('ufr')->findOrFail($data['departement_id']);
        $this->assertFormation($request, $dept);
        abort_unless(in_array($data['semestre'], Formation::semestresDuLibelle($data['niveau']), true), 422, 'Ce semestre ne correspond pas au niveau.');
        Formation::create($data);
        return back()->with('ok', 'Formation ajoutée.');
    }

    public function destroyFormation(Request $request, Formation $formation)
    {
        $this->assertFormation($request, $formation->departement);
        $formation->delete();
        return back()->with('ok', 'Formation supprimée.');
    }

    private function choixUniversite(Request $request)
    {
        if ($request->user()->isAdmin() && ! $request->query('universite_id')) {
            $universites = Universite::orderBy('nom')->get();
            return view('structures.choisir', compact('universites'));
        }
        return null;
    }

    private function assert(Request $request, bool $write = false): void
    {
        $roles = [Roles::ADMIN, Roles::ADMIN_UNIV];
        abort_unless(in_array($request->user()->role, $roles, true), 403);
        if (! $request->user()->isAdmin()) {
            abort_unless($request->user()->universite_id, 403);
        }
    }

    private function universiteId(Request $request): int
    {
        if ($request->user()->isAdmin()) {
            $id = (int) ($request->input('universite_id') ?: $request->query('universite_id'));
            abort_unless($id, 422, 'Choisissez une université.');
            return $id;
        }
        return (int) $request->user()->universite_id;
    }

    private function assertFormation(Request $request, Departement $dept): void
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return;
        }
        abort_unless((int) $dept->ufr->universite_id === (int) $user->universite_id, 403);
        if ($user->role === Roles::CHEF) {
            abort_unless((int) $user->departement_id === (int) $dept->id, 403);
        }
        abort_unless(in_array($user->role, [Roles::ADMIN_UNIV, Roles::CHEF], true), 403);
    }

    private function syncResponsables(Ufr $ufr): void
    {
        if ($ufr->directeur_adjoint_id) {
            User::where('role', Roles::ADJOINT)->where('ufr_id', $ufr->id)->where('id', '!=', $ufr->directeur_adjoint_id)->update(['ufr_id' => null]);
            Ufr::where('directeur_adjoint_id', $ufr->directeur_adjoint_id)->where('id', '!=', $ufr->id)->update(['directeur_adjoint_id' => null]);
            User::whereKey($ufr->directeur_adjoint_id)->update([
                'role' => Roles::ADJOINT,
                'ufr_id' => $ufr->id,
                'departement_id' => null,
                'universite_id' => $ufr->universite_id,
            ]);
        }
    }

    private function realignerBrouillons(Departement $dept, array $formations): void
    {
        $bilans = Bilan::where('departement_id', $dept->id)
            ->whereIn('statut', ['brouillon', 'retour_correction', 'corrige'])
            ->with('lignes')
            ->get();

        foreach ($bilans as $bilan) {
            $parLibelle = $bilan->lignes->keyBy(
                fn ($ligne) => mb_strtolower($ligne->filiere.'|'.($ligne->parcours ?: $ligne->niveau).'|'.$ligne->semestre)
            );
            $parNiveau = $bilan->lignes->keyBy(
                fn ($ligne) => mb_strtolower($ligne->filiere.'|'.$ligne->niveau.'|'.$ligne->semestre)
            );
            $bilan->lignes()->delete();
            foreach ($formations as $formation) {
                $clef = mb_strtolower($formation->filiere.'|'.$formation->parcours.'|'.$formation->semestre);
                $precedente = $parLibelle->get($clef) ?? $parNiveau->get($clef);
                BilanLigne::create([
                    'bilan_id' => $bilan->id,
                    'formation_id' => $formation->id,
                    'filiere' => $formation->filiere,
                    'parcours' => $formation->parcours,
                    'niveau' => $formation->niveau,
                    'semestre' => $formation->semestre,
                    'ordre' => $formation->ordre,
                    'ec_total' => $precedente?->ec_total ?? 0,
                    'ec_effectues' => $precedente?->ec_effectues ?? 0,
                    'ec_a_evaluer' => $precedente?->ec_a_evaluer ?? 0,
                    'ec_evalues' => $precedente?->ec_evalues ?? 0,
                    'deliberation' => $precedente?->deliberation ?? 0,
                    'observations' => $precedente?->observations,
                ]);
            }
        }
    }

    private function syncChef(Departement $dept): void
    {
        $dept->load('ufr');
        if ($dept->chef_id) {
            User::where('role', Roles::CHEF)->where('departement_id', $dept->id)->where('id', '!=', $dept->chef_id)->update(['departement_id' => null]);
            Departement::where('chef_id', $dept->chef_id)->where('id', '!=', $dept->id)->update(['chef_id' => null]);
            User::whereKey($dept->chef_id)->update([
                'role' => Roles::CHEF,
                'departement_id' => $dept->id,
                'ufr_id' => $dept->ufr_id,
                'universite_id' => $dept->ufr->universite_id,
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Formation;
use App\Models\Ufr;
use App\Models\Universite;
use App\Models\User;
use App\Support\Notifier;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        return view('structures.ufrs', compact('ufrs', 'personnes'));
    }

    public function storeUfr(Request $request)
    {
        $this->assert($request, true);
        $data = $request->validate([
            'nom' => 'required|string|max:180',
            'sigle' => 'nullable|string|max:20',
            'type' => 'required|in:ufr,institut',
            'directeur_id' => 'nullable|exists:users,id',
            'directeur_adjoint_id' => 'nullable|exists:users,id',
        ]);
        $data['universite_id'] = $this->universiteId($request);
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
            'directeur_id' => 'nullable|exists:users,id',
            'directeur_adjoint_id' => 'nullable|exists:users,id',
        ]);
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
        abort_unless(in_array($user->role, [Roles::ADMIN, Roles::PRESIDENT, Roles::CHEF, Roles::DIRECTEUR, Roles::ADJOINT], true), 403);
        $departements = Departement::with('ufr')
            ->whereHas('ufr', fn ($q) => $user->isAdmin() ? $q : $q->where('universite_id', $user->universite_id))
            ->when($user->role === Roles::CHEF, fn ($q) => $q->where('id', $user->departement_id))
            ->when(in_array($user->role, [Roles::DIRECTEUR, Roles::ADJOINT], true), fn ($q) => $q->where('ufr_id', $user->ufr_id))
            ->orderBy('nom')->get();
        $filtre = $request->integer('departement_id') ?: $departements->first()?->id;
        $formations = Formation::where('departement_id', $filtre)->orderBy('filiere')->orderBy('niveau')->get();
        return view('structures.formations', compact('departements', 'formations', 'filtre'));
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
        abort_unless(in_array($data['semestre'], Formation::SEMESTRES[$data['niveau']], true), 422, 'Ce semestre ne correspond pas au niveau.');
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
        $roles = $write ? [Roles::ADMIN, Roles::PRESIDENT] : [Roles::ADMIN, Roles::PRESIDENT, Roles::DIP, Roles::DIRECTEUR, Roles::VICE];
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
        abort_unless(in_array($user->role, [Roles::PRESIDENT, Roles::CHEF], true), 403);
    }

    private function syncResponsables(Ufr $ufr): void
    {
        if ($ufr->directeur_id) {
            User::whereKey($ufr->directeur_id)->update(['role' => Roles::DIRECTEUR, 'ufr_id' => $ufr->id, 'universite_id' => $ufr->universite_id]);
        }
        if ($ufr->directeur_adjoint_id) {
            User::whereKey($ufr->directeur_adjoint_id)->update(['role' => Roles::ADJOINT, 'ufr_id' => $ufr->id, 'universite_id' => $ufr->universite_id]);
        }
    }

    private function syncChef(Departement $dept): void
    {
        $dept->load('ufr');
        if ($dept->chef_id) {
            User::whereKey($dept->chef_id)->update([
                'role' => Roles::CHEF,
                'departement_id' => $dept->id,
                'ufr_id' => $dept->ufr_id,
                'universite_id' => $dept->ufr->universite_id,
            ]);
        }
    }
}

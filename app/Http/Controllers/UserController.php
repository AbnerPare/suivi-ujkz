<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Ufr;
use App\Models\Universite;
use App\Models\User;
use App\Support\Notifier;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->gereStructures(), 403);
        $users = User::with(['universite', 'ufr', 'departement'])
            ->whereIn('role', array_keys(Roles::ALL))
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('universite_id', $request->user()->universite_id))
            ->orderBy('name')->paginate(30);
        $universites = $request->user()->isAdmin() ? Universite::orderBy('nom')->get() : collect([$request->user()->universite]);
        $ufrs = Ufr::query()->when(! $request->user()->isAdmin(), fn ($q) => $q->where('universite_id', $request->user()->universite_id))->orderBy('nom')->get();
        $departements = Departement::with('ufr')->whereHas('ufr', function ($q) use ($request) {
            if (! $request->user()->isAdmin()) {
                $q->where('universite_id', $request->user()->universite_id);
            }
        })->orderBy('nom')->get();
        $roles = $this->rolesAutorises($request->user());
        return view('users.index', compact('users', 'universites', 'ufrs', 'departements', 'roles'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->gereStructures(), 403);
        $roles = array_keys($this->rolesAutorises($request->user()));
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in($roles)],
            'universite_id' => 'nullable|exists:universites,id',
            'ufr_id' => 'nullable|exists:ufrs,id',
            'departement_id' => 'nullable|exists:departements,id',
        ]);
        if (! $request->user()->isAdmin()) {
            $data['universite_id'] = $request->user()->universite_id;
        }
        $data = $this->limiterAuPerimetre($data);
        $this->verifierRattachement($request->user(), $data);
        $user = User::create($data);
        $this->rattacher($user);
        Notifier::journal($request->user(), 'Création d\'un compte', $user->email, Roles::label($user->role));
        return back()->with('ok', 'Compte créé.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()->gereStructures(), 403);
        if (! $request->user()->isAdmin()) {
            abort_unless((int) $user->universite_id === (int) $request->user()->universite_id, 403);
            abort_unless(array_key_exists($user->role, $this->rolesAutorises($request->user())), 403);
        }
        $roles = array_keys($this->rolesAutorises($request->user()));
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'role' => ['required', Rule::in($roles)],
            'ufr_id' => 'nullable|exists:ufrs,id',
            'departement_id' => 'nullable|exists:departements,id',
            'actif' => 'required|boolean',
            'password' => 'nullable|string|min:8',
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $data['universite_id'] = $user->universite_id;
        $data = $this->limiterAuPerimetre($data);
        $this->verifierRattachement($request->user(), $data);
        $user->update($data);
        $this->rattacher($user->fresh());
        Notifier::journal($request->user(), $user->actif ? 'Mise à jour d\'un compte' : 'Suspension d\'un compte', $user->email);
        return back()->with('ok', 'Compte mis à jour.');
    }

    private function rolesAutorises(User $acteur): array
    {
        if ($acteur->isAdmin()) {
            return Roles::ALL;
        }

        return array_intersect_key(Roles::ALL, array_flip([
            Roles::VICE, Roles::DIP, Roles::ADJOINT, Roles::CHEF,
        ]));
    }

    private function limiterAuPerimetre(array $data): array
    {
        if (in_array($data['role'] ?? null, [Roles::VICE, Roles::DIP, Roles::ADMIN_UNIV], true)) {
            $data['ufr_id'] = null;
            $data['departement_id'] = null;
        }

        return $data;
    }

    private function verifierRattachement(User $acteur, array $data): void
    {
        $universiteId = $acteur->isAdmin() ? ($data['universite_id'] ?? null) : $acteur->universite_id;
        if (($data['role'] ?? null) !== Roles::ADMIN && empty($universiteId)) {
            throw ValidationException::withMessages([
                'universite_id' => 'Une université est obligatoire pour ce rôle.',
            ]);
        }
        if (($data['role'] ?? null) === Roles::CHEF) {
            if (empty($data['departement_id'])) {
                throw ValidationException::withMessages([
                    'departement_id' => 'Choisissez le département dont cette personne est responsable.',
                ]);
            }
            $departement = Departement::with('ufr')->findOrFail($data['departement_id']);
            if ((int) $departement->ufr->universite_id !== (int) $universiteId) {
                throw ValidationException::withMessages([
                    'departement_id' => 'Ce département n\'appartient pas à cette université.',
                ]);
            }
        }
        if (($data['role'] ?? null) === Roles::ADJOINT) {
            if (empty($data['ufr_id'])) {
                throw ValidationException::withMessages([
                    'ufr_id' => 'Choisissez l\'UFR dont cette personne est directeur adjoint.',
                ]);
            }
            $ufr = Ufr::findOrFail($data['ufr_id']);
            if ((int) $ufr->universite_id !== (int) $universiteId) {
                throw ValidationException::withMessages([
                    'ufr_id' => 'Cette UFR n\'appartient pas à cette université.',
                ]);
            }
        }
    }

    private function rattacher(User $user): void
    {
        if ($user->role === Roles::CHEF && $user->departement_id) {
            $departement = Departement::with('ufr')->find($user->departement_id);
            if ($departement) {
                Ufr::where('directeur_adjoint_id', $user->id)->update(['directeur_adjoint_id' => null]);
                Departement::where('chef_id', $user->id)->where('id', '!=', $departement->id)->update(['chef_id' => null]);
                $departement->update(['chef_id' => $user->id]);
                $user->update(['ufr_id' => $departement->ufr_id, 'universite_id' => $departement->ufr->universite_id]);
            }
        }
        if ($user->role === Roles::ADJOINT && $user->ufr_id) {
            $ufr = Ufr::find($user->ufr_id);
            if ($ufr) {
                Departement::where('chef_id', $user->id)->update(['chef_id' => null]);
                Ufr::where('directeur_adjoint_id', $user->id)->where('id', '!=', $ufr->id)->update(['directeur_adjoint_id' => null]);
                User::where('role', Roles::ADJOINT)->where('ufr_id', $ufr->id)->where('id', '!=', $user->id)->update(['ufr_id' => null]);
                $ufr->update(['directeur_adjoint_id' => $user->id]);
                $user->update(['departement_id' => null, 'universite_id' => $ufr->universite_id]);
            }
        }
        if (in_array($user->role, [Roles::VICE, Roles::DIP, Roles::ADMIN_UNIV], true)) {
            $user->update(['ufr_id' => null, 'departement_id' => null]);
        }
    }
}

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

class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array($request->user()->role, [Roles::ADMIN, Roles::PRESIDENT], true), 403);
        $users = User::with(['universite', 'ufr', 'departement'])
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('universite_id', $request->user()->universite_id))
            ->orderBy('name')->paginate(30);
        $universites = $request->user()->isAdmin() ? Universite::orderBy('nom')->get() : collect([$request->user()->universite]);
        $ufrs = Ufr::query()->when(! $request->user()->isAdmin(), fn ($q) => $q->where('universite_id', $request->user()->universite_id))->orderBy('nom')->get();
        $departements = Departement::with('ufr')->whereHas('ufr', function ($q) use ($request) {
            if (! $request->user()->isAdmin()) {
                $q->where('universite_id', $request->user()->universite_id);
            }
        })->orderBy('nom')->get();
        $roles = $request->user()->isAdmin() ? Roles::ALL : array_diff_key(Roles::ALL, [Roles::ADMIN => true]);
        return view('users.index', compact('users', 'universites', 'ufrs', 'departements', 'roles'));
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role, [Roles::ADMIN, Roles::PRESIDENT], true), 403);
        $roles = $request->user()->isAdmin() ? array_keys(Roles::ALL) : array_keys(array_diff_key(Roles::ALL, [Roles::ADMIN => true]));
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
        if ($data['role'] !== Roles::ADMIN) {
            abort_unless(! empty($data['universite_id']), 422, 'Une université est obligatoire pour ce rôle.');
        }
        $user = User::create($data);
        Notifier::journal($request->user(), 'Création d\'un compte', $user->email, Roles::label($user->role));
        return back()->with('ok', 'Compte créé.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless(in_array($request->user()->role, [Roles::ADMIN, Roles::PRESIDENT], true), 403);
        if (! $request->user()->isAdmin()) {
            abort_unless((int) $user->universite_id === (int) $request->user()->universite_id, 403);
            abort_if($user->role === Roles::ADMIN, 403);
        }
        $roles = $request->user()->isAdmin() ? array_keys(Roles::ALL) : array_keys(array_diff_key(Roles::ALL, [Roles::ADMIN => true]));
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
        $user->update($data);
        Notifier::journal($request->user(), $user->actif ? 'Mise à jour d\'un compte' : 'Suspension d\'un compte', $user->email);
        return back()->with('ok', 'Compte mis à jour.');
    }
}

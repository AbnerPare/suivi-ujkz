<?php

namespace App\Http\Controllers;

use App\Models\Decision;
use App\Models\Ufr;
use App\Models\User;
use App\Support\Notifier;
use App\Support\Roles;
use Illuminate\Http\Request;

class DecisionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $decisions = Decision::with(['responsable', 'ufr', 'auteur', 'reunion'])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('universite_id', $user->universite_id))
            ->when($user->role === Roles::ADJOINT, fn ($q) => $q->where(fn ($s) => $s->where('ufr_id', $user->ufr_id)->orWhereNull('ufr_id')))
            ->latest()->paginate(20);
        $users = User::query()->when(! $user->isAdmin(), fn ($q) => $q->where('universite_id', $user->universite_id))->where('actif', true)->orderBy('name')->get();
        $ufrs = Ufr::query()->when(! $user->isAdmin(), fn ($q) => $q->where('universite_id', $user->universite_id))->orderBy('nom')->get();
        $peutCreer = in_array($user->role, [Roles::VICE, Roles::ADMIN, Roles::ADMIN_UNIV, Roles::DIP], true);
        return view('decisions.index', compact('decisions', 'users', 'ufrs', 'peutCreer'));
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role, [Roles::VICE, Roles::ADMIN, Roles::ADMIN_UNIV, Roles::DIP], true), 403);
        $data = $request->validate([
            'universite_id' => 'nullable|exists:universites,id',
            'type' => 'required|in:decision,recommandation,urgence',
            'titre' => 'required|string|max:180',
            'contenu' => 'required|string|max:5000',
            'responsable_id' => 'nullable|exists:users,id',
            'ufr_id' => 'nullable|exists:ufrs,id',
            'echeance' => 'nullable|date',
            'reunion_id' => 'nullable|exists:reunions,id',
        ]);
        if ($data['type'] === 'urgence' && ! in_array($request->user()->role, [Roles::VICE, Roles::ADMIN], true)) {
            abort(403, 'Les mesures d\'urgence sont décidées par le vice-président.');
        }
        $data['universite_id'] = $request->user()->isAdmin() ? ($data['universite_id'] ?? null) : $request->user()->universite_id;
        abort_unless($data['universite_id'], 422, 'Université requise.');
        $data['auteur_id'] = $request->user()->id;
        $decision = Decision::create($data);

        if ($decision->type === 'urgence') {
            $cibles = User::where('universite_id', $decision->universite_id)->where('role', Roles::ADJOINT)->where('actif', true)
                ->when($decision->ufr_id, fn ($q) => $q->where('ufr_id', $decision->ufr_id))->get();
            Notifier::send($cibles, 'Mesure d\'urgence', $decision->titre, route('decisions.index'));
        } elseif ($decision->responsable) {
            Notifier::send($decision->responsable, 'Décision à suivre', $decision->titre, route('decisions.index'));
        }
        Notifier::journal($request->user(), 'Enregistrement d\'une '.$decision->typeLabel(), $decision->titre);
        return back()->with('ok', 'Décision enregistrée.');
    }

    public function statut(Request $request, Decision $decision)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || (int) $decision->universite_id === (int) $user->universite_id, 403);
        $autorise = in_array($user->role, [Roles::VICE, Roles::ADMIN, Roles::ADMIN_UNIV, Roles::DIP], true)
            || (int) $decision->responsable_id === (int) $user->id
            || ($user->role === Roles::ADJOINT && (int) $decision->ufr_id === (int) $user->ufr_id);
        abort_unless($autorise, 403);
        $data = $request->validate(['statut' => 'required|in:a_faire,en_cours,executee']);
        $decision->update($data);
        return back()->with('ok', 'État d\'exécution mis à jour.');
    }
}

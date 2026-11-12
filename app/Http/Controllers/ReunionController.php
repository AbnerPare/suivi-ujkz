<?php

namespace App\Http\Controllers;

use App\Models\Reunion;
use App\Models\User;
use App\Support\Notifier;
use App\Support\Roles;
use Illuminate\Http\Request;

class ReunionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $reunions = Reunion::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('universite_id', $user->universite_id))
            ->withCount('participants')
            ->orderByDesc('date_seance')
            ->paginate(20);
        $users = User::query()->when(! $user->isAdmin(), fn ($q) => $q->where('universite_id', $user->universite_id))->where('actif', true)->orderBy('name')->get();
        $peutCreer = in_array($user->role, [Roles::SECRETAIRE, Roles::PRESIDENT, Roles::ADMIN], true);
        return view('reunions.index', compact('reunions', 'users', 'peutCreer'));
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role, [Roles::SECRETAIRE, Roles::PRESIDENT, Roles::ADMIN], true), 403);
        $data = $request->validate([
            'titre' => 'required|string|max:180',
            'date_seance' => 'required|date',
            'lieu' => 'nullable|string|max:180',
            'ordre_du_jour' => 'nullable|string|max:5000',
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:users,id',
        ]);
        $reunion = Reunion::create([
            'universite_id' => $request->user()->universite_id ?? User::find($data['participants'][0])->universite_id,
            'auteur_id' => $request->user()->id,
            'titre' => $data['titre'],
            'date_seance' => $data['date_seance'],
            'lieu' => $data['lieu'] ?? null,
            'ordre_du_jour' => $data['ordre_du_jour'] ?? null,
        ]);
        $reunion->participants()->sync($data['participants']);
        $invites = User::whereIn('id', $data['participants'])->get();
        Notifier::send($invites, 'Convocation à une réunion de suivi', $reunion->titre.' le '.$reunion->date_seance->format('d/m/Y H:i'), route('reunions.show', $reunion));
        Notifier::journal($request->user(), 'Programmation d\'une réunion', $reunion->titre);
        return redirect()->route('reunions.show', $reunion)->with('ok', 'Réunion programmée. Les participants sont notifiés.');
    }

    public function show(Request $request, Reunion $reunion)
    {
        $this->voir($request, $reunion);
        $reunion->load('participants', 'decisions.responsable', 'auteur');
        return view('reunions.show', compact('reunion'));
    }

    public function emarger(Request $request, Reunion $reunion)
    {
        $this->voir($request, $reunion);
        abort_unless($reunion->participants()->whereKey($request->user()->id)->exists(), 403, 'Vous n\'êtes pas convoqué à cette séance.');
        $reunion->participants()->updateExistingPivot($request->user()->id, ['present' => true, 'emarge_at' => now()]);
        return back()->with('ok', 'Émargement enregistré.');
    }

    public function presence(Request $request, Reunion $reunion)
    {
        abort_unless(in_array($request->user()->role, [Roles::SECRETAIRE, Roles::PRESIDENT, Roles::ADMIN], true), 403);
        $this->voir($request, $reunion);
        $data = $request->validate(['presents' => 'array', 'presents.*' => 'exists:users,id']);
        foreach ($reunion->participants as $p) {
            $present = in_array($p->id, $data['presents'] ?? [], true);
            $reunion->participants()->updateExistingPivot($p->id, [
                'present' => $present,
                'emarge_at' => $present ? ($p->pivot->emarge_at ?? now()) : null,
            ]);
        }
        $reunion->update(['statut' => 'tenue']);
        return back()->with('ok', 'Feuille de présence enregistrée.');
    }

    public function pv(Request $request, Reunion $reunion)
    {
        abort_unless(in_array($request->user()->role, [Roles::SECRETAIRE, Roles::ADMIN], true), 403);
        $this->voir($request, $reunion);
        $data = $request->validate(['pv' => 'required|string|max:20000', 'publier' => 'nullable|boolean']);
        $reunion->update(['pv' => $data['pv'], 'pv_publie' => $request->boolean('publier'), 'statut' => 'tenue']);
        if ($request->boolean('publier')) {
            Notifier::send($reunion->participants, 'Procès-verbal publié', $reunion->titre, route('reunions.show', $reunion));
        }
        Notifier::journal($request->user(), 'Rédaction d\'un procès-verbal', $reunion->titre);
        return back()->with('ok', 'Procès-verbal enregistré.');
    }

    private function voir(Request $request, Reunion $reunion): void
    {
        if (! $request->user()->isAdmin()) {
            abort_unless((int) $reunion->universite_id === (int) $request->user()->universite_id, 403);
        }
    }
}

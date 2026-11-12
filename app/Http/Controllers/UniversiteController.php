<?php

namespace App\Http\Controllers;

use App\Models\Universite;
use App\Support\Notifier;
use App\Support\Roles;
use Illuminate\Http\Request;

class UniversiteController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $universites = Universite::withCount(['users', 'ufrs'])->orderBy('nom')->get();
        return view('universites.index', compact('universites'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'nom' => 'required|string|max:180',
            'sigle' => 'required|string|max:20',
            'ville' => 'nullable|string|max:120',
            'email' => 'nullable|email',
            'telephone' => 'nullable|string|max:30',
        ]);
        $data['sigle'] = strtoupper($data['sigle']);
        $u = Universite::create($data);
        Notifier::journal($request->user(), 'Création d\'une université', $u->sigle);
        return back()->with('ok', 'Université créée. Créez ensuite son président dans Utilisateurs.');
    }

    public function update(Request $request, Universite $universite)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'nom' => 'required|string|max:180',
            'sigle' => 'required|string|max:20',
            'ville' => 'nullable|string|max:120',
            'actif' => 'required|boolean',
        ]);
        $data['sigle'] = strtoupper($data['sigle']);
        $universite->update($data);
        return back()->with('ok', 'Université mise à jour.');
    }
}

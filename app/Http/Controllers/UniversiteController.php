<?php

namespace App\Http\Controllers;

use App\Models\Universite;
use App\Models\User;
use App\Support\Notifier;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'admin_name' => 'required|string|max:120',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);
        $data['sigle'] = strtoupper($data['sigle']);
        $u = DB::transaction(function () use ($data, $request) {
            $universite = Universite::create([
                'nom' => $data['nom'],
                'sigle' => $data['sigle'],
                'ville' => $data['ville'] ?? null,
                'email' => $data['email'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'logo' => $request->hasFile('logo') ? $this->rangerLogo($request->file('logo'), null) : null,
            ]);
            User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'role' => Roles::ADMIN_UNIV,
                'universite_id' => $universite->id,
            ]);

            return $universite;
        });
        Notifier::journal($request->user(), 'Création d\'une université', $u->sigle, $data['admin_email']);
        return back()->with('ok', 'Université créée. Son administrateur peut maintenant créer les UFR, les départements et désigner les responsables.');
    }

    public function update(Request $request, Universite $universite)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'nom' => 'required|string|max:180',
            'sigle' => 'required|string|max:20',
            'ville' => 'nullable|string|max:120',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'actif' => 'required|boolean',
        ]);
        $data['sigle'] = strtoupper($data['sigle']);
        if ($request->hasFile('logo')) {
            $data['logo'] = $this->rangerLogo($request->file('logo'), $universite);
        }
        $universite->update($data);
        return back()->with('ok', 'Université mise à jour.');
    }

    public function logo(Universite $universite)
    {
        abort_unless($universite->logo && Storage::disk('local')->exists($universite->logo), 404);

        return response()->file(Storage::disk('local')->path($universite->logo));
    }

    private function rangerLogo(UploadedFile $fichier, ?Universite $universite): string
    {
        if ($universite?->logo) {
            Storage::disk('local')->delete($universite->logo);
        }

        $extension = $fichier->guessExtension() ?: 'png';
        if (! in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            $extension = 'png';
        }

        $nom = ($universite?->id ?? 'nouveau').'-'.Str::lower(Str::random(8)).'.'.$extension;

        return $fichier->storeAs('logos', $nom, 'local');
    }
}

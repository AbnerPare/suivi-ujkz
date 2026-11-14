<?php

namespace App\Http\Controllers;

use App\Support\Notifier;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required']);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (! $request->user()->actif || ! array_key_exists($request->user()->role, Roles::ALL) || ($request->user()->universite && ! $request->user()->universite->actif)) {
                Auth::logout();
                return back()->withErrors(['email' => 'Ce compte ou cette université est suspendu.'])->onlyInput('email');
            }
            $request->session()->regenerate();
            Notifier::journal($request->user(), 'Connexion');
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['email' => 'Email ou mot de passe incorrect.'])->onlyInput('email');
    }

    public function identifiant()
    {
        $profil = request()->user()->load('universite', 'ufr', 'departement');

        return view('compte.identifiant', compact('profil'));
    }

    public function updateIdentifiant(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'password' => ['required', 'current_password'],
        ], [
            'email.unique' => 'Cette adresse est déjà utilisée.',
            'password.current_password' => 'Le mot de passe actuel est incorrect.',
        ]);
        $ancien = $request->user()->email;
        $email = mb_strtolower(trim($data['email']));
        $request->user()->update(['email' => $email]);
        if ($ancien !== $email) {
            Notifier::journal($request->user(), 'Modification de l\'identifiant de connexion', $email, $ancien);
        }

        return back()->with('ok', 'Profil mis à jour. Utilisez cette adresse à la prochaine connexion.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        return redirect()->route('inscription')->withErrors(['email' => 'Seul l\'administrateur système crée une université et désigne son administrateur.']);
    }
}

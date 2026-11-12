<?php

namespace App\Http\Controllers;

use App\Models\Universite;
use App\Models\User;
use App\Support\Notifier;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            if (! $request->user()->actif || ($request->user()->universite && ! $request->user()->universite->actif)) {
                Auth::logout();
                return back()->withErrors(['email' => 'Ce compte ou cette université est suspendu.'])->onlyInput('email');
            }
            $request->session()->regenerate();
            Notifier::journal($request->user(), 'Connexion');
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['email' => 'Email ou mot de passe incorrect.'])->onlyInput('email');
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
        $data = $request->validate([
            'universite' => 'required|string|max:180',
            'sigle' => 'required|string|max:20',
            'ville' => 'nullable|string|max:120',
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = DB::transaction(function () use ($data) {
            $universite = Universite::create([
                'nom' => $data['universite'],
                'sigle' => strtoupper($data['sigle']),
                'ville' => $data['ville'] ?? null,
                'email' => $data['email'],
            ]);
            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => Roles::PRESIDENT,
                'universite_id' => $universite->id,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();
        Notifier::journal($user, 'Création d\'une université', $user->universite->sigle);

        return redirect()->route('dashboard')->with('ok', 'Université créée. Vous êtes connecté en tant que président. Complétez les structures et les comptes.');
    }
}

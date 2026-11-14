@extends('layouts.app')
@section('title', 'Profil')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-ujkz">Profil</h1>
    <p class="mt-1 text-slate-600">Vos informations de compte sur la plateforme.</p>
</div>

<div class="grid items-start gap-6 lg:grid-cols-[20rem_1fr]">
    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-4">
            <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-ujkz text-xl font-bold text-white">{{ $profil->initiales() }}</span>
            <div class="min-w-0">
                <h2 class="truncate text-lg font-semibold text-slate-900">{{ $profil->name }}</h2>
                <p class="text-sm text-slate-500">{{ $profil->roleLabel() }}</p>
            </div>
        </div>
        <dl class="mt-6 space-y-4 border-t border-slate-100 pt-5 text-sm">
            <div>
                <dt class="text-slate-500">Identifiant de connexion</dt>
                <dd class="mt-0.5 font-medium text-slate-900">{{ $profil->email }}</dd>
            </div>
            @if($profil->universite)
                <div>
                    <dt class="text-slate-500">Université</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $profil->universite->sigle }} — {{ $profil->universite->nom }}</dd>
                </div>
            @endif
            @if($profil->ufr)
                <div>
                    <dt class="text-slate-500">{{ $profil->ufr->libelleType() }}</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $profil->ufr->sigle }} — {{ $profil->ufr->nom }}</dd>
                </div>
            @endif
            @if($profil->departement)
                <div>
                    <dt class="text-slate-500">Département</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $profil->departement->nom }}</dd>
                </div>
            @endif
        </dl>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-ujkz">Identifiant de connexion</h2>
        <p class="mt-1 max-w-xl text-sm text-slate-600">Cette adresse sert à vous connecter. Indiquez votre mot de passe actuel pour la modifier.</p>
        <form method="POST" action="{{ route('compte.update') }}" class="mt-6 max-w-md space-y-4">
            @csrf @method('PUT')
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Adresse email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $profil->email) }}" required autocomplete="username" class="w-full rounded-lg border px-3 py-2 {{ $errors->has('email') ? 'border-red-600 bg-red-50' : 'border-slate-300' }}">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Mot de passe actuel</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-lg border px-3 py-2 {{ $errors->has('password') ? 'border-red-600 bg-red-50' : 'border-slate-300' }}">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button class="rounded-lg bg-ujkz px-4 py-2 font-semibold text-white">Enregistrer le profil</button>
        </form>
    </section>
</div>
@endsection

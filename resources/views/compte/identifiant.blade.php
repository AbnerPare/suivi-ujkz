@extends('layouts.app')
@section('title', 'Identifiant de connexion')
@section('content')
<h1 class="text-2xl font-bold text-ujkz">Identifiant de connexion</h1>
<p class="mt-1 max-w-xl text-slate-600">Cette adresse sert à vous connecter. Indiquez votre mot de passe actuel pour la modifier.</p>
<form method="POST" action="{{ route('compte.update') }}" class="mt-6 max-w-md space-y-4 rounded-lg border bg-white p-6">
    @csrf @method('PUT')
    <div>
        <label for="email" class="mb-1 block text-sm font-medium">Adresse email</label>
        <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required autocomplete="username" class="w-full rounded border px-3 py-2 {{ $errors->has('email') ? 'border-red-600 bg-red-50' : 'border-slate-300' }}">
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password" class="mb-1 block text-sm font-medium">Mot de passe actuel</label>
        <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded border px-3 py-2 {{ $errors->has('password') ? 'border-red-600 bg-red-50' : 'border-slate-300' }}">
        @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <button class="rounded bg-ujkz px-4 py-2 font-semibold text-white">Enregistrer</button>
</form>
@endsection

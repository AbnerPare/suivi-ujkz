@extends('layouts.app')
@section('title', 'Connexion')
@section('content')
<div class="mx-auto mt-8 grid max-w-4xl gap-8 lg:grid-cols-2">
    <div>
        @php $marque = \App\Models\Universite::where('sigle', 'UJKZ')->first(); @endphp
        <img src="{{ $marque?->urlLogo() ?? asset('images/logo-ujkz.png') }}" alt="{{ $marque->nom ?? 'Université Joseph Ki-Zerbo' }}" class="h-48 w-48 object-contain mix-blend-multiply">
        <h1 class="mt-3 text-2xl font-bold text-ujkz">SUIVI-ENSEIGNEMENTS</h1>
        <p class="mt-2 text-slate-600">Suivi de l'avancement des enseignements et des évaluations dans les universités du Burkina Faso.</p>
        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label for="email" class="mb-1 block text-sm font-medium">Adresse email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full rounded border border-slate-300 px-3 py-2">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Mot de passe</label>
                <input id="password" name="password" type="password" required class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember"> Rester connecté</label>
            <button class="w-full rounded bg-ujkz px-4 py-2.5 font-semibold text-white hover:bg-ujkz-dark">Se connecter</button>
        </form>
        <p class="mt-4 text-sm text-slate-500">Une université est créée par l'administrateur système, qui désigne ensuite l'administrateur de cet établissement.</p>
    </div>
    @if(app()->environment('local'))
    <aside class="rounded-lg border border-slate-200 bg-white p-6 text-sm">
        <h2 class="font-semibold">Comptes de démonstration</h2>
        <p class="mt-1 text-slate-500">Mot de passe : <code>password</code></p>
        <ul class="mt-3 space-y-1">
            <li>admin@ujkz.bf — administrateur système</li>
            <li>admin.ujkz@ujkz.bf — administrateur de l'université</li>
            <li>vp@ujkz.bf — vice-président</li>
            <li>dip@ujkz.bf — innovations pédagogiques</li>
            <li>adjoint@ujkz.bf — directeur adjoint</li>
            <li>chef@ujkz.bf — chef de département Informatique</li>
        </ul>
    </aside>
    @endif
</div>
@endsection

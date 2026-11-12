@extends('layouts.app')
@section('title', 'Utilisateurs')
@section('content')
<h1 class="text-2xl font-bold text-ujkz">Utilisateurs et droits</h1>
<form method="POST" action="{{ route('users.store') }}" class="mt-4 grid gap-2 rounded-lg border bg-white p-4 md:grid-cols-4">
    @csrf
    <input name="name" placeholder="Nom" required class="rounded border px-3 py-2">
    <input type="email" name="email" placeholder="Email" required class="rounded border px-3 py-2">
    <input type="password" name="password" placeholder="Mot de passe" required class="rounded border px-3 py-2">
    <select name="role" class="rounded border px-3 py-2">@foreach($roles as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
    @if(auth()->user()->isAdmin())
        <select name="universite_id" class="rounded border px-3 py-2"><option value="">Université</option>@foreach($universites as $u)<option value="{{ $u->id }}">{{ $u->sigle }}</option>@endforeach</select>
    @endif
    <select name="ufr_id" class="rounded border px-3 py-2"><option value="">UFR</option>@foreach($ufrs as $u)<option value="{{ $u->id }}">{{ $u->sigle }}</option>@endforeach</select>
    <select name="departement_id" class="rounded border px-3 py-2"><option value="">Département</option>@foreach($departements as $d)<option value="{{ $d->id }}">{{ $d->nom }}</option>@endforeach</select>
    <button class="rounded bg-ujkz px-3 py-2 font-semibold text-white">Créer le compte</button>
</form>
<div class="mt-6 space-y-3">
@foreach($users as $user)
    <form method="POST" action="{{ route('users.update', $user) }}" class="grid gap-2 rounded border bg-white p-3 md:grid-cols-6">
        @csrf @method('PUT')
        <input name="name" value="{{ $user->name }}" class="rounded border px-2 py-1">
        <p class="self-center text-sm text-slate-500">{{ $user->email }}</p>
        <select name="role" class="rounded border px-2 py-1">@foreach($roles as $k => $label)<option value="{{ $k }}" @selected($user->role===$k)>{{ $label }}</option>@endforeach</select>
        <select name="ufr_id" class="rounded border px-2 py-1"><option value="">UFR</option>@foreach($ufrs as $u)<option value="{{ $u->id }}" @selected($user->ufr_id==$u->id)>{{ $u->sigle }}</option>@endforeach</select>
        <select name="departement_id" class="rounded border px-2 py-1"><option value="">Département</option>@foreach($departements as $d)<option value="{{ $d->id }}" @selected($user->departement_id==$d->id)>{{ $d->nom }}</option>@endforeach</select>
        <div class="flex gap-2">
            <select name="actif" class="rounded border px-2 py-1"><option value="1" @selected($user->actif)>Actif</option><option value="0" @selected(! $user->actif)>Suspendu</option></select>
            <input type="password" name="password" placeholder="Nouveau mdp" class="w-28 rounded border px-2 py-1">
            <button class="rounded bg-slate-800 px-3 py-1 text-white">OK</button>
        </div>
    </form>
@endforeach
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection

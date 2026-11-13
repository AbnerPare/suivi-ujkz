@extends('layouts.app')
@section('title', 'Universités')
@section('content')
<h1 class="text-2xl font-bold text-ujkz">Universités</h1>
<p class="text-slate-600">Chaque établissement dispose d'un espace isolé.</p>
<form method="POST" action="{{ route('universites.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-3 rounded-lg border bg-white p-4 md:grid-cols-4">
    @csrf
    <input name="nom" placeholder="Nom de l'université" required class="rounded border px-3 py-2 md:col-span-2">
    <input name="sigle" placeholder="Sigle" required class="rounded border px-3 py-2">
    <input name="ville" placeholder="Ville" class="rounded border px-3 py-2">
    <label class="text-sm text-slate-600 md:col-span-4">Logo de l'université
        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full text-sm">
    </label>
    <input name="admin_name" placeholder="Nom de l'administrateur" required class="rounded border px-3 py-2 md:col-span-2">
    <input name="admin_email" type="email" placeholder="Email de l'administrateur" required class="rounded border px-3 py-2">
    <input name="admin_password" type="password" placeholder="Mot de passe" required class="rounded border px-3 py-2">
    <button class="rounded bg-ujkz px-4 py-2 font-semibold text-white md:col-span-4 md:w-fit">Créer l'université et son administrateur</button>
</form>
<div class="mt-6 space-y-3">
@foreach($universites as $u)
    <form method="POST" action="{{ route('universites.update', $u) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2 rounded border bg-white p-3">
        @csrf @method('PUT')
        @if($u->urlLogo())
            <img src="{{ $u->urlLogo() }}" alt="" class="h-12 w-12 object-contain">
        @endif
        <input name="nom" value="{{ $u->nom }}" class="rounded border px-2 py-1">
        <input name="sigle" value="{{ $u->sigle }}" class="w-24 rounded border px-2 py-1">
        <input name="ville" value="{{ $u->ville }}" class="rounded border px-2 py-1">
        <select name="actif" class="rounded border px-2 py-1"><option value="1" @selected($u->actif)>Active</option><option value="0" @selected(! $u->actif)>Suspendue</option></select>
        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" aria-label="Logo de {{ $u->sigle }}" class="text-sm">
        <span class="text-sm text-slate-500">{{ $u->ufrs_count }} UFR · {{ $u->users_count }} comptes</span>
        <a class="text-sm underline" href="{{ route('ufrs.index', ['universite_id' => $u->id]) }}">Structures</a>
        <button class="rounded bg-slate-800 px-3 py-1 text-white">Enregistrer</button>
    </form>
@endforeach
</div>
@endsection

@extends('layouts.app')
@section('title', 'Universités')
@section('content')
<h1 class="text-2xl font-bold text-ujkz">Universités</h1>
<p class="text-slate-600">Chaque établissement dispose d'un espace isolé.</p>
<form method="POST" action="{{ route('universites.store') }}" class="mt-4 flex flex-wrap gap-2 rounded-lg border bg-white p-4">
    @csrf
    <input name="nom" placeholder="Nom" required class="rounded border px-3 py-2">
    <input name="sigle" placeholder="Sigle" required class="w-24 rounded border px-3 py-2">
    <input name="ville" placeholder="Ville" class="rounded border px-3 py-2">
    <input name="email" type="email" placeholder="Email" class="rounded border px-3 py-2">
    <button class="rounded bg-ujkz px-4 py-2 font-semibold text-white">Créer</button>
</form>
<div class="mt-6 space-y-3">
@foreach($universites as $u)
    <form method="POST" action="{{ route('universites.update', $u) }}" class="flex flex-wrap items-center gap-2 rounded border bg-white p-3">
        @csrf @method('PUT')
        <input name="nom" value="{{ $u->nom }}" class="rounded border px-2 py-1">
        <input name="sigle" value="{{ $u->sigle }}" class="w-24 rounded border px-2 py-1">
        <input name="ville" value="{{ $u->ville }}" class="rounded border px-2 py-1">
        <select name="actif" class="rounded border px-2 py-1"><option value="1" @selected($u->actif)>Active</option><option value="0" @selected(! $u->actif)>Suspendue</option></select>
        <span class="text-sm text-slate-500">{{ $u->ufrs_count }} UFR · {{ $u->users_count }} comptes</span>
        <a class="text-sm underline" href="{{ route('ufrs.index', ['universite_id' => $u->id]) }}">Structures</a>
        <button class="rounded bg-slate-800 px-3 py-1 text-white">Enregistrer</button>
    </form>
@endforeach
</div>
@endsection

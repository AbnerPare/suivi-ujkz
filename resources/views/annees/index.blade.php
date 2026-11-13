@extends('layouts.app')
@section('title', 'Années et campagnes')
@section('content')
<h1 class="text-2xl font-bold text-ujkz">Années académiques et campagnes</h1>
<p class="mt-1 text-slate-600">Le vice-président et le Directeur des Innovations Pédagogiques ouvrent l'année, la campagne et le mois de suivi. Le chef de département peut ensuite créer son bilan.</p>
@if($editable)
<form method="POST" action="{{ route('annees.store') }}" class="mt-4 flex flex-wrap gap-2 rounded-lg border bg-white p-4">
    @csrf
    <input type="hidden" name="universite_id" value="{{ $universiteId }}">
    <input name="libelle" placeholder="2026-2027" required class="rounded border px-3 py-2">
    <input type="date" name="date_debut" required class="rounded border px-3 py-2">
    <input type="date" name="date_fin" required class="rounded border px-3 py-2">
    <button class="rounded bg-ujkz px-4 py-2 font-semibold text-white">Créer l'année</button>
</form>
@endif
<div class="mt-6 space-y-6">
@foreach($annees as $annee)
    <section class="rounded-lg border bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold">{{ $annee->libelle }} <span class="text-sm font-normal text-slate-500">{{ $annee->date_debut->format('d/m/Y') }} — {{ $annee->date_fin->format('d/m/Y') }} · {{ $annee->statut }}</span></h2>
            @if($editable)
            <form method="POST" action="{{ route('annees.statut', $annee) }}" class="flex gap-2">@csrf
                <input type="hidden" name="universite_id" value="{{ $universiteId }}">
                <select name="statut" class="rounded border px-2 py-1"><option @selected($annee->statut=='ouverte')>ouverte</option><option @selected($annee->statut=='cloturee')>cloturee</option><option @selected($annee->statut=='archivee')>archivee</option></select>
                <button class="rounded bg-slate-800 px-3 py-1 text-white">Appliquer</button>
            </form>
            @endif
        </div>
        @foreach($annee->campagnes as $c)
            <div class="mt-4 border-t pt-3">
                <p class="font-medium">Campagne : {{ $c->libelle }} <span class="text-sm text-slate-500">({{ $c->statut }})</span></p>
                @if($editable)
                <form method="POST" action="{{ route('campagnes.statut', $c) }}" class="mt-2 flex gap-2">@csrf
                    <input type="hidden" name="universite_id" value="{{ $universiteId }}">
                    <select name="statut" class="rounded border px-2 py-1"><option @selected($c->statut=='ouverte')>ouverte</option><option @selected($c->statut=='cloturee')>cloturee</option><option @selected($c->statut=='archivee')>archivee</option></select>
                    <button class="text-sm underline">Changer</button>
                </form>
                <form method="POST" action="{{ route('periodes.store') }}" class="mt-2 flex flex-wrap gap-2">@csrf
                    <input type="hidden" name="universite_id" value="{{ $universiteId }}">
                    <input type="hidden" name="campagne_id" value="{{ $c->id }}">
                    <select name="mois" class="rounded border px-2 py-1">@foreach(\App\Models\Periode::MOIS as $n => $nom)<option value="{{ $n }}">{{ $nom }}</option>@endforeach</select>
                    <input name="annee" type="number" value="{{ now()->year }}" class="w-24 rounded border px-2 py-1">
                    <button class="rounded bg-ujkz px-3 py-1 text-white">Ajouter le mois</button>
                </form>
                @endif
                <ul class="mt-2 text-sm">
                    @foreach($c->periodes as $p)
                        <li class="flex items-center gap-3 py-1">{{ $p->libelle }} — {{ $p->ouverte ? 'ouverte' : 'clôturée' }}
                            @if($editable)
                            <form method="POST" action="{{ route('periodes.toggle', $p) }}">@csrf<input type="hidden" name="universite_id" value="{{ $universiteId }}"><button class="underline">{{ $p->ouverte ? 'Clôturer' : 'Rouvrir' }}</button></form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
        @if($editable)
        <form method="POST" action="{{ route('campagnes.store') }}" class="mt-4 flex flex-wrap gap-2 border-t pt-3">@csrf
            <input type="hidden" name="universite_id" value="{{ $universiteId }}">
            <input type="hidden" name="annee_academique_id" value="{{ $annee->id }}">
            <input name="libelle" placeholder="Campagne de suivi" required class="rounded border px-3 py-2">
            <input type="date" name="date_debut" class="rounded border px-3 py-2">
            <input type="date" name="date_fin" class="rounded border px-3 py-2">
            <button class="rounded bg-slate-800 px-3 py-2 text-white">Nouvelle campagne</button>
        </form>
        @endif
    </section>
@endforeach
</div>
@endsection

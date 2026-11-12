@extends('layouts.app')
@section('title', 'Décisions')
@section('content')
<h1 class="text-2xl font-bold text-ujkz">Décisions, recommandations et mesures d'urgence</h1>
@if($peutCreer)
<form method="POST" action="{{ route('decisions.store') }}" class="mt-4 grid gap-3 rounded-lg border bg-white p-4 md:grid-cols-2">
    @csrf
    @if(auth()->user()->isAdmin())
        <select name="universite_id" required class="rounded border px-3 py-2">@foreach(\App\Models\Universite::orderBy('nom')->get() as $u)<option value="{{ $u->id }}">{{ $u->sigle }}</option>@endforeach</select>
    @endif
    <select name="type" class="rounded border px-3 py-2">
        @if(auth()->user()->role !== 'dip')<option value="urgence">Mesure d'urgence</option>@endif
        <option value="decision">Décision</option>
        <option value="recommandation">Recommandation</option>
    </select>
    <input name="titre" placeholder="Titre" required class="rounded border px-3 py-2">
    <select name="ufr_id" class="rounded border px-3 py-2"><option value="">Toutes les UFR</option>@foreach($ufrs as $u)<option value="{{ $u->id }}">{{ $u->sigle }}</option>@endforeach</select>
    <select name="responsable_id" class="rounded border px-3 py-2"><option value="">Responsable</option>@foreach($users as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
    <input type="date" name="echeance" class="rounded border px-3 py-2">
    <textarea name="contenu" required rows="3" placeholder="Contenu" class="rounded border px-3 py-2 md:col-span-2"></textarea>
    <button class="rounded bg-ujkz px-4 py-2 font-semibold text-white md:w-fit">Enregistrer</button>
</form>
@endif
<div class="mt-6 space-y-3">
    @foreach($decisions as $d)
        <article class="rounded-lg border bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold">{{ $d->titre }}</h2>
                <span class="text-xs font-semibold uppercase text-slate-500">{{ $d->typeLabel() }} · {{ $d->statut }}</span>
            </div>
            <p class="mt-2 text-sm">{{ $d->contenu }}</p>
            <p class="mt-2 text-xs text-slate-500">
                {{ $d->ufr->sigle ?? 'Université' }}
                @if($d->responsable) · {{ $d->responsable->name }} @endif
                @if($d->echeance) · échéance {{ $d->echeance->format('d/m/Y') }} @endif
            </p>
            <form method="POST" action="{{ route('decisions.statut', $d) }}" class="mt-2 flex gap-2">@csrf
                <select name="statut" class="rounded border px-2 py-1 text-sm">
                    <option value="a_faire" @selected($d->statut=='a_faire')>À faire</option>
                    <option value="en_cours" @selected($d->statut=='en_cours')>En cours</option>
                    <option value="executee" @selected($d->statut=='executee')>Exécutée</option>
                </select>
                <button class="text-sm underline">Mettre à jour</button>
            </form>
        </article>
    @endforeach
</div>
<div class="mt-4">{{ $decisions->links() }}</div>
@endsection

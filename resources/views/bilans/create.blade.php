@extends('layouts.app')
@section('title', 'Nouveau bilan')
@section('content')
<h1 class="text-2xl font-bold text-ujkz">Saisir un bilan</h1>
<p class="mt-1 text-slate-600">{{ $departement->ufr->sigle }} — {{ $departement->nom }}. Le tableau déjà dressé compte {{ $departement->formations->count() }} ligne(s). <a class="underline" href="{{ route('formations.index') }}">Revoir la structure</a>.</p>
<form method="POST" action="{{ route('bilans.store') }}" class="mt-6 max-w-lg space-y-4 rounded-lg border bg-white p-6">
    @csrf
    <label class="block text-sm font-medium">Période de suivi ouverte</label>
    <select name="periode_id" required class="w-full rounded border px-3 py-2">
        @foreach($periodes as $p)
            <option value="{{ $p->id }}">{{ $p->libelle }}</option>
        @endforeach
    </select>
    @if($periodes->isEmpty())
        <p class="text-sm text-amber-700">Aucune période ouverte. Le vice-président ou le Directeur des Innovations Pédagogiques doit ouvrir une campagne et un mois de suivi.</p>
    @endif
    <button class="rounded bg-ujkz px-4 py-2 font-semibold text-white" @disabled($periodes->isEmpty())>Créer le brouillon</button>
</form>
@endsection

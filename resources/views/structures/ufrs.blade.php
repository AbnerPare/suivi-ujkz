@extends('layouts.app')
@section('title', 'UFR et instituts')
@section('content')
<h1 class="text-2xl font-bold text-ujkz">UFR et instituts</h1>
@if(auth()->user()->gereStructures())
<form method="POST" action="{{ route('ufrs.store') }}" class="mt-4 grid gap-3 rounded-lg border bg-white p-4 md:grid-cols-5">
    @csrf
    <input type="hidden" name="universite_id" value="{{ $universiteId }}">
    <input name="nom" placeholder="Nom" required class="rounded border px-3 py-2 md:col-span-2">
    <input name="sigle" placeholder="Sigle" class="rounded border px-3 py-2">
    <select name="type" class="rounded border px-3 py-2"><option value="ufr">UFR</option><option value="institut">Institut</option></select>
    <select name="directeur_adjoint_id" class="rounded border px-3 py-2"><option value="">Directeur adjoint</option>@foreach($personnes as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
    <button class="rounded bg-ujkz px-3 py-2 font-semibold text-white md:col-span-5 md:w-fit">Ajouter</button>
</form>
@endif
<div class="mt-6 space-y-4">
    @foreach($ufrs as $ufr)
        <article class="rounded-lg border bg-white p-4">
            <form method="POST" action="{{ route('ufrs.update', $ufr) }}" class="grid gap-2 md:grid-cols-5">
                @csrf @method('PUT')
                <input type="hidden" name="universite_id" value="{{ $ufr->universite_id }}">
                <input name="nom" value="{{ $ufr->nom }}" class="rounded border px-3 py-2 md:col-span-2" @disabled(! auth()->user()->gereStructures())>
                <input name="sigle" value="{{ $ufr->sigle }}" class="rounded border px-3 py-2" @disabled(! auth()->user()->gereStructures())>
                <select name="type" class="rounded border px-3 py-2" @disabled(! auth()->user()->gereStructures())><option value="ufr" @selected($ufr->type==='ufr')>UFR</option><option value="institut" @selected($ufr->type==='institut')>Institut</option></select>
                <select name="directeur_adjoint_id" class="rounded border px-3 py-2" @disabled(! auth()->user()->gereStructures())><option value="">Directeur adjoint</option>@foreach($personnes as $p)<option value="{{ $p->id }}" @selected($ufr->directeur_adjoint_id==$p->id)>{{ $p->name }}</option>@endforeach</select>
                @if(auth()->user()->gereStructures())
                    <button class="rounded bg-slate-800 px-3 py-2 text-white md:col-span-5 md:w-fit">Enregistrer</button>
                @endif
            </form>
            <p class="mt-2 text-sm text-slate-600">{{ $ufr->departements->count() }} département(s) : {{ $ufr->departements->pluck('nom')->join(', ') ?: 'aucun' }}</p>
            @if(auth()->user()->gereStructures())
            <form method="POST" action="{{ route('ufrs.destroy', $ufr) }}" class="mt-2" onsubmit="return confirm('Supprimer cette UFR ?')">@csrf @method('DELETE')<button class="text-sm text-red-700 underline">Supprimer</button></form>
            @endif
        </article>
    @endforeach
</div>
@endsection

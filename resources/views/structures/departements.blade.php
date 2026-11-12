@extends('layouts.app')
@section('title', 'Départements')
@section('content')
<h1 class="text-2xl font-bold text-ujkz">Départements</h1>
@if(in_array(auth()->user()->role, ['admin','president']))
<form method="POST" action="{{ route('departements.store') }}" class="mt-4 grid gap-3 rounded-lg border bg-white p-4 md:grid-cols-5">
    @csrf
    <input type="hidden" name="universite_id" value="{{ $ufrs->first()?->universite_id }}">
    <select name="ufr_id" required class="rounded border px-3 py-2">@foreach($ufrs as $u)<option value="{{ $u->id }}">{{ $u->sigle }} — {{ $u->nom }}</option>@endforeach</select>
    <input name="nom" placeholder="Nom du département" required class="rounded border px-3 py-2">
    <select name="chef_id" class="rounded border px-3 py-2"><option value="">Chef de département</option>@foreach($personnes as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
    <select name="adjoint_id" class="rounded border px-3 py-2"><option value="">Adjoint</option>@foreach($personnes as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
    <button class="rounded bg-ujkz px-3 py-2 font-semibold text-white">Ajouter</button>
</form>
@endif
<div class="mt-6 space-y-6">
@foreach($ufrs as $ufr)
    <section>
        <h2 class="font-semibold">{{ $ufr->libelleType() }} {{ $ufr->sigle }} — {{ $ufr->nom }}</h2>
        @foreach($ufr->departements as $d)
            <form method="POST" action="{{ route('departements.update', $d) }}" class="mt-2 grid gap-2 rounded border bg-white p-3 md:grid-cols-5">
                @csrf @method('PUT')
                <input name="nom" value="{{ $d->nom }}" class="rounded border px-3 py-2" @disabled(! in_array(auth()->user()->role, ['admin','president']))>
                <select name="chef_id" class="rounded border px-3 py-2" @disabled(! in_array(auth()->user()->role, ['admin','president']))><option value="">Chef</option>@foreach($personnes as $p)<option value="{{ $p->id }}" @selected($d->chef_id==$p->id)>{{ $p->name }}</option>@endforeach</select>
                <select name="adjoint_id" class="rounded border px-3 py-2" @disabled(! in_array(auth()->user()->role, ['admin','president']))><option value="">Adjoint</option>@foreach($personnes as $p)<option value="{{ $p->id }}" @selected($d->adjoint_id==$p->id)>{{ $p->name }}</option>@endforeach</select>
                @if(in_array(auth()->user()->role, ['admin','president']))
                    <button class="rounded bg-slate-800 px-3 py-2 text-white">Enregistrer</button>
                @endif
            </form>
        @endforeach
    </section>
@endforeach
</div>
@endsection

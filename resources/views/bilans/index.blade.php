@extends('layouts.app')
@section('title', 'Bilans')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-ujkz">Bilans de suivi</h1>
        <p class="text-slate-600">Un bilan par département et par période mensuelle.</p>
    </div>
    @if(auth()->user()->role === 'chef')
        <a href="{{ route('bilans.create') }}" class="rounded bg-ujkz px-4 py-2 font-semibold text-white">Nouveau bilan</a>
    @endif
</div>
<form class="mb-4 flex flex-wrap gap-2" method="GET">
    <select name="statut" class="rounded border px-3 py-2" onchange="this.form.submit()">
        <option value="">Tous les statuts</option>
        @foreach(\App\Support\Roles::STATUTS as $k => $label)
            <option value="{{ $k }}" @selected(request('statut') === $k)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="periode_id" class="rounded border px-3 py-2" onchange="this.form.submit()">
        <option value="">Toutes les périodes</option>
        @foreach($periodes as $p)
            <option value="{{ $p->id }}" @selected((int) request('periode_id') === $p->id)>{{ $p->libelle }}</option>
        @endforeach
    </select>
</form>
<div class="overflow-hidden rounded-lg border bg-white">
    <table class="w-full text-sm">
        <thead class="bg-slate-100 text-left"><tr>
            <th class="px-4 py-3">Département</th><th class="px-4 py-3">Période</th><th class="px-4 py-3">Année</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Cours</th><th class="px-4 py-3">Évaluations</th>
        </tr></thead>
        <tbody>
        @forelse($bilans as $b)
            <tr class="border-t hover:bg-slate-50">
                <td class="px-4 py-3"><a class="font-medium text-ujkz underline" href="{{ route('bilans.edit', $b) }}">{{ $b->departement->nom }}</a><div class="text-xs text-slate-500">{{ $b->departement->ufr->sigle }}</div></td>
                <td class="px-4 py-3">{{ $b->periode->libelle }}</td>
                <td class="px-4 py-3">{{ $b->annee->libelle }}</td>
                <td class="px-4 py-3">@include('bilans._badge', ['statut' => $b->statut])</td>
                <td class="px-4 py-3">{{ $b->tauxCours() !== null ? $b->tauxCours().' %' : '—' }}</td>
                <td class="px-4 py-3">{{ $b->tauxEval() !== null ? $b->tauxEval().' %' : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucun bilan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $bilans->links() }}</div>
@endsection

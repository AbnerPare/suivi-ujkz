@extends('layouts.app')
@section('title', 'Bilans')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-ujkz">Bilans de suivi</h1>
        <p class="text-slate-600">
            @if(auth()->user()->role === 'chef') Bilan du département dont vous êtes responsable.
            @elseif(auth()->user()->role === 'directeur_adjoint') Bilans de tous les départements de votre UFR.
            @elseif(auth()->user()->recoitBilansOfficiels()) Bilans de tous les départements, regroupés par UFR.
            @else Un bilan par département et par période mensuelle.
            @endif
        </p>
    </div>
    @if(auth()->user()->role === 'chef')
        @if(auth()->user()->departement && auth()->user()->departement->formations()->doesntExist())
            <a href="{{ route('formations.index') }}" class="rounded bg-ujkz px-4 py-2 font-semibold text-white">Dresser le tableau</a>
        @else
            <a href="{{ route('bilans.create') }}" class="rounded bg-ujkz px-4 py-2 font-semibold text-white">Nouveau bilan</a>
        @endif
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
@if(auth()->user()->recoitBilansOfficiels() && $groupes !== null && ! request('ufr_id'))
    <div class="grid gap-4 sm:grid-cols-2">
        @forelse($groupes as $groupe)
            <a href="{{ route('bilans.index', array_merge(request()->query(), ['ufr_id' => $groupe['id']])) }}" class="rounded-lg border bg-white p-5 hover:border-ujkz">
                <h2 class="text-lg font-semibold text-ujkz">{{ $groupe['ufr'] }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $groupe['departements'] }} département(s) · {{ $groupe['bilans']->count() }} bilan(s) reçu(s)</p>
            </a>
        @empty
            <p class="rounded-lg border bg-white px-4 py-8 text-center text-slate-500 sm:col-span-2">Aucun bilan.</p>
        @endforelse
    </div>
@elseif($groupes !== null)
    @if(request('ufr_id'))
        <a href="{{ route('bilans.index', request()->except('ufr_id')) }}" class="mb-4 inline-block text-sm font-semibold text-ujkz underline">Toutes les UFR</a>
    @endif
    @forelse($groupes->when(request('ufr_id'), fn ($liste) => $liste->where('id', (int) request('ufr_id'))) as $groupe)
        <section class="mt-6">
            <h2 class="mb-2 text-lg font-semibold text-ujkz">{{ $groupe['ufr'] }}</h2>
            <div class="overflow-hidden rounded-lg border bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100 text-left"><tr>
                        <th class="px-4 py-3">Département</th><th class="px-4 py-3">Période</th><th class="px-4 py-3">Année</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Cours</th><th class="px-4 py-3">Évaluations</th>
                    </tr></thead>
                    <tbody>
                    @foreach($groupe['bilans'] as $b)
                        <tr class="border-t hover:bg-slate-50">
                            <td class="px-4 py-3"><a class="font-medium text-ujkz underline" href="{{ route('bilans.edit', $b) }}">{{ $b->departement->nom }}</a></td>
                            <td class="px-4 py-3">{{ $b->periode->libelle }}</td>
                            <td class="px-4 py-3">{{ $b->annee->libelle }}</td>
                            <td class="px-4 py-3">@include('bilans._badge', ['statut' => $b->statut])</td>
                            <td class="px-4 py-3">{{ $b->tauxCours() !== null ? $b->tauxCours().' %' : '—' }}</td>
                            <td class="px-4 py-3">{{ $b->tauxEval() !== null ? $b->tauxEval().' %' : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <p class="mt-6 rounded-lg border bg-white px-4 py-8 text-center text-slate-500">Aucun bilan.</p>
    @endforelse
@else
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
@endif
@endsection

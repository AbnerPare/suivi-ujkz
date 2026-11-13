@extends('layouts.app')
@section('title', 'Tableau de bord')
@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-ujkz">Tableau de bord</h1>
        @if($etablissement)
            <p class="mt-1 text-lg font-semibold text-slate-800">{{ $etablissement['nom'] }}</p>
        @endif
        @if(auth()->user()->role === 'chef')
            <p class="mt-1 text-lg font-semibold text-slate-800">{{ $departementChef->nom ?? 'Département non attribué' }}</p>
        @endif
        <p class="text-slate-600">
            {{ $annee?->libelle ?? 'Aucune année académique' }}
            @if($periode) · {{ $periode->libelle }} @endif
            @if($source->where('statut', 'officiel')->isEmpty() && $source->isNotEmpty())
                <span class="text-amber-700">· taux calculés sur les bilans déjà transmis (pas encore tous officiels)</span>
            @endif
        </p>
    </div>
    <form class="flex flex-wrap gap-2" method="GET">
        <select name="annee_id" class="rounded border px-3 py-2" onchange="this.form.submit()">
            @foreach($annees as $a)
                <option value="{{ $a->id }}" @selected(optional($annee)->id === $a->id)>{{ $a->libelle }}</option>
            @endforeach
        </select>
        <select name="periode_id" class="rounded border px-3 py-2" onchange="this.form.submit()">
            @foreach($periodes as $p)
                <option value="{{ $p->id }}" @selected(optional($periode)->id === $p->id)>{{ $p->libelle }}</option>
            @endforeach
        </select>
    </form>
</div>

@if(auth()->user()->role === 'chef' && auth()->user()->departement && auth()->user()->departement->formations()->doesntExist())
    <p class="mb-6 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm">Votre département n'a pas encore de tableau de bilan. <a class="font-semibold underline" href="{{ route('formations.index') }}">Dressez les filières et les niveaux</a> avant de renseigner le suivi.</p>
@endif

@if($etablissement)
<section class="mb-4 grid gap-4 sm:grid-cols-3">
    @foreach([
        ['UFR', $etablissement['ufrs']],
        ['Instituts', $etablissement['instituts']],
        ['Départements', $etablissement['departements']],
    ] as [$label, $val])
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">{{ $label }}</p>
            <p class="mt-1 text-3xl font-bold text-ujkz">{{ $val }}</p>
        </div>
    @endforeach
</section>
@endif

@if(auth()->user()->role !== 'directeur_adjoint')
<section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @include('dashboard._indicateurs', ['stats' => $stats])
</section>
@endif

@if($prochaine)
    <p class="mt-4 rounded border border-ujkz/20 bg-ujkz-light px-4 py-3 text-sm">Prochaine réunion : <a class="font-semibold underline" href="{{ route('reunions.show', $prochaine) }}">{{ $prochaine->titre }}</a> le {{ $prochaine->date_seance->format('d/m/Y H:i') }}.</p>
@endif

@if($cartesUfr !== null && ! $ufrOuverte)
    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        @forelse($cartesUfr as $carte)
            <a href="{{ route('dashboard', array_merge(request()->query(), ['ufr_id' => $carte['id']])) }}" class="rounded-lg border border-slate-200 bg-white p-5 hover:border-ujkz">
                <h2 class="text-lg font-semibold text-ujkz">{{ $carte['nom'] }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $carte['departements'] }} département(s)</p>
                <p class="mt-3 text-sm">Cours <span class="text-2xl font-bold text-ujkz">{{ $carte['taux_cours'] }} %</span> <span class="text-slate-400">·</span> Évaluations <span class="text-2xl font-bold text-ujkz">{{ $carte['taux_eval'] }} %</span></p>
            </a>
        @empty
            <p class="rounded-lg border border-slate-200 bg-white px-4 py-8 text-center text-slate-500 sm:col-span-2">Aucun bilan transmis.</p>
        @endforelse
    </div>
@else
@if($ufrOuverte)
    <a href="{{ route('dashboard', request()->except('ufr_id')) }}" class="mt-8 inline-block text-sm font-semibold text-ujkz underline">Toutes les UFR</a>
    <h2 class="mt-2 text-xl font-bold text-ujkz">{{ $ufrOuverte['nom'] }}</h2>
@endif
@if($graphiques->isNotEmpty())
    <p class="mt-4 text-sm text-slate-600">Chaque graphique correspond au dernier bilan transmis du département.</p>
@endif
@forelse($graphiques as $i => $graphique)
<section class="mt-8">
    <h2 class="mb-3 text-lg font-semibold text-ujkz">{{ $graphique['titre'] }}</h2>
    @if(! empty($graphique['stats']))
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @include('dashboard._indicateurs', ['stats' => $graphique['stats']])
        </div>
    @endif
    <div class="mt-4 rounded-lg border border-slate-200 bg-white p-5">
        <canvas id="chart-{{ $i }}" height="110"></canvas>
    </div>
</section>
@empty
<section class="mt-8 rounded-lg border border-slate-200 bg-white p-5">
    <p class="text-center text-slate-500">Aucun bilan transmis.</p>
</section>
@endforelse
@endif

@if($mesures->isNotEmpty())
<section class="mt-8 rounded-lg border border-rose-200 bg-white p-5">
    <h2 class="font-semibold">Mesures d'urgence en cours</h2>
    <ul class="mt-3 space-y-2 text-sm">
        @foreach($mesures as $m)
            <li><span class="font-medium">{{ $m->titre }}</span> — {{ \Illuminate\Support\Str::limit($m->contenu, 140) }}</li>
        @endforeach
    </ul>
</section>
@endif

@if(! auth()->user()->recoitBilansOfficiels())
<section class="mt-8 rounded-lg border border-slate-200 bg-white">
    <h2 class="border-b px-5 py-4 font-semibold">Dossiers à suivre</h2>
    @forelse($file as $b)
        <a href="{{ route('bilans.edit', $b) }}" class="flex items-center justify-between gap-3 border-b px-5 py-3 last:border-0 hover:bg-slate-50">
            <span>{{ $b->departement->nom }} <span class="text-slate-500">· {{ $b->periode->libelle }}</span></span>
            @include('bilans._badge', ['statut' => $b->statut])
        </a>
    @empty
        <p class="px-5 py-8 text-center text-slate-500">Aucun bilan dans votre périmètre.</p>
    @endforelse
</section>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
const graphiques = @json($graphiques);
graphiques.forEach(function (graphique, i) {
    new Chart(document.getElementById('chart-' + i), {
        type: 'bar',
        data: {
            labels: graphique.barres.map(x => x.nom),
            datasets: [
                { label: 'Cours (%)', data: graphique.barres.map(x => x.cours), backgroundColor: '#1E3A5F' },
                { label: 'Évaluations (%)', data: graphique.barres.map(x => x.eval), backgroundColor: '#7C93A8' },
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { min: 0, max: 100 },
                x: { ticks: { autoSkip: false, maxRotation: 40, minRotation: 0 } }
            }
        }
    });
});
</script>
@endsection

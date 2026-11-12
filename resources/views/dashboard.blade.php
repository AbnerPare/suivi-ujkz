@extends('layouts.app')
@section('title', 'Tableau de bord')
@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-ujkz">Tableau de bord</h1>
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

<section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach([
        ['Taux des cours', $stats['taux_cours'].' %'],
        ['Taux des évaluations', $stats['taux_eval'].' %'],
        ['Bilans officiels', $stats['officiels'].' / '.$stats['bilans']],
        ['Bilans en retard (< 50 %)', $stats['retards']],
    ] as [$label, $val])
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-500">{{ $label }}</p>
            <p class="mt-1 text-3xl font-bold text-ujkz">{{ $val }}</p>
        </div>
    @endforeach
</section>

@if($prochaine)
    <p class="mt-4 rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm">Prochaine réunion : <a class="font-semibold underline" href="{{ route('reunions.show', $prochaine) }}">{{ $prochaine->titre }}</a> le {{ $prochaine->date_seance->format('d/m/Y H:i') }}.</p>
@endif

<section class="mt-8 grid gap-6 lg:grid-cols-2">
    <div class="rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="mb-3 font-semibold">{{ $comparaisonTitre }}</h2>
        <canvas id="chartComparaison" height="180"></canvas>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="mb-3 font-semibold">Évolution mensuelle des cours</h2>
        <canvas id="chartMois" height="180"></canvas>
    </div>
</section>

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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
const comparaison = @json($comparaison);
const mois = @json($parMois);
new Chart(document.getElementById('chartComparaison'), {
    type: 'bar',
    data: {
        labels: comparaison.map(x => x.nom),
        datasets: [
            { label: 'Cours (%)', data: comparaison.map(x => x.cours), backgroundColor: '#1e3a8a' },
            { label: 'Évaluations (%)', data: comparaison.map(x => x.eval), backgroundColor: '#f59e0b' },
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { min: 0, max: 100 },
            x: { ticks: { autoSkip: false, maxRotation: 50, minRotation: 0 } }
        }
    }
});
new Chart(document.getElementById('chartMois'), {
    type: 'line',
    data: { labels: mois.map(x => x.mois), datasets: [{ label: 'Taux officiel des cours', data: mois.map(x => x.taux), borderColor: '#1e3a8a', tension: 0.2 }] },
    options: { responsive: true, scales: { y: { min: 0, max: 100 } } }
});
</script>
@endsection

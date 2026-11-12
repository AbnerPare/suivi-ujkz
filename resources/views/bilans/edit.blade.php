@extends('layouts.app')
@section('title', 'Bilan')
@section('content')
@php
    $user = auth()->user();
@endphp
<div class="mb-4 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-ujkz">{{ $bilan->departement->nom }}</h1>
        <p class="text-slate-600">{{ $bilan->departement->ufr->libelleType() }} {{ $bilan->departement->ufr->sigle }} · {{ $bilan->annee->libelle }} · {{ $bilan->periode->libelle }} · version {{ $bilan->version }}</p>
    </div>
    @include('bilans._badge', ['statut' => $bilan->statut])
</div>

@if($bilan->motif_reouverture)
    <p class="mb-4 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm">Réouverture : {{ $bilan->motif_reouverture }}</p>
@endif

<form method="POST" action="{{ route('bilans.update', $bilan) }}">
    @csrf @method('PUT')
    <div class="overflow-x-auto rounded-lg border border-slate-400 bg-white">
        <table class="min-w-[1200px] w-full border-collapse text-sm">
            <thead>@include('bilans._entete')</thead>
            <tbody>
            @forelse($groupes as $filiere)
                @foreach($filiere['parcours'] as $pi => $parcours)
                    @foreach($parcours['lignes'] as $li => $l)
                        @php($ro = ! $bilan->editablePar($user))
                        <tr>
                            @if($loop->parent->first && $li === 0)
                                <td rowspan="{{ $filiere['rows'] }}" class="border border-slate-300 px-2 py-2 align-middle font-medium">{{ $filiere['nom'] }}</td>
                            @endif
                            @if($li === 0)
                                <td rowspan="{{ count($parcours['lignes']) }}" class="border border-slate-300 px-2 py-2 text-center align-middle">{{ $parcours['nom'] }}</td>
                            @endif
                            <td class="border border-slate-300 px-2 py-2 text-center">{{ $l->semestre }}</td>
                            @foreach(['ec_total','ec_effectues'] as $f)
                                <td class="border border-slate-300 px-2 py-2 text-center"><input type="number" min="0" name="lignes[{{ $l->id }}][{{ $f }}]" value="{{ $l->$f }}" class="w-20 rounded border px-2 py-1 text-center" @disabled($ro)></td>
                            @endforeach
                            <td class="border border-slate-300 px-2 py-2 text-center font-medium">{{ $l->tauxCours() !== null ? $l->tauxCours().' %' : '—' }}</td>
                            @foreach(['ec_a_evaluer','ec_evalues'] as $f)
                                <td class="border border-slate-300 px-2 py-2 text-center"><input type="number" min="0" name="lignes[{{ $l->id }}][{{ $f }}]" value="{{ $l->$f }}" class="w-20 rounded border px-2 py-1 text-center" @disabled($ro)></td>
                            @endforeach
                            <td class="border border-slate-300 px-2 py-2 text-center font-medium">{{ $l->tauxEval() !== null ? $l->tauxEval().' %' : '—' }}</td>
                            <td class="border border-slate-300 px-2 py-2 text-center">
                                <select name="lignes[{{ $l->id }}][deliberation]" class="rounded border px-2 py-1" @disabled($ro)>
                                    <option value="0" @selected($l->deliberation==0)>0</option>
                                    <option value="1" @selected($l->deliberation==1)>1</option>
                                    <option value="2" @selected($l->deliberation==2)>2</option>
                                </select>
                            </td>
                            <td class="border border-slate-300 px-2 py-2"><input name="lignes[{{ $l->id }}][observations]" value="{{ $l->observations }}" class="w-44 rounded border px-2 py-1" @disabled($ro)></td>
                        </tr>
                    @endforeach
                @endforeach
            @empty
                <tr><td colspan="11" class="px-3 py-8 text-center text-slate-500">Aucune ligne. Le chef de département doit d'abord dresser le tableau.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        <label class="mb-1 block text-sm font-medium">Observations générales du chef de département</label>
        <textarea name="commentaire_chef" rows="3" class="w-full rounded border px-3 py-2" @disabled(! $bilan->editablePar($user))>{{ $bilan->commentaire_chef }}</textarea>
    </div>
    @if($bilan->editablePar($user))
        <button class="mt-3 rounded bg-slate-800 px-4 py-2 font-semibold text-white">Enregistrer</button>
    @endif
</form>

<div class="mt-4 flex flex-wrap gap-3">
    @if($bilan->editablePar($user) && $bilan->statut === 'brouillon')
        <form method="POST" action="{{ route('bilans.transmettre', $bilan) }}">@csrf
            <button class="rounded bg-ujkz px-4 py-2 font-semibold text-white">Transmettre au directeur adjoint</button>
        </form>
    @endif
    @if($bilan->editablePar($user) && in_array($bilan->statut, ['retour_correction','corrige']))
        <form method="POST" action="{{ route('bilans.transmettre', $bilan) }}">@csrf
            <button class="rounded bg-ujkz px-4 py-2 font-semibold text-white">Transmettre le bilan officiel</button>
        </form>
    @endif
</div>

@if($bilan->examinablePar($user))
<form method="POST" action="{{ route('bilans.examiner', $bilan) }}" class="mt-6 rounded-lg border bg-white p-5">
    @csrf
    <h2 class="font-semibold">Examen — {{ $user->roleLabel() }}</h2>
    <p class="mt-1 text-sm text-slate-600">La validation envoie le bilan à l'étape suivante. Le retour au chef de département n'a lieu qu'après le passage chez les deux directeurs.</p>
    <textarea name="contenu" rows="3" placeholder="Observations et recommandations" class="mt-3 w-full rounded border px-3 py-2"></textarea>
    <button class="mt-3 rounded bg-ujkz px-4 py-2 font-semibold text-white">
        {{ $bilan->statut === 'attente_adjoint' ? 'Valider et transmettre au directeur' : 'Valider et retourner au chef de département' }}
    </button>
</form>
@endif

@if($bilan->statut === 'officiel' && in_array($user->role, ['dip','president','admin']))
<form method="POST" action="{{ route('bilans.rouvrir', $bilan) }}" class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-5">
    @csrf
    <h2 class="font-semibold">Rouvrir le bilan officiel</h2>
    <textarea name="motif" required rows="2" placeholder="Motif de la réouverture" class="mt-3 w-full rounded border px-3 py-2"></textarea>
    <button class="mt-3 rounded bg-amber-700 px-4 py-2 font-semibold text-white">Autoriser la réouverture</button>
</form>
@endif

<section class="mt-8 grid gap-6 {{ $user->role === 'dip' ? '' : 'lg:grid-cols-2' }}">
    @if($user->role !== 'dip')
    <div class="rounded-lg border bg-white p-5">
        <h2 class="font-semibold">Observations</h2>
        @forelse($bilan->observations as $o)
            <article class="mt-3 border-t pt-3 text-sm">
                <p class="font-medium">{{ $o->auteur->name ?? 'Utilisateur' }} · {{ $o->roleLabel() }}</p>
                <p class="text-xs text-slate-500">{{ $o->created_at->format('d/m/Y H:i') }}</p>
                <p class="mt-1">{{ $o->contenu }}</p>
            </article>
        @empty
            <p class="mt-2 text-sm text-slate-500">Aucune observation des directeurs.</p>
        @endforelse
    </div>
    @endif
    <div class="rounded-lg border bg-white p-5">
        <h2 class="font-semibold">Historique des statuts</h2>
        <ol class="mt-3 space-y-2 text-sm">
            @foreach($bilan->historiques->sortByDesc('id') as $h)
                <li><span class="font-medium">{{ \App\Support\Roles::statut($h->vers) }}</span> — {{ $h->commentaire }} <span class="text-slate-500">({{ $h->created_at->format('d/m/Y H:i') }}{{ $h->auteur ? ', '.$h->auteur->name : '' }})</span></li>
            @endforeach
        </ol>
        @if($bilan->snapshots->isNotEmpty())
            <h3 class="mt-4 font-semibold">Versions conservées</h3>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach($bilan->snapshots as $s)
                    <li>Version {{ $s->version }} · {{ $s->created_at->format('d/m/Y H:i') }} · {{ $s->motif }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
@endsection

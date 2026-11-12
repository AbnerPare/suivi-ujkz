@extends('layouts.app')
@section('title', 'Bilan')
@section('content')
@php($user = auth()->user())
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
    <div class="overflow-x-auto rounded-lg border bg-white">
        <table class="min-w-[1100px] w-full text-sm">
            <thead class="bg-slate-100 text-left">
                <tr>
                    <th class="px-3 py-2">Filière</th>
                    <th class="px-3 py-2">Parcours</th>
                    <th class="px-3 py-2">Niveau</th>
                    <th class="px-3 py-2">Semestre</th>
                    <th class="px-3 py-2">EC prévus</th>
                    <th class="px-3 py-2">EC effectués</th>
                    <th class="px-3 py-2">Taux cours</th>
                    <th class="px-3 py-2">EC à évaluer</th>
                    <th class="px-3 py-2">EC évalués</th>
                    <th class="px-3 py-2">Taux éval.</th>
                    <th class="px-3 py-2">Délibération</th>
                    <th class="px-3 py-2">Observations</th>
                </tr>
            </thead>
            <tbody>
            @foreach($bilan->lignes as $l)
                @php($ro = ! $bilan->editablePar($user))
                <tr class="border-t">
                    <td class="px-3 py-2">{{ $l->filiere }}</td>
                    <td class="px-3 py-2">{{ $l->parcours }}</td>
                    <td class="px-3 py-2">{{ $l->niveau }}</td>
                    <td class="px-3 py-2">{{ $l->semestre }}</td>
                    @foreach(['ec_total','ec_effectues'] as $f)
                        <td class="px-3 py-2"><input type="number" min="0" name="lignes[{{ $l->id }}][{{ $f }}]" value="{{ $l->$f }}" class="w-20 rounded border px-2 py-1" @disabled($ro)></td>
                    @endforeach
                    <td class="px-3 py-2 font-medium">{{ $l->tauxCours() !== null ? $l->tauxCours().' %' : '—' }}</td>
                    @foreach(['ec_a_evaluer','ec_evalues'] as $f)
                        <td class="px-3 py-2"><input type="number" min="0" name="lignes[{{ $l->id }}][{{ $f }}]" value="{{ $l->$f }}" class="w-20 rounded border px-2 py-1" @disabled($ro)></td>
                    @endforeach
                    <td class="px-3 py-2 font-medium">{{ $l->tauxEval() !== null ? $l->tauxEval().' %' : '—' }}</td>
                    <td class="px-3 py-2">
                        <select name="lignes[{{ $l->id }}][deliberation]" class="rounded border px-2 py-1" @disabled($ro)>
                            <option value="0" @selected($l->deliberation==0)>0 — aucune</option>
                            <option value="1" @selected($l->deliberation==1)>1 — 1re session</option>
                            <option value="2" @selected($l->deliberation==2)>2 — session annuelle</option>
                        </select>
                    </td>
                    <td class="px-3 py-2"><input name="lignes[{{ $l->id }}][observations]" value="{{ $l->observations }}" class="w-48 rounded border px-2 py-1" @disabled($ro)></td>
                </tr>
            @endforeach
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

<section class="mt-8 grid gap-6 lg:grid-cols-2">
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

@extends('layouts.app')
@section('title', $reunion->titre)
@section('content')
@php
    $user = auth()->user();
@endphp
<h1 class="text-2xl font-bold text-ujkz">{{ $reunion->titre }}</h1>
<p class="text-slate-600">{{ $reunion->date_seance->format('d/m/Y H:i') }} @if($reunion->lieu)· {{ $reunion->lieu }}@endif · {{ $reunion->statut }}</p>
@if($reunion->ordre_du_jour)
    <div class="mt-4 rounded-lg border bg-white p-4"><h2 class="font-semibold">Ordre du jour</h2><p class="mt-2 whitespace-pre-line text-sm">{{ $reunion->ordre_du_jour }}</p></div>
@endif

<section class="mt-6 rounded-lg border bg-white p-4">
    <h2 class="font-semibold">Présences et émargement</h2>
    @if($reunion->participants->contains('id', $user->id))
        <form method="POST" action="{{ route('reunions.emarger', $reunion) }}" class="mt-2">@csrf<button class="rounded bg-ujkz px-3 py-1.5 text-sm font-semibold text-white">Émarger ma présence</button></form>
    @endif
    <form method="POST" action="{{ route('reunions.presence', $reunion) }}" class="mt-3">
        @csrf
        <ul class="space-y-1 text-sm">
            @foreach($reunion->participants as $p)
                <li class="flex items-center gap-2">
                    @if($user->organiseSeances())
                        <input type="checkbox" name="presents[]" value="{{ $p->id }}" @checked($p->pivot->present)>
                    @else
                        <span>{{ $p->pivot->present ? 'Présent' : 'Absent' }}</span>
                    @endif
                    {{ $p->name }} <span class="text-slate-500">{{ $p->roleLabel() }}</span>
                    @if($p->pivot->emarge_at)<span class="text-xs text-emerald-700">émargé</span>@endif
                </li>
            @endforeach
        </ul>
        @if($user->organiseSeances())
            <button class="mt-3 rounded bg-slate-800 px-3 py-1.5 text-white">Enregistrer la feuille de présence</button>
        @endif
    </form>
</section>

<section class="mt-6 rounded-lg border bg-white p-4">
    <h2 class="font-semibold">Procès-verbal</h2>
    @if($reunion->pv_publie || $user->organiseSeances())
        @if($user->organiseSeances())
        <form method="POST" action="{{ route('reunions.pv', $reunion) }}" class="mt-3 space-y-2">
            @csrf
            <textarea name="pv" rows="8" class="w-full rounded border px-3 py-2">{{ $reunion->pv }}</textarea>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="publier" value="1" @checked($reunion->pv_publie)> Publier et notifier les participants</label>
            <button class="rounded bg-ujkz px-4 py-2 font-semibold text-white">Enregistrer le PV</button>
        </form>
        @else
            <p class="mt-2 whitespace-pre-line text-sm">{{ $reunion->pv ?: 'Pas encore publié.' }}</p>
        @endif
    @else
        <p class="mt-2 text-sm text-slate-500">Le procès-verbal n'est pas encore publié.</p>
    @endif
</section>
@endsection

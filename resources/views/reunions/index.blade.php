@extends('layouts.app')
@section('title', 'Réunions')
@section('content')
<h1 class="text-2xl font-bold text-ujkz">Réunions de suivi</h1>
@if($peutCreer)
<form method="POST" action="{{ route('reunions.store') }}" class="mt-4 space-y-3 rounded-lg border bg-white p-4">
    @csrf
    <div class="grid gap-3 md:grid-cols-3">
        <input name="titre" placeholder="Titre de la séance" required class="rounded border px-3 py-2 md:col-span-2">
        <input type="datetime-local" name="date_seance" required class="rounded border px-3 py-2">
        <input name="lieu" placeholder="Lieu" class="rounded border px-3 py-2">
    </div>
    <textarea name="ordre_du_jour" rows="2" placeholder="Ordre du jour" class="w-full rounded border px-3 py-2"></textarea>
    <fieldset>
        <legend class="text-sm font-medium">Participants</legend>
        <div class="mt-2 grid gap-1 sm:grid-cols-2">
            @foreach($users as $p)
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="participants[]" value="{{ $p->id }}"> {{ $p->name }} <span class="text-slate-500">({{ $p->roleLabel() }})</span></label>
            @endforeach
        </div>
    </fieldset>
    <button class="rounded bg-ujkz px-4 py-2 font-semibold text-white">Programmer et convoquer</button>
</form>
@endif
<ul class="mt-6 divide-y rounded-lg border bg-white">
    @foreach($reunions as $r)
        <li><a class="flex justify-between px-4 py-3 hover:bg-slate-50" href="{{ route('reunions.show', $r) }}"><span class="font-medium">{{ $r->titre }}</span><span class="text-sm text-slate-500">{{ $r->date_seance->format('d/m/Y H:i') }} · {{ $r->statut }} · {{ $r->participants_count }} participants</span></a></li>
    @endforeach
</ul>
<div class="mt-4">{{ $reunions->links() }}</div>
@endsection

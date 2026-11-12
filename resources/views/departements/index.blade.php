@extends('layouts.app')
@section('title', 'Départements')
@section('content')
<h1 class="mb-6 text-2xl font-bold text-ujkz">Départements</h1>
<form method="POST" action="{{ route('departements.store') }}" class="mb-6 flex max-w-xl gap-2">
    @csrf
    <input name="nom" value="{{ old('nom') }}" required placeholder="Nom du nouveau département" class="w-full rounded border border-slate-300 px-3 py-2">
    <button class="whitespace-nowrap rounded bg-ujkz px-4 py-2 font-semibold text-white hover:bg-ujkz-dark">Ajouter</button>
</form>
<div class="max-w-xl overflow-hidden rounded-lg border border-slate-200 bg-white">
    @forelse($departements as $d)
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 last:border-0">
            <div><p class="font-medium">{{ $d->nom }}</p><p class="text-sm text-slate-500">{{ $d->cours_count }} cours · {{ $d->users_count }} utilisateur(s)</p></div>
            <form method="POST" action="{{ route('departements.destroy', $d) }}" onsubmit="return confirm('Supprimer ce département et ses cours ?')">@csrf @method('DELETE')<button class="text-sm text-red-700 underline">Supprimer</button></form>
        </div>
    @empty
        <p class="px-4 py-8 text-center text-slate-500">Aucun département. Ajoutez-en un avec le champ ci-dessus.</p>
    @endforelse
</div>
@endsection

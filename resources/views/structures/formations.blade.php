@extends('layouts.app')
@section('title', 'Formations')
@section('content')
<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <h1 class="text-2xl font-bold text-ujkz">Formations, niveaux et semestres</h1>
    <form method="GET">
        <select name="departement_id" class="rounded border px-3 py-2" onchange="this.form.submit()">
            @foreach($departements as $d)
                <option value="{{ $d->id }}" @selected($filtre==$d->id)>{{ $d->ufr->sigle }} — {{ $d->nom }}</option>
            @endforeach
        </select>
    </form>
</div>
@if(in_array(auth()->user()->role, ['admin','president','chef']) && $filtre)
<form method="POST" action="{{ route('formations.store') }}" class="mb-4 grid gap-2 rounded-lg border bg-white p-4 md:grid-cols-6">
    @csrf
    <input type="hidden" name="departement_id" value="{{ $filtre }}">
    <input name="filiere" placeholder="Filière" required class="rounded border px-3 py-2 md:col-span-2">
    <input name="parcours" placeholder="Parcours" class="rounded border px-3 py-2">
    <select name="niveau" class="rounded border px-3 py-2">@foreach(\App\Models\Formation::NIVEAUX as $n)<option>{{ $n }}</option>@endforeach</select>
    <select name="semestre" class="rounded border px-3 py-2">@foreach(['S1','S2','S3','S4','S5','S6'] as $s)<option>{{ $s }}</option>@endforeach</select>
    <button class="rounded bg-ujkz px-3 py-2 font-semibold text-white">Ajouter</button>
</form>
@endif
<table class="w-full overflow-hidden rounded-lg border bg-white text-sm">
    <thead class="bg-slate-100 text-left"><tr><th class="px-3 py-2">Filière</th><th class="px-3 py-2">Parcours</th><th class="px-3 py-2">Niveau</th><th class="px-3 py-2">Semestre</th><th></th></tr></thead>
    <tbody>
    @forelse($formations as $f)
        <tr class="border-t">
            <td class="px-3 py-2">{{ $f->filiere }}</td>
            <td class="px-3 py-2">{{ $f->parcours }}</td>
            <td class="px-3 py-2">{{ $f->niveau }}</td>
            <td class="px-3 py-2">{{ $f->semestre }}</td>
            <td class="px-3 py-2 text-right">
                @if(in_array(auth()->user()->role, ['admin','president','chef']))
                <form method="POST" action="{{ route('formations.destroy', $f) }}">@csrf @method('DELETE')<button class="text-red-700 underline">Retirer</button></form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">Aucune formation pour ce département.</td></tr>
    @endforelse
    </tbody>
</table>
<p class="mt-3 text-sm text-slate-500">Licence : L1 (S1-S2), L2 (S3-S4), L3 (S5-S6). Master : M1 (S1-S2), M2 (S3-S4).</p>
@endsection

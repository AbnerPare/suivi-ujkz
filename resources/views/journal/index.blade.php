@extends('layouts.app')
@section('title', 'Journal')
@section('content')
<h1 class="text-2xl font-bold text-ujkz">Journal d'activité</h1>
<table class="mt-4 w-full rounded-lg border bg-white text-sm">
    <thead class="bg-slate-100 text-left"><tr><th class="px-3 py-2">Date</th><th class="px-3 py-2">Utilisateur</th><th class="px-3 py-2">Université</th><th class="px-3 py-2">Action</th><th class="px-3 py-2">Détail</th></tr></thead>
    <tbody>
    @foreach($journaux as $j)
        <tr class="border-t">
            <td class="px-3 py-2 whitespace-nowrap">{{ $j->created_at->format('d/m/Y H:i') }}</td>
            <td class="px-3 py-2">{{ $j->user->name ?? '—' }}</td>
            <td class="px-3 py-2">{{ $j->universite->sigle ?? '—' }}</td>
            <td class="px-3 py-2">{{ $j->action }}</td>
            <td class="px-3 py-2">{{ $j->cible }} {{ $j->details }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="mt-4">{{ $journaux->links() }}</div>
@endsection

@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-ujkz">Notifications</h1>
    <form method="POST" action="{{ route('avis.tout') }}">@csrf<button class="text-sm underline">Tout marquer comme lu</button></form>
</div>
<div class="divide-y rounded-lg border bg-white">
    @forelse($avis as $a)
        <form method="POST" action="{{ route('avis.lire', $a) }}" class="block">
            @csrf
            <button class="w-full px-4 py-3 text-left hover:bg-slate-50 {{ $a->lu_at ? 'opacity-70' : 'bg-blue-50/40' }}">
                <span class="font-semibold">{{ $a->titre }}</span>
                <span class="ml-2 text-xs text-slate-500">{{ $a->created_at->format('d/m/Y H:i') }} · {{ $a->lu_at ? 'lue' : 'non lue' }}</span>
                <p class="text-sm">{{ $a->message }}</p>
            </button>
        </form>
    @empty
        <p class="px-4 py-8 text-center text-slate-500">Aucune notification.</p>
    @endforelse
</div>
<div class="mt-4">{{ $avis->links() }}</div>
@endsection

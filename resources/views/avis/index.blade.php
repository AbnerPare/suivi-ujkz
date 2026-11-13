@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="mb-4">
    <h1 class="text-2xl font-bold text-ujkz">Notifications</h1>
</div>
<div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
    @forelse($avis as $a)
        @php $nonLue = $a->lu_at === null; @endphp
        <a @if($a->lien) href="{{ $a->lien }}" @endif @if($nonLue) data-non-lue @endif class="flex flex-col gap-1 border-b border-slate-100 px-4 py-3.5 last:border-0 {{ $a->lien ? 'transition hover:bg-slate-50' : '' }} {{ $nonLue ? 'border-l-4 border-ujkz bg-ujkz-light/70' : '' }}">
            <span class="flex items-start justify-between gap-3">
                <span data-titre class="{{ $nonLue ? 'font-semibold text-slate-900 underline decoration-ujkz decoration-2 underline-offset-4' : 'font-medium text-slate-600' }}">{{ $a->titre }}</span>
                @if($nonLue)
                    <span data-badge class="shrink-0 rounded-full bg-ujkz px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-white">Non lue</span>
                @endif
            </span>
            <span class="text-xs text-slate-500">{{ $a->created_at->format('d/m/Y H:i') }}</span>
            <p data-message class="text-sm {{ $nonLue ? 'font-medium text-slate-800' : 'text-slate-500' }}">{{ $a->message }}</p>
        </a>
    @empty
        <p class="px-4 py-8 text-center text-slate-500">Aucune notification.</p>
    @endforelse
</div>
<div class="mt-4">{{ $avis->links() }}</div>
@if($avis->contains(fn ($a) => $a->lu_at === null))
<script>
    window.setTimeout(function () {
        var lignes = document.querySelectorAll('[data-non-lue]');
        var vues = lignes.length;
        lignes.forEach(function (ligne) {
            ligne.removeAttribute('data-non-lue');
            ligne.classList.remove('border-l-4', 'border-ujkz', 'bg-ujkz-light/70');
            var titre = ligne.querySelector('[data-titre]');
            if (titre) titre.className = 'font-medium text-slate-600';
            var message = ligne.querySelector('[data-message]');
            if (message) message.className = 'text-sm text-slate-500';
            var badge = ligne.querySelector('[data-badge]');
            if (badge) badge.remove();
        });
        var menu = document.getElementById('menu-notifications');
        var compteur = document.getElementById('menu-notifications-badge');
        if (!menu || !compteur) return;
        var reste = Math.max(0, Number(compteur.textContent) - vues);
        if (reste === 0) {
            compteur.remove();
            menu.classList.remove('underline', 'decoration-2', 'underline-offset-4');
        } else {
            compteur.textContent = String(reste);
        }
    }, 900);
</script>
@endif
@endsection

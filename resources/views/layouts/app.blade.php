<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1E3A5F">
    <title>@yield('title', 'Suivi des enseignements') | SUIVI-ENSEIGNEMENTS</title>
    @php
        $marque = auth()->user()?->universite;
        $logo = $marque?->urlLogo();
        $logoAlt = $marque?->nom ?? 'SUIVI-ENSEIGNEMENTS';
        if (! $logo && (! auth()->check() || auth()->user()->isAdmin())) {
            $ujkz = \App\Models\Universite::where('sigle', 'UJKZ')->first();
            $logo = $ujkz?->urlLogo() ?? asset('images/logo-ujkz.png');
            $logoAlt = $ujkz->nom ?? 'Université Joseph Ki-Zerbo';
        }
    @endphp
    <link rel="icon" href="{{ $logo ?? asset('images/logo-ujkz.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['"Source Sans 3"', 'system-ui', 'sans-serif'] },
            colors: { ujkz: { DEFAULT: '#1E3A5F', dark: '#152A45', light: '#E8EEF5' } }
        } } }
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased">
@auth
    @php
        $u = auth()->user();
        $nonLus = \App\Models\Avis::where('user_id', $u->id)->whereNull('lu_at')->count();
        $links = [
            ['dashboard', 'Tableau de bord', true],
            ['bilans.index', 'Bilans', $u->voitBilans()],
            ['formations.index', 'Tableau', in_array($u->role, ['admin','admin_universite','chef','directeur_adjoint'])],
            ['ufrs.index', 'UFR / Instituts', $u->gereStructures()],
            ['departements.index', 'Départements', $u->gereStructures()],
            ['annees.index', 'Campagnes', in_array($u->role, ['admin','admin_universite','dip','vice_president'])],
            ['reunions.index', 'Réunions', true],
            ['decisions.index', 'Décisions', true],
            ['users.index', 'Utilisateurs', $u->gereStructures()],
            ['universites.index', 'Universités', $u->isAdmin()],
            ['journal.index', 'Journal', $u->isAdmin()],
        ];
    @endphp
    <input id="menu-ouvert" type="checkbox" class="peer sr-only">
    <label for="menu-ouvert" class="fixed inset-0 z-20 hidden bg-slate-900/40 peer-checked:block md:!hidden"></label>
    <aside class="fixed inset-y-0 left-0 z-30 flex w-64 -translate-x-full flex-col bg-ujkz text-white transition-transform peer-checked:translate-x-0 md:translate-x-0">
        <div class="flex items-center justify-between gap-2 border-b border-white/10 px-4 py-4">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5">
                @if($logo)<img src="{{ $logo }}" alt="{{ $logoAlt }}" class="h-20 w-20 shrink-0 rounded-md bg-white object-contain p-1">@endif
                <span class="text-sm font-semibold leading-tight tracking-tight">SUIVI-ENSEIGNEMENTS</span>
            </a>
            <label for="menu-ouvert" class="cursor-pointer rounded px-2 py-1 text-sm hover:bg-white/10 md:hidden">Fermer</label>
        </div>
        <nav class="flex flex-1 flex-col gap-1 overflow-y-auto px-3 py-4 text-sm">
            @foreach($links as [$route, $label, $show])
                @if($show)
                    <a href="{{ route($route) }}" class="rounded px-3 py-2 hover:bg-white/10 {{ request()->routeIs(explode('.', $route)[0].'*') || ($route === 'dashboard' && request()->routeIs('dashboard')) ? 'bg-white/15 font-semibold' : '' }}">{{ $label }}</a>
                @endif
            @endforeach
            <a id="menu-notifications" href="{{ route('avis.index') }}" class="rounded px-3 py-2 hover:bg-white/10 {{ request()->routeIs('avis.*') ? 'bg-white/15 font-semibold' : '' }} {{ $nonLus ? 'underline decoration-2 underline-offset-4' : '' }}">Notifications @if($nonLus)<span id="menu-notifications-badge" class="ml-1 rounded-full bg-white px-1.5 text-xs font-bold text-ujkz no-underline">{{ $nonLus }}</span>@endif</a>
        </nav>
        <div class="border-t border-white/10 px-4 py-4 text-sm">
            <p class="truncate font-medium">{{ $u->name }}</p>
            <p class="mt-0.5 truncate text-white/75">{{ $u->roleLabel() }}@if($u->universite) · {{ $u->universite->sigle }}@endif</p>
            <a href="{{ route('compte.edit') }}" class="mt-3 block rounded px-3 py-1.5 hover:bg-white/10 {{ request()->routeIs('compte.*') ? 'bg-white/15 font-semibold' : '' }}">Identifiant</a>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button class="w-full rounded border border-white/40 px-3 py-1.5 hover:bg-white/10">Se déconnecter</button>
            </form>
        </div>
    </aside>
@endauth
<div class="@auth min-h-screen md:pl-64 @endauth">
@auth
    <div class="flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 md:hidden">
        <label for="menu-ouvert" class="cursor-pointer rounded border border-slate-300 px-3 py-1.5 text-sm font-medium">Menu</label>
        @if($logo)<img src="{{ $logo }}" alt="" class="h-12 w-12 object-contain">@endif
        <span class="font-semibold text-ujkz">SUIVI-ENSEIGNEMENTS</span>
    </div>
@endauth
<main class="@guest mx-auto max-w-7xl @endguest px-4 py-8 md:px-8">
    @if(session('ok'))
        <div class="mb-6 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800" role="status">{{ session('ok') }}</div>
    @endif
    @if(session('erreur'))
        <div class="mb-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800" role="alert">{{ session('erreur') }}</div>
    @endif
    @if($errors->any() && ! request()->routeIs('login', 'inscription'))
        <div class="mb-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800" role="alert">
            <ul class="list-inside list-disc text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
</div>
</body>
</html>

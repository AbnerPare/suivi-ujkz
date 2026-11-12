<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1e3a8a">
    <title>@yield('title', 'Suivi des enseignements') | SUIVI-ENSEIGNEMENTS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['"Source Sans 3"', 'system-ui', 'sans-serif'] },
            colors: { ujkz: { DEFAULT: '#1e3a8a', dark: '#172a63', light: '#e8edf9' } }
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
            ['formations.index', 'Tableau', in_array($u->role, ['admin','president','chef','directeur_ufr','directeur_adjoint'])],
            ['ufrs.index', 'UFR / Instituts', in_array($u->role, ['admin','president','dip','directeur_ufr','vice_president'])],
            ['departements.index', 'Départements', in_array($u->role, ['admin','president','dip','directeur_ufr','vice_president'])],
            ['annees.index', 'Campagnes', in_array($u->role, ['admin','president','dip','vice_president'])],
            ['reunions.index', 'Réunions', true],
            ['decisions.index', 'Décisions', true],
            ['users.index', 'Utilisateurs', in_array($u->role, ['admin','president'])],
            ['universites.index', 'Universités', $u->isAdmin()],
            ['journal.index', 'Journal', $u->isAdmin()],
        ];
    @endphp
    <header class="bg-ujkz text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-tight">SUIVI-ENSEIGNEMENTS</a>
            <nav class="flex flex-wrap items-center gap-1 text-sm">
                @foreach($links as [$route, $label, $show])
                    @if($show)
                        <a href="{{ route($route) }}" class="rounded px-2.5 py-1.5 hover:bg-white/10 {{ request()->routeIs(explode('.', $route)[0].'*') || ($route === 'dashboard' && request()->routeIs('dashboard')) ? 'bg-white/15' : '' }}">{{ $label }}</a>
                    @endif
                @endforeach
                <a href="{{ route('avis.index') }}" class="rounded px-2.5 py-1.5 hover:bg-white/10 {{ request()->routeIs('avis.*') ? 'bg-white/15' : '' }}">Notifications @if($nonLus)<span class="ml-1 rounded-full bg-amber-400 px-1.5 text-xs font-bold text-slate-900">{{ $nonLus }}</span>@endif</a>
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="flex items-center gap-3 text-sm">
                @csrf
                <span class="hidden text-blue-100 lg:inline">{{ $u->name }} · {{ $u->roleLabel() }}@if($u->universite) · {{ $u->universite->sigle }}@endif</span>
                <button class="rounded border border-white/40 px-3 py-1.5 hover:bg-white/10">Se déconnecter</button>
            </form>
        </div>
    </header>
@endauth
<main class="mx-auto max-w-7xl px-4 py-8">
    @if(session('ok'))
        <div class="mb-6 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800" role="status">{{ session('ok') }}</div>
    @endif
    @if($errors->any() && ! request()->routeIs('login', 'inscription'))
        <div class="mb-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800" role="alert">
            <ul class="list-inside list-disc text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>

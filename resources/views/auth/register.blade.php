@extends('layouts.app')
@section('title', 'Inscription université')
@section('content')
<div class="mx-auto max-w-lg">
    <h1 class="text-2xl font-bold text-ujkz">Créer l'espace d'une université</h1>
    <p class="mt-1 text-slate-600">Les données resteront isolées des autres établissements.</p>
    <form method="POST" action="{{ route('inscription') }}" class="mt-6 space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        <div><label class="mb-1 block text-sm font-medium">Nom de l'université</label><input name="universite" value="{{ old('universite') }}" required class="w-full rounded border px-3 py-2"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="mb-1 block text-sm font-medium">Sigle</label><input name="sigle" value="{{ old('sigle') }}" required class="w-full rounded border px-3 py-2"></div>
            <div><label class="mb-1 block text-sm font-medium">Ville</label><input name="ville" value="{{ old('ville') }}" class="w-full rounded border px-3 py-2"></div>
        </div>
        <div><label class="mb-1 block text-sm font-medium">Président(e) — nom</label><input name="name" value="{{ old('name') }}" required class="w-full rounded border px-3 py-2"></div>
        <div><label class="mb-1 block text-sm font-medium">Email</label><input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded border px-3 py-2"></div>
        <div><label class="mb-1 block text-sm font-medium">Mot de passe</label><input type="password" name="password" required class="w-full rounded border px-3 py-2"></div>
        <div><label class="mb-1 block text-sm font-medium">Confirmation</label><input type="password" name="password_confirmation" required class="w-full rounded border px-3 py-2"></div>
        <button class="w-full rounded bg-ujkz px-4 py-2.5 font-semibold text-white">Créer l'espace</button>
    </form>
</div>
@endsection

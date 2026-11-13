@extends('layouts.app')
@section('title', 'Inscription université')
@section('content')
<div class="mx-auto max-w-lg">
    <h1 class="text-2xl font-bold text-ujkz">Création d'une université</h1>
    <p class="mt-3 text-slate-600">L'administrateur système crée l'université et désigne l'administrateur qui prendra en charge les UFR, les départements et les responsables.</p>
    <a href="{{ route('login') }}" class="mt-6 inline-block font-semibold text-ujkz underline">Retour à la connexion</a>
</div>
@endsection

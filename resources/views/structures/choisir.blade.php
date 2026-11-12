@extends('layouts.app')
@section('title', 'Choisir une université')
@section('content')
<h1 class="text-2xl font-bold text-ujkz">Choisir une université</h1>
<ul class="mt-4 divide-y rounded-lg border bg-white">
    @foreach($universites as $u)
        <li><a class="block px-4 py-3 hover:bg-slate-50" href="{{ request()->url() }}?universite_id={{ $u->id }}">{{ $u->nom }} <span class="text-slate-500">({{ $u->sigle }})</span></a></li>
    @endforeach
</ul>
@endsection

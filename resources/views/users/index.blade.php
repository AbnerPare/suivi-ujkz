@extends('layouts.app')
@section('title', 'Utilisateurs')
@section('content')
@php $formulaire = old('formulaire'); @endphp
<h1 class="text-2xl font-bold text-ujkz">Utilisateurs et droits</h1>
<p class="mt-1 text-slate-600">Le vice-président et le DIP sont rattachés à l'université et la gèrent dans son ensemble. Le directeur adjoint est rattaché à une UFR, le chef à son département.</p>
<form method="POST" action="{{ route('users.store') }}" class="compte-form mt-4 grid gap-2 rounded-lg border bg-white p-4 md:grid-cols-4" data-univ="{{ auth()->user()->universite ? auth()->user()->universite->sigle.' — '.auth()->user()->universite->nom : '' }}">
    @csrf
    <input type="hidden" name="formulaire" value="creation">
    <input name="name" value="{{ $formulaire === 'creation' ? old('name') : '' }}" placeholder="Nom" required class="rounded border px-3 py-2">
    <input type="email" name="email" value="{{ $formulaire === 'creation' ? old('email') : '' }}" placeholder="Email" required class="rounded border px-3 py-2">
    <input type="password" name="password" placeholder="Mot de passe" required class="rounded border px-3 py-2">
    <select name="role" class="rounded border px-3 py-2">@foreach($roles as $k => $label)<option value="{{ $k }}" @selected($formulaire === 'creation' && old('role') === $k)>{{ $label }}</option>@endforeach</select>
    @if(auth()->user()->isAdmin())
        <select name="universite_id" class="rounded border px-3 py-2 {{ $formulaire === 'creation' && $errors->has('universite_id') ? 'border-red-600 bg-red-50' : '' }}"><option value="">Université</option>@foreach($universites as $u)<option value="{{ $u->id }}" @selected($formulaire === 'creation' && (string) old('universite_id') === (string) $u->id)>{{ $u->sigle }}</option>@endforeach</select>
    @endif
    <select name="ufr_id" data-champ="ufr" class="rounded border px-3 py-2 {{ $formulaire === 'creation' && $errors->has('ufr_id') ? 'border-red-600 bg-red-50 text-red-700 underline decoration-red-600 decoration-2' : '' }}"><option value="">UFR du directeur adjoint</option>@foreach($ufrs as $u)<option value="{{ $u->id }}" @selected($formulaire === 'creation' && (string) old('ufr_id') === (string) $u->id)>{{ $u->sigle }} — {{ $u->nom }}</option>@endforeach</select>
    <select name="departement_id" data-champ="departement" class="rounded border px-3 py-2 {{ $formulaire === 'creation' && $errors->has('departement_id') ? 'border-red-600 bg-red-50 text-red-700 underline decoration-red-600 decoration-2' : '' }}"><option value="">Département du chef</option>@foreach($departements as $d)<option value="{{ $d->id }}" @selected($formulaire === 'creation' && (string) old('departement_id') === (string) $d->id)>{{ $d->ufr->sigle }} — {{ $d->nom }}</option>@endforeach</select>
    <p data-universite class="hidden self-center text-sm font-medium text-ujkz md:col-span-2"></p>
    <p data-consigne class="self-center text-sm text-slate-500 md:col-span-3"></p>
    <button class="rounded bg-ujkz px-3 py-2 font-semibold text-white">Créer le compte</button>
</form>
<div class="mt-6 space-y-3">
@foreach($users as $user)
    @php $ligne = 'user-'.$user->id; @endphp
    @if(! isset($roles[$user->role]))
        <p class="rounded border bg-white p-3 text-sm">{{ $user->name }} · {{ $user->roleLabel() }} · {{ $user->email }}</p>
        @continue
    @endif
    <form method="POST" action="{{ route('users.update', $user) }}" class="compte-form grid gap-2 rounded border bg-white p-3 md:grid-cols-6" data-univ="{{ $user->universite ? $user->universite->sigle.' — '.$user->universite->nom : '' }}">
        @csrf @method('PUT')
        <input type="hidden" name="formulaire" value="{{ $ligne }}">
        <input name="name" value="{{ $formulaire === $ligne ? old('name', $user->name) : $user->name }}" class="rounded border px-2 py-1">
        <p class="self-center text-sm text-slate-500">{{ $user->email }}</p>
        <select name="role" class="rounded border px-2 py-1">@foreach($roles as $k => $label)<option value="{{ $k }}" @selected(($formulaire === $ligne ? old('role', $user->role) : $user->role) === $k)>{{ $label }}</option>@endforeach</select>
        <select name="ufr_id" data-champ="ufr" class="rounded border px-2 py-1 {{ $formulaire === $ligne && $errors->has('ufr_id') ? 'border-red-600 bg-red-50 text-red-700 underline decoration-red-600 decoration-2' : '' }}"><option value="">UFR du directeur adjoint</option>@foreach($ufrs as $u)<option value="{{ $u->id }}" @selected((string) ($formulaire === $ligne ? old('ufr_id', $user->ufr_id) : $user->ufr_id) === (string) $u->id)>{{ $u->sigle }}</option>@endforeach</select>
        <select name="departement_id" data-champ="departement" class="rounded border px-2 py-1 {{ $formulaire === $ligne && $errors->has('departement_id') ? 'border-red-600 bg-red-50 text-red-700 underline decoration-red-600 decoration-2' : '' }}"><option value="">Département du chef</option>@foreach($departements as $d)<option value="{{ $d->id }}" @selected((string) ($formulaire === $ligne ? old('departement_id', $user->departement_id) : $user->departement_id) === (string) $d->id)>{{ $d->ufr->sigle }} — {{ $d->nom }}</option>@endforeach</select>
        <p data-universite class="hidden self-center text-sm font-medium text-ujkz md:col-span-2"></p>
        <div class="flex gap-2">
            <select name="actif" class="rounded border px-2 py-1"><option value="1" @selected($user->actif)>Actif</option><option value="0" @selected(! $user->actif)>Suspendu</option></select>
            <input type="password" name="password" placeholder="Nouveau mdp" class="w-28 rounded border px-2 py-1">
            <button class="rounded bg-slate-800 px-3 py-1 text-white">OK</button>
        </div>
    </form>
@endforeach
</div>
<div class="mt-4">{{ $users->links() }}</div>
<script>
document.querySelectorAll('form.compte-form').forEach(function (form) {
    var role = form.querySelector('[name=role]');
    var ufr = form.querySelector('[name=ufr_id]');
    var dep = form.querySelector('[name=departement_id]');
    var univ = form.querySelector('[name=universite_id]');
    var bandeau = form.querySelector('[data-universite]');
    var consigne = form.querySelector('[data-consigne]');
    function nomUniv() {
        if (univ && univ.value) return univ.options[univ.selectedIndex].text;
        return form.getAttribute('data-univ') || '';
    }
    function sync() {
        var adjoint = role.value === 'directeur_adjoint';
        var chef = role.value === 'chef';
        var etablissement = role.value === 'vice_president' || role.value === 'dip' || role.value === 'admin_universite';
        ufr.required = adjoint;
        dep.required = chef;
        ufr.disabled = !adjoint;
        dep.disabled = !chef;
        ufr.classList.toggle('hidden', !adjoint);
        dep.classList.toggle('hidden', !chef);
        if (bandeau) {
            bandeau.textContent = nomUniv() ? 'Rattaché à l’université ' + nomUniv() : 'Choisissez l’université';
            bandeau.classList.toggle('hidden', !etablissement);
        }
        if (!consigne) return;
        var ufrOk = ufr.options.length > 1;
        var depOk = dep.options.length > 1;
        consigne.textContent = etablissement
            ? 'Le vice-président et le DIP gèrent toute l’université à laquelle ils sont rattachés.'
            : (adjoint
                ? (ufrOk ? 'Choisissez l’UFR dont cette personne est directeur adjoint.' : 'Créez d’abord une UFR, puis choisissez-la ici.')
                : (chef
                    ? (depOk ? 'Choisissez le département dont cette personne est responsable.' : 'Créez d’abord un département, puis choisissez-le ici.')
                    : ''));
    }
    role.addEventListener('change', sync);
    if (univ) univ.addEventListener('change', sync);
    sync();
});
</script>
@endsection

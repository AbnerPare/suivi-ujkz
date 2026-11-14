@extends('layouts.app')
@section('title', 'Tableau de bilan')
@section('content')
<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-ujkz">Tableau de bilan</h1>
        <p class="mt-1 max-w-3xl text-slate-600">Le chef de département dresse d'abord la grille. Pour chaque filière, il indique les libellés. L1 correspond à S1 et S2, L2 à S3 et S4, L3 à S5 et S6, M1 à S1 et S2, M2 à S3 et S4. Le suivi des EC se remplit ensuite, période par période.</p>
    </div>
    @if($departements->count() > 1)
        <form method="GET">
            <select name="departement_id" class="rounded border px-3 py-2" onchange="this.form.submit()">
                @foreach($departements as $d)
                    <option value="{{ $d->id }}" @selected($filtre==$d->id)>{{ $d->ufr->sigle }} — {{ $d->nom }}</option>
                @endforeach
            </select>
        </form>
    @endif
</div>

@if($peutDresser)
    <form id="tableau-form" method="POST" action="{{ route('formations.tableau') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="departement_id" value="{{ $filtre }}">
        <div id="filieres" class="space-y-4"></div>
        <div class="flex flex-wrap gap-3">
            <button type="button" id="ajouter-filiere" class="rounded border border-ujkz px-4 py-2 font-semibold text-ujkz">Ajouter une filière</button>
            <button class="rounded bg-ujkz px-4 py-2 font-semibold text-white">Enregistrer le tableau</button>
            @if($formations->isNotEmpty() && auth()->user()->role === 'chef')
                <a href="{{ route('bilans.create') }}" class="rounded border px-4 py-2 font-semibold">Renseigner le suivi</a>
            @endif
        </div>
        <p class="text-sm text-slate-500">Un brouillon déjà ouvert conserve ses chiffres lorsque la filière, le libellé et le semestre ne changent pas.</p>
    </form>

    <h2 class="mb-3 mt-8 font-semibold text-ujkz">Aperçu du tableau</h2>
    <div class="overflow-x-auto rounded-lg border border-slate-400 bg-white">
        <table class="min-w-[1100px] w-full border-collapse text-sm">
            <thead>@include('bilans._entete')</thead>
            <tbody id="apercu"></tbody>
        </table>
    </div>

    <script>
        const initial = @json(old('filieres', $structure));
        const depart = document.getElementById('filieres');
        const apercu = document.getElementById('apercu');

        const semestresParLibelle = @json(\App\Models\Formation::SEMESTRES);

        function semestres(label, index) {
            if (semestresParLibelle[label]) {
                return semestresParLibelle[label];
            }
            const debut = index * 2 + 1;
            return ['S' + debut, 'S' + (debut + 1)];
        }

        function blocVide() {
            return { nom: '', niveaux: ['L1', 'L2', 'L3'] };
        }

        let filieres = (Array.isArray(initial) && initial.length)
            ? initial.map(f => ({ nom: f.nom || '', niveaux: (f.niveaux && f.niveaux.length) ? f.niveaux.slice() : ['L1'] }))
            : [blocVide()];

        function dessiner() {
            depart.innerHTML = '';
            filieres.forEach((filiere, i) => {
                const card = document.createElement('section');
                card.className = 'rounded-lg border bg-white p-4';
                const head = document.createElement('div');
                head.className = 'grid gap-3 md:grid-cols-[1fr_12rem_auto] md:items-end';
                head.innerHTML = `
                    <label class="block text-sm font-medium">Filière
                        <input name="filieres[${i}][nom]" value="" required maxlength="180" placeholder="Licence en informatique appliquée" class="mt-1 w-full rounded border px-3 py-2">
                    </label>
                    <label class="block text-sm font-medium">Nombre de niveaux
                        <select class="niveau-count mt-1 w-full rounded border px-3 py-2"></select>
                    </label>
                    <button type="button" class="retirer rounded border border-red-200 px-3 py-2 text-sm text-red-700">Retirer</button>`;
                card.appendChild(head);
                head.querySelector('input').value = filiere.nom;
                const select = head.querySelector('select');
                for (let n = 1; n <= 8; n++) {
                    const option = document.createElement('option');
                    option.value = String(n);
                    option.textContent = String(n);
                    if (n === filiere.niveaux.length) option.selected = true;
                    select.appendChild(option);
                }
                select.addEventListener('change', () => {
                    const voulu = Number(select.value);
                    while (filiere.niveaux.length < voulu) {
                        filiere.niveaux.push('L' + (filiere.niveaux.length + 1));
                    }
                    filiere.niveaux.length = voulu;
                    filiere.nom = head.querySelector('input').value;
                    dessiner();
                });
                head.querySelector('input').addEventListener('input', (event) => {
                    filiere.nom = event.target.value;
                    apercuTableau();
                });
                head.querySelector('.retirer').addEventListener('click', () => {
                    if (filieres.length === 1) {
                        filieres = [blocVide()];
                    } else {
                        filieres.splice(i, 1);
                    }
                    dessiner();
                });

                const liste = document.createElement('div');
                liste.className = 'mt-4 space-y-2';
                filiere.niveaux.forEach((label, j) => {
                    const [s1, s2] = semestres(label, j);
                    const row = document.createElement('div');
                    row.className = 'grid items-center gap-3 sm:grid-cols-[1fr_auto]';
                    row.innerHTML = `
                        <label class="block text-sm">Libellé de la colonne Parcours
                            <input name="filieres[${i}][niveaux][${j}]" value="" required maxlength="80" class="mt-1 w-full rounded border px-3 py-2">
                        </label>
                        <span class="semestres text-sm text-slate-600">Colonne Niveau : <strong>${s1}</strong> et <strong>${s2}</strong></span>`;
                    const input = row.querySelector('input');
                    input.value = label;
                    input.addEventListener('input', () => {
                        filiere.niveaux[j] = input.value;
                        const [a, b] = semestres(input.value, j);
                        row.querySelector('.semestres').innerHTML = `Colonne Niveau : <strong>${a}</strong> et <strong>${b}</strong>`;
                        apercuTableau();
                    });
                    liste.appendChild(row);
                });
                card.appendChild(liste);
                depart.appendChild(card);
            });
            apercuTableau();
        }

        function apercuTableau() {
            apercu.innerHTML = '';
            filieres.forEach((filiere) => {
                const lignes = [];
                filiere.niveaux.forEach((label, j) => {
                    semestres(label, j).forEach((semestre) => lignes.push({ label: label || '—', semestre }));
                });
                const groupes = [];
                lignes.forEach((ligne) => {
                    const dernier = groupes[groupes.length - 1];
                    if (!dernier || dernier.label !== ligne.label) {
                        groupes.push({ label: ligne.label, lignes: [ligne] });
                    } else {
                        dernier.lignes.push(ligne);
                    }
                });
                groupes.forEach((groupe, gi) => {
                    groupe.lignes.forEach((ligne, li) => {
                        const tr = document.createElement('tr');
                        if (gi === 0 && li === 0) {
                            const td = document.createElement('td');
                            td.rowSpan = lignes.length;
                            td.className = 'border border-slate-300 px-2 py-2 align-middle font-medium';
                            td.textContent = filiere.nom || '—';
                            tr.appendChild(td);
                        }
                        if (li === 0) {
                            const td = document.createElement('td');
                            td.rowSpan = groupe.lignes.length;
                            td.className = 'border border-slate-300 px-2 py-2 text-center align-middle';
                            td.textContent = groupe.label;
                            tr.appendChild(td);
                        }
                        const niveau = document.createElement('td');
                        niveau.className = 'border border-slate-300 px-2 py-2 text-center';
                        niveau.textContent = ligne.semestre;
                        tr.appendChild(niveau);
                        for (let c = 0; c < 8; c++) {
                            const td = document.createElement('td');
                            td.className = 'border border-slate-300 px-2 py-2 text-center text-slate-300';
                            td.textContent = '·';
                            tr.appendChild(td);
                        }
                        apercu.appendChild(tr);
                    });
                });
            });
        }

        document.getElementById('ajouter-filiere').addEventListener('click', () => {
            document.querySelectorAll('#filieres input[name$="[nom]"]').forEach((input, i) => {
                if (filieres[i]) filieres[i].nom = input.value;
            });
            filieres.push(blocVide());
            dessiner();
        });

        dessiner();
    </script>
@else
    <div class="overflow-x-auto rounded-lg border border-slate-400 bg-white">
        <table class="min-w-[1100px] w-full border-collapse text-sm">
            <thead>@include('bilans._entete')</thead>
            <tbody>
            @forelse($structure as $filiere)
                @php
                    $total = collect($filiere['niveaux'])->sum(fn () => 2);
                    $cursor = 0;
                @endphp
                @foreach($filiere['niveaux'] as $index => $label)
                    @php
                        $semestresNiveau = \App\Models\Formation::semestresDuLibelle($label, $index + 1);
                    @endphp
                    @foreach($semestresNiveau as $si => $semestre)
                        <tr>
                            @if($cursor === 0)
                                <td rowspan="{{ $total }}" class="border border-slate-300 px-2 py-2 align-middle font-medium">{{ $filiere['nom'] }}</td>
                            @endif
                            @if($si === 0)
                                <td rowspan="2" class="border border-slate-300 px-2 py-2 text-center align-middle">{{ $label }}</td>
                            @endif
                            <td class="border border-slate-300 px-2 py-2 text-center">{{ $semestre }}</td>
                            @for($c = 0; $c < 8; $c++)
                                <td class="border border-slate-300 px-2 py-2 text-center text-slate-300">·</td>
                            @endfor
                        </tr>
                        @php($cursor++)
                    @endforeach
                @endforeach
            @empty
                <tr><td colspan="11" class="px-3 py-8 text-center text-slate-500">Ce département n'a pas encore dressé son tableau.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection

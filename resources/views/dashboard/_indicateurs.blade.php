@foreach([
    ['Taux des cours', $stats['taux_cours'].' %'],
    ['Taux des évaluations', $stats['taux_eval'].' %'],
    ['Bilans officiels', $stats['officiels'].' / '.$stats['bilans']],
    ['Bilans en retard (< 50 %)', $stats['retards']],
] as [$label, $val])
    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <p class="text-sm text-slate-500">{{ $label }}</p>
        <p class="mt-1 text-3xl font-bold text-ujkz">{{ $val }}</p>
    </div>
@endforeach

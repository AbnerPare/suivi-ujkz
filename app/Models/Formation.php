<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Formation extends Model
{
    protected $fillable = ['departement_id', 'filiere', 'parcours', 'niveau', 'semestre', 'ordre'];

    public const NIVEAUX = ['L1', 'L2', 'L3', 'M1', 'M2'];

    public const SEMESTRES = [
        'L1' => ['S1', 'S2'],
        'L2' => ['S3', 'S4'],
        'L3' => ['S5', 'S6'],
        'M1' => ['S1', 'S2'],
        'M2' => ['S3', 'S4'],
    ];

    public function departement(): BelongsTo { return $this->belongsTo(Departement::class); }

    public static function semestresDuRang(int $rang = 1): array
    {
        $debut = max(1, ($rang - 1) * 2 + 1);

        return ['S'.$debut, 'S'.($debut + 1)];
    }

    public static function semestresDuLibelle(string $libelle, int $rang = 1): array
    {
        return self::SEMESTRES[$libelle] ?? self::semestresDuRang($rang);
    }

    public static function libelleParDefaut(int $rang): string
    {
        return 'L'.$rang;
    }

    public static function structureDepuis($formations): array
    {
        $structure = [];
        $index = [];
        foreach ($formations->sortBy([['ordre', 'asc'], ['id', 'asc']]) as $formation) {
            $nom = $formation->filiere;
            if (! isset($index[$nom])) {
                $index[$nom] = count($structure);
                $structure[] = ['nom' => $nom, 'niveaux' => []];
            }
            $label = ($formation->parcours && $formation->niveau && $formation->parcours !== $formation->niveau)
                ? $formation->niveau
                : ($formation->parcours ?: $formation->niveau);
            $i = $index[$nom];
            if (! in_array($label, $structure[$i]['niveaux'], true)) {
                $structure[$i]['niveaux'][] = $label;
            }
        }

        return $structure;
    }
}

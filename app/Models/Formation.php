<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Formation extends Model
{
    protected $fillable = ['departement_id', 'filiere', 'parcours', 'niveau', 'semestre'];

    public const NIVEAUX = ['L1', 'L2', 'L3', 'M1', 'M2'];
    public const SEMESTRES = [
        'L1' => ['S1', 'S2'],
        'L2' => ['S3', 'S4'],
        'L3' => ['S5', 'S6'],
        'M1' => ['S1', 'S2'],
        'M2' => ['S3', 'S4'],
    ];

    public function departement(): BelongsTo { return $this->belongsTo(Departement::class); }
}

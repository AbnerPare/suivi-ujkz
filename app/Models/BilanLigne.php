<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BilanLigne extends Model
{
    protected $fillable = [
        'bilan_id', 'formation_id', 'filiere', 'parcours', 'niveau', 'semestre',
        'ec_total', 'ec_effectues', 'ec_a_evaluer', 'ec_evalues', 'deliberation', 'observations',
    ];

    public function bilan(): BelongsTo { return $this->belongsTo(Bilan::class); }
    public function formation(): BelongsTo { return $this->belongsTo(Formation::class); }

    public function tauxCours(): ?int
    {
        return $this->ec_total > 0 ? (int) round($this->ec_effectues / $this->ec_total * 100) : null;
    }

    public function tauxEval(): ?int
    {
        return $this->ec_a_evaluer > 0 ? (int) round($this->ec_evalues / $this->ec_a_evaluer * 100) : null;
    }
}

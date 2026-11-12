<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periode extends Model
{
    protected $fillable = ['campagne_id', 'mois', 'annee', 'libelle', 'ouverte'];
    protected $casts = ['ouverte' => 'boolean'];

    public const MOIS = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];

    public function campagne(): BelongsTo { return $this->belongsTo(Campagne::class); }
    public function bilans(): HasMany { return $this->hasMany(Bilan::class); }
}

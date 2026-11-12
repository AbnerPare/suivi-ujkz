<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campagne extends Model
{
    protected $fillable = ['annee_academique_id', 'libelle', 'date_debut', 'date_fin', 'statut'];
    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date'];

    public function annee(): BelongsTo { return $this->belongsTo(AnneeAcademique::class, 'annee_academique_id'); }
    public function periodes(): HasMany { return $this->hasMany(Periode::class); }
}

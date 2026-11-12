<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnneeAcademique extends Model
{
    protected $table = 'annees_academiques';
    protected $fillable = ['universite_id', 'libelle', 'date_debut', 'date_fin', 'statut'];
    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date'];

    public function universite(): BelongsTo { return $this->belongsTo(Universite::class); }
    public function campagnes(): HasMany { return $this->hasMany(Campagne::class); }
}

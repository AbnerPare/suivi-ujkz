<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reunion extends Model
{
    protected $fillable = ['universite_id', 'auteur_id', 'titre', 'date_seance', 'lieu', 'ordre_du_jour', 'pv', 'pv_publie', 'statut'];
    protected $casts = ['date_seance' => 'datetime', 'pv_publie' => 'boolean'];

    public function universite(): BelongsTo { return $this->belongsTo(Universite::class); }
    public function auteur(): BelongsTo { return $this->belongsTo(User::class, 'auteur_id'); }
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('present', 'emarge_at');
    }
    public function decisions(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Decision::class); }
}

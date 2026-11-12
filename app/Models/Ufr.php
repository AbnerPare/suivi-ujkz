<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ufr extends Model
{
    protected $fillable = ['universite_id', 'type', 'nom', 'sigle', 'directeur_id', 'directeur_adjoint_id'];

    public function universite(): BelongsTo { return $this->belongsTo(Universite::class); }
    public function directeur(): BelongsTo { return $this->belongsTo(User::class, 'directeur_id'); }
    public function adjoint(): BelongsTo { return $this->belongsTo(User::class, 'directeur_adjoint_id'); }
    public function departements(): HasMany { return $this->hasMany(Departement::class); }

    public function libelleType(): string
    {
        return $this->type === 'institut' ? 'Institut' : 'UFR';
    }
}

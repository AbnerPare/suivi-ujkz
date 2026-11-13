<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Universite extends Model
{
    protected $fillable = ['nom', 'sigle', 'ville', 'email', 'telephone', 'logo', 'actif'];
    protected $casts = ['actif' => 'boolean'];

    public function urlLogo(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        return route('universites.logo', $this).'?v='.$this->updated_at?->getTimestamp();
    }

    public function ufrs(): HasMany { return $this->hasMany(Ufr::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function annees(): HasMany { return $this->hasMany(AnneeAcademique::class); }
}

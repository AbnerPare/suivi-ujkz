<?php

namespace App\Models;

use App\Support\Roles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'universite_id', 'ufr_id', 'departement_id', 'actif'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'actif' => 'boolean'];
    }

    public function universite(): BelongsTo { return $this->belongsTo(Universite::class); }
    public function ufr(): BelongsTo { return $this->belongsTo(Ufr::class); }
    public function departement(): BelongsTo { return $this->belongsTo(Departement::class); }
    public function avis(): HasMany { return $this->hasMany(Avis::class); }
    public function reunions(): BelongsToMany { return $this->belongsToMany(Reunion::class)->withPivot('present', 'emarge_at'); }

    public function isAdmin(): bool { return $this->role === Roles::ADMIN; }
    public function roleLabel(): string { return Roles::label($this->role); }

    public function initiales(): string
    {
        $mots = preg_split('/\s+/u', trim($this->name)) ?: [];
        $lettres = '';
        foreach (array_slice($mots, 0, 2) as $mot) {
            $lettres .= mb_strtoupper(mb_substr($mot, 0, 1));
        }

        return $lettres !== '' ? $lettres : 'U';
    }

    public function gereStructures(): bool
    {
        return in_array($this->role, [Roles::ADMIN, Roles::ADMIN_UNIV], true);
    }

    public function organiseSeances(): bool
    {
        return in_array($this->role, [Roles::ADMIN, Roles::ADMIN_UNIV, Roles::VICE], true);
    }

    public function voitBilans(): bool
    {
        return true;
    }

    public function recoitBilansOfficiels(): bool
    {
        return in_array($this->role, [Roles::DIP, Roles::VICE], true);
    }

    public function ouvreCampagnes(): bool
    {
        return in_array($this->role, [Roles::ADMIN, Roles::ADMIN_UNIV, Roles::VICE, Roles::DIP], true);
    }

    public function scopeDeUniversite($q, ?int $universiteId)
    {
        return $universiteId ? $q->where('universite_id', $universiteId) : $q;
    }
}

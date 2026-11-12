<?php

namespace App\Models;

use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bilan extends Model
{
    protected $fillable = [
        'universite_id', 'departement_id', 'annee_academique_id', 'campagne_id', 'periode_id',
        'statut', 'commentaire_chef', 'version', 'motif_reouverture', 'transmis_at',
    ];

    protected $casts = ['transmis_at' => 'datetime'];

    public function universite(): BelongsTo { return $this->belongsTo(Universite::class); }
    public function departement(): BelongsTo { return $this->belongsTo(Departement::class); }
    public function annee(): BelongsTo { return $this->belongsTo(AnneeAcademique::class, 'annee_academique_id'); }
    public function campagne(): BelongsTo { return $this->belongsTo(Campagne::class); }
    public function periode(): BelongsTo { return $this->belongsTo(Periode::class); }
    public function lignes(): HasMany { return $this->hasMany(BilanLigne::class); }
    public function observations(): HasMany { return $this->hasMany(BilanObservation::class); }
    public function historiques(): HasMany { return $this->hasMany(BilanHistorique::class); }
    public function snapshots(): HasMany { return $this->hasMany(BilanSnapshot::class); }

    public function statutLabel(): string { return Roles::statut($this->statut); }

    public function editablePar(User $user): bool
    {
        if ($user->role !== Roles::CHEF || (int) $user->departement_id !== (int) $this->departement_id) {
            return false;
        }
        return in_array($this->statut, ['brouillon', 'retour_correction', 'corrige'], true);
    }

    public function examinablePar(User $user): bool
    {
        $ufrId = $this->departement?->ufr_id;
        if ($this->statut === 'attente_adjoint' && $user->role === Roles::ADJOINT && (int) $user->ufr_id === (int) $ufrId) {
            return true;
        }
        if ($this->statut === 'attente_directeur' && $user->role === Roles::DIRECTEUR && (int) $user->ufr_id === (int) $ufrId) {
            return true;
        }
        return false;
    }

    public function scopeVisibleTo($q, User $user)
    {
        if ($user->isAdmin()) {
            return $q;
        }
        $q->where('universite_id', $user->universite_id);
        if ($user->role === Roles::CHEF) {
            $q->where('departement_id', $user->departement_id);
        }
        if (in_array($user->role, [Roles::DIRECTEUR, Roles::ADJOINT], true)) {
            $q->whereHas('departement', fn ($d) => $d->where('ufr_id', $user->ufr_id));
        }
        return $q;
    }

    public function tauxCours(): ?int
    {
        $total = $this->lignes->sum('ec_total');
        return $total > 0 ? (int) round($this->lignes->sum('ec_effectues') / $total * 100) : null;
    }

    public function tauxEval(): ?int
    {
        $total = $this->lignes->sum('ec_a_evaluer');
        return $total > 0 ? (int) round($this->lignes->sum('ec_evalues') / $total * 100) : null;
    }
}

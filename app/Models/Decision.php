<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decision extends Model
{
    protected $fillable = ['universite_id', 'reunion_id', 'auteur_id', 'responsable_id', 'ufr_id', 'type', 'titre', 'contenu', 'echeance', 'statut'];
    protected $casts = ['echeance' => 'date'];

    public function universite(): BelongsTo { return $this->belongsTo(Universite::class); }
    public function reunion(): BelongsTo { return $this->belongsTo(Reunion::class); }
    public function auteur(): BelongsTo { return $this->belongsTo(User::class, 'auteur_id'); }
    public function responsable(): BelongsTo { return $this->belongsTo(User::class, 'responsable_id'); }
    public function ufr(): BelongsTo { return $this->belongsTo(Ufr::class); }

    public function typeLabel(): string
    {
        return ['decision' => 'Décision', 'recommandation' => 'Recommandation', 'urgence' => 'Mesure d\'urgence'][$this->type] ?? $this->type;
    }
}

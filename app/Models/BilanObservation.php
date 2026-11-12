<?php

namespace App\Models;

use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BilanObservation extends Model
{
    protected $table = 'bilan_observations';
    protected $fillable = ['bilan_id', 'user_id', 'role', 'contenu'];

    public function bilan(): BelongsTo { return $this->belongsTo(Bilan::class); }
    public function auteur(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public function roleLabel(): string { return Roles::label($this->role); }
}

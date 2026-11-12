<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BilanSnapshot extends Model
{
    protected $fillable = ['bilan_id', 'user_id', 'version', 'motif', 'donnees'];
    protected $casts = ['donnees' => 'array'];

    public function bilan(): BelongsTo { return $this->belongsTo(Bilan::class); }
    public function auteur(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}

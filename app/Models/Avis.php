<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avis extends Model
{
    protected $fillable = ['user_id', 'universite_id', 'titre', 'message', 'lien', 'lu_at'];
    protected $casts = ['lu_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

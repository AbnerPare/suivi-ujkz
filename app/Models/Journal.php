<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Journal extends Model
{
    protected $table = 'journaux';
    protected $fillable = ['user_id', 'universite_id', 'action', 'cible', 'details'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function universite(): BelongsTo { return $this->belongsTo(Universite::class); }
}

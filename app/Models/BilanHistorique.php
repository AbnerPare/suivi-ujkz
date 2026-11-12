<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BilanHistorique extends Model
{
    protected $table = 'bilan_historiques';
    protected $fillable = ['bilan_id', 'user_id', 'de', 'vers', 'commentaire'];

    public function bilan(): BelongsTo { return $this->belongsTo(Bilan::class); }
    public function auteur(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}

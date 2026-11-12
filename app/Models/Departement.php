<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departement extends Model
{
    protected $fillable = ['ufr_id', 'nom', 'chef_id', 'adjoint_id'];

    public function ufr(): BelongsTo { return $this->belongsTo(Ufr::class); }
    public function chef(): BelongsTo { return $this->belongsTo(User::class, 'chef_id'); }
    public function adjoint(): BelongsTo { return $this->belongsTo(User::class, 'adjoint_id'); }
    public function formations(): HasMany { return $this->hasMany(Formation::class); }
    public function bilans(): HasMany { return $this->hasMany(Bilan::class); }
}

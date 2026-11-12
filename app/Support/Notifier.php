<?php

namespace App\Support;

use App\Models\Avis;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Support\Collection;

class Notifier
{
    public static function send(User|array|Collection|null $users, string $titre, string $message, ?string $lien = null): void
    {
        foreach (collect($users)->filter() as $user) {
            Avis::create([
                'user_id' => $user->id,
                'universite_id' => $user->universite_id,
                'titre' => $titre,
                'message' => $message,
                'lien' => $lien,
            ]);
        }
    }

    public static function journal(?User $user, string $action, ?string $cible = null, ?string $details = null): void
    {
        Journal::create([
            'user_id' => $user?->id,
            'universite_id' => $user?->universite_id,
            'action' => $action,
            'cible' => $cible,
            'details' => $details,
        ]);
    }
}

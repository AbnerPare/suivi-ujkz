<?php

namespace App\Support;

class Roles
{
    public const ADMIN = 'admin';
    public const PRESIDENT = 'president';
    public const VICE = 'vice_president';
    public const SECRETAIRE = 'secretaire';
    public const DIP = 'dip';
    public const DIRECTEUR = 'directeur_ufr';
    public const ADJOINT = 'directeur_adjoint';
    public const CHEF = 'chef';

    public const ALL = [
        self::ADMIN => 'Administrateur système',
        self::PRESIDENT => 'Président(e) de l\'université',
        self::VICE => 'Vice-président(e)',
        self::SECRETAIRE => 'Secrétaire de séance',
        self::DIP => 'Directeur des Innovations Pédagogiques',
        self::DIRECTEUR => 'Directeur(trice) d\'UFR',
        self::ADJOINT => 'Directeur(trice) adjoint(e) d\'UFR',
        self::CHEF => 'Chef de département',
    ];

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'attente_adjoint' => 'En attente du directeur adjoint',
        'attente_directeur' => 'En attente du directeur d\'UFR',
        'retour_correction' => 'Retourné pour correction',
        'corrige' => 'Bilan corrigé',
        'officiel' => 'Transmis officiellement',
    ];

    public static function label(string $role): string
    {
        return self::ALL[$role] ?? $role;
    }

    public static function statut(string $statut): string
    {
        return self::STATUTS[$statut] ?? $statut;
    }
}

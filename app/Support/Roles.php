<?php

namespace App\Support;

class Roles
{
    public const ADMIN = 'admin';
    public const ADMIN_UNIV = 'admin_universite';
    public const VICE = 'vice_president';
    public const DIP = 'dip';
    public const ADJOINT = 'directeur_adjoint';
    public const CHEF = 'chef';

    public const ALL = [
        self::ADMIN => 'Administrateur système',
        self::ADMIN_UNIV => 'Administrateur de l\'université',
        self::VICE => 'Vice-président(e)',
        self::DIP => 'Directeur des Innovations Pédagogiques',
        self::ADJOINT => 'Directeur(trice) adjoint(e) d\'UFR',
        self::CHEF => 'Chef de département',
    ];

    public const HISTORIQUE = [
        'president' => 'Président(e) de l\'université',
        'secretaire' => 'Secrétaire de séance',
        'directeur_ufr' => 'Directeur(trice) d\'UFR',
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
        return self::ALL[$role] ?? self::HISTORIQUE[$role] ?? $role;
    }

    public static function statut(string $statut): string
    {
        return self::STATUTS[$statut] ?? $statut;
    }
}

# SUIVI-ENSEIGNEMENTS

Plateforme de suivi de l'avancement des enseignements et des évaluations, prévue pour plusieurs universités du Burkina Faso. Les données d'un établissement ne sont pas visibles par un autre.

Laravel 12, MySQL, Apache (XAMPP) : `http://localhost/suivi-ujkz`

## Mise en route

1. Démarrer Apache et MySQL dans XAMPP
2. Base `suivi`, utilisateur `root` sans mot de passe (voir `.env`)

```bash
php artisan migrate --seed
```

Si la base contient déjà l'ancienne version (cours / heures CM-TD-TP), relancer `php artisan migrate` puis `php artisan db:seed`. La migration du 11 novembre 2026 remplace ce modèle par le circuit du cahier des charges.

## Comptes de démonstration (mot de passe : `password`)

| Rôle | Email |
|---|---|
| Administrateur système | admin@ujkz.bf |
| Administrateur de l'université | admin.ujkz@ujkz.bf |
| Vice-président | vp@ujkz.bf |
| Directeur des Innovations Pédagogiques | dip@ujkz.bf |
| Directeur adjoint UFR SEA | adjoint@ujkz.bf |
| Chef de département Informatique | chef@ujkz.bf |
| Chef de département Mathématiques | maths@ujkz.bf |

L'administrateur système crée chaque université et désigne son administrateur. Cet administrateur crée les UFR, les départements, puis désigne le chef de chaque département et le directeur adjoint de chaque UFR. Le chef suit son département, le directeur adjoint voit les départements de son UFR, et le vice-président comme le DIP voient les bilans de tous les départements, regroupés par UFR.

## Circuit d'un bilan

Le tableau reprend les colonnes du fichier Excel : filière, parcours, niveau, semestre, éléments constitutifs prévus / effectués, éléments à évaluer / évalués, délibération (0, 1 ou 2) et observations.

1. Le chef de département saisit le brouillon et le transmet au directeur adjoint.
2. Le directeur adjoint observe et valide : le bilan revient au chef de département.
3. Le chef corrige, puis transmet la version officielle au Directeur des Innovations Pédagogiques et au vice-président. Ils voient le même bilan, sans les observations internes du circuit.
4. Une réouverture (DIP, vice-président ou administrateur) conserve la version officielle et renvoie le bilan au chef.

## Autres modules

- UFR, instituts, départements, formations (L1 à M2, semestres associés)
- Années académiques, campagnes et périodes mensuelles
- Notifications ciblées, lues ou non lues
- Réunions, émargement, procès-verbaux
- Décisions, recommandations et mesures d'urgence
- Tableau de bord comparatif (UFR et mois)
- Journal d'activité pour l'administrateur système

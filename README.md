# OmnesEvent

Projet étudiant PHP/MySQL pour consulter, réserver et gérer des événements associatifs Omnes.

## Installation locale

1. Placer le dossier dans `C:\wamp64\www\OmnesEvent`
2. Lancer WAMP
3. Ouvrir `http://localhost/phpmyadmin`
4. Créer ou sélectionner l'onglet `Importer`
5. Importer le fichier `database/omnesevent.sql`
6. Ouvrir `http://localhost/OmnesEvent/`

## Base de données

- Nom de la base : `omnesevent`
- Connexion PHP : `localhost`, utilisateur `root`, mot de passe vide

## Comptes de test

- Participant : `participant@omnes.fr` / `password`
- Organisateur validé : `organisateur@omnes.fr` / `password`
- Administrateur : `admin@omnes.fr` / `password`
- Organisateur en attente : `orga-attente@omnes.fr` / `password`

## Fonctionnalités disponibles

- Inscription, connexion et déconnexion
- Gestion des rôles `participant`, `organisateur`, `administrateur`
- Catalogue dynamique avec filtres date, catégorie et association
- Page détail avec réservation réelle et contrôle des doublons/capacités
- Profil utilisateur modifiable
- Billets du participant avec annulation simple
- Création, modification et annulation d'événements pour les organisateurs
- Liste des inscrits et validation de présence
- Tableau de bord administrateur pour gérer utilisateurs et événements

## Limites actuelles

- Les événements sont modérés par changement de statut `actif/annule` plutôt que par suppression définitive.
- Les billets n'intègrent pas de vrai QR code, seulement une référence de réservation simple.
- L'envoi d'e-mails n'est pas implémenté dans ce MVP.

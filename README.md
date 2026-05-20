# OmnesEvent

Projet étudiant PHP/MySQL pour consulter, réserver et gérer des événements associatifs Omnes.

## Installation locale

1. Placer le dossier dans `C:\wamp64\www\OmnesEvent`
2. Lancer WAMP
3. Ouvrir `http://localhost/phpmyadmin`
4. Cliquer sur l onglet `Importer`
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
- Gestion des roles `participant`, `organisateur`, `administrateur`
- Catalogue dynamique avec filtres date, catégorie et association
- Calendrier mensuel des événements
- Page détail avec réservation réelle et contrôle des doublons/capacités
- Paiement simulé pour les événements payants
- Liste d’attente avec promotion automatique lors d’un désistement
- QR code par billet avec page de vérification organisateur
- Profil utilisateur modifiable
- Billets du participant avec annulation simple
- Création, modification et annulation d’événements pour les organisateurs
- Liste des inscrits et validation de présence
- Tableau de bord administrateur pour gérer utilisateurs et événements

## Limites actuelles

- Le QR code est généré via un service public d’image QR. Le token du billet reste affiché comme solution de secours.
- Le paiement est volontairement simulé pour rester adapté au MVP scolaire.
- Il n’y a pas d’envoi d’e-mail automatique lors de la promotion depuis la liste d’attente.

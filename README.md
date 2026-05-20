# OmnesEvent

Projet etudiant PHP/MySQL pour consulter, reserver et gerer des evenements associatifs Omnes.

## Installation locale

1. Placer le dossier dans `C:\wamp64\www\OmnesEvent`
2. Lancer WAMP
3. Ouvrir `http://localhost/phpmyadmin`
4. Cliquer sur l onglet `Importer`
5. Importer le fichier `database/omnesevent.sql`
6. Ouvrir `http://localhost/OmnesEvent/`

## Base de donnees

- Nom de la base : `omnesevent`
- Connexion PHP : `localhost`, utilisateur `root`, mot de passe vide

## Comptes de test

- Participant : `participant@omnes.fr` / `password`
- Organisateur valide : `organisateur@omnes.fr` / `password`
- Administrateur : `admin@omnes.fr` / `password`
- Organisateur en attente : `orga-attente@omnes.fr` / `password`

## Fonctionnalites disponibles

- Inscription, connexion et deconnexion
- Gestion des roles `participant`, `organisateur`, `administrateur`
- Catalogue dynamique avec filtres date, categorie et association
- Calendrier mensuel des evenements
- Page detail avec reservation reelle et controle des doublons/capacites
- Paiement simule pour les evenements payants
- Liste d attente avec promotion automatique lors d un desistement
- QR code par billet avec page de verification organisateur
- Profil utilisateur modifiable
- Billets du participant avec annulation simple
- Creation, modification et annulation d evenements pour les organisateurs
- Liste des inscrits et validation de presence
- Tableau de bord administrateur pour gerer utilisateurs et evenements

## Limites actuelles

- Le QR code est genere via un service public d image QR. Le token du billet reste affiche comme solution de secours.
- Le paiement est volontairement simule pour rester adapte au MVP scolaire.
- Il n y a pas d envoi d e-mail automatique lors de la promotion depuis la liste d attente.

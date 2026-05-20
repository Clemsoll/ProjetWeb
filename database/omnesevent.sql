CREATE DATABASE IF NOT EXISTS omnesevent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE omnesevent;

DROP TABLE IF EXISTS listes_attente;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS evenements;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('participant', 'organisateur', 'administrateur') NOT NULL DEFAULT 'participant',
    statut_organisateur ENUM('valide', 'en_attente') NOT NULL DEFAULT 'valide',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evenements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    details_complets TEXT NULL,
    date_evenement DATETIME NOT NULL,
    lieu VARCHAR(180) NOT NULL,
    adresse_complete VARCHAR(255) NULL,
    categorie VARCHAR(50) NOT NULL,
    association VARCHAR(120) NOT NULL,
    image VARCHAR(255) NULL,
    capacite_max INT NOT NULL,
    prix DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    places_reservees INT NOT NULL DEFAULT 0,
    statut ENUM('actif', 'annule') NOT NULL DEFAULT 'actif',
    organisateur_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evenements_organisateur
        FOREIGN KEY (organisateur_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    evenement_id INT NOT NULL,
    statut ENUM('reserve', 'annule') NOT NULL DEFAULT 'reserve',
    presence_validee TINYINT(1) NOT NULL DEFAULT 0,
    qr_token VARCHAR(80) NULL UNIQUE,
    payment_status ENUM('non_requis', 'en_attente', 'paye') NOT NULL DEFAULT 'non_requis',
    payment_reference VARCHAR(120) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reservation (user_id, evenement_id),
    CONSTRAINT fk_reservations_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_reservations_evenement
        FOREIGN KEY (evenement_id) REFERENCES evenements(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE listes_attente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    evenement_id INT NOT NULL,
    position_attente INT NOT NULL,
    statut ENUM('en_attente', 'convertie', 'annulee') NOT NULL DEFAULT 'en_attente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_waitlist (user_id, evenement_id),
    KEY idx_waitlist_event_status (evenement_id, statut, position_attente),
    CONSTRAINT fk_listes_attente_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_listes_attente_evenement
        FOREIGN KEY (evenement_id) REFERENCES evenements(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, prenom, nom, email, password, role, statut_organisateur, created_at) VALUES
(1, 'Paul', 'Participant', 'participant@omnes.fr', '$2y$10$faA6Ytdvt8677MTkWiqfXeCocJz0GU1GhQhhgQuFaXY8BMpfQMTJu', 'participant', 'valide', '2026-05-01 09:00:00'),
(2, 'Olivia', 'Organisatrice', 'organisateur@omnes.fr', '$2y$10$faA6Ytdvt8677MTkWiqfXeCocJz0GU1GhQhhgQuFaXY8BMpfQMTJu', 'organisateur', 'valide', '2026-05-01 09:30:00'),
(3, 'Alice', 'Admin', 'admin@omnes.fr', '$2y$10$faA6Ytdvt8677MTkWiqfXeCocJz0GU1GhQhhgQuFaXY8BMpfQMTJu', 'administrateur', 'valide', '2026-05-01 10:00:00'),
(4, 'Nora', 'Association', 'orga-attente@omnes.fr', '$2y$10$faA6Ytdvt8677MTkWiqfXeCocJz0GU1GhQhhgQuFaXY8BMpfQMTJu', 'organisateur', 'en_attente', '2026-05-03 11:00:00'),
(5, 'Camille', 'Martin', 'camille@omnes.fr', '$2y$10$faA6Ytdvt8677MTkWiqfXeCocJz0GU1GhQhhgQuFaXY8BMpfQMTJu', 'participant', 'valide', '2026-05-05 12:00:00');

INSERT INTO evenements (id, titre, description, details_complets, date_evenement, lieu, adresse_complete, categorie, association, image, capacite_max, prix, places_reservees, statut, organisateur_id, created_at) VALUES
(1, 'Soiree d''integration du BDE', 'Une grande soiree de rentree pour rencontrer les associations et les nouveaux etudiants.', 'Au programme : DJ set, animations, buffet et presentation des projets associatifs du semestre. Pensez a apporter votre carte etudiante a l entree.', '2026-06-12 20:00:00', 'Campus principal', '10 rue Sextius Michel, 75015 Paris', 'Soiree', 'BDE', 'images/Soiree.webp', 120, 0.00, 2, 'actif', 2, '2026-05-10 18:00:00'),
(2, 'Tournoi de football inter-promo', 'Le BDS organise un tournoi 5 contre 5 ouvert a tous les etudiants.', 'Chaque equipe doit etre presente 30 minutes avant le debut des matchs. Une buvette sera disponible sur place. Les debutants sont bienvenus.', '2026-06-20 14:00:00', 'Terrain de sport Omnes', '4 rue Alphonse Daudet, 92300 Levallois-Perret', 'Sport', 'BDS', 'images/foot.webp', 60, 5.00, 1, 'actif', 2, '2026-05-11 14:00:00'),
(3, 'Conference IA et cybersecurite', 'Une conference avec des intervenants professionnels sur les usages de l IA.', 'Presentation des metiers, demonstrations et temps de questions-reponses avec des alumni et des experts du secteur tech.', '2026-06-28 18:30:00', 'Amphitheatre A', '19 rue Yves Toudic, 75010 Paris', 'Culture', 'Junior Entreprise', 'images/reunion.webp', 150, 0.00, 1, 'actif', 2, '2026-05-12 16:30:00'),
(4, 'Projection cinema solidaire', 'Soiree cinema organisee au profit d une association partenaire.', 'Les benefices de la billetterie seront reverses a une association locale. Une vente de snacks est prevue avant la projection.', '2026-07-05 19:30:00', 'Salle polyvalente', '65 quai de Grenelle, 75015 Paris', 'Culture', 'BDA', 'images/reunion.webp', 80, 3.50, 0, 'annule', 2, '2026-05-13 17:15:00'),
(5, 'Hackathon associatif', 'Un hackathon de 24 heures autour d outils numeriques pour les associations.', 'Evenement deja termine. Les participants ont travaille en equipes sur des idees d applications et de services utiles a la vie associative.', '2026-05-10 09:00:00', 'Learning Lab', '48 boulevard Jourdan, 75014 Paris', 'Culture', 'Junior Entreprise', 'images/reunion.webp', 40, 0.00, 1, 'actif', 2, '2026-04-18 10:30:00'),
(6, 'Afterwork des associations', 'Rencontre informelle entre les responsables associatifs et les etudiants interesses.', 'Moment convivial pour decouvrir le fonctionnement des bureaux associatifs et proposer de nouveaux projets pour la rentree prochaine.', '2026-08-02 18:00:00', 'Rooftop du campus', '242 rue du Faubourg Saint-Antoine, 75012 Paris', 'Soiree', 'BDE', 'images/Soiree.webp', 90, 7.50, 0, 'actif', 2, '2026-05-14 19:00:00'),
(7, 'Atelier design thinking', 'Un atelier limite a une seule equipe pour tester la liste d attente.', 'L evenement sert aussi de demonstration pour le fonctionnement de la liste d attente intelligente du projet.', '2026-07-18 10:00:00', 'Salle Innovation', '5 rue Armand Moisant, 75015 Paris', 'Culture', 'Junior Entreprise', 'images/reunion.webp', 1, 0.00, 1, 'actif', 2, '2026-05-15 10:30:00');

INSERT INTO reservations (id, user_id, evenement_id, statut, presence_validee, qr_token, payment_status, payment_reference, created_at) VALUES
(1, 1, 1, 'reserve', 0, 'OMNESQR0001PAUL', 'non_requis', NULL, '2026-05-15 10:00:00'),
(2, 1, 2, 'reserve', 0, 'OMNESQR0002PAUL', 'paye', 'PAY-DEMO-0002', '2026-05-15 10:05:00'),
(3, 1, 5, 'reserve', 1, 'OMNESQR0003PAUL', 'non_requis', NULL, '2026-05-02 09:00:00'),
(4, 5, 1, 'reserve', 0, 'OMNESQR0004CAMILLE', 'non_requis', NULL, '2026-05-15 11:00:00'),
(5, 5, 3, 'reserve', 0, 'OMNESQR0005CAMILLE', 'non_requis', NULL, '2026-05-16 14:30:00'),
(6, 5, 4, 'annule', 0, 'OMNESQR0006CAMILLE', 'paye', 'PAY-DEMO-0006', '2026-05-16 15:00:00'),
(7, 1, 7, 'reserve', 0, 'OMNESQR0007PAUL', 'non_requis', NULL, '2026-05-18 09:00:00');

INSERT INTO listes_attente (id, user_id, evenement_id, position_attente, statut, created_at) VALUES
(1, 5, 7, 1, 'en_attente', '2026-05-18 09:30:00');

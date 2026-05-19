CREATE DATABASE IF NOT EXISTS omnesevent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE omnesevent;

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
    categorie VARCHAR(50) NOT NULL,
    association VARCHAR(120) NOT NULL,
    image VARCHAR(255) NULL,
    capacite_max INT NOT NULL,
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

INSERT INTO users (id, prenom, nom, email, password, role, statut_organisateur, created_at) VALUES
(1, 'Paul', 'Participant', 'participant@omnes.fr', '$2y$10$faA6Ytdvt8677MTkWiqfXeCocJz0GU1GhQhhgQuFaXY8BMpfQMTJu', 'participant', 'valide', '2026-05-01 09:00:00'),
(2, 'Olivia', 'Organisatrice', 'organisateur@omnes.fr', '$2y$10$faA6Ytdvt8677MTkWiqfXeCocJz0GU1GhQhhgQuFaXY8BMpfQMTJu', 'organisateur', 'valide', '2026-05-01 09:30:00'),
(3, 'Alice', 'Admin', 'admin@omnes.fr', '$2y$10$faA6Ytdvt8677MTkWiqfXeCocJz0GU1GhQhhgQuFaXY8BMpfQMTJu', 'administrateur', 'valide', '2026-05-01 10:00:00'),
(4, 'Nora', 'Association', 'orga-attente@omnes.fr', '$2y$10$faA6Ytdvt8677MTkWiqfXeCocJz0GU1GhQhhgQuFaXY8BMpfQMTJu', 'organisateur', 'en_attente', '2026-05-03 11:00:00'),
(5, 'Camille', 'Martin', 'camille@omnes.fr', '$2y$10$faA6Ytdvt8677MTkWiqfXeCocJz0GU1GhQhhgQuFaXY8BMpfQMTJu', 'participant', 'valide', '2026-05-05 12:00:00');

INSERT INTO evenements (id, titre, description, details_complets, date_evenement, lieu, categorie, association, image, capacite_max, places_reservees, statut, organisateur_id, created_at) VALUES
(1, 'Soirée d''intégration du BDE', 'Une grande soirée de rentrée pour rencontrer les associations et les nouveaux étudiants.', 'Au programme : DJ set, animations, buffet et présentation des projets associatifs du semestre. Pensez à apporter votre carte étudiante à l''entrée.', '2026-06-12 20:00:00', 'Campus principal', 'Soirée', 'BDE', 'images/Soiree.webp', 120, 2, 'actif', 2, '2026-05-10 18:00:00'),
(2, 'Tournoi de football inter-promo', 'Le BDS organise un tournoi 5 contre 5 ouvert à tous les étudiants.', 'Chaque équipe doit être présente 30 minutes avant le début des matchs. Une buvette sera disponible sur place. Les débutants sont bienvenus.', '2026-06-20 14:00:00', 'Terrain de sport Omnes', 'Sport', 'BDS', 'images/foot.webp', 60, 1, 'actif', 2, '2026-05-11 14:00:00'),
(3, 'Conférence IA et cybersécurité', 'Une conférence avec des intervenants professionnels sur les usages de l''IA.', 'Présentation des métiers, démonstrations et temps de questions-réponses avec des alumni et des experts du secteur tech.', '2026-06-28 18:30:00', 'Amphithéâtre A', 'Culture', 'Junior Entreprise', 'images/reunion.webp', 150, 1, 'actif', 2, '2026-05-12 16:30:00'),
(4, 'Projection cinéma solidaire', 'Soirée cinéma organisée au profit d''une association partenaire.', 'Les bénéfices de la billetterie seront reversés à une association locale. Une vente de snacks est prévue avant la projection.', '2026-07-05 19:30:00', 'Salle polyvalente', 'Culture', 'BDA', 'images/reunion.webp', 80, 0, 'annule', 2, '2026-05-13 17:15:00'),
(5, 'Hackathon associatif', 'Un hackathon de 24 heures autour d''outils numériques pour les associations.', 'Événement déjà terminé. Les participants ont travaillé en équipes sur des idées d''applications et de services utiles à la vie associative.', '2026-05-10 09:00:00', 'Learning Lab', 'Culture', 'Junior Entreprise', 'images/reunion.webp', 40, 1, 'actif', 2, '2026-04-18 10:30:00'),
(6, 'Afterwork des associations', 'Rencontre informelle entre les responsables associatifs et les étudiants intéressés.', 'Moment convivial pour découvrir le fonctionnement des bureaux associatifs et proposer de nouveaux projets pour la rentrée prochaine.', '2026-08-02 18:00:00', 'Rooftop du campus', 'Soirée', 'BDE', 'images/Soiree.webp', 90, 0, 'actif', 2, '2026-05-14 19:00:00');

INSERT INTO reservations (id, user_id, evenement_id, statut, presence_validee, created_at) VALUES
(1, 1, 1, 'reserve', 0, '2026-05-15 10:00:00'),
(2, 1, 2, 'reserve', 0, '2026-05-15 10:05:00'),
(3, 1, 5, 'reserve', 1, '2026-05-02 09:00:00'),
(4, 5, 1, 'reserve', 0, '2026-05-15 11:00:00'),
(5, 5, 3, 'reserve', 0, '2026-05-16 14:30:00'),
(6, 5, 4, 'annule', 0, '2026-05-16 15:00:00');

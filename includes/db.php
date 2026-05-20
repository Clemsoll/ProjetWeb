<?php
declare(strict_types=1);

$dbHost = 'fdb1031.your-hosting.net';
$dbName = '4761126_omnesevent';
$dbUser = '4761126_omnesevent';
$dbPassword = 'Web15012006!';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $exception) {
    exit('Connexion impossible à la base de données "omnesevent". Importez d’abord le fichier database/omnesevent.sql dans phpMyAdmin.');
}

try {
    $tableColumns = [
        'evenements' => [
            'places_reservees' => 'ALTER TABLE evenements ADD places_reservees INT NOT NULL DEFAULT 0 AFTER capacite_max',
            'adresse_complete' => 'ALTER TABLE evenements ADD adresse_complete VARCHAR(255) NULL AFTER lieu',
            'prix' => 'ALTER TABLE evenements ADD prix DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER capacite_max',
        ],
        'reservations' => [
            'qr_token' => 'ALTER TABLE reservations ADD qr_token VARCHAR(80) NULL AFTER presence_validee',
            'payment_status' => 'ALTER TABLE reservations ADD payment_status ENUM("non_requis","en_attente","paye") NOT NULL DEFAULT "non_requis" AFTER qr_token',
            'payment_reference' => 'ALTER TABLE reservations ADD payment_reference VARCHAR(120) NULL AFTER payment_status',
        ],
    ];

    foreach ($tableColumns as $table => $columns) {
        foreach ($columns as $column => $query) {
            $columnCheck = $pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
            $exists = $columnCheck !== false && $columnCheck->fetch() !== false;

            if (!$exists) {
                $pdo->exec($query);
            }
        }
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS listes_attente (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            evenement_id INT NOT NULL,
            position_attente INT NOT NULL,
            statut ENUM("en_attente","convertie","annulee") NOT NULL DEFAULT "en_attente",
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $indexCheck = $pdo->query("SHOW INDEX FROM reservations WHERE Key_name = 'uniq_qr_token'");
    $hasQrIndex = $indexCheck !== false && $indexCheck->fetch() !== false;

    if (!$hasQrIndex) {
        $pdo->exec('ALTER TABLE reservations ADD UNIQUE KEY uniq_qr_token (qr_token)');
    }

    $pdo->exec(
        'UPDATE evenements e
         SET e.places_reservees = (
             SELECT COUNT(*)
             FROM reservations r
             WHERE r.evenement_id = e.id
               AND r.statut = "reserve"
         )'
    );

    $pdo->exec(
        'UPDATE evenements
         SET adresse_complete = lieu
         WHERE adresse_complete IS NULL
            OR adresse_complete = ""'
    );

    $paidEventsCountStmt = $pdo->query('SELECT COUNT(*) FROM evenements WHERE prix > 0');
    $paidEventsCount = $paidEventsCountStmt !== false ? (int) $paidEventsCountStmt->fetchColumn() : 0;

    if ($paidEventsCount === 0) {
        $pdo->exec(
            "UPDATE evenements
             SET prix = CASE
                 WHEN titre = 'Tournoi de football inter-promo' THEN 5.00
                 WHEN titre = 'Projection cinema solidaire' THEN 3.50
                 WHEN titre = 'Afterwork des associations' THEN 7.50
                 ELSE prix
             END
             WHERE titre IN (
                 'Tournoi de football inter-promo',
                 'Projection cinema solidaire',
                 'Afterwork des associations'
             )"
        );
    }

    $pdo->exec(
        'UPDATE reservations r
         INNER JOIN evenements e ON e.id = r.evenement_id
         SET r.payment_status = CASE
             WHEN e.prix > 0 THEN "paye"
             ELSE "non_requis"
         END
         WHERE r.payment_status <> "en_attente"
            OR r.payment_status IS NULL'
    );

    $pdo->exec(
        'UPDATE reservations
         SET qr_token = UPPER(REPLACE(UUID(), "-", ""))
         WHERE qr_token IS NULL
            OR qr_token = ""'
    );
} catch (PDOException $exception) {
    // Le site reste accessible meme si une mise a niveau automatique echoue.
}

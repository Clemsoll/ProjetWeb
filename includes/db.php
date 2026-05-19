<?php
declare(strict_types=1);

$dbHost = 'localhost';
$dbName = 'omnesevent';
$dbUser = 'root';
$dbPassword = '';

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
    exit('Connexion impossible à la base de données "omnesevent". Importez d\'abord le fichier database/omnesevent.sql dans phpMyAdmin.');
}

<?php
// db_connect.php
function db_connect() {
    // config ici (adapte host/username/password/database)
    $host = '127.0.0.1';
    $db   = 'attendance';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Pour debug local : on peut afficher l'erreur ; en production on logge seulement.
        error_log("DB connect error: " . $e->getMessage());
        return null;
    }
}

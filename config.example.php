<?php
// Paramètres de connexion MySQL


define('DB_HOST', 'localhost');
define('DB_NAME', '...');
define('DB_USER', '...');
define('DB_PASS', '');
define('DB_PORT', 3306);


// Démarrage de la session pour toute l'application
if (session_status() === PHP_SESSION_NONE) {
session_start();
}

/** Établir la connexion MySQLi
 */
function connectDB() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if (!$conn) {
        die("Erreur de connexion: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8");
    return $conn;
}

/**
 * Fermer la connexion MySQLi
 */
function closeDB($conn) {
    mysqli_close($conn);
}

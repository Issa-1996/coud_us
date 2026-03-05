<?php
/**
 * Configuration des chemins - USCOUD
 * Gestion des URLs et redirections
 */

// Credentials API (fichier protégé, hors dépôt Git)
require_once __DIR__ . '/credentials.php';

// Détection automatique de l'environnement
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_path = '/campuscoud.com/securite'; // Chemin de base de l'application

// Configuration des chemins
define('BASE_URL', $protocol . '://' . $host . $base_path);
define('BASE_PATH', $base_path);

/**
 * Redirection vers un chemin
 */
function redirect($path) {
    if (strpos($path, '/') === 0) {
        $url = BASE_URL . $path;
    } else {
        $url = $path;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Générer une URL absolue
 */
function url($path) {
    if (strpos($path, '/') === 0) {
        return BASE_URL . $path;
    }
    return $path;
}

/**
 * Générer une URL pour les assets
 */
function asset_url($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}

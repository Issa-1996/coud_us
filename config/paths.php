<?php
/**
 * Configuration des chemins - USCOUD
 * Gestion des URLs et redirections
 */

// Détection automatique de l'environnement
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_path = '/COUD/uscoud';

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

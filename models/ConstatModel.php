<?php
/**
 * Modèle procédural pour la gestion des Procès-Verbaux de Constat d'Incident
 * Contient les appels de fonctions vers ConstatModel-fonctions.php
 */

// Inclure le fichier de fonctions
require_once __DIR__ . '/../data/ConstatModel-fonctions.php';

// Initialisation des données en mémoire
$GLOBALS['constat_pv_data'] = [];

/**
 * Générer des données fictives pour le développement
 */
function generateFakeData($count = 30) {
    return generateFakeConstatData($count);
}

/**
 * Obtenir tous les PV avec pagination et filtres
 */
function getAllPV($page = 1, $itemsPerPage = 10, $search = '', $status = '') {
    return getAllConstatPV($page, $itemsPerPage, $search, $status);
}

/**
 * Obtenir un PV par son ID
 */
function getPVById($id) {
    return getConstatPVById($id);
}

/**
 * Créer un nouveau PV
 */
function createPV($data) {
    return createConstatPV($data);
}

/**
 * Mettre à jour un PV
 */
function updatePV($id, $data) {
    return updateConstatPV($id, $data);
}

/**
 * Supprimer un PV
 */
function deletePV($id) {
    return deleteConstatPV($id);
}

/**
 * Obtenir les statistiques
 */
function getStatistics() {
    return getConstatStatistics();
}

/**
 * Valider les données d'un PV
 */
function validatePV($data) {
    return validateConstatPV($data);
}

/**
 * Exporter les PV en CSV
 */
function exportToCSV($search = '', $status = '') {
    return exportConstatToCSV($search, $status);
}
?>

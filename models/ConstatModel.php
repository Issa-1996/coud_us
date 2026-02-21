<?php
/**
 * Modèle procédural pour la gestion des Procès-Verbaux de Constat d'Incident
 * Utilise la base de données MySQLi avec le préfixe uscoud_pv_
 */

// Inclure le fichier de fonctions de base de données
require_once __DIR__ . '/../data/database.php';
require_once __DIR__ . '/../data/constat_database_functions.php';

/**
 * Générer des données fictives pour le développement (avec sauvegarde en base)
 */
function generateFakeData($count = 30) {
    return generateFakeDataPVConstat($count);
}

/**
 * Obtenir tous les PV avec pagination et filtres
 */
function getAllPV($page = 1, $itemsPerPage = 10, $search = '', $status = '', $agentId = null) {
    return getAllPVConstat($page, $itemsPerPage, $search, $status, $agentId);
}

/**
 * Obtenir un PV par son ID
 */
function getPVById($id) {
    return getPVConstatById($id);
}

/**
 * Créer un nouveau PV
 */
function createPV($data) {
    return createPVConstat($data);
}

/**
 * Mettre à jour un PV
 */
function updatePV($id, $data) {
    return updatePVConstat($id, $data);
}

/**
 * Supprimer un PV
 */
function deletePV($id, $idAgent = null) {
    return deletePVConstat($id, $idAgent);
}

/**
 * Obtenir les statistiques
 */
function getStatistics($agentId = null) {
    return getStatisticsPVConstat($agentId);
}

/**
 * Valider les données d'un PV
 */
function validatePV($data) {
    return validatePVConstat($data);
}

/**
 * Exporter les PV en CSV
 */
function exportToCSV($search = '', $status = '') {
    exportPVConstatToCSV($search, $status);
}
?>

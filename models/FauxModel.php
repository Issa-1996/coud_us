<?php
/**
 * Modèle procédural pour la gestion des Procès-Verbaux de Faux et Usage de Faux
 * Utilise la base de données MySQLi avec le préfixe uscoud_pv_
 */

// Inclure le fichier de fonctions de base de données
require_once __DIR__ . '/../data/database.php';
require_once __DIR__ . '/../data/faux_database_functions_uscoud.php';

/**
 * Générer des données fictives pour le développement (avec sauvegarde en base)
 */
function generateFakeData($count = 30) {
    return generateFakeDataPVFaux($count);
}

/**
 * Obtenir tous les PV avec pagination et filtres
 */
function getAllPV($page = 1, $itemsPerPage = 10, $search = '', $status = '', $agentId = null) {
    return getAllPVFaux($page, $itemsPerPage, $search, $status, $agentId);
}

/**
 * Obtenir un PV par son ID
 */
function getPVById($id) {
    return getPVFauxById($id);
}

/**
 * Créer un nouveau PV
 */
function createPV($data) {
    return createPVFaux($data);
}

/**
 * Mettre à jour un PV
 */
function updatePV($id, $data) {
    return updatePVFaux($id, $data);
}

/**
 * Supprimer un PV
 */
function deletePV($id, $idAgent = null) {
    return deletePVFaux($id, $idAgent);
}

/**
 * Obtenir les statistiques
 */
function getStatistics($agentId = null) {
    return getStatisticsPVFaux($agentId);
}

/**
 * Valider les données d'un PV
 */
function validatePV($data) {
    return validatePVFaux($data);
}

/**
 * Exporter les PV en CSV
 */
function exportToCSV($search = '', $status = '') {
    exportPVFauxToCSV($search, $status);
}
?>

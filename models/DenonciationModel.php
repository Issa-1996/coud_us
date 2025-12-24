<?php
/**
 * Modèle procédural pour la gestion des Procès-Verbaux de Dénonciation
 * Contient les appels de fonctions vers DenonciationModel-fonctions.php
 */

// Inclure le fichier de fonctions
require_once __DIR__ . '/../data/DenonciationModel-fonctions.php';

// Initialisation des données en mémoire
$GLOBALS['denonciation_pv_data'] = [];

/**
 * Générer des données fictives pour le développement
 */
function generateFakeData($count = 30) {
    return generateFakeDenonciationData($count);
}

/**
 * Obtenir tous les PV avec pagination et filtres
 */
function getAllPV($page = 1, $itemsPerPage = 10, $search = '', $status = '') {
    return getAllDenonciationPV($page, $itemsPerPage, $search, $status);
}

/**
 * Obtenir un PV par son ID
 */
function getPVById($id) {
    return getDenonciationPVById($id);
}

/**
 * Créer un nouveau PV
 */
function createPV($data) {
    return createDenonciationPV($data);
}

/**
 * Mettre à jour un PV
 */
function updatePV($id, $data) {
    return updateDenonciationPV($id, $data);
}

/**
 * Supprimer un PV
 */
function deletePV($id) {
    return deleteDenonciationPV($id);
}

/**
 * Obtenir les statistiques
 */
function getStatistics() {
    return getDenonciationStatistics();
}

/**
 * Valider les données d'un PV
 */
function validatePV($data) {
    return validateDenonciationPV($data);
}

/**
 * Exporter les PV en CSV
 */
function exportToCSV($search = '', $status = '') {
    return exportDenonciationToCSV($search, $status);
}
?>

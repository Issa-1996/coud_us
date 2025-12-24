<?php
/**
 * Modèle procédural pour la gestion des Procès-Verbaux de Faux et Usage de Faux
 * Contient les appels de fonctions vers FauxModel-fonctions.php
 */

// Inclure le fichier de fonctions
require_once __DIR__ . '/../data/FauxModel-fonctions.php';

// Initialisation des données en mémoire
$GLOBALS['faux_pv_data'] = [];

/**
 * Générer des données fictives pour le développement (sans sauvegarder)
 */
function generateFakeData($count = 30) {
    return generateFakeFauxData($count);
}

/**
 * Obtenir tous les PV avec pagination et filtres
 */
function getAllPV($page = 1, $itemsPerPage = 10, $search = '', $status = '') {
    return getAllFauxPV($page, $itemsPerPage, $search, $status);
}

/**
 * Obtenir un PV par son ID
 */
function getPVById($id) {
    return getFauxPVById($id);
}

/**
 * Créer un nouveau PV
 */
function createPV($data) {
    return createFauxPV($data);
}

/**
 * Mettre à jour un PV
 */
function updatePV($id, $data) {
    return updateFauxPV($id, $data);
}

/**
 * Supprimer un PV
 */
function deletePV($id) {
    return deleteFauxPV($id);
}

/**
 * Obtenir les statistiques
 */
function getStatistics() {
    return getFauxStatistics();
}

/**
 * Valider les données d'un PV
 */
function validatePV($data) {
    return validateFauxPV($data);
}

/**
 * Exporter les PV en CSV
 */
function exportToCSV($search = '', $status = '') {
    return exportFauxToCSV($search, $status);
}
?>

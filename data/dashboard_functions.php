<?php
require_once __DIR__ . '/database.php';

/**
 * Obtenir les statistiques globales pour le tableau de bord
 */
function getGlobalStatistics()
{
    $connexion = getConnection();
    if (!$connexion) {
        return [
            'total_pv' => 0,
            'en_cours' => 0,
            'traites' => 0,
            'ce_mois' => 0
        ];
    }

    $currentMonth = date('Y-m');
    $currentYear = date('Y');

    // Statistiques pour les PV Faux
    $sqlFaux = "SELECT COUNT(*) as total, SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours, SUM(CASE WHEN statut = 'traite' THEN 1 ELSE 0 END) as traites, SUM(CASE WHEN DATE_FORMAT(created_at, '%Y-%m') = ? THEN 1 ELSE 0 END) as ce_mois FROM uscoud_pv_faux";

    $resultFaux = executeQuery($connexion, $sqlFaux, 's', $currentMonth);

    // Statistiques pour les PV Constat
    $sqlConstat = "SELECT COUNT(*) as total, SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours, SUM(CASE WHEN statut = 'traite' THEN 1 ELSE 0 END) as traites, SUM(CASE WHEN DATE_FORMAT(created_at, '%Y-%m') = ? THEN 1 ELSE 0 END) as ce_mois FROM uscoud_pv_constat";

    $resultConstat = executeQuery($connexion, $sqlConstat, 's', $currentMonth);

    // Statistiques pour les PV Dénonciation
    $sqlDenonciation = "SELECT COUNT(*) as total, SUM(CASE WHEN statut = 'en_attente' OR statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours, SUM(CASE WHEN statut = 'traite' THEN 1 ELSE 0 END) as traites, SUM(CASE WHEN DATE_FORMAT(created_at, '%Y-%m') = ? THEN 1 ELSE 0 END) as ce_mois FROM uscoud_pv_denonciation";

    $resultDenonciation = executeQuery($connexion, $sqlDenonciation, 's', $currentMonth);

    $faux = $resultFaux[0] ?? ['total' => 0, 'en_cours' => 0, 'traites' => 0, 'ce_mois' => 0];
    $constat = $resultConstat[0] ?? ['total' => 0, 'en_cours' => 0, 'traites' => 0, 'ce_mois' => 0];
    $denonciation = $resultDenonciation[0] ?? ['total' => 0, 'en_cours' => 0, 'traites' => 0, 'ce_mois' => 0];

    closeConnection($connexion);

    return [
        'total_pv' => $faux['total'] + $constat['total'] + $denonciation['total'],
        'en_cours' => $faux['en_cours'] + $constat['en_cours'] + $denonciation['en_cours'],
        'traites' => $faux['traites'] + $constat['traites'] + $denonciation['traites'],
        'ce_mois' => $faux['ce_mois'] + $constat['ce_mois'] + $denonciation['ce_mois'],
        'details' => [
            'faux' => $faux,
            'constat' => $constat,
            'denonciation' => $denonciation
        ]
    ];
}

/**
 * Obtenir les 5 derniers PV pour chaque type
 */
function getLatestPVs()
{
    $connexion = getConnection();
    if (!$connexion) {
        return [
            'faux' => [],
            'constat' => [],
            'denonciation' => []
        ];
    }

    // Derniers PV Faux
    $sqlFaux = "SELECT id, nom, prenoms, statut, created_at, CASE WHEN statut = 'en_cours' THEN 'warning' WHEN statut = 'traite' THEN 'success' ELSE 'secondary' END as statut_color FROM uscoud_pv_faux ORDER BY created_at DESC LIMIT 5";

    $resultFaux = executeQuery($connexion, $sqlFaux);

    // Derniers PV Constat
    $sqlConstat = "SELECT id, lieu_incident, statut, created_at, CASE WHEN statut = 'en_cours' THEN 'warning' WHEN statut = 'traite' THEN 'success' ELSE 'secondary' END as statut_color FROM uscoud_pv_constat ORDER BY created_at DESC LIMIT 5";

    $resultConstat = executeQuery($connexion, $sqlConstat);

    // Derniers PV Dénonciation
    $sqlDenonciation = "SELECT id, denonciateur_nom, type_denonciation, statut, created_at, CASE WHEN statut = 'en_attente' OR statut = 'en_cours' THEN 'warning' WHEN statut = 'traite' THEN 'success' ELSE 'secondary' END as statut_color FROM uscoud_pv_denonciation ORDER BY created_at DESC LIMIT 5";

    $resultDenonciation = executeQuery($connexion, $sqlDenonciation);

    closeConnection($connexion);

    return [
        'faux' => $resultFaux,
        'constat' => $resultConstat,
        'denonciation' => $resultDenonciation
    ];
}

/**
 * Obtenir les statistiques mensuelles pour les graphiques
 */
function getMonthlyStatistics($year = null)
{
    $connexion = getConnection();
    if (!$connexion) {
        return [];
    }

    $year = $year ?? date('Y');
    $monthlyData = [];

    for ($month = 1; $month <= 12; $month++) {
        $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
        $dateFilter = "$year-$monthStr";

        // PV Faux
        $sqlFaux = "SELECT COUNT(*) as count FROM uscoud_pv_faux 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
        $resultFaux = executeQuery($connexion, $sqlFaux, 's', $dateFilter);

        // PV Constat
        $sqlConstat = "SELECT COUNT(*) as count FROM uscoud_pv_constat 
                      WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
        $resultConstat = executeQuery($connexion, $sqlConstat, 's', $dateFilter);

        // PV Dénonciation
        $sqlDenonciation = "SELECT COUNT(*) as count FROM uscoud_pv_denonciation 
                           WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
        $resultDenonciation = executeQuery($connexion, $sqlDenonciation, 's', $dateFilter);

        $monthlyData[] = [
            'month' => $month,
            'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
            'faux' => $resultFaux[0]['count'] ?? 0,
            'constat' => $resultConstat[0]['count'] ?? 0,
            'denonciation' => $resultDenonciation[0]['count'] ?? 0,
            'total' => ($resultFaux[0]['count'] ?? 0) + ($resultConstat[0]['count'] ?? 0) + ($resultDenonciation[0]['count'] ?? 0)
        ];
    }

    closeConnection($connexion);
    return $monthlyData;
}

/**
 * Obtenir les activités récentes
 */
function getRecentActivities($limit = 10)
{
    $connexion = getConnection();
    if (!$connexion) {
        return [];
    }

    $activities = [];

    // Activités des PV Faux
    $sqlFaux = "SELECT 'faux' as type, id, nom, prenoms, statut, created_at,
                CASE 
                    WHEN statut = 'en_cours' THEN 'warning'
                    WHEN statut = 'traite' THEN 'success'
                    ELSE 'secondary'
                END as statut_color,
                CONCAT(nom, ' ', prenoms) as person_name
                FROM uscoud_pv_faux 
                ORDER BY created_at DESC 
                LIMIT $limit";

    $resultFaux = executeQuery($connexion, $sqlFaux);

    // Activités des PV Constat
    $sqlConstat = "SELECT 'constat' as type, id, lieu_incident, statut, created_at,
                  CASE 
                      WHEN statut = 'en_cours' THEN 'warning'
                      WHEN statut = 'traite' THEN 'success'
                      ELSE 'secondary'
                  END as statut_color,
                  lieu_incident as person_name
                  FROM uscoud_pv_constat 
                  ORDER BY created_at DESC 
                  LIMIT $limit";

    $resultConstat = executeQuery($connexion, $sqlConstat);

    // Activités des PV Dénonciation
    $sqlDenonciation = "SELECT 'denonciation' as type, id, denonciateur_nom, statut, created_at,
                       CASE 
                           WHEN statut = 'en_attente' OR statut = 'en_cours' THEN 'warning'
                           WHEN statut = 'traite' THEN 'success'
                           ELSE 'secondary'
                       END as statut_color,
                       denonciateur_nom as person_name
                       FROM uscoud_pv_denonciation 
                       ORDER BY created_at DESC 
                       LIMIT $limit";

    $resultDenonciation = executeQuery($connexion, $sqlDenonciation);

    closeConnection($connexion);

    // Combiner et trier par date
    $allActivities = array_merge($resultFaux, $resultConstat, $resultDenonciation);

    // Trier par date décroissante
    usort($allActivities, function ($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    return array_slice($allActivities, 0, $limit);
}

/**
 * Obtenir les types d'incidents pour les graphiques
 */
function getIncidentTypes()
{
    $connexion = getConnection();
    if (!$connexion) {
        return [];
    }

    // Types de dénonciation
    $sqlDenonciation = "SELECT type_denonciation as type, COUNT(*) as count 
                        FROM uscoud_pv_denonciation 
                        WHERE type_denonciation IS NOT NULL 
                        GROUP BY type_denonciation";

    $resultDenonciation = executeQuery($connexion, $sqlDenonciation);

    // Types d'incidents (pour constat)
    $sqlConstat = "SELECT type_incident as type, COUNT(*) as count 
                   FROM uscoud_pv_constat 
                   WHERE type_incident IS NOT NULL 
                   GROUP BY type_incident";

    $resultConstat = executeQuery($connexion, $sqlConstat);

    closeConnection($connexion);

    return [
        'denonciation_types' => $resultDenonciation,
        'incident_types' => $resultConstat
    ];
}

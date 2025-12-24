<?php
/**
 * Fonctions pour la gestion des Procès-Verbaux de Constat d'Incident
 * Contient toutes les logiques métier et opérations CRUD
 */

// Variables globales pour les données en mémoire
$GLOBALS['constat_pv_data'] = [];

/**
 * Générer des données fictives pour le développement
 */
function generateFakeConstatData($count = 30) {
    $noms = ['Diop', 'Ndiaye', 'Fall', 'Sarr', 'Sow', 'Ba', 'Gueye', 'Diouf', 'Mbaye', 'Sy'];
    $prenoms = ['Moussa', 'Fatou', 'Abdoulaye', 'Aminata', 'Ibrahima', 'Mariama', 'Ousmane', 'Aissatou'];
    $campus = ['Campus Social ESP', 'Campus Social UCAD', 'Résidence Claudel', 'Cité Mixte'];
    $statuts = ['en_cours', 'traite'];
    $typesIncident = ['Vol', 'Agression', 'Dégradation', 'Perte', 'Autre'];
    
    $fakeData = [];
    for ($i = 1; $i <= $count; $i++) {
        $date = new DateTime('2024-' . rand(1, 12) . '-' . rand(1, 28));
        
        $fakeData[] = [
            'id' => time() + $i,
            'carteEtudiant' => 'ETU' . str_pad($i, 6, '0', STR_PAD_LEFT),
            'nom' => $noms[array_rand($noms)],
            'prenoms' => $prenoms[array_rand($prenoms)],
            'campus' => $campus[array_rand($campus)],
            'telephone' => '7' . rand(1000000, 9999999),
            'typeIncident' => $typesIncident[array_rand($typesIncident)],
            'description' => 'Description de l\'incident #' . $i,
            'lieu' => 'Lieu de l\'incident #' . $i,
            'statut' => $statuts[array_rand($statuts)],
            'dateIncident' => $date->format('Y-m-d'),
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s'),
            'blesse' => [],
            'dommage' => [],
            'assaillant' => [],
            'audition' => [],
            'temoignage' => []
        ];
    }
    
    return $fakeData;
}

/**
 * Obtenir tous les PV avec pagination et filtres
 */
function getAllConstatPV($page = 1, $itemsPerPage = 10, $search = '', $status = '') {
    $filtered = $GLOBALS['constat_pv_data'];
    
    // Filtre par recherche
    if (!empty($search)) {
        $search = strtolower($search);
        $filtered = array_filter($filtered, function($pv) use ($search) {
            return strpos(strtolower($pv['nom']), $search) !== false ||
                   strpos(strtolower($pv['prenoms']), $search) !== false ||
                   strpos(strtolower($pv['carteEtudiant']), $search) !== false ||
                   strpos($pv['telephone'], $search) !== false;
        });
    }
    
    // Filtre par statut
    if (!empty($status)) {
        $filtered = array_filter($filtered, function($pv) use ($status) {
            return $pv['statut'] === $status;
        });
    }
    
    // Pagination
    $total = count($filtered);
    $totalPages = ceil($total / $itemsPerPage);
    $offset = ($page - 1) * $itemsPerPage;
    $paginated = array_slice($filtered, $offset, $itemsPerPage);
    
    return [
        'data' => $paginated,
        'total' => $total,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'itemsPerPage' => $itemsPerPage
    ];
}

/**
 * Obtenir un PV par son ID
 */
function getConstatPVById($id) {
    foreach ($GLOBALS['constat_pv_data'] as $pv) {
        if ($pv['id'] == $id) {
            return $pv;
        }
    }
    return null;
}

/**
 * Créer un nouveau PV
 */
function createConstatPV($data) {
    $pv = [
        'id' => time(),
        'carteEtudiant' => $data['carteEtudiant'] ?? '',
        'nom' => $data['nom'] ?? '',
        'prenoms' => $data['prenoms'] ?? '',
        'campus' => $data['campus'] ?? '',
        'telephone' => $data['telephone'] ?? '',
        'typeIncident' => $data['typeIncident'] ?? '',
        'description' => $data['description'] ?? '',
        'lieu' => $data['lieu'] ?? '',
        'statut' => $data['statut'] ?? 'en_cours',
        'dateIncident' => $data['dateIncident'] ?? date('Y-m-d'),
        'createdAt' => date('Y-m-d H:i:s'),
        'updatedAt' => date('Y-m-d H:i:s'),
        'blesse' => $data['blesse'] ?? [],
        'dommage' => $data['dommage'] ?? [],
        'assaillant' => $data['assaillant'] ?? [],
        'audition' => $data['audition'] ?? [],
        'temoignage' => $data['temoignage'] ?? []
    ];
    
    $GLOBALS['constat_pv_data'][] = $pv;
    return $pv;
}

/**
 * Mettre à jour un PV
 */
function updateConstatPV($id, $data) {
    foreach ($GLOBALS['constat_pv_data'] as $key => $pv) {
        if ($pv['id'] == $id) {
            $GLOBALS['constat_pv_data'][$key] = array_merge($pv, [
                'carteEtudiant' => $data['carteEtudiant'] ?? $pv['carteEtudiant'],
                'nom' => $data['nom'] ?? $pv['nom'],
                'prenoms' => $data['prenoms'] ?? $pv['prenoms'],
                'campus' => $data['campus'] ?? $pv['campus'],
                'telephone' => $data['telephone'] ?? $pv['telephone'],
                'typeIncident' => $data['typeIncident'] ?? $pv['typeIncident'],
                'description' => $data['description'] ?? $pv['description'],
                'lieu' => $data['lieu'] ?? $pv['lieu'],
                'statut' => $data['statut'] ?? $pv['statut'],
                'dateIncident' => $data['dateIncident'] ?? $pv['dateIncident'],
                'updatedAt' => date('Y-m-d H:i:s'),
                'blesse' => $data['blesse'] ?? $pv['blesse'],
                'dommage' => $data['dommage'] ?? $pv['dommage'],
                'assaillant' => $data['assaillant'] ?? $pv['assaillant'],
                'audition' => $data['audition'] ?? $pv['audition'],
                'temoignage' => $data['temoignage'] ?? $pv['temoignage']
            ]);
            return $GLOBALS['constat_pv_data'][$key];
        }
    }
    return false;
}

/**
 * Supprimer un PV
 */
function deleteConstatPV($id) {
    foreach ($GLOBALS['constat_pv_data'] as $key => $pv) {
        if ($pv['id'] == $id) {
            unset($GLOBALS['constat_pv_data'][$key]);
            $GLOBALS['constat_pv_data'] = array_values($GLOBALS['constat_pv_data']); // Réindexer
            return true;
        }
    }
    return false;
}

/**
 * Obtenir les statistiques
 */
function getConstatStatistics() {
    $total = count($GLOBALS['constat_pv_data']);
    $enCours = count(array_filter($GLOBALS['constat_pv_data'], function($pv) {
        return $pv['statut'] === 'en_cours';
    }));
    $traites = count(array_filter($GLOBALS['constat_pv_data'], function($pv) {
        return $pv['statut'] === 'traite';
    }));
    
    return [
        'total' => $total,
        'enCours' => $enCours,
        'traites' => $traites
    ];
}

/**
 * Valider les données d'un PV
 */
function validateConstatPV($data) {
    $errors = [];
    
    if (empty($data['carteEtudiant'])) {
        $errors[] = 'Le numéro de carte étudiant est requis';
    }
    
    if (empty($data['nom'])) {
        $errors[] = 'Le nom est requis';
    }
    
    if (empty($data['prenoms'])) {
        $errors[] = 'Le prénom est requis';
    }
    
    if (empty($data['campus'])) {
        $errors[] = 'Le campus/résidence est requis';
    }
    
    if (empty($data['telephone'])) {
        $errors[] = 'Le téléphone est requis';
    } elseif (!preg_match('/^7[0-9]{7}$/', $data['telephone'])) {
        $errors[] = 'Le format du téléphone est invalide';
    }
    
    if (empty($data['typeIncident'])) {
        $errors[] = 'Le type d\'incident est requis';
    }
    
    if (empty($data['description'])) {
        $errors[] = 'La description est requise';
    }
    
    if (empty($data['lieu'])) {
        $errors[] = 'Le lieu est requis';
    }
    
    return $errors;
}

/**
 * Exporter les PV en CSV
 */
function exportConstatToCSV($search = '', $status = '') {
    $result = getAllConstatPV(1, 10000, $search, $status);
    $data = $result['data'];
    
    $filename = 'constat_pv_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV
    fputcsv($output, [
        'ID',
        'N° Carte Étudiant',
        'Nom',
        'Prénoms',
        'Campus/Résidence',
        'Téléphone',
        'Type Incident',
        'Description',
        'Lieu',
        'Statut',
        'Date Incident',
        'Date de création'
    ]);
    
    // Données
    foreach ($data as $pv) {
        fputcsv($output, [
            $pv['id'],
            $pv['carteEtudiant'],
            $pv['nom'],
            $pv['prenoms'],
            $pv['campus'],
            $pv['telephone'],
            $pv['typeIncident'],
            $pv['description'],
            $pv['lieu'],
            $pv['statut'] === 'en_cours' ? 'En cours' : 'Traité',
            $pv['dateIncident'],
            $pv['createdAt']
        ]);
    }
    
    fclose($output);
    exit;
}
?>

<?php
/**
 * Fonctions pour la gestion des Procès-Verbaux de Faux et Usage de Faux
 * Contient toutes les logiques métier et opérations CRUD
 */

// Variables globales pour les données en mémoire
$GLOBALS['faux_pv_data'] = [];

/**
 * Générer des données fictives pour le développement (sans sauvegarder)
 */
function generateFakeFauxData($count = 30) {
    $noms = ['Diop', 'Ndiaye', 'Fall', 'Sarr', 'Sow', 'Ba', 'Gueye', 'Diouf', 'Mbaye', 'Sy'];
    $prenoms = ['Moussa', 'Fatou', 'Abdoulaye', 'Aminata', 'Ibrahima', 'Mariama', 'Ousmane', 'Aissatou'];
    $campus = ['Campus Social ESP', 'Campus Social UCAD', 'Résidence Claudel', 'Cité Mixte'];
    $statuts = ['en_cours', 'traite'];
    $identitesFaux = ['M. Mme Fall Mamadou', 'M. Diop Abdoulaye', 'Mme Sarr Fatoumata', 'M. Ba Ousmane', 'Mlle Ndiaye Aissatou'];
    $typesDocument = ['carte_etudiant', 'cni', 'passeport', 'autre'];
    
    $fakeData = [];
    for ($i = 1; $i <= $count; $i++) {
        $date = new DateTime('2024-' . rand(1, 12) . '-' . rand(1, 28));
        
        $fakeData[] = [
            'id' => time() + $i,
            'carteEtudiant' => 'ETU' . str_pad($i, 6, '0', STR_PAD_LEFT),
            'nom' => $noms[array_rand($noms)],
            'prenoms' => $prenoms[array_rand($prenoms)],
            'campus' => $campus[array_rand($campus)],
            'telephone7' => '7' . rand(1000000, 9999999),
            'telephoneResistant' => rand(60000000, 99999999),
            'identiteFaux' => $identitesFaux[array_rand($identitesFaux)],
            'typeDocument' => $typesDocument[array_rand($typesDocument)],
            'observations' => 'Observations pour le PV #' . $i,
            'statut' => $statuts[array_rand($statuts)],
            'date' => $date->format('Y-m-d'),
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s')
        ];
    }
    
    return $fakeData;
}

/**
 * Obtenir tous les PV avec pagination et filtres
 */
function getAllFauxPV($page = 1, $itemsPerPage = 10, $search = '', $status = '') {
    $filtered = $GLOBALS['faux_pv_data'];
    
    // Filtre par recherche
    if (!empty($search)) {
        $search = strtolower($search);
        $filtered = array_filter($filtered, function($pv) use ($search) {
            return strpos(strtolower($pv['nom']), $search) !== false ||
                   strpos(strtolower($pv['prenoms']), $search) !== false ||
                   strpos(strtolower($pv['carteEtudiant']), $search) !== false ||
                   strpos($pv['telephone7'], $search) !== false;
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
function getFauxPVById($id) {
    foreach ($GLOBALS['faux_pv_data'] as $pv) {
        if ($pv['id'] == $id) {
            return $pv;
        }
    }
    return null;
}

/**
 * Créer un nouveau PV
 */
function createFauxPV($data) {
    $pv = [
        'id' => time(),
        'carteEtudiant' => $data['carteEtudiant'] ?? '',
        'nom' => $data['nom'] ?? '',
        'prenoms' => $data['prenoms'] ?? '',
        'campus' => $data['campus'] ?? '',
        'telephone7' => $data['telephone7'] ?? '',
        'telephoneResistant' => $data['telephoneResistant'] ?? '',
        'observations' => $data['observations'] ?? '',
        'statut' => $data['statut'] ?? 'en_cours',
        'date' => $data['date'] ?? date('Y-m-d'),
        'createdAt' => date('Y-m-d H:i:s'),
        'updatedAt' => date('Y-m-d H:i:s')
    ];
    
    $GLOBALS['faux_pv_data'][] = $pv;
    return $pv;
}

/**
 * Mettre à jour un PV
 */
function updateFauxPV($id, $data) {
    foreach ($GLOBALS['faux_pv_data'] as $key => $pv) {
        if ($pv['id'] == $id) {
            $GLOBALS['faux_pv_data'][$key] = array_merge($pv, [
                'carteEtudiant' => $data['carteEtudiant'] ?? $pv['carteEtudiant'],
                'nom' => $data['nom'] ?? $pv['nom'],
                'prenoms' => $data['prenoms'] ?? $pv['prenoms'],
                'campus' => $data['campus'] ?? $pv['campus'],
                'telephone7' => $data['telephone7'] ?? $pv['telephone7'],
                'telephoneResistant' => $data['telephoneResistant'] ?? $pv['telephoneResistant'],
                'observations' => $data['observations'] ?? $pv['observations'],
                'statut' => $data['statut'] ?? $pv['statut'],
                'date' => $data['date'] ?? $pv['date'],
                'updatedAt' => date('Y-m-d H:i:s')
            ]);
            return $GLOBALS['faux_pv_data'][$key];
        }
    }
    return false;
}

/**
 * Supprimer un PV
 */
function deleteFauxPV($id) {
    foreach ($GLOBALS['faux_pv_data'] as $key => $pv) {
        if ($pv['id'] == $id) {
            unset($GLOBALS['faux_pv_data'][$key]);
            $GLOBALS['faux_pv_data'] = array_values($GLOBALS['faux_pv_data']); // Réindexer
            return true;
        }
    }
    return false;
}

/**
 * Obtenir les statistiques
 */
function getFauxStatistics() {
    $total = count($GLOBALS['faux_pv_data']);
    $enCours = count(array_filter($GLOBALS['faux_pv_data'], function($pv) {
        return $pv['statut'] === 'en_cours';
    }));
    $traites = count(array_filter($GLOBALS['faux_pv_data'], function($pv) {
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
function validateFauxPV($data) {
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
    
    if (empty($data['telephone7'])) {
        $errors[] = 'Le téléphone (N° 7...) est requis';
    } elseif (!preg_match('/^7[0-9]{7}$/', $data['telephone7'])) {
        $errors[] = 'Le format du téléphone (N° 7...) est invalide';
    }
    
    if (!empty($data['telephoneResistant']) && !preg_match('/^[0-9]{8}$/', $data['telephoneResistant'])) {
        $errors[] = 'Le format du téléphone résistant est invalide';
    }
    
    return $errors;
}

/**
 * Exporter les PV en CSV
 */
function exportFauxToCSV($search = '', $status = '') {
    $result = getAllFauxPV(1, 10000, $search, $status);
    $data = $result['data'];
    
    $filename = 'faux_pv_' . date('Y-m-d_H-i-s') . '.csv';
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
        'Téléphone (N° 7...)',
        'Téléphone (résistante)',
        'Observations',
        'Statut',
        'Date PV',
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
            $pv['telephone7'],
            $pv['telephoneResistant'],
            $pv['observations'],
            $pv['statut'] === 'en_cours' ? 'En cours' : 'Traité',
            $pv['date'],
            $pv['createdAt']
        ]);
    }
    
    fclose($output);
    exit;
}
?>

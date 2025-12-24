<?php
/**
 * Fonctions pour la gestion des Procès-Verbaux de Dénonciation
 * Contient toutes les logiques métier et opérations CRUD
 */

// Variables globales pour les données en mémoire
$GLOBALS['denonciation_pv_data'] = [];

/**
 * Générer des données fictives pour le développement
 */
function generateFakeDenonciationData($count = 30) {
    $noms = ['Diop', 'Ndiaye', 'Fall', 'Sarr', 'Sow', 'Ba', 'Gueye', 'Diouf', 'Mbaye', 'Sy'];
    $prenoms = ['Moussa', 'Fatou', 'Abdoulaye', 'Aminata', 'Ibrahima', 'Mariama', 'Ousmane', 'Aissatou'];
    $campus = ['Campus Social ESP', 'Campus Social UCAD', 'Résidence Claudel', 'Cité Mixte'];
    $types = ['violence', 'harcelement', 'diffamation', 'vol'];
    $statuts = ['en_cours', 'traite'];
    
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
            'typeDenonciation' => $types[array_rand($types)],
            'description' => 'Description de la dénonciation #' . $i,
            'lieu' => 'Lieu de l\'incident #' . $i,
            'statut' => $statuts[array_rand($statuts)],
            'dateDenonciation' => $date->format('Y-m-d'),
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s')
        ];
    }
    
    return $fakeData;
}

/**
 * Obtenir tous les PV avec pagination et filtres
 */
function getAllDenonciationPV($page = 1, $itemsPerPage = 10, $search = '', $status = '') {
    $filtered = $GLOBALS['denonciation_pv_data'];
    
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
function getDenonciationPVById($id) {
    foreach ($GLOBALS['denonciation_pv_data'] as $pv) {
        if ($pv['id'] == $id) {
            return $pv;
        }
    }
    return null;
}

/**
 * Créer un nouveau PV
 */
function createDenonciationPV($data) {
    $pv = [
        'id' => time(),
        'carteEtudiant' => $data['carteEtudiant'] ?? '',
        'nom' => $data['nom'] ?? '',
        'prenoms' => $data['prenoms'] ?? '',
        'campus' => $data['campus'] ?? '',
        'telephone' => $data['telephone'] ?? '',
        'typeDenonciation' => $data['typeDenonciation'] ?? '',
        'description' => $data['description'] ?? '',
        'lieu' => $data['lieu'] ?? '',
        'statut' => $data['statut'] ?? 'en_cours',
        'dateDenonciation' => $data['dateDenonciation'] ?? date('Y-m-d'),
        'createdAt' => date('Y-m-d H:i:s'),
        'updatedAt' => date('Y-m-d H:i:s')
    ];
    
    $GLOBALS['denonciation_pv_data'][] = $pv;
    return $pv;
}

/**
 * Mettre à jour un PV
 */
function updateDenonciationPV($id, $data) {
    foreach ($GLOBALS['denonciation_pv_data'] as $key => $pv) {
        if ($pv['id'] == $id) {
            $GLOBALS['denonciation_pv_data'][$key] = array_merge($pv, [
                'carteEtudiant' => $data['carteEtudiant'] ?? $pv['carteEtudiant'],
                'nom' => $data['nom'] ?? $pv['nom'],
                'prenoms' => $data['prenoms'] ?? $pv['prenoms'],
                'campus' => $data['campus'] ?? $pv['campus'],
                'telephone' => $data['telephone'] ?? $pv['telephone'],
                'typeDenonciation' => $data['typeDenonciation'] ?? $pv['typeDenonciation'],
                'description' => $data['description'] ?? $pv['description'],
                'lieu' => $data['lieu'] ?? $pv['lieu'],
                'statut' => $data['statut'] ?? $pv['statut'],
                'dateDenonciation' => $data['dateDenonciation'] ?? $pv['dateDenonciation'],
                'updatedAt' => date('Y-m-d H:i:s')
            ]);
            return $GLOBALS['denonciation_pv_data'][$key];
        }
    }
    return false;
}

/**
 * Supprimer un PV
 */
function deleteDenonciationPV($id) {
    foreach ($GLOBALS['denonciation_pv_data'] as $key => $pv) {
        if ($pv['id'] == $id) {
            unset($GLOBALS['denonciation_pv_data'][$key]);
            $GLOBALS['denonciation_pv_data'] = array_values($GLOBALS['denonciation_pv_data']); // Réindexer
            return true;
        }
    }
    return false;
}

/**
 * Obtenir les statistiques
 */
function getDenonciationStatistics() {
    $total = count($GLOBALS['denonciation_pv_data']);
    $enCours = count(array_filter($GLOBALS['denonciation_pv_data'], function($pv) {
        return $pv['statut'] === 'en_cours';
    }));
    $traites = count(array_filter($GLOBALS['denonciation_pv_data'], function($pv) {
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
function validateDenonciationPV($data) {
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
    
    if (empty($data['typeDenonciation'])) {
        $errors[] = 'Le type de dénonciation est requis';
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
function exportDenonciationToCSV($search = '', $status = '') {
    $result = getAllDenonciationPV(1, 10000, $search, $status);
    $data = $result['data'];
    
    $filename = 'denonciation_pv_' . date('Y-m-d_H-i-s') . '.csv';
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
        'Type Dénonciation',
        'Description',
        'Lieu',
        'Statut',
        'Date Dénonciation',
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
            $pv['typeDenonciation'],
            $pv['description'],
            $pv['lieu'],
            $pv['statut'] === 'en_cours' ? 'En cours' : 'Traité',
            $pv['dateDenonciation'],
            $pv['createdAt']
        ]);
    }
    
    fclose($output);
    exit;
}
?>

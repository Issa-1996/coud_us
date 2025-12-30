<?php
/**
 * Fonctions pour la gestion des Procès-Verbaux de Faux et Usage de Faux
 * Contient toutes les logiques métier et opérations CRUD
 */

// Variables globales pour les données en mémoire
require_once __DIR__ . '/../config.php';

//$GLOBALS['faux_pv_data'] = [];

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
    $conn = connectDB();
    
    // Construction de la requête avec filtres
    $where = [];
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $where[] = "(nom LIKE ? OR prenoms LIKE ? OR carteEtudiant LIKE ? OR telephone7 LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ssss";
    }
    
    if (!empty($status)) {
        $where[] = "statut = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // Compter le total
    $count_sql = "SELECT COUNT(*) as total FROM faux_pv $where_clause";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($count_stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($count_stmt)) {
        mysqli_stmt_close($count_stmt);
        closeDB($conn);
        return ['data' => [], 'total' => 0, 'totalPages' => 0, 'currentPage' => 1, 'itemsPerPage' => $itemsPerPage];
    }
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total = mysqli_fetch_assoc($count_result)['total'];
    mysqli_stmt_close($count_stmt);
    
    // Calculer la pagination
    $totalPages = ceil($total / $itemsPerPage);
    $offset = ($page - 1) * $itemsPerPage;
    
    // Récupérer les données
    $sql = "SELECT * FROM faux_pv $where_clause ORDER BY createdAt DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    $final_types = $types . "ii";
    $final_params = array_merge($params, [$itemsPerPage, $offset]);
    mysqli_stmt_bind_param($stmt, $final_types, ...$final_params);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        closeDB($conn);
        return ['data' => [], 'total' => 0, 'totalPages' => 0, 'currentPage' => 1, 'itemsPerPage' => $itemsPerPage];
    }
    $result = mysqli_stmt_get_result($stmt);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    closeDB($conn);
    
    return [
        'data' => $data,
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
    $conn = connectDB();
    
    $sql = "SELECT * FROM faux_pv WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        closeDB($conn);
        return null;
    }
    $result = mysqli_stmt_get_result($stmt);

    $pv = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    closeDB($conn);

    return $pv;
}


/**
 * Créer un nouveau PV
 */
function createFauxPV($data) {
    $conn = connectDB();
    
    $sql = "INSERT INTO faux_pv (carteEtudiant, nom, prenoms, campus, telephone7, telephoneResistant, identiteFaux, typeDocument, chargeEnquete, agentAction, observations, statut, date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssssssss", 
        $data['carteEtudiant'], 
        $data['nom'], 
        $data['prenoms'], 
        $data['campus'], 
        $data['telephone7'], 
        $data['telephoneResistant'], 
        $data['identiteFaux'], 
        $data['typeDocument'], 
        $data['chargeEnquete'], 
        $data['agentAction'], 
        $data['observations'], 
        $data['statut'], 
        $data['date']
    );
    
    $result = mysqli_stmt_execute($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        closeDB($conn);
        return false;
    }
    $new_id = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);
    closeDB($conn);

    return $result ? $new_id : false;
}


/**
 * Mettre à jour un PV
 */
function updateFauxPV($id, $data) {
    $conn = connectDB();
    
    $sql = "UPDATE faux_pv SET 
            carteEtudiant = ?, nom = ?, prenoms = ?, campus = ?, 
            telephone7 = ?, telephoneResistant = ?, identiteFaux = ?, 
            typeDocument = ?, chargeEnquete = ?, agentAction = ?, 
            observations = ?, statut = ?, date = ?, updatedAt = CURRENT_TIMESTAMP 
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssssssssi", 
        $data['carteEtudiant'], 
        $data['nom'], 
        $data['prenoms'], 
        $data['campus'], 
        $data['telephone7'], 
        $data['telephoneResistant'], 
        $data['identiteFaux'], 
        $data['typeDocument'], 
        $data['chargeEnquete'], 
        $data['agentAction'], 
        $data['observations'], 
        $data['statut'], 
        $data['date'], 
        $id
    );
    
    $result = mysqli_stmt_execute($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        closeDB($conn);
        return false;
    }

    mysqli_stmt_close($stmt);
    closeDB($conn);

    return $result;
}


/**
 * Supprimer un PV
 */
function deleteFauxPV($id) {
    $conn = connectDB();
    
    $sql = "DELETE FROM faux_pv WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    $result = mysqli_stmt_execute($stmt);
    
    mysqli_stmt_close($stmt);
    closeDB($conn);
    
    return $result;
}

/**
 * Obtenir les statistiques
 */
function getFauxStatistics() {
    $conn = connectDB();

    $sql = "SELECT statut, COUNT(*) as count FROM faux_pv GROUP BY statut";
    $result = mysqli_query($conn, $sql);

    $stats = ['total' => 0, 'enCours' => 0, 'traites' => 0];

    while ($row = mysqli_fetch_assoc($result)) {
        $stats['total'] += $row['count'];
        if ($row['statut'] == 'en_cours') {
            $stats['enCours'] = $row['count'];
        } elseif ($row['statut'] == 'traite') {
            $stats['traites'] = $row['count'];
        }
    }

    // Add this month's count
    $thisMonth = date('Y-m');
    $sql_this = "SELECT COUNT(*) as count FROM faux_pv WHERE DATE_FORMAT(date, '%Y-%m') = ?";
    $stmt = mysqli_prepare($conn, $sql_this);
    mysqli_stmt_bind_param($stmt, "s", $thisMonth);
    mysqli_stmt_execute($stmt);
    $result_this = mysqli_stmt_get_result($stmt);
    $stats['thisMonth'] = mysqli_fetch_assoc($result_this)['count'];
    mysqli_stmt_close($stmt);

    closeDB($conn);
    return $stats;
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

    // Rendre le téléphone 7 optionnel mais valider le format si fourni
    if (!empty($data['telephone7']) && !preg_match('/^7[0-9]{8}$/', $data['telephone7'])) {
        $errors[] = 'Le format du téléphone (N° 7...) est invalide (doit être au format 7XXXXXXXX)';
    }

    if (!empty($data['telephoneResistant']) && !preg_match('/^7[0-9]{8}$/', $data['telephoneResistant'])) {
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

/**
 * Peupler la base de données avec des données fictives
 */
function populateFakeData($count = 30) {
    $fakeData = generateFakeFauxData($count);
    $inserted = 0;

    foreach ($fakeData as $data) {
        if (createFauxPV($data)) {
            $inserted++;
        } else {
            // Optionnel : logger l'erreur ou continuer
            error_log("Erreur lors de l'insertion des données fictives");
        }
    }

    return $inserted;
}

/**
 * Obtenir le top 5 des faux par campus (fréquence)
 */
function getTop5Faux() {
    $conn = connectDB();

    $sql = "SELECT campus, COUNT(*) as count FROM faux_pv GROUP BY campus ORDER BY count DESC LIMIT 5";
    $result = mysqli_query($conn, $sql);

    $top5 = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $top5[] = $row;
    }

    closeDB($conn);
    return $top5;
}

/**
 * Obtenir les activités récentes (derniers PVs)
 */
function getRecentFaux($limit = 5) {
    $conn = connectDB();

    $sql = "SELECT id, nom, prenoms, statut, date, createdAt FROM faux_pv ORDER BY createdAt DESC LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $recent = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $recent[] = $row;
    }

    mysqli_stmt_close($stmt);
    closeDB($conn);
    return $recent;
}

?>




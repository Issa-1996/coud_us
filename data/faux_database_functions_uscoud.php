<?php
/**
 * Fonctions CRUD pour les Procès-Verbaux de Faux et Usage de Faux
 * MySQLi procédural - Base de données avec préfixe uscoud_pv_
 */

require_once __DIR__ . '/database.php';

/**
 * Créer un nouveau PV de Faux
 * @param array $data Données du PV
 * @return int|false ID du PV créé ou false en cas d'erreur
 */
function createPVFaux($data) {
    $connexion = getConnection();
    if (!$connexion) return false;
    
    $sql = "INSERT INTO uscoud_pv_faux
            (carte_etudiant, nom, prenoms, campus, telephone_principal, telephone_resistant,
             identite_faux, empreinte, type_document, charge_enquete, agent_action, observations, statut, date_pv, id_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $result = executeNonQuery($connexion, $sql, 'ssssssssssssssi',
        $data['carteEtudiant'] ?? '',
        $data['nom'] ?? '',
        $data['prenoms'] ?? '',
        $data['campus'] ?? '',
        $data['telephone7'] ?? '',
        $data['telephoneResistant'] ?? '',
        $data['identiteFaux'] ?? '',
        $data['empreinte'] ?? '',
        $data['typeDocument'] ?? '',
        $data['chargeEnquete'] ?? '',
        $data['agentAction'] ?? '',
        $data['observations'] ?? '',
        $data['statut'] ?? 'en_cours',
        $data['date'] ?? date('Y-m-d'),
        $data['idAgent'] ?? 0
    );
    
    if ($result) {
        createLog($connexion, $data['idAgent'] ?? null, 'CREATE', 'uscoud_pv_faux', $result, [], $data);
    }
    
    closeConnection($connexion);
    return $result;
}

/**
 * Récupérer un PV de Faux par son ID
 * @param int $id ID du PV
 * @return array|null Données du PV ou null
 */
function getPVFauxById($id) {
    $connexion = getConnection();
    if (!$connexion) return null;
    
    $sql = "SELECT f.*, u.nom as agent_nom, u.prenoms as agent_prenoms 
            FROM uscoud_pv_faux f 
            LEFT JOIN uscoud_pv_utilisateurs u ON f.id_agent = u.id 
            WHERE f.id = ?";
    
    $result = executeQuery($connexion, $sql, 'i', $id);
    $pv = fetchRow($result);
    
    closeConnection($connexion);
    return $pv;
}

/**
 * Récupérer tous les PV de Faux avec pagination et filtres
 * @param int $page Page actuelle
 * @param int $itemsPerPage Éléments par page
 * @param string $search Terme de recherche
 * @param string $status Filtre par statut
 * @return array Données paginées
 */
function getAllPVFaux($page = 1, $itemsPerPage = 10, $search = '', $status = '', $agentId = null) {
    $connexion = getConnection();
    if (!$connexion) return ['data' => [], 'total' => 0, 'totalPages' => 0, 'currentPage' => 1, 'itemsPerPage' => $itemsPerPage];

    $offset = ($page - 1) * $itemsPerPage;
    $whereConditions = [];
    $params = [];
    $types = '';

    // Filtre par agent (pour les agents qui ne voient que leurs PV)
    if ($agentId !== null) {
        $whereConditions[] = "f.id_agent = ?";
        $params[] = $agentId;
        $types .= 'i';
    }

    // Filtre par recherche
    if (!empty($search)) {
        $whereConditions[] = "(f.nom LIKE ? OR f.prenoms LIKE ? OR f.carte_etudiant LIKE ? OR f.telephone_principal LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    // Filtre par statut
    if (!empty($status)) {
        $whereConditions[] = "f.statut = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Requête pour les données
    $sql = "SELECT f.*, u.nom as agent_nom, u.prenoms as agent_prenoms 
            FROM uscoud_pv_faux f 
            LEFT JOIN uscoud_pv_utilisateurs u ON f.id_agent = u.id 
            $whereClause 
            ORDER BY f.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $params[] = $itemsPerPage;
    $params[] = $offset;
    $types .= 'ii';
    
    $result = executeQuery($connexion, $sql, $types, ...$params);
    $data = fetchAll($result);
    
    // Requête pour le total
    $sqlCount = "SELECT COUNT(*) as total FROM uscoud_pv_faux f $whereClause";
    $resultCount = executeQuery($connexion, $sqlCount, substr($types, 0, -2), ...array_slice($params, 0, -2));
    $totalRow = fetchRow($resultCount);
    $total = $totalRow['total'] ?? 0;
    
    $totalPages = ceil($total / $itemsPerPage);
    
    closeConnection($connexion);
    
    return [
        'data' => $data,
        'total' => $total,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'itemsPerPage' => $itemsPerPage
    ];
}

/**
 * Mettre à jour un PV de Faux
 * @param int $id ID du PV
 * @param array $data Nouvelles données
 * @return bool Succès ou échec
 */
function updatePVFaux($id, $data) {
    $connexion = getConnection();
    if (!$connexion) return false;
    
    // Récupérer les anciennes données pour le log
    $ancienPV = getPVFauxById($id);
    if (!$ancienPV) {
        closeConnection($connexion);
        return false;
    }
    
    $sql = "UPDATE uscoud_pv_faux SET
            carte_etudiant = ?, nom = ?, prenoms = ?, campus = ?,
            telephone_principal = ?, telephone_resistant = ?, identite_faux = ?,
            empreinte = ?, type_document = ?, charge_enquete = ?, agent_action = ?, observations = ?,
            statut = ?, date_pv = ?, id_agent = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";

    $result = executeNonQuery($connexion, $sql, 'ssssssssssssssii',
        $data['carteEtudiant'] ?? $ancienPV['carte_etudiant'],
        $data['nom'] ?? $ancienPV['nom'],
        $data['prenoms'] ?? $ancienPV['prenoms'],
        $data['campus'] ?? $ancienPV['campus'],
        $data['telephone7'] ?? $ancienPV['telephone_principal'],
        $data['telephoneResistant'] ?? $ancienPV['telephone_resistant'] ?? '',
        $data['identiteFaux'] ?? $ancienPV['identite_faux'],
        $data['empreinte'] ?? $ancienPV['empreinte'] ?? '',
        $data['typeDocument'] ?? $ancienPV['type_document'],
        $data['chargeEnquete'] ?? $ancienPV['charge_enquete'],
        $data['agentAction'] ?? $ancienPV['agent_action'],
        $data['observations'] ?? $ancienPV['observations'],
        $data['statut'] ?? $ancienPV['statut'],
        $data['date'] ?? $ancienPV['date_pv'],
        $data['idAgent'] ?? $ancienPV['id_agent'] ?? 0,
        $id
    );
    
    if ($result) {
        createLog($connexion, $data['idAgent'] ?? null, 'UPDATE', 'uscoud_pv_faux', $id, $ancienPV, $data);
    }
    
    closeConnection($connexion);
    return $result > 0;
}

/**
 * Supprimer un PV de Faux
 * @param int $id ID du PV
 * @param int $idAgent ID de l'agent qui supprime
 * @return bool Succès ou échec
 */
function deletePVFaux($id, $idAgent = null) {
    $connexion = getConnection();
    if (!$connexion) return false;
    
    // Récupérer les anciennes données pour le log
    $ancienPV = getPVFauxById($id);
    if (!$ancienPV) {
        closeConnection($connexion);
        return false;
    }
    
    $sql = "DELETE FROM uscoud_pv_faux WHERE id = ?";
    $result = executeNonQuery($connexion, $sql, 'i', $id);
    
    if ($result) {
        createLog($connexion, $idAgent, 'DELETE', 'uscoud_pv_faux', $id, $ancienPV, []);
    }
    
    closeConnection($connexion);
    return $result > 0;
}

/**
 * Obtenir les statistiques des PV de Faux
 * @return array Statistiques
 */
function getStatisticsPVFaux($agentId = null) {
    $connexion = getConnection();
    if (!$connexion) return ['total' => 0, 'enCours' => 0, 'traites' => 0, 'archives' => 0];

    $whereClause = '';
    if ($agentId !== null) {
        $whereClause = " WHERE id_agent = ?";
    }

    $sql = "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as enCours,
            SUM(CASE WHEN statut = 'traite' THEN 1 ELSE 0 END) as traites,
            SUM(CASE WHEN statut = 'archive' THEN 1 ELSE 0 END) as archives
            FROM uscoud_pv_faux" . $whereClause;

    if ($agentId !== null) {
        $result = executeQuery($connexion, $sql, 'i', $agentId);
    } else {
        $result = executeQuery($connexion, $sql);
    }
    $stats = fetchRow($result);

    closeConnection($connexion);
    
    return [
        'total' => (int)($stats['total'] ?? 0),
        'enCours' => (int)($stats['enCours'] ?? 0),
        'traites' => (int)($stats['traites'] ?? 0),
        'archives' => (int)($stats['archives'] ?? 0)
    ];
}

/**
 * Valider les données d'un PV de Faux
 * @param array $data Données à valider
 * @return array Erreurs de validation
 */
function validatePVFaux($data) {
    $errors = [];
    
    if (empty($data['carteEtudiant'])) {
        $errors[] = 'Le numéro de pièce est requis';
    } elseif (!preg_match('/^[A-Za-z0-9\/\-\s]{2,30}$/', $data['carteEtudiant'])) {
        $errors[] = 'Le format du numéro de pièce est invalide';
    }
    
    if (empty($data['nom'])) {
        $errors[] = 'Le nom est requis';
    } elseif (strlen($data['nom']) < 2) {
        $errors[] = 'Le nom doit contenir au moins 2 caractères';
    }
    
    if (empty($data['prenoms'])) {
        $errors[] = 'Le prénom est requis';
    } elseif (strlen($data['prenoms']) < 2) {
        $errors[] = 'Le prénom doit contenir au moins 2 caractères';
    }
    
    if (empty($data['campus'])) {
        $errors[] = 'Le campus/résidence est requis';
    }
    
    if (empty($data['telephone7'])) {
        $errors[] = 'Le téléphone (N° 7...) est requis';
    } elseif (!preg_match('/^7[0-9]{8}$/', $data['telephone7'])) {
        $errors[] = 'Le format du téléphone (N° 7...) est invalide (ex: 771234567 ou 712345678)';
    }
    
    if (!empty($data['telephoneResistant']) && !preg_match('/^[0-9]{9}$/', $data['telephoneResistant'])) {
        $errors[] = 'Le format du téléphone résistant est invalide (9 chiffres)';
    }
    
    if (empty($data['typeDocument'])) {
        $errors[] = 'Le type de document est requis';
    }
    
    if (empty($data['date'])) {
        $errors[] = 'La date du PV est requise';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $data['date']);
        if (!$date || $date->format('Y-m-d') !== $data['date']) {
            $errors[] = 'Le format de la date est invalide';
        } elseif ($date > new DateTime()) {
            $errors[] = 'La date du PV ne peut pas être dans le futur';
        }
    }
    
    return $errors;
}

/**
 * Exporter les PV de Faux en CSV
 * @param string $search Terme de recherche
 * @param string $status Filtre par statut
 * @return void
 */
function exportPVFauxToCSV($search = '', $status = '') {
    $connexion = getConnection();
    if (!$connexion) return;
    
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereConditions[] = "(f.nom LIKE ? OR f.prenoms LIKE ? OR f.carte_etudiant LIKE ? OR f.telephone_principal LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    if (!empty($status)) {
        $whereConditions[] = "f.statut = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "SELECT f.numero_pv, f.carte_etudiant, f.nom, f.prenoms, f.campus, 
            f.telephone_principal, f.telephone_resistant, f.identite_faux, 
            f.type_document, f.observations, f.statut, f.date_pv, f.created_at,
            CONCAT(u.nom, ' ', u.prenoms) as agent_nom_complet
            FROM uscoud_pv_faux f 
            LEFT JOIN uscoud_pv_utilisateurs u ON f.id_agent = u.id 
            $whereClause 
            ORDER BY f.created_at DESC";
    
    $result = executeQuery($connexion, $sql, $types, ...$params);
    $data = fetchAll($result);
    
    $filename = 'faux_pv_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV
    fputcsv($output, [
        'Numéro PV',
        'N° Carte Étudiant',
        'Nom',
        'Prénoms',
        'Campus/Résidence',
        'Téléphone (N° 7...)',
        'Téléphone (résistante)',
        'Identité Faux',
        'Type Document',
        'Observations',
        'Statut',
        'Date PV',
        'Date de création',
        'Agent'
    ]);
    
    // Données
    foreach ($data as $pv) {
        fputcsv($output, [
            $pv['numero_pv'],
            $pv['carte_etudiant'],
            $pv['nom'],
            $pv['prenoms'],
            $pv['campus'],
            $pv['telephone_principal'],
            $pv['telephone_resistant'],
            $pv['identite_faux'],
            $pv['type_document'],
            $pv['observations'],
            $pv['statut'] === 'en_cours' ? 'En cours' : ($pv['statut'] === 'traite' ? 'Traité' : 'Archivé'),
            $pv['date_pv'],
            $pv['created_at'],
            $pv['agent_nom_complet'] ?? 'N/A'
        ]);
    }
    
    fclose($output);
    closeConnection($connexion);
    exit;
}

/**
 * Générer des données fictives pour les tests
 * @param int $count Nombre d'enregistrements à générer
 * @return array Données générées
 */
function generateFakeDataPVFaux($count = 30) {
    $connexion = getConnection();
    if (!$connexion) return [];
    
    $noms = ['Diop', 'Ndiaye', 'Fall', 'Sarr', 'Sow', 'Ba', 'Gueye', 'Diouf', 'Mbaye', 'Sy'];
    $prenoms = ['Moussa', 'Fatou', 'Abdoulaye', 'Aminata', 'Ibrahima', 'Mariama', 'Ousmane', 'Aissatou'];
    $campus = ['Campus Social ESP', 'Campus Social UCAD', 'Résidence Claudel', 'Cité Mixte'];
    $statuts = ['en_cours', 'traite', 'archive'];
    $identitesFaux = ['M. Mme Fall Mamadou', 'M. Diop Abdoulaye', 'Mme Sarr Fatoumata', 'M. Ba Ousmane', 'Mlle Ndiaye Aissatou'];
    $typesDocument = ['carte_etudiant', 'cni', 'passport', 'carte_personnel'];
    
    $generatedData = [];
    
    beginTransaction($connexion);
    
    try {
        for ($i = 1; $i <= $count; $i++) {
            $date = new DateTime('2024-' . rand(1, 12) . '-' . rand(1, 28));
            
            $data = [
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
                'idAgent' => 1 // Admin par défaut
            ];
            
            $id = createPVFaux($data);
            if ($id) {
                $data['id'] = $id;
                $generatedData[] = $data;
            }
        }
        
        commitTransaction($connexion);
    } catch (Exception $e) {
        rollbackTransaction($connexion);
        $generatedData = [];
    }
    
    closeConnection($connexion);
    return $generatedData;
}
?>

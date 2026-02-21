<?php
/**
 * Modèle de recherche d'étudiants avec base de données MySQL
 * Utilise la même base de données que le système de constat
 */

// Inclure le fichier de fonctions de base de données
require_once __DIR__ . '/../data/database.php';

/**
 * Obtenir tous les étudiants avec pagination et filtres
 * @param int $page Page actuelle
 * @param int $itemsPerPage Éléments par page
 * @param string $search Terme de recherche
 * @return array Données paginées
 */
function getAllEtudiants($page = 1, $itemsPerPage = 20, $search = '') {
    $connexion = getConnection();
    if (!$connexion) {
        error_log("Erreur de connexion MySQL dans getAllEtudiants");
        return ['data' => [], 'total' => 0, 'totalPages' => 0, 'currentPage' => 1, 'itemsPerPage' => $itemsPerPage];
    }
    
    $offset = ($page - 1) * $itemsPerPage;
    $whereConditions = [];
    $params = [];
    $types = '';
    
    // Filtre par recherche - adapté à la nouvelle structure
    if (!empty($search)) {
        $whereConditions[] = "(e.num_etu LIKE ? OR e.nom LIKE ? OR e.prenoms LIKE ? OR e.email_perso LIKE ? OR e.email_ucad LIKE ? OR e.etablissement LIKE ? OR e.departement LIKE ? OR e.niveauFormation LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssssssss';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Requête adaptée à la nouvelle structure
    $sql = "SELECT e.*, 
                    CASE 
                        WHEN e.typeEtudiant = 'Régulier' THEN 'Régulier'
                        WHEN e.typeEtudiant = 'Privé' THEN 'Privé'
                        ELSE e.typeEtudiant
                    END as statut_label
            FROM uscoud_pv_etudiants e 
            $whereClause 
            ORDER BY e.id_etu DESC 
            LIMIT ? OFFSET ?";
    
    $params[] = $itemsPerPage;
    $params[] = $offset;
    $types .= 'ii';
    
    error_log("SQL getAllEtudiants: " . $sql);
    error_log("Params: " . print_r($params, true));
    error_log("Types: " . $types);
    
    $result = executeQuery($connexion, $sql, $types, ...$params);
    if (!$result) {
        error_log("Erreur executeQuery: " . mysqli_error($connexion));
        return ['data' => [], 'total' => 0, 'totalPages' => 0, 'currentPage' => 1, 'itemsPerPage' => $itemsPerPage];
    }
    
    $data = fetchAll($result);
    
    // Requête pour le total
    $sqlCount = "SELECT COUNT(*) as total FROM uscoud_pv_etudiants e $whereClause";
    $resultCount = executeQuery($connexion, $sqlCount, substr($types, 0, -2), ...array_slice($params, 0, -2));
    $totalRow = fetchRow($resultCount);
    $total = $totalRow['total'] ?? 0;
    
    $totalPages = ceil($total / $itemsPerPage);
    
    closeConnection($connexion);
    
    error_log("Résultats getAllEtudiants: " . count($data) . " étudiants trouvés");
    
    return [
        'data' => $data,
        'total' => $total,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'itemsPerPage' => $itemsPerPage
    ];
}

/**
 * Rechercher des étudiants
 * @param string $query Terme de recherche
 * @return array Résultats de recherche
 */
function searchEtudiants($query) {
    $connexion = getConnection();
    if (!$connexion) return [];
    
    if (empty($query)) {
        closeConnection($connexion);
        return [];
    }
    
    $searchParam = "%$query%";
    $sql = "SELECT e.*, 
                    CASE 
                        WHEN e.statut = 'actif' THEN 'Actif'
                        WHEN e.statut = 'inactif' THEN 'Inactif'
                        WHEN e.statut = 'diplome' THEN 'Diplômé'
                        WHEN e.statut = 'exclu' THEN 'Exclu'
                        ELSE e.statut
                    END as statut_label
            FROM uscoud_pv_etudiants e 
            WHERE e.carte_etudiant LIKE ? OR e.nom LIKE ? OR e.prenoms LIKE ? OR e.email LIKE ? OR e.campus LIKE ? OR e.filiere LIKE ? OR e.niveau_etude LIKE ?
            ORDER BY e.nom ASC, e.prenoms ASC";
    
    $result = executeQuery($connexion, $sql, 'sssssss', 
        $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    $data = fetchAll($result);
    
    closeConnection($connexion);
    return $data;
}

/**
 * Obtenir un étudiant par son ID
 * @param int $id ID de l'étudiant
 * @return array|null Données de l'étudiant ou null
 */
function getEtudiantById($id) {
    $connexion = getConnection();
    if (!$connexion) return null;
    
    $sql = "SELECT e.*, 
                    CASE 
                        WHEN e.statut = 'actif' THEN 'Actif'
                        WHEN e.statut = 'inactif' THEN 'Inactif'
                        WHEN e.statut = 'diplome' THEN 'Diplômé'
                        WHEN e.statut = 'exclu' THEN 'Exclu'
                        ELSE e.statut
                    END as statut_label
            FROM uscoud_pv_etudiants e 
            WHERE e.id = ? OR e.carte_etudiant = ?";
    
    $result = executeQuery($connexion, $sql, 'is', $id, $id);
    $etudiant = fetchRow($result);
    
    closeConnection($connexion);
    return $etudiant;
}

/**
 * Ajouter un nouvel étudiant
 * @param array $data Données de l'étudiant
 * @return int|false ID de l'étudiant créé ou false en cas d'erreur
 */
function addEtudiant($data) {
    $connexion = getConnection();
    if (!$connexion) return false;
    
    $sql = "INSERT INTO uscoud_pv_etudiants 
            (carte_etudiant, nom, prenoms, email, telephone, campus, residence, 
             niveau_etude, filiere, statut, date_naissance, lieu_naissance, nationalite) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $result = executeNonQuery($connexion, $sql, 'sssssssssss',
        $data['carte_etudiant'] ?? '',
        $data['nom'] ?? '',
        $data['prenoms'] ?? '',
        $data['email'] ?? null,
        $data['telephone'] ?? null,
        $data['campus'] ?? '',
        $data['residence'] ?? null,
        $data['niveau_etude'] ?? null,
        $data['filiere'] ?? null,
        $data['statut'] ?? 'actif',
        $data['date_naissance'] ?? null,
        $data['lieu_naissance'] ?? null,
        $data['nationalite'] ?? 'Senegalaise'
    );
    
    closeConnection($connexion);
    return $result;
}

/**
 * Mettre à jour un étudiant
 * @param int $id ID de l'étudiant
 * @param array $data Nouvelles données
 * @return bool Succès ou échec
 */
function updateEtudiant($id, $data) {
    $connexion = getConnection();
    if (!$connexion) return false;
    
    $sql = "UPDATE uscoud_pv_etudiants SET 
            carte_etudiant = ?, nom = ?, prenoms = ?, email = ?, telephone = ?, 
            campus = ?, residence = ?, niveau_etude = ?, filiere = ?, statut = ?, 
            date_naissance = ?, lieu_naissance = ?, nationalite = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?";
    
    $result = executeNonQuery($connexion, $sql, 'sssssssssssi',
        $data['carte_etudiant'] ?? '',
        $data['nom'] ?? '',
        $data['prenoms'] ?? '',
        $data['email'] ?? null,
        $data['telephone'] ?? null,
        $data['campus'] ?? '',
        $data['residence'] ?? null,
        $data['niveau_etude'] ?? null,
        $data['filiere'] ?? null,
        $data['statut'] ?? 'actif',
        $data['date_naissance'] ?? null,
        $data['lieu_naissance'] ?? null,
        $data['nationalite'] ?? 'Senegalaise',
        $id
    );
    
    closeConnection($connexion);
    return $result > 0;
}

/**
 * Supprimer un étudiant
 * @param int $id ID de l'étudiant
 * @return bool Succès ou échec
 */
function deleteEtudiant($id) {
    $connexion = getConnection();
    if (!$connexion) return false;
    
    $sql = "DELETE FROM uscoud_pv_etudiants WHERE id = ?";
    $result = executeNonQuery($connexion, $sql, 'i', $id);
    
    closeConnection($connexion);
    return $result > 0;
}

/**
 * Obtenir les statistiques des étudiants
 * @return array Statistiques
 */
function getStatisticsEtudiants() {
    $connexion = getConnection();
    if (!$connexion) {
        return [
            'total' => 0,
            'actifs' => 0,
            'inactifs' => 0,
            'diplomes' => 0,
            'exclus' => 0
        ];
    }
    
    // Requête adaptée à la nouvelle structure
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN typeEtudiant = 'Régulier' THEN 1 ELSE 0 END) as reguliers,
                SUM(CASE WHEN typeEtudiant = 'Privé' THEN 1 ELSE 0 END) as prives,
                SUM(CASE WHEN regime = 'payant' THEN 1 ELSE 0 END) as payants,
                SUM(CASE WHEN regime = 'non-payant' THEN 1 ELSE 0 END) as non_payants
            FROM uscoud_pv_etudiants";
    
    $result = executeQuery($connexion, $sql);
    if (!$result) {
        error_log("Erreur statistiques: " . mysqli_error($connexion));
        closeConnection($connexion);
        return [
            'total' => 0,
            'actifs' => 0,
            'inactifs' => 0,
            'diplomes' => 0,
            'exclus' => 0
        ];
    }
    
    $stats = fetchRow($result);
    closeConnection($connexion);
    
    return [
        'total' => $stats['total'] ?? 0,
        'actifs' => $stats['reguliers'] ?? 0,
        'inactifs' => $stats['prives'] ?? 0,
        'diplomes' => $stats['payants'] ?? 0,
        'exclus' => $stats['non_payants'] ?? 0
    ];
}

/**
 * Valider les données d'un étudiant
 * @param array $data Données à valider
 * @return array Erreurs de validation
 */
function validateEtudiant($data) {
    $errors = [];
    
    if (empty($data['carte_etudiant'])) {
        $errors[] = 'Le numéro de carte étudiant est requis';
    } elseif (!preg_match('/^[A-Z0-9]{3,20}$/', $data['carte_etudiant'])) {
        $errors[] = 'Le format de la carte étudiant est invalide';
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
        $errors[] = 'Le campus est requis';
    }
    
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email est invalide';
    }
    
    if (!empty($data['telephone']) && !preg_match('/^\+221 [0-9]{2} [0-9]{7}$/', $data['telephone'])) {
        $errors[] = 'Le format du téléphone est invalide (ex: +221 77 1234567)';
    }
    
    if (!empty($data['date_naissance'])) {
        $date = DateTime::createFromFormat('Y-m-d', $data['date_naissance']);
        if (!$date || $date->format('Y-m-d') !== $data['date_naissance']) {
            $errors[] = 'Le format de la date de naissance est invalide';
        } elseif ($date > new DateTime()) {
            $errors[] = 'La date de naissance ne peut pas être dans le futur';
        }
    }
    
    return $errors;
}

/**
 * Exporter les étudiants en CSV
 * @param string $search Terme de recherche
 * @return void
 */
function exportEtudiantsToCSV($search = '') {
    $connexion = getConnection();
    if (!$connexion) return;
    
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereConditions[] = "(e.carte_etudiant LIKE ? OR e.nom LIKE ? OR e.prenoms LIKE ? OR e.email LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "SELECT e.carte_etudiant, e.nom, e.prenoms, e.email, e.telephone, 
            e.campus, e.residence, e.niveau_etude, e.filiere, e.statut, 
            e.date_naissance, e.lieu_naissance, e.nationalite, e.created_at
            FROM uscoud_pv_etudiants e 
            $whereClause 
            ORDER BY e.nom ASC, e.prenoms ASC";
    
    $result = executeQuery($connexion, $sql, $types, ...$params);
    $data = fetchAll($result);
    
    $filename = 'etudiants_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV
    fputcsv($output, [
        'Carte Étudiant',
        'Nom',
        'Prénoms',
        'Email',
        'Téléphone',
        'Campus',
        'Résidence',
        'Niveau Étude',
        'Filière',
        'Statut',
        'Date Naissance',
        'Lieu Naissance',
        'Nationalité',
        'Date Création'
    ]);
    
    // Données
    foreach ($data as $etudiant) {
        fputcsv($output, [
            $etudiant['carte_etudiant'],
            $etudiant['nom'],
            $etudiant['prenoms'],
            $etudiant['email'],
            $etudiant['telephone'],
            $etudiant['campus'],
            $etudiant['residence'],
            $etudiant['niveau_etude'],
            $etudiant['filiere'],
            $etudiant['statut_label'] ?? $etudiant['statut'],
            $etudiant['date_naissance'],
            $etudiant['lieu_naissance'],
            $etudiant['nationalite'],
            $etudiant['created_at']
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
function generateFakeDataEtudiants($count = 100) {
    $connexion = getConnection();
    if (!$connexion) return [];
    
    $noms = ['Diop', 'Ndiaye', 'Fall', 'Sarr', 'Sow', 'Ba', 'Gueye', 'Diouf', 'Mbaye', 'Sy', 'Kane', 'Ly', 'Tall', 'Seck', 'Faye'];
    $prenoms = ['Moussa', 'Fatou', 'Abdoulaye', 'Aminata', 'Ibrahima', 'Mariama', 'Ousmane', 'Aissatou', 'Cheikh', 'Adama', 'Baba', 'Mame', 'Penda', 'Modou'];
    $campus = ['Campus Social ESP', 'Campus Social UCAD', 'Résidence Claudel', 'Cité Mixte', 'Résidence Universitaire'];
    $niveaux = ['L1', 'L2', 'L3', 'M1', 'M2', 'D1', 'D2', 'D3'];
    $filieres = ['Informatique', 'Génie Civil', 'Mathématiques', 'Physique', 'Chimie', 'Biologie', 'Économie', 'Droit', 'Littérature'];
    $statuts = ['actif', 'inactif', 'diplome', 'exclu'];
    
    $generatedData = [];
    
    beginTransaction($connexion);
    
    try {
        for ($i = 1; $i <= $count; $i++) {
            $dateNaissance = new DateTime(rand(1995, 2005) . '-' . rand(1, 12) . '-' . rand(1, 28));
            $lieuxNaissance = ['Dakar', 'Thiès', 'Saint-Louis', 'Kaolack', 'Ziguinchor', 'Fatick', 'Diourbel', 'Louga', 'Tambacounda', 'Kédougou'];
            
            $data = [
                'carte_etudiant' => 'ETU' . str_pad($i + 1000, 6, '0', STR_PAD_LEFT),
                'nom' => $noms[array_rand($noms)],
                'prenoms' => $prenoms[array_rand($prenoms)],
                'email' => strtolower(str_replace(' ', '.', $prenoms[array_rand($prenoms)])) . '.' . strtolower($noms[array_rand($noms)]) . '@ucad.edu.sn',
                'telephone' => '+221 ' . rand(70, 78) . ' ' . rand(1000000, 9999999),
                'campus' => $campus[array_rand($campus)],
                'residence' => $campus[array_rand($campus)],
                'niveau_etude' => $niveaux[array_rand($niveaux)],
                'filiere' => $filieres[array_rand($filieres)],
                'statut' => $statuts[array_rand($statuts)],
                'date_naissance' => $dateNaissance->format('Y-m-d'),
                'lieu_naissance' => $lieuxNaissance[array_rand($lieuxNaissance)],
                'nationalite' => 'Senegalaise'
            ];
            
            $id = addEtudiant($data);
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

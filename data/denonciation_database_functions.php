<?php
/**
 * Fonctions CRUD pour les Procès-Verbaux de Dénonciation
 * MySQLi procédural - Base de données uscoud_pv_denonciation et tables associées
 */

require_once __DIR__ . '/database.php';

/**
 * Créer un nouveau PV de Dénonciation
 * @param array $data Données du PV
 * @return int|false ID du PV créé ou false en cas d'erreur
 */
function createPVDenonciation($data) {
    $connexion = getConnection();
    if (!$connexion) return false;
    
    beginTransaction($connexion);
    
    try {
        // Insertion du PV principal
        $sql = "INSERT INTO uscoud_pv_denonciation
                (numero_pv, denonciateur_nom, denonciateur_prenoms, denonciateur_telephone,
                 denonciateur_email, denonciateur_adresse, denonciateur_anonyme,
                 id_etudiant, type_denonciation, motif_denonciation, description_denonciation,
                 date_denonciation, date_faits, lieu_faits, statut, id_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $result = executeNonQuery($connexion, $sql, 'ssssssissssssssi',
            generateNumeroPV(),
            $data['denonciateur_nom'] ?? '',
            $data['denonciateur_prenoms'] ?? '',
            $data['denonciateur_telephone'] ?? '',
            $data['denonciateur_email'] ?? '',
            $data['denonciateur_adresse'] ?? '',
            $data['denonciateur_anonyme'] ?? 0,
            $data['idEtudiant'] ?? null,
            $data['type_denonciation'] ?? '',
            $data['motif_denonciation'] ?? '',
            $data['description_denonciation'] ?? '',
            $data['date_denonciation'] ?? date('Y-m-d'),
            $data['date_faits'] ?? null,
            $data['lieu_faits'] ?? '',
            $data['statut'] ?? 'en_attente',
            $data['idAgent'] ?? null
        );
        
        if (!$result) {
            throw new Exception("Erreur lors de la création du PV principal");
        }
        
        $idDenonciation = $result;
        
        // Insertion des preuves
        if (!empty($data['preuves'])) {
            foreach ($data['preuves'] as $preuve) {
                $sql = "INSERT INTO uscoud_pv_preuves_denonciation 
                        (id_denonciation, type_preuve, description_preuve, chemin_fichier, date_preuve) 
                        VALUES (?, ?, ?, ?, ?)";
                
                $result = executeNonQuery($connexion, $sql, 'issss',
                    $idDenonciation,
                    $preuve['type_preuve'] ?? $preuve['type'] ?? 'document',
                    $preuve['description_preuve'] ?? $preuve['description'] ?? '',
                    $preuve['chemin_fichier'] ?? $preuve['chemin'] ?? null,
                    $preuve['date_preuve'] ?? $preuve['date'] ?? date('Y-m-d H:i:s')
                );
                
                if (!$result) {
                    throw new Exception("Erreur lors de l'insertion des preuves");
                }
            }
        }
        
        @createLog($connexion, $data['idAgent'] ?? null, 'CREATE', 'uscoud_pv_denonciation', $idDenonciation, [], $data);
        commitTransaction($connexion);
        
        closeConnection($connexion);
        return $idDenonciation;
        
    } catch (Exception $e) {
        rollbackTransaction($connexion);
        closeConnection($connexion);
        error_log("Erreur création PV Dénonciation: " . $e->getMessage());
        return false;
    }
}

/**
 * Rechercher un étudiant par numéro de carte et retourner son id_etu
 * @param string $carteNumero Numéro de carte étudiant
 * @return int|null ID de l'étudiant ou null
 */
function getEtudiantIdByCarte($carteNumero) {
    if (empty($carteNumero)) return null;

    $connexion = getConnection();
    if (!$connexion) return null;

    $sql = "SELECT id_etu FROM uscoud_pv_etudiants WHERE num_etu = ? LIMIT 1";
    $result = executeQuery($connexion, $sql, 's', $carteNumero);
    $row = fetchRow($result);

    closeConnection($connexion);
    return $row ? (int)$row['id_etu'] : null;
}

/**
 * Rechercher un étudiant par numéro et retourner toutes ses informations
 * @param string $query Terme de recherche (num_etu, nom, prenoms)
 * @return array Liste d'étudiants trouvés
 */
function searchEtudiantForDenonciation($query) {
    if (empty($query)) return [];

    $connexion = getConnection();
    if (!$connexion) return [];

    $searchParam = "%$query%";
    $sql = "SELECT id_etu, num_etu, nom, prenoms, email_perso, email_ucad,
            etablissement, departement, niveauFormation, typeEtudiant, regime
            FROM uscoud_pv_etudiants
            WHERE num_etu LIKE ? OR nom LIKE ? OR prenoms LIKE ?
            ORDER BY nom ASC, prenoms ASC
            LIMIT 10";

    $result = executeQuery($connexion, $sql, 'sss', $searchParam, $searchParam, $searchParam);
    $data = fetchAll($result);

    closeConnection($connexion);
    return $data ?: [];
}

/**
 * Récupérer un PV de Dénonciation avec toutes ses informations associées
 * @param int $id ID du PV
 * @return array|null Données complètes du PV ou null
 */
function getPVDenonciationById($id) {
    $connexion = getConnection();
    if (!$connexion) return null;
    
    // PV principal
    $sql = "SELECT d.*, u.nom as agent_nom, u.prenoms as agent_prenoms,
            e.num_etu as etudiant_carte, e.nom as etudiant_nom, e.prenoms as etudiant_prenoms,
            e.email_perso as etudiant_email, e.email_ucad as etudiant_email_ucad,
            e.etablissement as etudiant_etablissement, e.departement as etudiant_departement,
            e.niveauFormation as etudiant_niveau, e.typeEtudiant as etudiant_type,
            e.regime as etudiant_regime
            FROM uscoud_pv_denonciation d
            LEFT JOIN uscoud_pv_utilisateurs u ON d.id_agent = u.id
            LEFT JOIN uscoud_pv_etudiants e ON d.id_etudiant = e.id_etu
            WHERE d.id = ?";
    
    $result = executeQuery($connexion, $sql, 'i', $id);
    $pv = fetchRow($result);
    
    if (!$pv) {
        closeConnection($connexion);
        return null;
    }
    
    // Preuves
    $sql = "SELECT * FROM uscoud_pv_preuves_denonciation WHERE id_denonciation = ?";
    $result = executeQuery($connexion, $sql, 'i', $id);
    $pv['preuves'] = fetchAll($result);
    
    closeConnection($connexion);
    return $pv;
}

/**
 * Récupérer tous les PV de Dénonciation avec pagination et filtres
 * @param int $page Page actuelle
 * @param int $itemsPerPage Éléments par page
 * @param string $search Terme de recherche
 * @param string $status Filtre par statut
 * @return array Données paginées
 */
function getAllPVDenonciation($page = 1, $itemsPerPage = 10, $search = '', $status = '', $agentId = null) {
    $connexion = getConnection();
    if (!$connexion) return ['data' => [], 'total' => 0, 'totalPages' => 0, 'currentPage' => 1, 'itemsPerPage' => $itemsPerPage];

    $offset = ($page - 1) * $itemsPerPage;
    $whereConditions = [];
    $params = [];
    $types = '';

    // Filtre par agent (pour les agents qui ne voient que leurs PV)
    if ($agentId !== null) {
        $whereConditions[] = "d.id_agent = ?";
        $params[] = $agentId;
        $types .= 'i';
    }

    // Filtre par recherche - adapté à la nouvelle structure
    if (!empty($search)) {
        $whereConditions[] = "(d.denonciateur_nom LIKE ? OR d.denonciateur_prenoms LIKE ? OR d.motif_denonciation LIKE ? OR e.num_etu LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    // Filtre par statut
    if (!empty($status)) {
        $whereConditions[] = "d.statut = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Requête pour les données - adaptée à la nouvelle structure
    $sql = "SELECT d.*, u.nom as agent_nom, u.prenoms as agent_prenoms,
            e.num_etu as etudiant_carte, e.nom as etudiant_nom, e.prenoms as etudiant_prenoms,
            e.etablissement as etudiant_etablissement, e.departement as etudiant_departement,
            e.niveauFormation as etudiant_niveau
            FROM uscoud_pv_denonciation d
            LEFT JOIN uscoud_pv_utilisateurs u ON d.id_agent = u.id
            LEFT JOIN uscoud_pv_etudiants e ON d.id_etudiant = e.id_etu
            $whereClause
            ORDER BY d.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $itemsPerPage;
    $params[] = $offset;
    $types .= 'ii';
    
    $result = executeQuery($connexion, $sql, $types, ...$params);
    $data = fetchAll($result);
    
    // Requête pour le total - adaptée à la nouvelle structure
    $sqlCount = "SELECT COUNT(*) as total FROM uscoud_pv_denonciation d 
                LEFT JOIN uscoud_pv_etudiants e ON d.id_etudiant = e.id_etu 
                $whereClause";
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
 * Mettre à jour un PV de Dénonciation
 * @param int $id ID du PV
 * @param array $data Nouvelles données
 * @return bool Succès ou échec
 */
function updatePVDenonciation($id, $data) {
    $connexion = getConnection();
    if (!$connexion) return false;
    
    beginTransaction($connexion);
    
    try {
        // Récupérer les anciennes données pour le log
        $ancienPV = getPVDenonciationById($id);
        if (!$ancienPV) {
            throw new Exception("PV non trouvé");
        }
        
        // Mise à jour du PV principal
        $sql = "UPDATE uscoud_pv_denonciation SET 
                id_etudiant = ?, denonciateur_nom = ?, denonciateur_prenoms = ?, 
                denonciateur_telephone = ?, denonciateur_email = ?, denonciateur_adresse = ?, 
                denonciateur_anonyme = ?, type_denonciation = ?, motif_denonciation = ?, 
                description_denonciation = ?, date_denonciation = ?, date_faits = ?, 
                lieu_faits = ?, statut = ?, id_agent = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $result = executeNonQuery($connexion, $sql, 'isssssisssssssii',
            $data['idEtudiant'] ?? $ancienPV['id_etudiant'],
            $data['denonciateur_nom'] ?? $ancienPV['denonciateur_nom'],
            $data['denonciateur_prenoms'] ?? $ancienPV['denonciateur_prenoms'],
            $data['denonciateur_telephone'] ?? $ancienPV['denonciateur_telephone'],
            $data['denonciateur_email'] ?? $ancienPV['denonciateur_email'],
            $data['denonciateur_adresse'] ?? $ancienPV['denonciateur_adresse'],
            $data['denonciateur_anonyme'] ?? $ancienPV['denonciateur_anonyme'],
            $data['type_denonciation'] ?? $ancienPV['type_denonciation'],
            $data['motif_denonciation'] ?? $ancienPV['motif_denonciation'],
            $data['description_denonciation'] ?? $ancienPV['description_denonciation'],
            $data['date_denonciation'] ?? $ancienPV['date_denonciation'],
            $data['date_faits'] ?? $ancienPV['date_faits'],
            $data['lieu_faits'] ?? $ancienPV['lieu_faits'],
            $data['statut'] ?? $ancienPV['statut'],
            $data['idAgent'] ?? $ancienPV['id_agent'],
            $id
        );
        
        if (!$result) {
            throw new Exception("Erreur lors de la mise à jour du PV principal");
        }
        
        // Mise à jour des preuves (suppression et réinsertion)
        if (isset($data['preuves'])) {
            $sql = "DELETE FROM uscoud_pv_preuves_denonciation WHERE id_denonciation = ?";
            executeNonQuery($connexion, $sql, 'i', $id);
            
            if (!empty($data['preuves'])) {
                foreach ($data['preuves'] as $preuve) {
                    $sql = "INSERT INTO uscoud_pv_preuves_denonciation 
                            (id_denonciation, type_preuve, description_preuve, chemin_fichier, date_preuve) 
                            VALUES (?, ?, ?, ?, ?)";
                    
                    executeNonQuery($connexion, $sql, 'issss',
                        $id,
                        $preuve['type_preuve'] ?? $preuve['type'] ?? 'document',
                        $preuve['description_preuve'] ?? $preuve['description'] ?? '',
                        $preuve['chemin_fichier'] ?? $preuve['chemin'] ?? null,
                        $preuve['date_preuve'] ?? $preuve['date'] ?? date('Y-m-d H:i:s')
                    );
                }
            }
        }
        
        @createLog($connexion, $data['idAgent'] ?? null, 'UPDATE', 'uscoud_pv_denonciation', $id, $ancienPV, $data);
        commitTransaction($connexion);
        
        closeConnection($connexion);
        return true;
        
    } catch (Exception $e) {
        rollbackTransaction($connexion);
        closeConnection($connexion);
        error_log("Erreur mise à jour PV Dénonciation: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprimer un PV de Dénonciation
 * @param int $id ID du PV
 * @param int $idAgent ID de l'agent qui supprime
 * @return bool Succès ou échec
 */
function deletePVDenonciation($id, $idAgent = null) {
    $connexion = getConnection();
    if (!$connexion) return false;

    // Récupérer les anciennes données pour le log
    $ancienPV = getPVDenonciationById($id);
    if (!$ancienPV) {
        closeConnection($connexion);
        return false;
    }

    beginTransaction($connexion);

    try {
        // Supprimer les données enfants avant le parent
        executeNonQuery($connexion, "DELETE FROM uscoud_pv_preuves_denonciation WHERE id_denonciation = ?", 'i', $id);

        // Supprimer le PV principal
        $sql = "DELETE FROM uscoud_pv_denonciation WHERE id = ?";
        $result = executeNonQuery($connexion, $sql, 'i', $id);

        if ($result === false) {
            throw new Exception("Erreur lors de la suppression du PV");
        }

        @createLog($connexion, $idAgent, 'DELETE', 'uscoud_pv_denonciation', $id, $ancienPV, []);
        commitTransaction($connexion);
        closeConnection($connexion);
        return true;

    } catch (Exception $e) {
        rollbackTransaction($connexion);
        closeConnection($connexion);
        error_log("Erreur suppression PV Dénonciation: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtenir les statistiques des PV de Dénonciation
 * @return array Statistiques
 */
function getStatisticsPVDenonciation($agentId = null) {
    $connexion = getConnection();
    if (!$connexion) return ['total' => 0, 'enAttente' => 0, 'enCours' => 0, 'traites' => 0, 'archives' => 0];

    $whereClause = '';
    if ($agentId !== null) {
        $whereClause = " WHERE id_agent = ?";
    }

    $sql = "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as enAttente,
            SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as enCours,
            SUM(CASE WHEN statut = 'traite' THEN 1 ELSE 0 END) as traites,
            SUM(CASE WHEN statut = 'archive' THEN 1 ELSE 0 END) as archives
            FROM uscoud_pv_denonciation" . $whereClause;

    if ($agentId !== null) {
        $result = executeQuery($connexion, $sql, 'i', $agentId);
    } else {
        $result = executeQuery($connexion, $sql);
    }
    $stats = fetchRow($result);

    closeConnection($connexion);
    
    return [
        'total' => (int)($stats['total'] ?? 0),
        'enAttente' => (int)($stats['enAttente'] ?? 0),
        'enCours' => (int)($stats['enCours'] ?? 0),
        'traites' => (int)($stats['traites'] ?? 0),
        'archives' => (int)($stats['archives'] ?? 0)
    ];
}

/**
 * Valider les données d'un PV de Dénonciation
 * @param array $data Données à valider
 * @return array Erreurs de validation
 */
function validatePVDenonciation($data) {
    $errors = [];
    
    if (empty($data['denonciateur_nom'])) {
        $errors[] = 'Le nom du dénonciateur est requis';
    } elseif (strlen($data['denonciateur_nom']) < 2) {
        $errors[] = 'Le nom du dénonciateur doit contenir au moins 2 caractères';
    }
    
    if (empty($data['denonciateur_prenoms'])) {
        $errors[] = 'Le prénom du dénonciateur est requis';
    } elseif (strlen($data['denonciateur_prenoms']) < 2) {
        $errors[] = 'Le prénom du dénonciateur doit contenir au moins 2 caractères';
    }
    
    if (!empty($data['denonciateur_telephone']) && !preg_match('/^7[0-9]{8}$/', $data['denonciateur_telephone'])) {
        $errors[] = 'Le format du téléphone du dénonciateur est invalide (ex: 712345678)';
    }
    
    if (!empty($data['denonciateur_email']) && !filter_var($data['denonciateur_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Le format de l\'email du dénonciateur est invalide';
    }
    
    if (empty($data['type_denonciation'])) {
        $errors[] = 'Le type de dénonciation est requis';
    }
    
    if (empty($data['motif_denonciation'])) {
        $errors[] = 'Le motif de la dénonciation est requis';
    } elseif (strlen($data['motif_denonciation']) < 10) {
        $errors[] = 'Le motif doit contenir au moins 10 caractères';
    }
    
    if (empty($data['description_denonciation'])) {
        $errors[] = 'La description de la dénonciation est requise';
    } elseif (strlen($data['description_denonciation']) < 20) {
        $errors[] = 'La description doit contenir au moins 20 caractères';
    }
    
    if (empty($data['date_denonciation'])) {
        $errors[] = 'La date de dénonciation est requise';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $data['date_denonciation']);
        if (!$date || $date->format('Y-m-d') !== $data['date_denonciation']) {
            $errors[] = 'Le format de la date est invalide';
        } elseif ($date > new DateTime()) {
            $errors[] = 'La date de dénonciation ne peut pas être dans le futur';
        }
    }
    
    if (!empty($data['date_faits'])) {
        $dateFaits = DateTime::createFromFormat('Y-m-d', $data['date_faits']);
        if (!$dateFaits || $dateFaits->format('Y-m-d') !== $data['date_faits']) {
            $errors[] = 'Le format de la date des faits est invalide';
        }
    }
    
    return $errors;
}

/**
 * Exporter les PV de Dénonciation en CSV
 * @param string $search Terme de recherche
 * @param string $status Filtre par statut
 * @return void
 */
function exportPVDenonciationToCSV($search = '', $status = '') {
    $connexion = getConnection();
    if (!$connexion) return;
    
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereConditions[] = "(d.denonciateur_nom LIKE ? OR d.denonciateur_prenoms LIKE ? OR d.motif_denonciation LIKE ? OR e.num_etu LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    if (!empty($status)) {
        $whereConditions[] = "d.statut = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "SELECT 
            d.id, d.numero_pv, d.denonciateur_nom, d.denonciateur_prenoms, d.denonciateur_telephone,
            d.type_denonciation, d.motif_denonciation, d.description_denonciation,
            d.date_denonciation, d.date_faits, d.lieu_faits, d.statut, d.created_at,
            CONCAT(u.nom, ' ', u.prenoms) as agent_nom_complet,
            e.num_etu as etudiant_carte
            FROM uscoud_pv_denonciation d 
            LEFT JOIN uscoud_pv_utilisateurs u ON d.id_agent = u.id 
            LEFT JOIN uscoud_pv_etudiants e ON d.id_etudiant = e.id_etu 
            $whereClause 
            ORDER BY d.created_at DESC";
    
    $result = executeQuery($connexion, $sql, $types, ...$params);
    $data = fetchAll($result);
    
    $filename = 'denonciation_pv_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV
    fputcsv($output, [
        'Numéro PV',
        'Dénonciateur Nom',
        'Dénonciateur Prénoms',
        'Téléphone',
        'Email',
        'Anonyme',
        'Type Dénonciation',
        'Motif',
        'Description',
        'Date Dénonciation',
        'Date Faits',
        'Lieu Faits',
        'Statut',
        'Date de création',
        'Agent',
        'Carte Étudiant Visé'
    ]);
    
    // Données
    foreach ($data as $pv) {
        fputcsv($output, [
            $pv['numero_pv'],
            $pv['denonciateur_nom'],
            $pv['denonciateur_prenoms'],
            $pv['denonciateur_telephone'],
            $pv['denonciateur_email'],
            $pv['denonciateur_anonyme'] ? 'Oui' : 'Non',
            $pv['type_denonciation'],
            $pv['motif_denonciation'],
            $pv['description_denonciation'],
            $pv['date_denonciation'],
            $pv['date_faits'],
            $pv['lieu_faits'],
            $pv['statut'] === 'en_attente' ? 'En attente' : ($pv['statut'] === 'en_cours' ? 'En cours' : ($pv['statut'] === 'traite' ? 'Traité' : 'Archivé')),
            $pv['created_at'],
            $pv['agent_nom_complet'] ?? 'N/A',
            $pv['etudiant_carte'] ?? 'N/A'
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
function generateFakeDataPVDenonciation($count = 30) {
    $connexion = getConnection();
    if (!$connexion) return [];
    
    $noms = ['Diop', 'Ndiaye', 'Fall', 'Sarr', 'Sow', 'Ba', 'Gueye', 'Diouf', 'Mbaye', 'Sy'];
    $prenoms = ['Moussa', 'Fatou', 'Abdoulaye', 'Aminata', 'Ibrahima', 'Mariama', 'Ousmane', 'Aissatou'];
    $statuts = ['en_attente', 'en_cours', 'traite', 'archive'];
    $types = ['violence', 'harcelement', 'diffamation', 'vol', 'fraude', 'autre'];
    
    $generatedData = [];
    
    beginTransaction($connexion);
    
    try {
        for ($i = 1; $i <= $count; $i++) {
            $date = new DateTime('2024-' . rand(1, 12) . '-' . rand(1, 28));
            $dateFaits = new DateTime('2024-' . rand(1, 12) . '-' . rand(1, 28));
            
            $data = [
                'denonciateur_nom' => $noms[array_rand($noms)],
                'denonciateur_prenoms' => $prenoms[array_rand($prenoms)],
                'denonciateur_telephone' => '7' . rand(1000000, 9999999),
                'denonciateur_email' => 'email' . $i . '@example.com',
                'denonciateur_adresse' => 'Adresse #' . $i . ', Dakar',
                'denonciateur_anonyme' => rand(0, 1),
                'type_denonciation' => $types[array_rand($types)],
                'motif_denonciation' => 'Motif de dénonciation #' . $i . ' avec détails complets',
                'description_denonciation' => 'Description détaillée des faits dénoncés #' . $i,
                'date_denonciation' => $date->format('Y-m-d'),
                'date_faits' => $dateFaits->format('Y-m-d'),
                'lieu_faits' => 'Lieu des faits #' . $i,
                'statut' => $statuts[array_rand($statuts)],
                'idAgent' => 1,
                'preuves' => rand(0, 2) ? [
                    [
                        'type' => 'document',
                        'description' => 'Preuve documentaire #' . $i,
                        'chemin' => '/uploads/preuve_' . $i . '.pdf'
                    ]
                ] : []
            ];
            
            $id = createPVDenonciation($data);
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
/**
 * Générer une référence unique pour un PV
 * @return string Référence générée
 */
function generateNumeroPV() {
    $prefix = 'DEN';
    $year = date('Y');
    $month = date('m');
    
    // Obtenir le dernier numéro du mois
    $connexion = getConnection();
    if (!$connexion) return $prefix . $year . $month . '001';
    
    $sql = "SELECT COUNT(*) as count FROM uscoud_pv_denonciation 
            WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
    $result = executeQuery($connexion, $sql, 'ii', $year, $month);
    $row = fetchRow($result);
    
    $count = ($row['count'] ?? 0) + 1;
    $reference = $prefix . $year . $month . str_pad($count, 3, '0', STR_PAD_LEFT);
    
    closeConnection($connexion);
    return $reference;
}

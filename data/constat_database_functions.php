<?php
/**
 * Fonctions CRUD pour les Procès-Verbaux de Constat d'Incident
 * MySQLi procédural - Base de données uscoud_pv_constat et tables associées
 */

require_once __DIR__ . '/database.php';

/**
 * Créer un nouveau PV de Constat
 * @param array $data Données du PV
 * @return int|false ID du PV créé ou false en cas d'erreur
 */
function createPVConstat($data) {
    $connexion = getConnection();
    if (!$connexion) return false;
    
    beginTransaction($connexion);
    
    try {
        // Insertion du PV principal
        $sql = "INSERT INTO uscoud_pv_constat 
                (carte_etudiant, nom, prenoms, campus, telephone, type_incident, 
                 description_incident, lieu_incident, date_incident, heure_incident, 
                 suites_blesses, suites_dommages, suites_assaillants, observations,
                 statut, id_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $result = executeNonQuery($connexion, $sql, 'sssssssssssssssi',
            $data['carteEtudiant'] ?? '',
            $data['nom'] ?? '',
            $data['prenoms'] ?? '',
            $data['campus'] ?? '',
            $data['telephone'] ?? '',
            $data['typeIncident'] ?? '',
            $data['descriptionIncident'] ?? '',
            $data['lieuIncident'] ?? '',
            $data['dateIncident'] ?? date('Y-m-d'),
            $data['heureIncident'] ?? null,
            $data['suitesBlesses'] ?? '',
            $data['suitesDommages'] ?? '',
            $data['suitesAssaillants'] ?? '',
            $data['observations'] ?? '',
            $data['statut'] ?? 'en_cours',
            $data['idAgent'] ?? null
        );
        
        if (!$result) {
            throw new Exception("Erreur lors de la création du PV principal");
        }
        
        $idConstat = $result;
        
        // Insertion des blessés
        if (!empty($data['blesses'])) {
            foreach ($data['blesses'] as $blessé) {
                $sql = "INSERT INTO uscoud_pv_blesses 
                        (id_constat, nom, prenoms, type_blessure, description_blessure, 
                         evacuation, hopital) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $result = executeNonQuery($connexion, $sql, 'issssbs',
                    $idConstat,
                    $blessé['nom'] ?? '',
                    $blessé['prenoms'] ?? '',
                    $blessé['typeBlessure'] ?? 'leger',
                    $blessé['description'] ?? '',
                    $blessé['evacuation'] ?? false,
                    $blessé['hopital'] ?? null
                );
                
                if (!$result) {
                    throw new Exception("Erreur lors de l'insertion des blessés");
                }
            }
        }
        
        // Insertion des dommages
        if (!empty($data['dommages'])) {
            foreach ($data['dommages'] as $dommage) {
                $sql = "INSERT INTO uscoud_pv_dommages 
                        (id_constat, type_domage, description_domage, estimation_valeur, 
                         proprietaire) 
                        VALUES (?, ?, ?, ?, ?)";
                
                $result = executeNonQuery($connexion, $sql, 'issds',
                    $idConstat,
                    $dommage['type'] ?? '',
                    $dommage['description'] ?? '',
                    $dommage['estimation'] ?? null,
                    $dommage['proprietaire'] ?? ''
                );
                
                if (!$result) {
                    throw new Exception("Erreur lors de l'insertion des dommages");
                }
            }
        }
        
        // Insertion des assaillants
        if (!empty($data['assaillants'])) {
            foreach ($data['assaillants'] as $assaillant) {
                $sql = "INSERT INTO uscoud_pv_assaillants 
                        (id_constat, nom, prenoms, description_physique, 
                         signes_distinctifs, statut) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                
                $result = executeNonQuery($connexion, $sql, 'isssss',
                    $idConstat,
                    $assaillant['nom'] ?? '',
                    $assaillant['prenoms'] ?? '',
                    $assaillant['description'] ?? '',
                    $assaillant['signes'] ?? '',
                    $assaillant['statut'] ?? 'inconnu'
                );
                
                if (!$result) {
                    throw new Exception("Erreur lors de l'insertion des assaillants");
                }
            }
        }
        
        // Insertion des auditions
        if (!empty($data['auditions'])) {
            foreach ($data['auditions'] as $audition) {
                $sql = "INSERT INTO uscoud_pv_auditions 
                        (id_constat, temoin_nom, temoin_prenoms, temoin_telephone, 
                         temoin_statut, declaration, date_audition) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $result = executeNonQuery($connexion, $sql, 'issssss',
                    $idConstat,
                    $audition['nom'] ?? '',
                    $audition['prenoms'] ?? '',
                    $audition['telephone'] ?? '',
                    $audition['statut'] ?? 'etudiant',
                    $audition['declaration'] ?? '',
                    $audition['date'] ?? date('Y-m-d H:i:s')
                );
                
                if (!$result) {
                    throw new Exception("Erreur lors de l'insertion des auditions");
                }
            }
        }
        
        // Insertion des témoignages
        error_log('[DEBUG createPVConstat] temoignages reçus: ' . json_encode($data['temoignages'] ?? 'NON DEFINI'));
        if (!empty($data['temoignages'])) {
            foreach ($data['temoignages'] as $temoignage) {
                $sql = "INSERT INTO uscoud_pv_temoignages 
                        (id_constat, temoin_nom, temoin_prenoms, temoin_telephone, 
                         temoin_adresse, temoin_statut, temoignage, date_temoignage) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $result = executeNonQuery($connexion, $sql, 'isssssss',
                    $idConstat,
                    $temoignage['nom'] ?? '',
                    $temoignage['prenoms'] ?? '',
                    $temoignage['telephone'] ?? '',
                    $temoignage['adresse'] ?? '',
                    $temoignage['statut'] ?? 'etudiant',
                    $temoignage['temoignage'] ?? '',
                    $temoignage['date'] ?? date('Y-m-d H:i:s')
                );
                
                if (!$result) {
                    throw new Exception("Erreur lors de l'insertion des témoignages");
                }
            }
        }
        
        createLog($connexion, $data['idAgent'] ?? null, 'CREATE', 'uscoud_pv_constat', $idConstat, [], $data);
        commitTransaction($connexion);
        
        closeConnection($connexion);
        return $idConstat;
        
    } catch (Exception $e) {
        rollbackTransaction($connexion);
        closeConnection($connexion);
        error_log("Erreur création PV Constat: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupérer un PV de Constat avec toutes ses informations associées
 * @param int $id ID du PV
 * @return array|null Données complètes du PV ou null
 */
function getPVConstatById($id) {
    $connexion = getConnection();
    if (!$connexion) return null;
    
    // PV principal
    $sql = "SELECT c.*, u.nom as agent_nom, u.prenoms as agent_prenoms 
            FROM uscoud_pv_constat c 
            LEFT JOIN uscoud_pv_utilisateurs u ON c.id_agent = u.id 
            WHERE c.id = ?";
    
    $result = executeQuery($connexion, $sql, 'i', $id);
    $pv = fetchRow($result);
    
    if (!$pv) {
        closeConnection($connexion);
        return null;
    }
    
    // Blessés
    $sql = "SELECT * FROM uscoud_pv_blesses WHERE id_constat = ?";
    $result = executeQuery($connexion, $sql, 'i', $id);
    $pv['blesses'] = fetchAll($result);
    
    // Dommages
    $sql = "SELECT * FROM uscoud_pv_dommages WHERE id_constat = ?";
    $result = executeQuery($connexion, $sql, 'i', $id);
    $pv['dommages'] = fetchAll($result);
    
    // Assaillants
    $sql = "SELECT * FROM uscoud_pv_assaillants WHERE id_constat = ?";
    $result = executeQuery($connexion, $sql, 'i', $id);
    $pv['assaillants'] = fetchAll($result);
    
    // Auditions
    $sql = "SELECT * FROM uscoud_pv_auditions WHERE id_constat = ?";
    $result = executeQuery($connexion, $sql, 'i', $id);
    $pv['auditions'] = fetchAll($result);
    
    // Témoignages
    $sql = "SELECT * FROM uscoud_pv_temoignages WHERE id_constat = ?";
    $result = executeQuery($connexion, $sql, 'i', $id);
    $pv['temoignages'] = fetchAll($result);
    
    closeConnection($connexion);
    return $pv;
}

/**
 * Récupérer tous les PV de Constat avec pagination et filtres
 * @param int $page Page actuelle
 * @param int $itemsPerPage Éléments par page
 * @param string $search Terme de recherche
 * @param string $status Filtre par statut
 * @return array Données paginées
 */
function getAllPVConstat($page = 1, $itemsPerPage = 10, $search = '', $status = '', $agentId = null) {
    $connexion = getConnection();
    if (!$connexion) return ['data' => [], 'total' => 0, 'totalPages' => 0, 'currentPage' => 1, 'itemsPerPage' => $itemsPerPage];

    $offset = ($page - 1) * $itemsPerPage;
    $whereConditions = [];
    $params = [];
    $types = '';

    // Filtre par agent (pour les agents qui ne voient que leurs PV)
    if ($agentId !== null) {
        $whereConditions[] = "c.id_agent = ?";
        $params[] = $agentId;
        $types .= 'i';
    }

    // Filtre par recherche
    if (!empty($search)) {
        $whereConditions[] = "(c.nom LIKE ? OR c.prenoms LIKE ? OR c.carte_etudiant LIKE ? OR c.telephone LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    // Filtre par statut
    if (!empty($status)) {
        $whereConditions[] = "c.statut = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Requête pour les données
    $sql = "SELECT c.*, u.nom as agent_nom, u.prenoms as agent_prenoms 
            FROM uscoud_pv_constat c 
            LEFT JOIN uscoud_pv_utilisateurs u ON c.id_agent = u.id 
            $whereClause 
            ORDER BY c.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $params[] = $itemsPerPage;
    $params[] = $offset;
    $types .= 'ii';
    
    $result = executeQuery($connexion, $sql, $types, ...$params);
    $data = fetchAll($result);
    
    // Requête pour le total
    $sqlCount = "SELECT COUNT(*) as total FROM uscoud_pv_constat c $whereClause";
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
 * Mettre à jour un PV de Constat
 * @param int $id ID du PV
 * @param array $data Nouvelles données
 * @return bool Succès ou échec
 */
function updatePVConstat($id, $data) {
    $connexion = getConnection();
    if (!$connexion) return false;
    
    beginTransaction($connexion);
    
    try {
        // Récupérer les anciennes données pour le log
        $ancienPV = getPVConstatById($id);
        if (!$ancienPV) {
            throw new Exception("PV non trouvé");
        }
        
        // Mise à jour du PV principal
        $sql = "UPDATE uscoud_pv_constat SET 
                carte_etudiant = ?, nom = ?, prenoms = ?, campus = ?, 
                telephone = ?, type_incident = ?, description_incident = ?, 
                lieu_incident = ?, date_incident = ?, heure_incident = ?, 
                suites_blesses = ?, suites_dommages = ?, suites_assaillants = ?, observations = ?,
                statut = ?, id_agent = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $result = executeNonQuery($connexion, $sql, 'sssssssssssssssii',
            $data['carteEtudiant'] ?? $ancienPV['carte_etudiant'],
            $data['nom'] ?? $ancienPV['nom'],
            $data['prenoms'] ?? $ancienPV['prenoms'],
            $data['campus'] ?? $ancienPV['campus'],
            $data['telephone'] ?? $ancienPV['telephone'],
            $data['typeIncident'] ?? $ancienPV['type_incident'],
            $data['descriptionIncident'] ?? $ancienPV['description_incident'],
            $data['lieuIncident'] ?? $ancienPV['lieu_incident'],
            $data['dateIncident'] ?? $ancienPV['date_incident'],
            $data['heureIncident'] ?? $ancienPV['heure_incident'],
            $data['suitesBlesses'] ?? $ancienPV['suites_blesses'] ?? '',
            $data['suitesDommages'] ?? $ancienPV['suites_dommages'] ?? '',
            $data['suitesAssaillants'] ?? $ancienPV['suites_assaillants'] ?? '',
            $data['observations'] ?? $ancienPV['observations'] ?? '',
            $data['statut'] ?? $ancienPV['statut'],
            $data['idAgent'] ?? $ancienPV['id_agent'],
            $id
        );
        
        if (!$result) {
            throw new Exception("Erreur lors de la mise à jour du PV principal");
        }
        
        // Mise à jour des blessés (suppression et réinsertion)
        if (isset($data['blesses'])) {
            $sql = "DELETE FROM uscoud_pv_blesses WHERE id_constat = ?";
            executeNonQuery($connexion, $sql, 'i', $id);
            
            if (!empty($data['blesses'])) {
                foreach ($data['blesses'] as $blessé) {
                    $sql = "INSERT INTO uscoud_pv_blesses 
                            (id_constat, nom, prenoms, type_blessure, description_blessure, 
                             evacuation, hopital) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    
                    executeNonQuery($connexion, $sql, 'issssbs',
                        $id,
                        $blessé['nom'] ?? '',
                        $blessé['prenoms'] ?? '',
                        $blessé['typeBlessure'] ?? 'leger',
                        $blessé['description'] ?? '',
                        $blessé['evacuation'] ?? false,
                        $blessé['hopital'] ?? null
                    );
                }
            }
        }
        
        // Mise à jour des dommages
        if (isset($data['dommages'])) {
            $sql = "DELETE FROM uscoud_pv_dommages WHERE id_constat = ?";
            executeNonQuery($connexion, $sql, 'i', $id);
            
            if (!empty($data['dommages'])) {
                foreach ($data['dommages'] as $dommage) {
                    $sql = "INSERT INTO uscoud_pv_dommages 
                            (id_constat, type_domage, description_domage, estimation_valeur, 
                             proprietaire) 
                            VALUES (?, ?, ?, ?, ?)";
                    
                    executeNonQuery($connexion, $sql, 'issds',
                        $id,
                        $dommage['type'] ?? '',
                        $dommage['description'] ?? '',
                        $dommage['estimation'] ?? null,
                        $dommage['proprietaire'] ?? ''
                    );
                }
            }
        }
        
        // Mise à jour des assaillants
        if (isset($data['assaillants'])) {
            $sql = "DELETE FROM uscoud_pv_assaillants WHERE id_constat = ?";
            executeNonQuery($connexion, $sql, 'i', $id);

            if (!empty($data['assaillants'])) {
                foreach ($data['assaillants'] as $assaillant) {
                    $sql = "INSERT INTO uscoud_pv_assaillants
                            (id_constat, nom, prenoms, description_physique,
                             signes_distinctifs, statut)
                            VALUES (?, ?, ?, ?, ?, ?)";

                    executeNonQuery($connexion, $sql, 'isssss',
                        $id,
                        $assaillant['nom'] ?? '',
                        $assaillant['prenoms'] ?? '',
                        $assaillant['description'] ?? '',
                        $assaillant['signes'] ?? '',
                        $assaillant['statut'] ?? 'inconnu'
                    );
                }
            }
        }

        // Mise à jour des auditions
        if (isset($data['auditions'])) {
            $sql = "DELETE FROM uscoud_pv_auditions WHERE id_constat = ?";
            executeNonQuery($connexion, $sql, 'i', $id);

            if (!empty($data['auditions'])) {
                foreach ($data['auditions'] as $audition) {
                    $sql = "INSERT INTO uscoud_pv_auditions
                            (id_constat, temoin_nom, temoin_prenoms, temoin_telephone,
                             temoin_statut, declaration, date_audition)
                            VALUES (?, ?, ?, ?, ?, ?, ?)";

                    executeNonQuery($connexion, $sql, 'issssss',
                        $id,
                        $audition['nom'] ?? '',
                        $audition['prenoms'] ?? '',
                        $audition['telephone'] ?? '',
                        $audition['statut'] ?? 'etudiant',
                        $audition['declaration'] ?? '',
                        $audition['date'] ?? date('Y-m-d H:i:s')
                    );
                }
            }
        }

        // Mise à jour des témoignages
        if (isset($data['temoignages'])) {
            $sql = "DELETE FROM uscoud_pv_temoignages WHERE id_constat = ?";
            executeNonQuery($connexion, $sql, 'i', $id);

            if (!empty($data['temoignages'])) {
                foreach ($data['temoignages'] as $temoignage) {
                    $sql = "INSERT INTO uscoud_pv_temoignages
                            (id_constat, temoin_nom, temoin_prenoms, temoin_telephone,
                             temoin_adresse, temoin_statut, temoignage, date_temoignage)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                    executeNonQuery($connexion, $sql, 'isssssss',
                        $id,
                        $temoignage['nom'] ?? '',
                        $temoignage['prenoms'] ?? '',
                        $temoignage['telephone'] ?? '',
                        $temoignage['adresse'] ?? '',
                        $temoignage['statut'] ?? 'etudiant',
                        $temoignage['temoignage'] ?? '',
                        $temoignage['date'] ?? date('Y-m-d H:i:s')
                    );
                }
            }
        }

        createLog($connexion, $data['idAgent'] ?? null, 'UPDATE', 'uscoud_pv_constat', $id, $ancienPV, $data);
        commitTransaction($connexion);

        closeConnection($connexion);
        return true;

    } catch (Exception $e) {
        rollbackTransaction($connexion);
        closeConnection($connexion);
        error_log("Erreur mise à jour PV Constat: " . $e->getMessage());
        return false;
    }
}

/**
 * Supprimer un PV de Constat
 * @param int $id ID du PV
 * @param int $idAgent ID de l'agent qui supprime
 * @return bool Succès ou échec
 */
function deletePVConstat($id, $idAgent = null) {
    $connexion = getConnection();
    if (!$connexion) return false;

    // Récupérer les anciennes données pour le log
    $ancienPV = getPVConstatById($id);
    if (!$ancienPV) {
        closeConnection($connexion);
        return false;
    }

    beginTransaction($connexion);

    try {
        // Supprimer les données enfants avant le parent
        executeNonQuery($connexion, "DELETE FROM uscoud_pv_temoignages WHERE id_constat = ?", 'i', $id);
        executeNonQuery($connexion, "DELETE FROM uscoud_pv_auditions WHERE id_constat = ?", 'i', $id);
        executeNonQuery($connexion, "DELETE FROM uscoud_pv_assaillants WHERE id_constat = ?", 'i', $id);
        executeNonQuery($connexion, "DELETE FROM uscoud_pv_dommages WHERE id_constat = ?", 'i', $id);
        executeNonQuery($connexion, "DELETE FROM uscoud_pv_blesses WHERE id_constat = ?", 'i', $id);

        // Supprimer le PV principal
        $sql = "DELETE FROM uscoud_pv_constat WHERE id = ?";
        $result = executeNonQuery($connexion, $sql, 'i', $id);

        if ($result === false) {
            throw new Exception("Erreur lors de la suppression du PV");
        }

        createLog($connexion, $idAgent, 'DELETE', 'uscoud_pv_constat', $id, $ancienPV, []);
        commitTransaction($connexion);
        closeConnection($connexion);
        return true;

    } catch (Exception $e) {
        rollbackTransaction($connexion);
        closeConnection($connexion);
        error_log("Erreur suppression PV Constat: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtenir les statistiques des PV de Constat
 * @return array Statistiques
 */
function getStatisticsPVConstat($agentId = null) {
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
            FROM uscoud_pv_constat" . $whereClause;

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
 * Récupérer les blessés d'un PV
 * @param int $pvId ID du PV
 * @return array Liste des blessés
 */
function getBlessesByPVId($pvId) {
    $connexion = getConnection();
    if (!$connexion) return [];
    
    // Pour l'instant, retourner un tableau vide car les tables des steps n'existent pas encore
    // TODO: Implémenter quand les tables des steps seront créées
    closeConnection($connexion);
    return [];
}

/**
 * Récupérer les dommages d'un PV
 * @param int $pvId ID du PV
 * @return array Liste des dommages
 */
function getDommagesByPVId($pvId) {
    $connexion = getConnection();
    if (!$connexion) return [];
    
    // TODO: Implémenter quand les tables des steps seront créées
    closeConnection($connexion);
    return [];
}

/**
 * Récupérer les assaillants d'un PV
 * @param int $pvId ID du PV
 * @return array Liste des assaillants
 */
function getAssaillantsByPVId($pvId) {
    $connexion = getConnection();
    if (!$connexion) return [];
    
    // TODO: Implémenter quand les tables des steps seront créées
    closeConnection($connexion);
    return [];
}

/**
 * Récupérer les auditions d'un PV
 * @param int $pvId ID du PV
 * @return array Liste des auditions
 */
function getAuditionsByPVId($pvId) {
    $connexion = getConnection();
    if (!$connexion) return [];
    
    // TODO: Implémenter quand les tables des steps seront créées
    closeConnection($connexion);
    return [];
}

/**
 * Récupérer les témoignages d'un PV
 * @param int $pvId ID du PV
 * @return array Liste des témoignages
 */
function getTemoignagesByPVId($pvId) {
    $connexion = getConnection();
    if (!$connexion) return [];
    
    // TODO: Implémenter quand les tables des steps seront créées
    closeConnection($connexion);
    return [];
}

/**
 * Valider les données d'un PV de Constat
 * @param array $data Données à valider
 * @return array Erreurs de validation
 */
function validatePVConstat($data) {
    $errors = [];
    
    if (empty($data['carteEtudiant'])) {
        $errors[] = 'Le numéro de carte étudiant est requis';
    } elseif (!preg_match('/^[A-Za-z0-9\/\-]{3,20}$/', $data['carteEtudiant'])) {
        $errors[] = 'Le format de la carte étudiant est invalide (3 à 20 caractères alphanumériques)';
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
    
    if (empty($data['telephone'])) {
        // Le téléphone n'est plus obligatoire
    } elseif (!preg_match('/^[0-9]{9,15}$/', $data['telephone'])) {
        $errors[] = 'Le format du téléphone est invalide (ex: 771234567 ou 701234567)';
    }
    
    if (empty($data['typeIncident'])) {
        $errors[] = 'Le type d\'incident est requis';
    }
    
    if (empty($data['descriptionIncident'])) {
        $errors[] = 'La description de l\'incident est requise';
    } elseif (strlen($data['descriptionIncident']) < 5) {
        $errors[] = 'La description doit contenir au moins 5 caractères';
    }
    
    if (empty($data['lieuIncident'])) {
        $errors[] = 'Le lieu de l\'incident est requis';
    }
    
    if (empty($data['dateIncident'])) {
        $errors[] = 'La date de l\'incident est requise';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $data['dateIncident']);
        if (!$date || $date->format('Y-m-d') !== $data['dateIncident']) {
            $errors[] = 'Le format de la date est invalide';
        } elseif ($date > new DateTime()) {
            $errors[] = 'La date de l\'incident ne peut pas être dans le futur';
        }
    }
    
    if (!empty($data['heureIncident']) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['heureIncident'])) {
        $errors[] = 'Le format de l\'heure est invalide (ex: 14:30)';
    }
    
    return $errors;
}

/**
 * Exporter les PV de Constat en CSV
 * @param string $search Terme de recherche
 * @param string $status Filtre par statut
 * @return void
 */
function exportPVConstatToCSV($search = '', $status = '') {
    $connexion = getConnection();
    if (!$connexion) return;
    
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereConditions[] = "(c.nom LIKE ? OR c.prenoms LIKE ? OR c.carte_etudiant LIKE ? OR c.telephone LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    if (!empty($status)) {
        $whereConditions[] = "c.statut = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "SELECT c.numero_pv, c.carte_etudiant, c.nom, c.prenoms, c.campus, 
            c.telephone, c.type_incident, c.description_incident, c.lieu_incident, 
            c.date_incident, c.heure_incident, c.statut, c.created_at,
            CONCAT(u.nom, ' ', u.prenoms) as agent_nom_complet
            FROM uscoud_pv_constat c 
            LEFT JOIN uscoud_pv_utilisateurs u ON c.id_agent = u.id 
            $whereClause 
            ORDER BY c.created_at DESC";
    
    $result = executeQuery($connexion, $sql, $types, ...$params);
    $data = fetchAll($result);
    
    $filename = 'constat_pv_' . date('Y-m-d_H-i-s') . '.csv';
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
        'Téléphone',
        'Type Incident',
        'Description',
        'Lieu',
        'Date Incident',
        'Heure',
        'Statut',
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
            $pv['telephone'],
            $pv['type_incident'],
            $pv['description_incident'],
            $pv['lieu_incident'],
            $pv['date_incident'],
            $pv['heure_incident'],
            $pv['statut'] === 'en_cours' ? 'En cours' : ($pv['statut'] === 'traite' ? 'Traité' : 'Archivé'),
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
function generateFakeDataPVConstat($count = 30) {
    $connexion = getConnection();
    if (!$connexion) return [];
    
    $noms = ['Diop', 'Ndiaye', 'Fall', 'Sarr', 'Sow', 'Ba', 'Gueye', 'Diouf', 'Mbaye', 'Sy'];
    $prenoms = ['Moussa', 'Fatou', 'Abdoulaye', 'Aminata', 'Ibrahima', 'Mariama', 'Ousmane', 'Aissatou'];
    $campus = ['Campus Social ESP', 'Campus Social UCAD', 'Résidence Claudel', 'Cité Mixte'];
    $statuts = ['en_cours', 'traite', 'archive'];
    $typesIncident = ['vol', 'agression', 'degradation', 'perte', 'incendie', 'autre'];
    
    $generatedData = [];
    
    beginTransaction($connexion);
    
    try {
        for ($i = 1; $i <= $count; $i++) {
            $date = new DateTime('2024-' . rand(1, 12) . '-' . rand(1, 28));
            $heure = sprintf('%02d:%02d', rand(8, 23), rand(0, 59));
            
            $data = [
                'carteEtudiant' => 'ETU' . str_pad($i + 100, 6, '0', STR_PAD_LEFT),
                'nom' => $noms[array_rand($noms)],
                'prenoms' => $prenoms[array_rand($prenoms)],
                'campus' => $campus[array_rand($campus)],
                'telephone' => '7' . rand(1000000, 9999999),
                'typeIncident' => $typesIncident[array_rand($typesIncident)],
                'description' => 'Description de l\'incident #' . $i . ' avec détails complets',
                'lieu' => 'Lieu de l\'incident #' . $i,
                'dateIncident' => $date->format('Y-m-d'),
                'heureIncident' => $heure,
                'statut' => $statuts[array_rand($statuts)],
                'idAgent' => 1,
                'blesses' => rand(0, 2) ? [
                    [
                        'nom' => $noms[array_rand($noms)],
                        'prenoms' => $prenoms[array_rand($prenoms)],
                        'typeBlessure' => 'leger',
                        'description' => 'Blessure légère',
                        'evacuation' => false
                    ]
                ] : [],
                'dommages' => rand(0, 2) ? [
                    [
                        'type' => 'materiel',
                        'description' => 'Téléphone cassé',
                        'estimation' => 50000,
                        'proprietaire' => 'Étudiant'
                    ]
                ] : []
            ];
            
            $id = createPVConstat($data);
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

<?php
/**
 * Modèle pour la gestion des utilisateurs
 * Gère les opérations CRUD pour les utilisateurs du système USCOUD
 */

require_once __DIR__ . '/../data/database.php';

/**
 * Obtenir tous les utilisateurs avec pagination
 */
function getAllUtilisateurs($page = 1, $itemsPerPage = 10, $search = '', $role = '', $statut = '') {
    $connexion = getConnection();
    if (!$connexion) {
        return [
            'data' => [],
            'total' => 0,
            'totalPages' => 0,
            'currentPage' => 1,
            'itemsPerPage' => $itemsPerPage
        ];
    }

    $offset = ($page - 1) * $itemsPerPage;
    
    // Construction de la requête de base
    $sql = "SELECT u.*, 
                    (SELECT COUNT(*) FROM uscoud_pv_faux WHERE id_agent = u.id) as nb_faux,
                    (SELECT COUNT(*) FROM uscoud_pv_constat WHERE id_agent = u.id) as nb_constat,
                    (SELECT COUNT(*) FROM uscoud_pv_denonciation WHERE id_agent = u.id) as nb_denonciation
            FROM uscoud_pv_utilisateurs u 
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Ajouter les filtres de recherche
    if (!empty($search)) {
        $sql .= " AND (u.nom LIKE ? OR u.prenoms LIKE ? OR u.email LIKE ? OR u.matricule LIKE ? OR u.role LIKE ?)";
        $searchParam = "%$search%";
        $params = array_fill(0, 5, $searchParam);
        $types = str_repeat('s', 5);
    }

    if (!empty($role)) {
        $sql .= " AND u.role = ?";
        $params[] = $role;
        $types .= 's';
    }
    
    if (!empty($statut)) {
        $sql .= " AND u.statut = ?";
        $params[] = $statut;
        $types .= 's';
    }
    
    // Ajouter l'ordre
    $sql .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $itemsPerPage;
    $params[] = $offset;
    $types .= 'ii';
    
    $result = executeQuery($connexion, $sql, $types, ...$params);
    
    // Obtenir le nombre total pour la pagination
    $countSql = "SELECT COUNT(*) as total FROM uscoud_pv_utilisateurs u WHERE 1=1";
    $countParams = [];
    $countTypes = '';
    
    if (!empty($search)) {
        $countSql .= " AND (u.nom LIKE ? OR u.prenoms LIKE ? OR u.email LIKE ? OR u.matricule LIKE ? OR u.role LIKE ?)";
        $searchParam = "%$search%";
        $countParams = array_fill(0, 5, $searchParam);
        $countTypes = str_repeat('s', 5);
    }
    
    if (!empty($role)) {
        $countSql .= " AND u.role = ?";
        $countParams[] = $role;
        $countTypes .= 's';
    }
    
    if (!empty($statut)) {
        $countSql .= " AND u.statut = ?";
        $countParams[] = $statut;
        $countTypes .= 's';
    }
    
    $countResult = executeQuery($connexion, $countSql, $countTypes, ...$countParams);
    $total = $countResult[0]['total'] ?? 0;
    $totalPages = ceil($total / $itemsPerPage);
    
    closeConnection($connexion);
    
    return [
        'data' => $result,
        'total' => $total,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'itemsPerPage' => $itemsPerPage
    ];
}

/**
 * Obtenir un utilisateur par son ID
 */
function getUtilisateurById($id) {
    $connexion = getConnection();
    if (!$connexion) {
        return null;
    }
    
    $sql = "SELECT u.*, 
                    (SELECT COUNT(*) FROM uscoud_pv_faux WHERE id_agent = u.id) as nb_faux,
                    (SELECT COUNT(*) FROM uscoud_pv_constat WHERE id_agent = u.id) as nb_constat,
                    (SELECT COUNT(*) FROM uscoud_pv_denonciation WHERE id_agent = u.id) as nb_denonciation
            FROM uscoud_pv_utilisateurs u 
            WHERE u.id = ?";
    
    $result = executeQuery($connexion, $sql, 'i', $id);
    
    closeConnection($connexion);
    
    return $result[0] ?? null;
}

/**
 * Créer un nouvel utilisateur
 */
function createUtilisateur($data) {
    $connexion = getConnection();
    if (!$connexion) {
        return false;
    }
    
    // Validation des données
    $errors = validateUtilisateur($data);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Vérifier si le matricule existe déjà
    $checkSql = "SELECT id FROM uscoud_pv_utilisateurs WHERE matricule = ?";
    $checkResult = executeQuery($connexion, $checkSql, 's', $data['matricule']);
    
    if (!empty($checkResult)) {
        return ['success' => false, 'errors' => ['Ce matricule existe déjà']];
    }
    
    // Définir le mot de passe fixe
    $data['mot_de_passe'] = 'COUD';
    
    // Définir le statut par défaut si non fourni
    if (empty($data['statut'])) {
        $data['statut'] = 'actif';
    }
    
    // Hasher le mot de passe
    $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
    
    // Définir la date de création
    $data['created_at'] = date('Y-m-d H:i:s');
    
    // Insérer l'utilisateur
    $sql = "INSERT INTO uscoud_pv_utilisateurs 
                (matricule, nom, prenoms, email, telephone, role, statut, mot_de_passe, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $data['matricule'],
        $data['nom'],
        $data['prenoms'],
        $data['email'],
        $data['telephone'] ?? '',
        $data['role'] ?? 'agent',
        $data['statut'] ?? 'actif',
        $data['mot_de_passe'],
        $data['created_at']
    ];
    
    $types = 'sssssssss';
    
    $result = executeNonQuery($connexion, $sql, $types, ...$params);
    
    closeConnection($connexion);
    
    if ($result) {
        return ['success' => true, 'message' => 'Utilisateur créé avec succès'];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de la création de l\'utilisateur'];
}

/**
 * Mettre à jour un utilisateur
 */
function updateUtilisateur($id, $data) {
    $connexion = getConnection();
    if (!$connexion) {
        return false;
    }
    
    // Validation des données
    $errors = validateUtilisateur($data, true);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Si le mot de passe est fourni, le hasher (mais nous ne le gérons plus)
    // Le mot de passe reste "COUD" et n'est pas modifiable
    if (!empty($data['mot_de_passe'])) {
        $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
    }
    
    // Mettre à jour la date de modification
    $data['updated_at'] = date('Y-m-d H:i:s');
    
    // Construire la requête de mise à jour
    $sql = "UPDATE uscoud_pv_utilisateurs SET ";
    $params = [];
    $types = '';
    
    // Exclure le mot de passe de la mise à jour (fixé à COUD)
    $fields = ['matricule', 'nom', 'prenoms', 'email', 'telephone', 'role', 'statut', 'updated_at'];
    
    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $sql .= "$field = ?, ";
            $params[] = $data[$field];
            $types .= 's';
        }
    }
    
    // Supprimer la dernière virgule
    $sql = rtrim($sql, ', ');
    
    $sql .= " WHERE id = ?";
    $params[] = $id;
    $types .= 'i';
    
    $result = executeNonQuery($connexion, $sql, $types, ...$params);
    
    closeConnection($connexion);
    
    if ($result) {
        return ['success' => true, 'message' => 'Utilisateur mis à jour avec succès'];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'utilisateur'];
}

/**
 * Supprimer un utilisateur
 */
function deleteUtilisateur($id) {
    $connexion = getConnection();
    if (!$connexion) {
        return false;
    }
    
    // Vérifier si l'utilisateur n'a pas de PV associés
    $checkSql = "SELECT 
        (SELECT COUNT(*) FROM uscoud_pv_faux WHERE id_agent = ?) as nb_faux,
        (SELECT COUNT(*) FROM uscoud_pv_constat WHERE id_agent = ?) as nb_constat,
        (SELECT COUNT(*) FROM uscoud_pv_denonciation WHERE id_agent = ?) as nb_denonciation
        FROM uscoud_pv_utilisateurs 
        WHERE id = ?";
    
    $checkResult = executeQuery($connexion, $checkSql, 'iiii', $id, $id, $id, $id);
    
    $checkData = $checkResult[0] ?? [];
    if ($checkData['nb_faux'] > 0 || $checkData['nb_constat'] > 0 || $checkData['nb_denonciation'] > 0) {
        return ['success' => false, 'message' => 'Impossible de supprimer cet utilisateur car il a des PV associés'];
    }
    
    $sql = "DELETE FROM uscoud_pv_utilisateurs WHERE id = ?";
    $result = executeNonQuery($connexion, $sql, 'i', $id);
    
    closeConnection($connexion);
    
    if ($result) {
        return ['success' => true, 'message' => 'Utilisateur supprimé avec succès'];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de la suppression de l\'utilisateur'];
}

/**
 * Obtenir les statistiques des utilisateurs
 */
function getStatisticsUtilisateurs() {
    $connexion = getConnection();
    if (!$connexion) {
        return [
            'total' => 0,
            'actifs' => 0,
            'inactifs' => 0,
            'par_role' => []
        ];
    }
    
    // Statistiques globales
    $sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as actifs,
        SUM(CASE WHEN statut = 'inactif' THEN 1 ELSE 0 END) as inactifs
        FROM uscoud_pv_utilisateurs";
    
    $result = executeQuery($connexion, $sql);
    $stats = $result[0] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0];
    
    // Statistiques par rôle
    $sqlRole = "SELECT role, COUNT(*) as count FROM uscoud_pv_utilisateurs GROUP BY role ORDER BY count DESC";
    $resultRole = executeQuery($connexion, $sqlRole);
    
    $stats['par_role'] = $resultRole;
    
    closeConnection($connexion);
    
    return $stats;
}

/**
 * Valider les données d'un utilisateur
 */
function validateUtilisateur($data, $isUpdate = false) {
    $errors = [];
    
    // Champs obligatoires
    if (empty($data['matricule'])) {
        $errors[] = 'Le matricule est obligatoire';
    }
    
    if (empty($data['nom'])) {
        $errors[] = 'Le nom est obligatoire';
    }
    
    if (empty($data['prenoms'])) {
        $errors[] = 'Les prénoms sont obligatoires';
    }
    
    if (empty($data['email'])) {
        $errors[] = 'L\'email est obligatoire';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide';
    }
    
    // Validation du téléphone si fourni
    if (!empty($data['telephone'])) {
        if (!preg_match('/^[0-9]{9}$/', $data['telephone'])) {
            $errors[] = 'Le numéro de téléphone doit contenir exactement 9 chiffres';
        }
    }
    
    // Validation du matricule
    if (empty($data['matricule'])) {
        $errors[] = 'Le matricule est obligatoire';
    }
    
    // Validation du rôle
    $roles_valides = ['admin', 'agent', 'superviseur', 'operateur'];
    if (!empty($data['role']) && !in_array($data['role'], $roles_valides)) {
        $errors[] = 'Le rôle n\'est pas valide';
    }
    
    // Validation du statut
    $statuts_valides = ['actif', 'inactif'];
    if (!empty($data['statut']) && !in_array($data['statut'], $statuts_valides)) {
        $errors[] = 'Le statut n\'est pas valide';
    }
    
    // Validation du mot de passe pour la création (plus nécessaire car fixé à COUD)
    // Le mot de passe est automatiquement "COUD" dans le code PHP
    
    return $errors;
}

/**
 * Générer un mot de passe sécurisé
 */
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Activer/Désactiver un utilisateur
 */
function toggleUtilisateurStatut($id, $statut) {
    $connexion = getConnection();
    if (!$connexion) {
        return false;
    }
    
    $sql = "UPDATE uscoud_pv_utilisateurs SET statut = ?, updated_at = ? WHERE id = ?";
    $result = executeNonQuery($connexion, $sql, 'sss', $statut, date('Y-m-d H:i:s'), $id);
    
    closeConnection($connexion);
    
    return $result > 0;
}

/**
 * Obtenir un utilisateur par son email
 */
function getUtilisateurByEmail($email) {
    $connexion = getConnection();
    if (!$connexion) {
        return null;
    }

    $sql = "SELECT * FROM uscoud_pv_utilisateurs WHERE email = ? LIMIT 1";
    $result = executeQuery($connexion, $sql, 's', $email);

    closeConnection($connexion);

    return $result[0] ?? null;
}

/**
 * Mettre à jour la dernière connexion d'un utilisateur
 */
function mettreAJourDerniereConnexion($id) {
    $connexion = getConnection();
    if (!$connexion) {
        return false;
    }

    $sql = "UPDATE uscoud_pv_utilisateurs SET derniere_connexion = NOW() WHERE id = ?";
    $result = executeNonQuery($connexion, $sql, 'i', $id);

    closeConnection($connexion);

    return $result !== false;
}

/**
 * Changer le mot de passe d'un utilisateur et désactiver le flag première connexion
 */
function changerMotDePasse($id, $nouveauMdp) {
    $connexion = getConnection();
    if (!$connexion) {
        return ['success' => false, 'message' => 'Erreur de connexion'];
    }

    $hashedPassword = password_hash($nouveauMdp, PASSWORD_DEFAULT);

    $sql = "UPDATE uscoud_pv_utilisateurs SET mot_de_passe = ?, doit_changer_mdp = 0, updated_at = ? WHERE id = ?";
    $result = executeNonQuery($connexion, $sql, 'ssi', $hashedPassword, date('Y-m-d H:i:s'), $id);

    closeConnection($connexion);

    if ($result) {
        return ['success' => true, 'message' => 'Mot de passe changé avec succès'];
    }

    return ['success' => false, 'message' => 'Erreur lors du changement de mot de passe'];
}

/**
 * Réinitialiser le mot de passe d'un utilisateur
 */
function resetPassword($id) {
    $connexion = getConnection();
    if (!$connexion) {
        return false;
    }
    
    $newPassword = 'COUD'; // Mot de passe fixe
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $sql = "UPDATE uscoud_pv_utilisateurs SET mot_de_passe = ?, doit_changer_mdp = 1, updated_at = ? WHERE id = ?";
    $result = executeNonQuery($connexion, $sql, 'ssi', $hashedPassword, date('Y-m-d H:i:s'), $id);
    
    closeConnection($connexion);
    
    if ($result) {
        return ['success' => true, 'message' => 'Mot de passe réinitialisé avec succès', 'password' => $newPassword];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de la réinitialisation du mot de passe'];
}
?>

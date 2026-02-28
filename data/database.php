<?php
/**
 * Connexion à la base de données MySQLi procédural
 * USCOUD - Système de Gestion des Procès-Verbaux
 */

//connexion à la base de données
// NB: connexionBD() est chargée dans codif_recherche_functions.php via traitement/fonction.php

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'uscoud_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Connexion à la base de données
 * @return mysqli|false
 */
function getConnection() {
    $connexion = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if (!$connexion) {
        error_log("Erreur de connexion: " . mysqli_connect_error());
        return false;
    }
    
    // Définir le jeu de caractères
    mysqli_set_charset($connexion, DB_CHARSET);
    
    return $connexion;
}

/**
 * Vérifier si la connexion est active
 * @param mysqli $connexion
 * @return bool
 */
function isConnected($connexion) {
    if (!$connexion) return false;
    // mysqli_ping() supprimé en PHP 8.4 - vérifier via une requête simple
    try {
        return @mysqli_query($connexion, 'SELECT 1') !== false;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Fermer la connexion à la base de données
 * @param mysqli $connexion
 * @return bool
 */
function closeConnection($connexion) {
    if ($connexion) {
        return mysqli_close($connexion);
    }
    return true;
}

/**
 * Exécuter une requête SELECT et retourner les résultats sous forme de tableau
 * @param mysqli $connexion
 * @param string $sql
 * @param string $types Types des paramètres
 * @param mixed ...$params Paramètres
 * @return array|false Tableau de résultats ou false en cas d'erreur
 */
function executeQuery($connexion, $sql, $types = '', ...$params) {
    if (!isConnected($connexion)) {
        error_log("Connexion perdue");
        return false;
    }
    
    // Préparer la requête si des paramètres sont fournis
    if ($types && !empty($params)) {
        $stmt = mysqli_prepare($connexion, $sql);
        if (!$stmt) {
            error_log("Erreur préparation: " . mysqli_error($connexion));
            return false;
        }
        
        // Utiliser call_user_func_array pour éviter les problèmes de références
        $bindParams = [];
        for ($i = 0; $i < count($params); $i++) {
            $bindParams[] = &$params[$i];
        }
        
        if (!empty($bindParams)) {
            $success = mysqli_stmt_bind_param($stmt, $types, ...$bindParams);
            if (!$success) {
                error_log("Erreur bind_param: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                return false;
            }
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Erreur exécution: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
        
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        
        if ($result) {
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            return $data;
        }
        
        return false;
    } else {
        // Exécution directe sans paramètres
        $result = mysqli_query($connexion, $sql);
        if (!$result) {
            error_log("Erreur requête: " . mysqli_error($connexion));
            return false;
        }
        
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }
}

/**
 * Exécuter une requête d'insertion/update/delete
 * @param mysqli $connexion
 * @param string $sql
 * @param string $types Types des paramètres
 * @param mixed ...$params Paramètres
 * @return int|false Nombre de lignes affectées ou ID inséré
 */
function executeNonQuery($connexion, $sql, $types = '', ...$params) {
    if (!isConnected($connexion)) {
        error_log("Connexion perdue");
        return false;
    }
    
    // Préparer la requête si des paramètres sont fournis
    if ($types && !empty($params)) {
        $stmt = mysqli_prepare($connexion, $sql);
        if (!$stmt) {
            error_log("Erreur préparation: " . mysqli_error($connexion));
            return false;
        }
        
        // Lier les paramètres de manière compatible
        $refs = [];
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }
        mysqli_stmt_bind_param($stmt, $types, ...$refs);
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Erreur exécution: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
        
        $affected = mysqli_stmt_affected_rows($stmt);
        $insertId = mysqli_insert_id($connexion);
        
        mysqli_stmt_close($stmt);
        
        // Retourner l'ID inséré si c'est une insertion, sinon le nombre de lignes affectées
        return $insertId > 0 ? $insertId : $affected;
    } else {
        // Exécution directe sans paramètres
        $result = mysqli_query($connexion, $sql);
        if (!$result) {
            error_log("Erreur requête: " . mysqli_error($connexion));
            return false;
        }
        
        $affected = mysqli_affected_rows($connexion);
        $insertId = mysqli_insert_id($connexion);
        
        return $insertId > 0 ? $insertId : $affected;
    }
}

/**
 * Récupérer une seule ligne de résultat
 * @param mysqli_result|array $result
 * @return array|null
 */
function fetchRow($result) {
    // Si c'est déjà un tableau avec une seule ligne, la retourner
    if (is_array($result) && count($result) > 0) {
        return $result[0];
    }
    
    // Si c'est un tableau vide, retourner null
    if (is_array($result)) {
        return null;
    }
    
    // Sinon, traiter comme mysqli_result
    return $result ? mysqli_fetch_assoc($result) : null;
}

/**
 * Récupérer toutes les lignes de résultat
 * @param mysqli_result|array $result
 * @return array
 */
function fetchAll($result) {
    // Si c'est déjà un tableau, le retourner directement
    if (is_array($result)) {
        return $result;
    }
    
    // Sinon, traiter comme mysqli_result
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

/**
 * Récupérer le nombre de lignes affectées
 * @param mysqli $connexion
 * @return int
 */
function getAffectedRows($connexion) {
    return mysqli_affected_rows($connexion);
}

/**
 * Échapper une chaîne pour éviter les injections SQL
 * @param mysqli $connexion
 * @param string $string
 * @return string
 */
function escapeString($connexion, $string) {
    return mysqli_real_escape_string($connexion, $string);
}

/**
 * Commencer une transaction
 * @param mysqli $connexion
 * @return bool
 */
function beginTransaction($connexion) {
    return mysqli_begin_transaction($connexion);
}

/**
 * Valider une transaction
 * @param mysqli $connexion
 * @return bool
 */
function commitTransaction($connexion) {
    return mysqli_commit($connexion);
}

/**
 * Annuler une transaction
 * @param mysqli $connexion
 * @return bool
 */
function rollbackTransaction($connexion) {
    return mysqli_rollback($connexion);
}

/**
 * Obtenir le dernier ID inséré
 * @param mysqli $connexion
 * @return int
 */
function getLastInsertId($connexion) {
    return mysqli_insert_id($connexion);
}

/**
 * Vérifier si une table existe
 * @param mysqli $connexion
 * @param string $tableName
 * @return bool
 */
function tableExists($connexion, $tableName) {
    $sql = "SHOW TABLES LIKE '" . escapeString($connexion, $tableName) . "'";
    $result = mysqli_query($connexion, $sql);
    return $result && mysqli_num_rows($result) > 0;
}

/**
 * Créer un log d'audit
 * @param mysqli $connexion
 * @param int $idUtilisateur
 * @param string $action
 * @param string $table
 * @param int $idEnregistrement
 * @param array $anciennesValeurs
 * @param array $nouvellesValeurs
 * @return bool
 */
function createLog($connexion, $idUtilisateur, $action, $table, $idEnregistrement = null, $anciennesValeurs = [], $nouvellesValeurs = []) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $sql = "INSERT INTO securite_pv_logs (id_utilisateur, action, table_concernee, id_enregistrement, ancienne_valeurs, nouvelles_valeurs, adresse_ip, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $ancienJson = !empty($anciennesValeurs) ? json_encode($anciennesValeurs) : null;
    $nouveauJson = !empty($nouvellesValeurs) ? json_encode($nouvellesValeurs) : null;
    
    return executeNonQuery($connexion, $sql, 'ississss',
        $idUtilisateur, $action, $table, $idEnregistrement, $ancienJson, $nouveauJson, $ip, $userAgent) > 0;
}

// La connexion à bdcodif2 (résidents) est gérée par connexionBD()
// définie dans traitement/fonction.php — chargé via require_once ci-dessus.

/**
 * Fonction de test de connexion
 * @return array
 */
function testDatabaseConnection() {
    $connexion = getConnection();
    
    if (!$connexion) {
        return [
            'success' => false,
            'message' => 'Impossible de se connecter à la base de données',
            'error' => mysqli_connect_error()
        ];
    }
    
    // Test simple
    $result = executeQuery($connexion, "SELECT 1 as test");
    $row = fetchRow($result);
    
    closeConnection($connexion);
    
    if ($row && $row['test'] == 1) {
        return [
            'success' => true,
            'message' => 'Connexion à la base de données établie avec succès'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Erreur lors du test de requête'
        ];
    }
}

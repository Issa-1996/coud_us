<?php
/**
 * Fonctions de recherche d'étudiants dans la base bdcodif
 * Recherche dans les tables codif_etudiant, codif_affectation, codif_lit
 */

require_once __DIR__ . '/database.php';

// Connexion bdcodif2 via traitement/fonction.php
// Le try-catch capture l'erreur de connexion globale ($connexion = connexionBD() ligne 17)
// sans bloquer le chargement des fonctions définies avant cette ligne
try {
    require_once __DIR__ . '/../../traitement/fonction.php';
} catch (mysqli_sql_exception $e) {
    error_log("Erreur connexion globale bdcodif (include fonction.php): " . $e->getMessage());
    // connexionBD() est quand même définie (déclarée avant la ligne qui échoue)
    // L'erreur sera re-lancée et capturée dans recherche.php lors de l'appel réel
}

/**
 * Rechercher des étudiants résidents dans bdcodif
 * @param string $search Terme de recherche (nom, prénoms, num_etu, téléphone)
 * @return array Liste des étudiants trouvés
 */
function rechercherEtudiantsCodif($search) {
    // Connexion à bdcodif2 via connexionBD() de traitement/fonction.php
    $connexion_campus_coud = connexionBD();

    if (empty(trim($search))) {
        closeConnection($connexion_campus_coud);
        return [];
    }

    $sql = "SELECT
                e.id_etu,
                e.nom,
                e.prenoms,
                e.num_etu,
                e.telephone,
                e.dateNaissance,
                e.sexe,
                l.chambre,
                l.pavillon,
                l.lit,
                a.statut
            FROM codif_etudiant e
            LEFT JOIN codif_affectation a ON e.id_etu = a.id_etu
            LEFT JOIN codif_lit l ON a.id_lit = l.id_lit
            WHERE
                e.nom LIKE ?
                OR e.prenoms LIKE ?
                OR e.num_etu LIKE ?
                OR e.telephone LIKE ?
            ORDER BY e.nom ASC";

    $stmt = mysqli_prepare($connexion_campus_coud, $sql);

    if (!$stmt) {
        error_log("Erreur préparation recherche codif: " . mysqli_error($connexion_campus_coud));
        closeConnection($connexion_campus_coud);
        return [];
    }

    $searchParam = "%" . $search . "%";

    mysqli_stmt_bind_param($stmt, "ssss",
        $searchParam,
        $searchParam,
        $searchParam,
        $searchParam
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $etudiants = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $etudiants[] = $row;
    }

    mysqli_stmt_close($stmt);
    closeConnection($connexion_campus_coud);

    return $etudiants;
}

/**
 * Rechercher un étudiant via l'API UCAD par numéro de carte
 * @param string $numeroCarte Numéro de carte étudiant
 * @return array ['data' => array|null, 'error' => string]
 */
function rechercherEtudiantUCAD($numeroCarte) {
    $numeroCarte = trim($numeroCarte);
    if (empty($numeroCarte)) {
        return ['data' => null, 'error' => 'Numéro de carte vide.'];
    }

    if (!function_exists('curl_init')) {
        return ['data' => null, 'error' => 'L\'extension cURL n\'est pas activée sur ce serveur.'];
    }

    $url = API_UCAD_BASE_URL . '/etudiant/' . urlencode($numeroCarte);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => API_UCAD_USERNAME . ':' . API_UCAD_PASSWORD,
        CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    unset($ch);

    if ($curlError) {
        error_log("Erreur cURL API UCAD: $curlError");
        return ['data' => null, 'error' => "Erreur de connexion à l'API : $curlError"];
    }

    if ($httpCode === 401) {
        return ['data' => null, 'error' => 'Authentification refusée par l\'API UCAD (401).'];
    }

    if ($httpCode === 404) {
        return ['data' => null, 'error' => null]; // Étudiant introuvable, pas une erreur système
    }

    if ($httpCode !== 200) {
        error_log("Erreur API UCAD HTTP $httpCode");
        return ['data' => null, 'error' => "L'API a retourné une erreur (code $httpCode)."];
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['data' => null, 'error' => 'Réponse invalide de l\'API UCAD.'];
    }

    if (empty($data)) {
        return ['data' => null, 'error' => null]; // Aucun résultat trouvé
    }

    return ['data' => $data[0], 'error' => null];
}

/**
 * Rechercher les infractions d'un étudiant dans uscoud_db
 * (faux et usage de faux, constat d'incident, dénonciation)
 * @param string $search Numéro de carte, nom, prénom ou téléphone
 * @return array ['faux' => [], 'constat' => [], 'denonciation' => []]
 */
function rechercherInfractionsEtudiant($search) {
    $connexion = getConnection();
    if (!$connexion) return ['faux' => [], 'constat' => [], 'denonciation' => []];

    $p = '%' . trim($search) . '%';
    $resultats = ['faux' => [], 'constat' => [], 'denonciation' => []];

    // --- Faux et usage de faux ---
    $sqlFaux = "SELECT id, numero_pv, nom, prenoms, carte_etudiant,
                       telephone_principal, type_document, statut, date_pv, created_at
                FROM uscoud_pv_faux
                WHERE carte_etudiant LIKE ?
                   OR nom          LIKE ?
                   OR prenoms      LIKE ?
                   OR telephone_principal LIKE ?
                ORDER BY date_pv DESC";
    $stmt = mysqli_prepare($connexion, $sqlFaux);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssss', $p, $p, $p, $p);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $resultats['faux'][] = $row;
        mysqli_stmt_close($stmt);
    }

    // --- Constat d'incident ---
    $sqlConstat = "SELECT id, numero_pv, nom, prenoms, carte_etudiant,
                          telephone, type_incident, lieu_incident,
                          date_incident, statut, created_at
                   FROM uscoud_pv_constat
                   WHERE carte_etudiant LIKE ?
                      OR nom            LIKE ?
                      OR prenoms        LIKE ?
                      OR telephone      LIKE ?
                   ORDER BY date_incident DESC";
    $stmt = mysqli_prepare($connexion, $sqlConstat);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssss', $p, $p, $p, $p);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $resultats['constat'][] = $row;
        mysqli_stmt_close($stmt);
    }

    // --- Dénonciation (dénonciateur ou sujet via uscoud_pv_etudiants) ---
    $sqlDenon = "SELECT d.id, d.numero_pv, d.denonciateur_nom, d.denonciateur_prenoms,
                        d.denonciateur_telephone, d.type_denonciation,
                        d.date_denonciation, d.statut, d.denonciateur_anonyme, d.created_at,
                        e.nom AS sujet_nom, e.prenoms AS sujet_prenoms, e.num_etu AS sujet_carte
                 FROM uscoud_pv_denonciation d
                 LEFT JOIN uscoud_pv_etudiants e ON d.id_etudiant = e.id_etu
                 WHERE d.denonciateur_nom       LIKE ?
                    OR d.denonciateur_prenoms   LIKE ?
                    OR d.denonciateur_telephone LIKE ?
                    OR e.nom                    LIKE ?
                    OR e.prenoms                LIKE ?
                    OR e.num_etu                LIKE ?
                 ORDER BY d.date_denonciation DESC";
    $stmt = mysqli_prepare($connexion, $sqlDenon);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssssss', $p, $p, $p, $p, $p, $p);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) $resultats['denonciation'][] = $row;
        mysqli_stmt_close($stmt);
    }

    closeConnection($connexion);
    return $resultats;
}

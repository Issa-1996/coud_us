<?php
/**
 * Fonctions de recherche d'étudiants dans la base bdcodif
 * Recherche dans les tables codif_etudiant, codif_affectation, codif_lit
 */

require_once __DIR__ . '/database.php';

/**
 * Rechercher des étudiants résidents dans bdcodif
 * @param string $search Terme de recherche (nom, prénoms, num_etu, téléphone)
 * @return array Liste des étudiants trouvés
 */
function rechercherEtudiantsCodif($search) {
    $connexion = getCodifConnection();
    if (!$connexion) return [];

    if (empty(trim($search))) {
        closeConnection($connexion);
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
            FROM codif_affectation a
            LEFT JOIN codif_etudiant e ON a.id_etu = e.id_etu
            LEFT JOIN codif_lit l ON a.id_lit = l.id_lit
            WHERE
                e.nom LIKE ?
                OR e.prenoms LIKE ?
                OR e.num_etu LIKE ?
                OR e.telephone LIKE ?
            ORDER BY e.nom ASC";

    $stmt = mysqli_prepare($connexion, $sql);

    if (!$stmt) {
        error_log("Erreur préparation recherche codif: " . mysqli_error($connexion));
        closeConnection($connexion);
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
    closeConnection($connexion);

    return $etudiants;
}

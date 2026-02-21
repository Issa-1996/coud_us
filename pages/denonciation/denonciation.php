<?php
ob_start(); // Capturer toute sortie parasite AVANT tout include
ini_set('display_errors', '0'); // Ne pas afficher les erreurs PHP dans les réponses JSON
error_reporting(E_ALL); // Mais les logger dans les logs

// Gestionnaire d'erreurs fatales pour toujours retourner du JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Vider tout buffer restant
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Erreur serveur interne',
            'debug' => $error['message'] . ' in ' . basename($error['file']) . ':' . $error['line']
        ]);
    }
});

session_start();
require_once __DIR__ . '/../../config/paths.php';
if (!isset($_SESSION['utilisateur_id'])) {
    redirect('/login');
    exit();
}
if (!empty($_SESSION['doit_changer_mdp'])) {
    redirect('/change-password');
    exit();
}

// Inclure les fonctions de base de données
require_once __DIR__ . '/../../data/denonciation_database_functions.php';

// Traitement des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lire les données JSON depuis le corps de la requête
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true) ?? [];

    $action = $data['action'] ?? $_POST['action'] ?? '';
    $userRole = $_SESSION['role'] ?? '';
    $userId = $_SESSION['utilisateur_id'] ?? 0;

    switch ($action) {
        case 'create':
            ob_end_clean();
            header('Content-Type: application/json');
            if ($userRole === 'operateur') {
                echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission de créer un PV']);
                exit;
            }
            $requestData = [
                'denonciateur_nom' => $data['denonciateur_nom'] ?? $_POST['denonciateur_nom'] ?? '',
                'denonciateur_prenoms' => $data['denonciateur_prenoms'] ?? $_POST['denonciateur_prenoms'] ?? '',
                'denonciateur_telephone' => $data['denonciateur_telephone'] ?? $_POST['denonciateur_telephone'] ?? '',
                'denonciateur_email' => $data['denonciateur_email'] ?? $_POST['denonciateur_email'] ?? '',
                'denonciateur_adresse' => $data['denonciateur_adresse'] ?? $_POST['denonciateur_adresse'] ?? '',
                'denonciateur_anonyme' => $data['denonciateur_anonyme'] ?? $_POST['denonciateur_anonyme'] ?? 0,
                'idEtudiant' => null,
                'type_denonciation' => $data['type_denonciation'] ?? $_POST['type_denonciation'] ?? '',
                'motif_denonciation' => $data['motif_denonciation'] ?? $_POST['motif_denonciation'] ?? '',
                'description_denonciation' => $data['description_denonciation'] ?? $_POST['description_denonciation'] ?? '',
                'date_denonciation' => $data['date_denonciation'] ?? $_POST['date_denonciation'] ?? date('Y-m-d'),
                'date_faits' => $data['date_faits'] ?? $_POST['date_faits'] ?? null,
                'lieu_faits' => $data['lieu_faits'] ?? $_POST['lieu_faits'] ?? '',
                'statut' => $data['statut'] ?? $_POST['statut'] ?? 'en_attente',
                'idAgent' => $_SESSION['utilisateur_id'] ?? 1,
                'preuves' => $data['preuves'] ?? []
            ];

            // Résoudre le numéro de carte étudiant en id_etu
            $carteEtudiant = $data['idEtudiant'] ?? $_POST['idEtudiant'] ?? null;
            if (!empty($carteEtudiant)) {
                $requestData['idEtudiant'] = @getEtudiantIdByCarte($carteEtudiant);
            }

            $errors = validatePVDenonciation($requestData);
            if (empty($errors)) {
                $result = createPVDenonciation($requestData);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'PV créé avec succès', 'id' => $result]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du PV']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            }
            exit;

        case 'update':
            ob_end_clean();
            header('Content-Type: application/json');
            if ($userRole === 'operateur') {
                echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission de modifier un PV']);
                exit;
            }
            $id = $data['id'] ?? $_POST['id'] ?? '';

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID du PV manquant']);
                exit;
            }

            // Agent : vérifier propriété du PV
            if ($userRole === 'agent') {
                $pvCheck = getPVDenonciationById($id);
                if ($pvCheck && $pvCheck['id_agent'] != $userId) {
                    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez modifier que vos propres PV']);
                    exit;
                }
            }

            // Résoudre le numéro de carte étudiant en id_etu
            $carteEtudiant = $data['idEtudiant'] ?? null;
            $idEtudiantResolu = !empty($carteEtudiant) ? @getEtudiantIdByCarte($carteEtudiant) : null;

            $updateData = [
                'denonciateur_nom' => $data['denonciateur_nom'] ?? '',
                'denonciateur_prenoms' => $data['denonciateur_prenoms'] ?? '',
                'denonciateur_telephone' => $data['denonciateur_telephone'] ?? '',
                'denonciateur_email' => $data['denonciateur_email'] ?? '',
                'denonciateur_adresse' => $data['denonciateur_adresse'] ?? '',
                'denonciateur_anonyme' => $data['denonciateur_anonyme'] ?? 0,
                'idEtudiant' => $idEtudiantResolu,
                'type_denonciation' => $data['type_denonciation'] ?? '',
                'motif_denonciation' => $data['motif_denonciation'] ?? '',
                'description_denonciation' => $data['description_denonciation'] ?? '',
                'date_denonciation' => $data['date_denonciation'] ?? date('Y-m-d'),
                'date_faits' => $data['date_faits'] ?? null,
                'lieu_faits' => $data['lieu_faits'] ?? '',
                'statut' => $data['statut'] ?? 'en_attente',
                'idAgent' => $_SESSION['utilisateur_id'] ?? 1,
                'preuves' => $data['preuves'] ?? []
            ];

            $result = updatePVDenonciation($id, $updateData);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'PV mis à jour avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du PV']);
            }
            exit;

        case 'delete':
            ob_end_clean();
            header('Content-Type: application/json');
            if ($userRole === 'operateur' || $userRole === 'superviseur') {
                echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission de supprimer un PV']);
                exit;
            }
            $id = $data['id'] ?? $_POST['id'] ?? '';
            // Agent : vérifier propriété du PV
            if ($userRole === 'agent') {
                $pvCheck = getPVDenonciationById($id);
                if ($pvCheck && $pvCheck['id_agent'] != $userId) {
                    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez supprimer que vos propres PV']);
                    exit;
                }
            }
            $result = deletePVDenonciation($id, $_SESSION['utilisateur_id'] ?? 1);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'PV supprimé avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du PV']);
            }
            exit;

        case 'upload_preuve':
            ob_end_clean();
            header('Content-Type: application/json');

            if (!isset($_FILES['fichier_preuve']) || $_FILES['fichier_preuve']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu ou erreur d\'upload']);
                exit;
            }

            $file = $_FILES['fichier_preuve'];
            $maxSize = 10 * 1024 * 1024; // 10 Mo
            if ($file['size'] > $maxSize) {
                echo json_encode(['success' => false, 'message' => 'Le fichier dépasse la taille maximale de 10 Mo']);
                exit;
            }

            $allowedTypes = [
                'application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'video/mp4', 'video/avi', 'video/quicktime',
                'audio/mpeg', 'audio/wav', 'audio/ogg',
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain'
            ];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            unset($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé (' . $mimeType . ')']);
                exit;
            }

            $uploadDir = __DIR__ . '/../../uploads/preuves_denonciation/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $nomFichier = 'preuve_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $cheminComplet = $uploadDir . $nomFichier;

            if (move_uploaded_file($file['tmp_name'], $cheminComplet)) {
                $cheminRelatif = 'uploads/preuves_denonciation/' . $nomFichier;
                echo json_encode([
                    'success' => true,
                    'message' => 'Fichier uploadé avec succès',
                    'chemin' => $cheminRelatif,
                    'nom_original' => $file['name']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du fichier']);
            }
            exit;

        case 'generate':
            $result = generateFakeDataPVDenonciation(30);
            if ($result) {
                header('Location: denonciation.php?success=4');
                exit;
            } else {
                header('Location: denonciation.php?error=2');
                exit;
            }
            break;

        case 'export':
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            exportPVDenonciationToCSV($search, $status);
            break;
    }
}

// Traitement des requêtes GET pour les détails
if (isset($_GET['action']) && $_GET['action'] === 'detail') {
    ob_end_clean();
    header('Content-Type: application/json');
    $id = $_GET['id'] ?? '';
    $pv = getPVDenonciationById($id);
    if ($pv) {
        echo json_encode($pv);
    } else {
        echo json_encode(['error' => 'PV non trouvé']);
    }
    exit;
}

// Recherche d'étudiants
if (isset($_GET['action']) && $_GET['action'] === 'search_etudiant') {
    ob_end_clean();
    header('Content-Type: application/json');
    $query = $_GET['q'] ?? '';
    $etudiants = searchEtudiantForDenonciation($query);
    echo json_encode(['success' => true, 'etudiants' => $etudiants]);
    exit;
}

// Variables pour la vue
$currentPage = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Filtrer par agent si le rôle est "agent"
$agentFilter = (($_SESSION['role'] ?? '') === 'agent') ? ($_SESSION['utilisateur_id'] ?? null) : null;

$result = getAllPVDenonciation($currentPage, 10, $search, $status, $agentFilter);
$pvData = $result['data'] ?? [];
$pagination = [
    'total' => $result['total'] ?? 0,
    'totalPages' => $result['totalPages'] ?? 1,
    'currentPage' => $result['currentPage'] ?? 1,
    'itemsPerPage' => $result['itemsPerPage'] ?? 10
];
$statistics = getStatisticsPVDenonciation($agentFilter) ?: ['total' => 0];

// Traitement AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    ob_end_clean();
    header('Content-Type: application/json');

    echo json_encode([
        'success' => true,
        'pvData' => $pvData,
        'pagination' => $pagination,
        'statistics' => $statistics
    ]);
    exit;
}

// Vider le buffer pour la page HTML
ob_end_clean();

// Variables pour le header
$pageTitle = "Procès-Verbal de Dénonciation - Campus Social UCAD";
$bannerText = "Procès-Verbal: Dénonciation - USCOUD";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procès-Verbal de Dénonciation - USCOUD</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Styles COUD'MAINT -->
    <link rel="stylesheet" href="../../assets/css/common.css">
    <link rel="stylesheet" href="../../assets/css/denonciation.css">
</head>

<body>
    <?php
    $bannerText = "Procès-Verbal: Dénonciation - USCOUD";
    include __DIR__ . '/../../includes/head.php';
    ?>

    <!-- Statistiques -->
    <div class="container mt-4" id="statsContainer">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-start border-primary border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total PV</p>
                            <h4 id="statTotal"><?php echo $statistics['total'] ?? 0; ?></h4>
                        </div>
                        <i class="fas fa-file-alt fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-start border-info border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">En attente</p>
                            <h4 class="text-info" id="statEnAttente"><?php echo $statistics['enAttente'] ?? 0; ?></h4>
                        </div>
                        <i class="fas fa-hourglass-half fa-2x text-info"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-start border-warning border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">En cours</p>
                            <h4 class="text-warning" id="statEnCours"><?php echo $statistics['enCours'] ?? 0; ?></h4>
                        </div>
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-start border-success border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Traités</p>
                            <h4 class="text-success" id="statTraites"><?php echo $statistics['traites'] ?? 0; ?></h4>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Recherche et Filtres -->
        <div class="row mb-4">
            <div class="col-md-6">
                <input type="text" class="form-control search-box" id="searchInput" placeholder="Rechercher par nom, téléphone, lieu...">
            </div>
            <div class="col-md-3">
                <select class="form-select search-box" id="filterMotif">
                    <option value="">Tous les motifs</option>
                    <option value="violence">Violence</option>
                    <option value="harcelement">Harcèlement</option>
                    <option value="diffamation">Diffamation</option>
                    <option value="vol">Vol</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select search-box" id="filterStatus">
                    <option value="">Tous les statuts</option>
                    <option value="en_cours">En cours</option>
                    <option value="traite">Traité</option>
                </select>
            </div>
        </div>

        <!-- Tableau des PV -->
        <div class="table-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-list me-2"></i>Liste des Procès-Verbaux</h5>
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <label class="me-2">Afficher:</label>
                        <select class="form-select form-select-sm d-inline-block w-auto" id="itemsPerPage" onchange="changeItemsPerPage()">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <span class="badge bg-danger">Total: <span id="totalCount">0</span></span>
                    <?php if (($_SESSION['role'] ?? '') !== 'operateur'): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus-circle me-1"></i>Nouveau PV
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="pvTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Dénonciateur</th>
                            <th>Agent</th>
                            <th>Lieu</th>
                            <th>Type</th>
                            <th>Étudiant concerné</th>
                            <th>Téléphone</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pvTableBody">
                        <!-- Les données seront chargées ici -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4" id="paginationContainer" style="display: none;">
                <div>
                    <small class="text-muted" id="paginationInfo"></small>
                </div>
                <nav aria-label="Pagination">
                    <ul class="pagination mb-0" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal Ajout PV avec Steps -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2 text-success"></i>Nouveau Procès-Verbal de Dénonciation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Stepper Progress -->
                    <div class="stepper-wrapper mb-4">
                        <div class="stepper-item active" data-step="1">
                            <div class="step-counter">1</div>
                            <div class="step-name">Informations</div>
                        </div>
                        <div class="stepper-item" data-step="2">
                            <div class="step-counter">2</div>
                            <div class="step-name">Type & Détails</div>
                        </div>
                        <div class="stepper-item" data-step="3">
                            <div class="step-counter">3</div>
                            <div class="step-name">Dates & Lieu</div>
                        </div>
                        <div class="stepper-item" data-step="4">
                            <div class="step-counter">4</div>
                            <div class="step-name">Finalisation</div>
                        </div>
                    </div>

                    <form id="addForm">
                        <!-- STEP 1: Informations du dénonciateur -->
                        <div class="form-step active" id="step1">
                            <div class="section-title">
                                <i class="fas fa-user me-2"></i>Informations du Dénonciateur
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="addDenonciateurNom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="addDenonciateurNom" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="addDenonciateurPrenoms" class="form-label">Prénoms *</label>
                                    <input type="text" class="form-control" id="addDenonciateurPrenoms" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="addDenonciateurTelephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="addDenonciateurTelephone" placeholder="712345678">
                                </div>
                                <div class="col-md-4">
                                    <label for="addDenonciateurEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="addDenonciateurEmail">
                                </div>
                                <div class="col-md-4">
                                    <label for="addDenonciateurAdresse" class="form-label">Adresse</label>
                                    <input type="text" class="form-control" id="addDenonciateurAdresse">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="addDenonciateurAnonyme">
                                        <label class="form-check-label" for="addDenonciateurAnonyme">
                                            <i class="fas fa-user-secret me-1"></i>Dénonciation anonyme
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: Type de dénonciation -->
                        <div class="form-step" id="step2">
                            <div class="section-title">
                                <i class="fas fa-exclamation-circle me-2"></i>Type de Dénonciation *
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="addTypeDenonciation" class="form-label">Type *</label>
                                    <select class="form-select" id="addTypeDenonciation" required>
                                        <option value="">Sélectionner un type</option>
                                        <option value="violence">Violence</option>
                                        <option value="harcelement">Harcèlement</option>
                                        <option value="diffamation">Diffamation</option>
                                        <option value="vol">Vol</option>
                                        <option value="fraude">Fraude</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="addMotifDenonciation" class="form-label">Motif (min. 10 caractères) *</label>
                                    <textarea class="form-control" id="addMotifDenonciation" rows="3" required placeholder="Décrivez le motif de la dénonciation..."></textarea>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="addDescriptionDenonciation" class="form-label">Description détaillée (min. 20 caractères) *</label>
                                    <textarea class="form-control" id="addDescriptionDenonciation" rows="5" required placeholder="Décrivez en détail les faits dénoncés..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3: Informations sur les faits -->
                        <div class="form-step" id="step3">
                            <div class="section-title">
                                <i class="fas fa-calendar-alt me-2"></i>Dates et Lieu
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="addDateDenonciation" class="form-label">Date de la dénonciation</label>
                                    <input type="date" class="form-control" id="addDateDenonciation">
                                </div>
                                <div class="col-md-6">
                                    <label for="addDateFaits" class="form-label">Date des faits</label>
                                    <input type="date" class="form-control" id="addDateFaits">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="addLieuFaits" class="form-label">Lieu des faits</label>
                                    <input type="text" class="form-control" id="addLieuFaits" placeholder="Ex: Campus Social UCAD, Salle A101...">
                                </div>
                            </div>

                            <div class="section-title mt-4">
                                <i class="fas fa-user-graduate me-2"></i>Étudiant concerné (optionnel)
                            </div>
                            
                            <div class="section-title mt-4">
                                <i class="fas fa-user-graduate me-2"></i>Étudiant concerné
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label for="addIdEtudiant" class="form-label">Rechercher un étudiant (N° étudiant, nom ou prénom)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="addIdEtudiant" placeholder="Ex: 20211234 ou Diop">
                                        <button class="btn btn-outline-danger" type="button" onclick="searchEtudiant('add')">
                                            <i class="fas fa-search me-1"></i>Rechercher
                                        </button>
                                    </div>
                                    <div id="addEtudiantResults" class="list-group mt-1" style="display:none; max-height:200px; overflow-y:auto;"></div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="clearEtudiant('add')">
                                        <i class="fas fa-times me-1"></i>Effacer
                                    </button>
                                </div>
                            </div>
                            <div id="addEtudiantPreview" class="card mb-3" style="display:none;">
                                <div class="card-body p-2">
                                    <div class="row">
                                        <div class="col-md-4"><small class="text-muted">Nom complet</small><br><strong id="addEtuNom">-</strong></div>
                                        <div class="col-md-4"><small class="text-muted">N° Étudiant</small><br><strong id="addEtuNum">-</strong></div>
                                        <div class="col-md-4"><small class="text-muted">Type</small><br><strong id="addEtuType">-</strong></div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-4"><small class="text-muted">Établissement</small><br><strong id="addEtuEtablissement">-</strong></div>
                                        <div class="col-md-4"><small class="text-muted">Département</small><br><strong id="addEtuDepartement">-</strong></div>
                                        <div class="col-md-4"><small class="text-muted">Niveau</small><br><strong id="addEtuNiveau">-</strong></div>
                                    </div>
                                </div>
                            </div>

                            <div class="section-title mt-4">
                                <i class="fas fa-file-alt me-2"></i>Preuves / Pièces jointes
                            </div>
                            <div id="addPreuvesContainer">
                                <!-- Les preuves seront ajoutées dynamiquement -->
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm mt-2" onclick="addPreuveRow('add')">
                                <i class="fas fa-plus me-1"></i>Ajouter une preuve
                            </button>
                        </div>

                        <!-- STEP 4: Finalisation -->
                        <div class="form-step" id="step4">
                            <div class="section-title">
                                <i class="fas fa-check-circle me-2"></i>Récapitulatif
                            </div>

                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Récapitulatif de votre dénonciation</h6>
                                <p>Veuillez vérifier les informations avant de valider :</p>
                                <ul id="recapitulatif">
                                    <li>Nom du dénonciateur : <strong id="recapNom">-</strong></li>
                                    <li>Type de dénonciation : <strong id="recapType">-</strong></li>
                                    <li>Motif : <strong id="recapMotif">-</strong></li>
                                    <li>Date des faits : <strong id="recapDate">-</strong></li>
                                    <li>Lieu : <strong id="recapLieu">-</strong></li>
                                    <li>Preuves : <strong id="recapPreuves">0</strong></li>
                                </ul>
                            </div>

                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Important</h6>
                                <p>Toute dénonciation fausse ou malveillante est passible de poursuites judiciaires.</p>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="prevBtn" onclick="changeStepForm(-1)" style="display: none;">
                        <i class="fas fa-chevron-left me-1"></i>Précédent
                    </button>
                    <button type="button" class="btn btn-danger" id="nextBtn" onclick="changeStep(1)">
                        Suivant<i class="fas fa-chevron-right ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-danger" id="submitBtn" onclick="savePV()" style="display: none;">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modification (même structure que addModal) -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2 text-warning"></i>Modifier le Procès-Verbal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="editId" name="editId">

                        <div class="section-title">
                            <i class="fas fa-user me-2"></i>Informations du Dénonciateur
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editDenonciateurNom" class="form-label">Nom *</label>
                                <input type="text" class="form-control" id="editDenonciateurNom" name="editDenonciateurNom" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editDenonciateurPrenoms" class="form-label">Prénoms *</label>
                                <input type="text" class="form-control" id="editDenonciateurPrenoms" name="editDenonciateurPrenoms" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="editDenonciateurTelephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="editDenonciateurTelephone" name="editDenonciateurTelephone" placeholder="712345678">
                            </div>
                            <div class="col-md-4">
                                <label for="editDenonciateurEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="editDenonciateurEmail" name="editDenonciateurEmail">
                            </div>
                            <div class="col-md-4">
                                <label for="editDenonciateurAdresse" class="form-label">Adresse</label>
                                <input type="text" class="form-control" id="editDenonciateurAdresse" name="editDenonciateurAdresse">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="editDenonciateurAnonyme" name="editDenonciateurAnonyme">
                                    <label class="form-check-label" for="editDenonciateurAnonyme">
                                        <i class="fas fa-user-secret me-1"></i>Dénonciation anonyme
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-exclamation-circle me-2"></i>Type et Détails
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="editTypeDenonciation" class="form-label">Type *</label>
                                <select class="form-select" id="editTypeDenonciation" name="editTypeDenonciation" required>
                                    <option value="">Sélectionner un type</option>
                                    <option value="violence">Violence</option>
                                    <option value="harcelement">Harcèlement</option>
                                    <option value="diffamation">Diffamation</option>
                                    <option value="vol">Vol</option>
                                    <option value="fraude">Fraude</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="editMotifDenonciation" class="form-label">Motif *</label>
                                <textarea class="form-control" id="editMotifDenonciation" name="editMotifDenonciation" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="editDescriptionDenonciation" class="form-label">Description détaillée *</label>
                                <textarea class="form-control" id="editDescriptionDenonciation" name="editDescriptionDenonciation" rows="5" required></textarea>
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-calendar-alt me-2"></i>Dates, Lieu et Statut
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="editDateDenonciation" class="form-label">Date de la dénonciation</label>
                                <input type="date" class="form-control" id="editDateDenonciation" name="editDateDenonciation">
                            </div>
                            <div class="col-md-4">
                                <label for="editDateFaits" class="form-label">Date des faits</label>
                                <input type="date" class="form-control" id="editDateFaits" name="editDateFaits">
                            </div>
                            <div class="col-md-4">
                                <label for="editLieuFaits" class="form-label">Lieu des faits</label>
                                <input type="text" class="form-control" id="editLieuFaits" name="editLieuFaits">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editStatut" class="form-label">Statut</label>
                                <select class="form-select" id="editStatut" name="editStatut">
                                    <option value="en_attente">En attente</option>
                                    <option value="en_cours">En cours</option>
                                    <option value="traite">Traité</option>
                                    <option value="archive">Archivé</option>
                                </select>
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-user-graduate me-2"></i>Étudiant concerné
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="editIdEtudiant" class="form-label">Rechercher un étudiant (N° étudiant, nom ou prénom)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="editIdEtudiant" name="editIdEtudiant" placeholder="Ex: 20211234 ou Diop">
                                    <button class="btn btn-outline-danger" type="button" onclick="searchEtudiant('edit')">
                                        <i class="fas fa-search me-1"></i>Rechercher
                                    </button>
                                </div>
                                <div id="editEtudiantResults" class="list-group mt-1" style="display:none; max-height:200px; overflow-y:auto;"></div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="clearEtudiant('edit')">
                                    <i class="fas fa-times me-1"></i>Effacer
                                </button>
                            </div>
                        </div>
                        <div id="editEtudiantPreview" class="card mb-3" style="display:none;">
                            <div class="card-body p-2">
                                <div class="row">
                                    <div class="col-md-4"><small class="text-muted">Nom complet</small><br><strong id="editEtuNom">-</strong></div>
                                    <div class="col-md-4"><small class="text-muted">N° Étudiant</small><br><strong id="editEtuNum">-</strong></div>
                                    <div class="col-md-4"><small class="text-muted">Type</small><br><strong id="editEtuType">-</strong></div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4"><small class="text-muted">Établissement</small><br><strong id="editEtuEtablissement">-</strong></div>
                                    <div class="col-md-4"><small class="text-muted">Département</small><br><strong id="editEtuDepartement">-</strong></div>
                                    <div class="col-md-4"><small class="text-muted">Niveau</small><br><strong id="editEtuNiveau">-</strong></div>
                                </div>
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-file-alt me-2"></i>Preuves / Pièces jointes
                        </div>
                        <div id="editPreuvesContainer">
                            <!-- Les preuves seront chargées dynamiquement -->
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm mt-2 mb-3" onclick="addPreuveRow('edit')">
                            <i class="fas fa-plus me-1"></i>Ajouter une preuve
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-danger" onclick="showConfirmEditModal()">
                        <i class="fas fa-save me-1"></i>Modifier
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détails -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>Détails du Procès-Verbal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Le contenu sera chargé dynamiquement -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Fermer
                    </button>
                    <button type="button" class="btn btn-danger" onclick="printPV()">
                        <i class="fas fa-print me-1"></i>Imprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2 text-danger"></i>Confirmation de Suppression
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Êtes-vous sûr de vouloir supprimer ce procès-verbal ?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Attention :</strong> Cette action est irréversible et toutes les données associées seront perdues.
                    </div>
                    <div class="text-center">
                        <div class="pv-info mb-2">
                            <strong>Dénonciateur :</strong> <span id="deletePvName">-</span><br>
                            <strong>Type :</strong> <span id="deletePvType">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-1"></i>Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de succès suppression -->
    <div class="modal fade" id="successDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Suppression réussie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-trash-alt text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <p>Le procès-verbal a été supprimé avec succès !</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de modification -->
    <div class="modal fade" id="confirmEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2 text-warning"></i>Confirmer la modification
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir modifier ce procès-verbal ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmEditBtn">
                        <i class="fas fa-save me-1"></i>Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de succès modification -->
    <div class="modal fade" id="successEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modification réussie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                    </div>
                    <p>Le procès-verbal a été modifié avec succès !</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de succès création -->
    <div class="modal fade" id="successCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Création réussie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                    </div>
                    <p>Le procès-verbal a été créé avec succès !</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>var USER_ROLE = '<?php echo $_SESSION["role"] ?? ""; ?>'; var USER_ID = <?php echo $_SESSION["utilisateur_id"] ?? 0; ?>;</script>
    <script src="../../assets/js/denonciation-database.js"></script>
    
    <script>
    // Gestion du stepper - version corrigée
        let currentStepForm = 1;
        const totalStepsForm = 4;

        function validateStepForm(step) {
            let isValid = true;
            let message = '';

            switch (step) {
                case 1:
                    const nom = document.getElementById('addDenonciateurNom')?.value.trim();
                    const prenoms = document.getElementById('addDenonciateurPrenoms')?.value.trim();
                    if (!nom || !prenoms) {
                        message = 'Veuillez renseigner le nom et prénom du dénonciateur';
                        isValid = false;
                    }
                    break;
                case 2:
                    const type = document.getElementById('addTypeDenonciation')?.value;
                    const motif = document.getElementById('addMotifDenonciation')?.value.trim();
                    const description = document.getElementById('addDescriptionDenonciation')?.value.trim();
                    if (!type) {
                        message = 'Veuillez sélectionner un type de dénonciation';
                        isValid = false;
                    }
                    if (!motif || motif.length < 10) {
                        message = 'Veuillez renseigner un motif d\'au moins 10 caractères';
                        isValid = false;
                    }
                    if (!description || description.length < 20) {
                        message = 'Veuillez renseigner une description d\'au moins 20 caractères';
                        isValid = false;
                    }
                    break;
            }

            if (!isValid) {
                alert(message);
            }

            return isValid;
        }

        function changeStepForm(direction) {
            console.log('changeStepForm appelé avec direction:', direction, 'currentStepForm:', currentStepForm);
            
            if (direction === 1 && !validateStepForm(currentStepForm)) {
                console.log('Validation échouée pour l\'étape:', currentStepForm);
                return;
            }

            // Masquer l'étape actuelle
            const currentStepElement = document.getElementById('step' + currentStepForm);
            const currentStepperItem = document.querySelector('.stepper-item[data-step="' + currentStepForm + '"]');
            
            if (currentStepElement) {
                currentStepElement.classList.remove('active');
            }
            if (currentStepperItem) {
                currentStepperItem.classList.remove('active');
            }

            // Marquer comme complétée si on avance
            if (direction === 1 && currentStepperItem) {
                currentStepperItem.classList.add('completed');
            }

            // Changer d'étape
            currentStepForm += direction;
            console.log('Nouvelle étape:', currentStepForm);

            // Remplir le récapitulatif si on arrive à l'étape 4
            if (currentStepForm === 4) {
                const nom = document.getElementById('addDenonciateurNom')?.value || '';
                const prenoms = document.getElementById('addDenonciateurPrenoms')?.value || '';
                document.getElementById('recapNom').textContent = (nom + ' ' + prenoms).trim() || '-';
                document.getElementById('recapType').textContent = document.getElementById('addTypeDenonciation')?.value || '-';
                document.getElementById('recapMotif').textContent = document.getElementById('addMotifDenonciation')?.value || '-';
                document.getElementById('recapDate').textContent = document.getElementById('addDateFaits')?.value || '-';
                document.getElementById('recapLieu').textContent = document.getElementById('addLieuFaits')?.value || '-';
                const nbPreuves = document.getElementById('addPreuvesContainer')?.querySelectorAll('.preuve-row').length || 0;
                document.getElementById('recapPreuves').textContent = nbPreuves > 0 ? nbPreuves + ' pièce(s)' : 'Aucune';
            }

            // Afficher la nouvelle étape
            const newStepElement = document.getElementById('step' + currentStepForm);
            const newStepperItem = document.querySelector('.stepper-item[data-step="' + currentStepForm + '"]');
            
            if (newStepElement) {
                newStepElement.classList.add('active');
            }
            if (newStepperItem) {
                newStepperItem.classList.add('active');
            }

            updateStepButtonsForm();
        }

        function updateStepButtonsForm() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtn = document.getElementById('submitBtn');
            
            console.log('updateStepButtonsForm appelé, currentStepForm:', currentStepForm);

            // Bouton Précédent
            if (prevBtn) {
                if (currentStepForm === 1) {
                    prevBtn.style.display = 'none';
                } else {
                    prevBtn.style.display = 'inline-block';
                }
            }

            // Bouton Suivant / Enregistrer
            if (nextBtn && submitBtn) {
                if (currentStepForm === totalStepsForm) {
                    nextBtn.style.display = 'none';
                    submitBtn.style.display = 'inline-block';
                } else {
                    nextBtn.style.display = 'inline-block';
                    submitBtn.style.display = 'none';
                }
            }
        }

        function resetStepperForm() {
            currentStepForm = 1;

            // Réinitialiser toutes les étapes
            for (let i = 1; i <= totalStepsForm; i++) {
                document.getElementById('step' + i).classList.remove('active');
                const stepItem = document.querySelector('.stepper-item[data-step="' + i + '"]');
                stepItem.classList.remove('active', 'completed');
            }

            // Activer la première étape
            document.getElementById('step1').classList.add('active');
            document.querySelector('.stepper-item[data-step="1"]').classList.add('active');

            updateStepButtonsForm();
            
            // Réinitialiser le formulaire
            document.getElementById('addForm')?.reset();

            // Vider les preuves
            const addPreuvesContainer = document.getElementById('addPreuvesContainer');
            if (addPreuvesContainer) addPreuvesContainer.innerHTML = '';

            // Vider l'étudiant
            clearEtudiant('add');
        }

        function savePVForm() {
            // Validation finale
            if (!validateStepForm(4)) {
                return;
            }

            const data = {
                action: 'create',
                denonciateur_nom: document.getElementById('addDenonciateurNom')?.value || '',
                denonciateur_prenoms: document.getElementById('addDenonciateurPrenoms')?.value || '',
                denonciateur_telephone: document.getElementById('addDenonciateurTelephone')?.value || '',
                denonciateur_email: document.getElementById('addDenonciateurEmail')?.value || '',
                denonciateur_adresse: document.getElementById('addDenonciateurAdresse')?.value || '',
                denonciateur_anonyme: document.getElementById('addDenonciateurAnonyme')?.checked ? 1 : 0,
                idEtudiant: document.getElementById('addIdEtudiant')?.value || null,
                type_denonciation: document.getElementById('addTypeDenonciation')?.value || '',
                motif_denonciation: document.getElementById('addMotifDenonciation')?.value || '',
                description_denonciation: document.getElementById('addDescriptionDenonciation')?.value || '',
                date_denonciation: document.getElementById('addDateDenonciation')?.value || new Date().toISOString().split('T')[0],
                date_faits: document.getElementById('addDateFaits')?.value || null,
                lieu_faits: document.getElementById('addLieuFaits')?.value || '',
                statut: 'en_attente',
                preuves: collectPreuves('add')
            };

            fetch('denonciation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Réponse non-JSON:', text.substring(0, 500));
                        throw new Error('Réponse serveur non-JSON: ' + text.substring(0, 200));
                    });
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
                    if (modal) modal.hide();

                    const successModal = new bootstrap.Modal(document.getElementById('successCreateModal'));
                    successModal.show();

                    loadPVData();
                    resetStepperForm();
                } else {
                    alert((result.message || 'Erreur lors de la création du PV') + (result.debug ? '\n[DEBUG: ' + result.debug + ']' : ''));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Erreur de connexion au serveur');
            });
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser la date du jour
            const dateInput = document.getElementById('addDateDenonciation');
            if (dateInput) {
                dateInput.valueAsDate = new Date();
            }
            
            // Réinitialiser le stepper à l'ouverture du modal
            const addModal = document.getElementById('addModal');
            if (addModal) {
                addModal.addEventListener('show.bs.modal', function() {
                    resetStepperForm();
                });
            }
            
            updateStepButtonsForm();
        });

        // Remplacer les fonctions globales
        window.changeStep = changeStepForm;
        window.savePV = savePVForm;
        window.resetStepper = resetStepperForm;
    </script>

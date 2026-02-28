<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
// Vérification de session
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

// Inclure le modèle
require_once __DIR__ . '/../../models/ConstatModel.php';

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
            header('Content-Type: application/json');
            if ($userRole === 'operateur') {
                echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission de créer un PV']);
                exit;
            }

            try {
                $requestData = [
                    'carteEtudiant' => $data['carteEtudiant'] ?? $_POST['carteEtudiant'] ?? '',
                    'nom' => $data['nom'] ?? $_POST['nom'] ?? '',
                    'prenoms' => $data['prenoms'] ?? $_POST['prenoms'] ?? '',
                    'campus' => $data['campus'] ?? $_POST['campus'] ?? '',
                    'telephone' => $data['telephone'] ?? $_POST['telephone'] ?? '',
                    'typeIncident' => $data['typeIncident'] ?? $_POST['typeIncident'] ?? '',
                    'descriptionIncident' => $data['descriptionIncident'] ?? $_POST['descriptionIncident'] ?? '',
                    'lieuIncident' => $data['lieuIncident'] ?? $_POST['lieuIncident'] ?? '',
                    'dateIncident' => $data['dateIncident'] ?? $_POST['dateIncident'] ?? '',
                    'heureIncident' => $data['heureIncident'] ?? $_POST['heureIncident'] ?? '',
                    'blesses' => $data['blesses'] ?? (isset($_POST['blesses']) ? json_decode($_POST['blesses'], true) : []),
                    'dommages' => $data['dommages'] ?? (isset($_POST['dommages']) ? json_decode($_POST['dommages'], true) : []),
                    'assaillants' => $data['assaillants'] ?? (isset($_POST['assaillants']) ? json_decode($_POST['assaillants'], true) : []),
                    'auditions' => $data['auditions'] ?? (isset($_POST['auditions']) ? json_decode($_POST['auditions'], true) : []),
                    'temoignages' => $data['temoignages'] ?? (isset($_POST['temoignages']) ? json_decode($_POST['temoignages'], true) : []),
                    'suitesBlesses' => $data['suitesBlesses'] ?? $_POST['suitesBlesses'] ?? '',
                    'suitesDommages' => $data['suitesDommages'] ?? $_POST['suitesDommages'] ?? '',
                    'suitesAssaillants' => $data['suitesAssaillants'] ?? $_POST['suitesAssaillants'] ?? '',
                    'observations' => $data['observations'] ?? $_POST['observations'] ?? '',
                    'statut' => $data['statut'] ?? $_POST['statut'] ?? 'en_cours',
                    'date' => $data['date'] ?? $_POST['date'] ?? date('Y-m-d'),
                    'idAgent' => $_SESSION['utilisateur_id'] ?? 1
                ];

                $errors = validatePVConstat($requestData);
                if (empty($errors)) {
                    $result = createPV($requestData);
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'PV créé avec succès', 'id' => $result]);
                        exit;
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du PV']);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreurs de validation', 'errors' => $errors]);
                    exit;
                }
            } catch (Exception $e) {
                error_log('Erreur création PV Constat: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la création du PV']);
                exit;
            } catch (Error $e) {
                error_log('Erreur fatale création PV Constat: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la création du PV']);
                exit;
            }
            break;

        case 'update':
            header('Content-Type: application/json');

            if ($userRole === 'operateur') {
                echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission de modifier un PV']);
                exit;
            }

            try {
                if (!$data || !isset($data['id'])) {
                    echo json_encode(['success' => false, 'message' => 'Données invalides ou ID manquant']);
                    exit;
                }

                $id = $data['id'];

                // Agent : vérifier propriété du PV
                if ($userRole === 'agent') {
                    $pvCheck = getPVById($id);
                    if ($pvCheck && $pvCheck['id_agent'] != $userId) {
                        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez modifier que vos propres PV']);
                        exit;
                    }
                }

                $updateData = [
                    'id' => $id,
                    'carteEtudiant' => $data['carte_etudiant'] ?? '',
                    'nom' => $data['nom'] ?? '',
                    'prenoms' => $data['prenoms'] ?? '',
                    'campus' => $data['campus'] ?? '',
                    'telephone' => $data['telephone'] ?? '',
                    'typeIncident' => $data['type_incident'] ?? '',
                    'descriptionIncident' => $data['description_incident'] ?? '',
                    'lieuIncident' => $data['lieu_incident'] ?? '',
                    'dateIncident' => $data['date_incident'] ?? '',
                    'heureIncident' => $data['heure_incident'] ?? '',
                    'suitesBlesses' => $data['suites_blesses'] ?? '',
                    'suitesDommages' => $data['suites_dommages'] ?? '',
                    'suitesAssaillants' => $data['suites_assaillants'] ?? '',
                    'observations' => $data['observations'] ?? '',
                    'statut' => $data['statut'] ?? 'en_cours',
                    'idAgent' => $_SESSION['utilisateur_id'] ?? 1,
                    'date' => $data['created_at'] ?? date('Y-m-d'),
                    'blesses' => $data['blesses'] ?? [],
                    'dommages' => $data['dommages'] ?? [],
                    'assaillants' => $data['assaillants'] ?? [],
                    'auditions' => $data['auditions'] ?? [],
                    'temoignages' => $data['temoignages'] ?? []
                ];

                $errors = validatePVConstat($updateData);

                if (empty($errors)) {
                    $result = updatePV($id, $updateData);

                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'PV mis à jour avec succès']);
                        exit;
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du PV']);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreurs de validation', 'errors' => $errors]);
                    exit;
                }
            } catch (Exception $e) {
                error_log('Erreur mise à jour PV Constat: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour du PV']);
                exit;
            } catch (Error $e) {
                error_log('Erreur fatale mise à jour PV Constat: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour du PV']);
                exit;
            }
            break;

        case 'delete':
            header('Content-Type: application/json');
            if ($userRole === 'operateur' || $userRole === 'superviseur') {
                echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission de supprimer un PV']);
                exit;
            }
            $id = $data['id'] ?? $_POST['id'] ?? '';
            // Agent : vérifier propriété du PV
            if ($userRole === 'agent') {
                $pvCheck = getPVById($id);
                if ($pvCheck && $pvCheck['id_agent'] != $userId) {
                    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez supprimer que vos propres PV']);
                    exit;
                }
            }
            $result = deletePV($id, $_SESSION['utilisateur_id'] ?? 1);
            if ($result) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'PV supprimé avec succès']);
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du PV']);
                exit;
            }
            break;

        case 'detail':
            $id = $_GET['id'] ?? '';
            $pv = getPVById($id);
            if ($pv) {
                header('Content-Type: application/json');
                echo json_encode($pv);
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'PV non trouvé']);
                exit;
            }
            break;

        case 'generate':
            generateFakeData(30);
            header('Location: constat.php?success=4');
            exit;
            break;

        case 'export':
            $search = $_POST['search'] ?? '';
            $status = $_POST['status'] ?? '';
            exportToCSV($search, $status);
            break;
    }
}

// Traitement des requêtes GET pour les détails et les données AJAX
if (isset($_GET['action']) && $_GET['action'] === 'detail') {
    $id = $_GET['id'] ?? '';
    $pv = getPVById($id);
    if ($pv) {
        header('Content-Type: application/json');
        echo json_encode($pv);
        exit;
    }
}

// Variables pour la vue
$currentPage = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Filtrer par agent si le rôle est "agent"
$agentFilter = (($_SESSION['role'] ?? '') === 'agent') ? ($_SESSION['utilisateur_id'] ?? null) : null;

// Utiliser les données de la base de données via le modèle
$itemsPerPage = $_GET['itemsPerPage'] ?? 10;
$result = getAllPV($currentPage, $itemsPerPage, $search, $status, $agentFilter);

$pvData = $result['data'] ?? [];
$pagination = [
    'total' => $result['total'] ?? 0,
    'totalPages' => $result['totalPages'] ?? 0,
    'currentPage' => $result['currentPage'] ?? 1,
    'itemsPerPage' => $itemsPerPage
];

// Obtenir les statistiques depuis la base de données
$stats = getStatistics($agentFilter);
$statistics = [
    'total' => $stats['total'] ?? 0,
    'enCours' => $stats['enCours'] ?? 0,
    'traites' => $stats['traites'] ?? 0
];

// Traitement des requêtes AJAX pour charger les données
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'pvData' => $pvData,
        'pagination' => $pagination,
        'statistics' => $statistics
    ]);
    exit;
}

// Variables pour le header
$pageTitle = "Gestion des Procès-Verbaux - Constat d'Incident";
$bannerText = "Procès-Verbal: Constat d'Incident - USCOUD";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Procès-Verbaux - Constat d'Incident</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Styles COUD'MAINT -->
    <link rel="stylesheet" href="../../assets/css/common.css">
    <link rel="stylesheet" href="../../assets/css/constat.css">
</head>

<body>
    <?php
    $bannerText = "Procès-Verbal: Constat d'Incident - USCOUD";
    include __DIR__ . '/../../includes/head.php';
    ?>

    <!-- Statistiques -->
    <div class="container mt-4" id="statsContainer">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-start border-primary border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total PV</p>
                            <h4 id="statTotal"><?php echo $statistics['total']; ?></h4>
                        </div>
                        <i class="fas fa-file-alt fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-start border-warning border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">En cours</p>
                            <h4 class="text-warning" id="statEnCours"><?php echo $statistics['enCours']; ?></h4>
                        </div>
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-start border-success border-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Traités</p>
                            <h4 class="text-success" id="statTraites"><?php echo $statistics['traites']; ?></h4>
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
            <div class="col-md-8">
                <input type="text" class="form-control search-box" id="searchInput" placeholder="Rechercher par nom, prénom, carte, lieu...">
            </div>
            <div class="col-md-4">
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
                <div class="d-flex align-items-center">
                    <h5 class="me-3"><i class="fas fa-list me-2"></i>Liste des Procès-Verbaux</h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <label class="me-2">Afficher:</label>
                        <select class="form-select form-select-sm d-inline-block w-auto" id="itemsPerPage" onchange="changeItemsPerPage()">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <span class="badge bg-primary">Total: <span id="totalCount">0</span></span>
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
                            <th>N° Piéce</th>
                            <th>Nom & Prénom</th>
                            <th>Date & Heure</th>
                            <th>Lieu</th>
                            <th>Type d'Incident</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pvTableBody">
                        <!-- Les données seront chargées ici par JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4" id="paginationContainer" style="display: none;">
                <div>
                    <small class="text-muted" id="paginationInfo"></small>
                </div>
                <nav aria-label="Pagination">
                    <ul class="pagination mb-0" id="pagination">
                        <!-- La pagination sera générée dynamiquement -->
                    </ul>
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
                        <i class="fas fa-plus-circle me-2 text-success"></i>Nouveau Procès-Verbal de Constat d'Incident
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Stepper Progress -->
                    <div class="stepper-wrapper mb-4">
                        <div class="stepper-item active" data-step="1">
                            <div class="step-counter">1</div>
                            <div class="step-name">Blessés Physiques</div>
                        </div>
                        <div class="stepper-item" data-step="2">
                            <div class="step-counter">2</div>
                            <div class="step-name">Dommages Matériels</div>
                        </div>
                        <div class="stepper-item" data-step="3">
                            <div class="step-counter">3</div>
                            <div class="step-name">Assaillants</div>
                        </div>
                        <div class="stepper-item" data-step="4">
                            <div class="step-counter">4</div>
                            <div class="step-name">Auditions</div>
                        </div>
                        <div class="stepper-item" data-step="5">
                            <div class="step-counter">5</div>
                            <div class="step-name">Témoignages</div>
                        </div>
                    </div>

                    <form id="addForm">
                        <!-- STEP 1: Cas de Blessés Physiques -->
                        <div class="form-step active" id="step1">
                            <div class="section-title">
                                <i class="fas fa-user-injured me-2"></i>1. CAS DE BLESSÉS PHYSIQUES
                            </div>

                            <div id="blessesContainer">
                                <!-- Les blessés seront ajoutés dynamiquement -->
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addBlesse()">
                                <i class="fas fa-plus me-1"></i>Ajouter un blessé
                            </button>

                            <div class="mt-4">
                                <label for="addSuitesBlesses" class="form-label">Suites blessés :</label>
                                <textarea class="form-control" id="addSuitesBlesses" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- STEP 2: Cas de Dommages Matériels -->
                        <div class="form-step" id="step2">
                            <div class="section-title">
                                <i class="fas fa-tools me-2"></i>2. CAS DE DOMMAGES MATÉRIELS
                            </div>

                            <div id="dommagesContainer">
                                <!-- Les dommages seront ajoutés dynamiquement -->
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addDommage()">
                                <i class="fas fa-plus me-1"></i>Ajouter un dommage
                            </button>

                            <div class="mt-4">
                                <label for="addSuitesDommages" class="form-label">Suites dommages :</label>
                                <textarea class="form-control" id="addSuitesDommages" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- STEP 3: Les Assaillants Cités -->
                        <div class="form-step" id="step3">
                            <div class="section-title">
                                <i class="fas fa-user-secret me-2"></i>3. LES ASSAILLANTS CITÉS
                            </div>

                            <div id="assaillantsContainer">
                                <!-- Les assaillants seront ajoutés dynamiquement -->
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addAssaillant()">
                                <i class="fas fa-plus me-1"></i>Ajouter un assaillant
                            </button>

                            <div class="mt-4">
                                <label for="addSuitesAssaillants" class="form-label">Suites assaillants :</label>
                                <textarea class="form-control" id="addSuitesAssaillants" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- STEP 4: Auditions -->
                        <div class="form-step" id="step4">
                            <div class="section-title">
                                <i class="fas fa-microphone me-2"></i>4. AUDITIONS
                            </div>

                            <div id="auditionsContainer">
                                <!-- Les auditions seront ajoutées dynamiquement -->
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addAudition()">
                                <i class="fas fa-plus me-1"></i>Ajouter une audition
                            </button>
                        </div>

                        <!-- STEP 5: Témoignages et Finalisation -->
                        <div class="form-step" id="step5">
                            <div class="section-title">
                                <i class="fas fa-comments me-2"></i>5. TÉMOIGNAGES OU INFORMATIONS COMPLÉMENTAIRES
                            </div>

                            <div id="temoignagesContainer">
                                <!-- Les témoignages seront ajoutés dynamiquement -->
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary mb-4" onclick="addTemoignage()">
                                <i class="fas fa-plus me-1"></i>Ajouter un témoignage
                            </button>

                            <div class="section-title mt-4">
                                <i class="fas fa-info-circle me-2"></i>Informations Générales du PV
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="addCarteEtudiant" class="form-label">
                                        <i class="fas fa-id-card me-1"></i>N° Carte *
                                    </label>
                                    <input type="text" class="form-control" id="addCarteEtudiant" required placeholder="Ex: 123456789">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="addNom" class="form-label">
                                        <i class="fas fa-user me-1"></i>Nom *
                                    </label>
                                    <input type="text" class="form-control" id="addNom" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="addPrenoms" class="form-label">
                                        <i class="fas fa-user me-1"></i>Prénoms *
                                    </label>
                                    <input type="text" class="form-control" id="addPrenoms" required>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="addCampus" class="form-label">
                                        <i class="fas fa-building me-1"></i>Campus/Résidence *
                                    </label>
                                    <input type="text" class="form-control" id="addCampus" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="addTelephone" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Téléphone
                                    </label>
                                    <input type="tel" class="form-control" id="addTelephone" placeholder="77 XXX XX XX">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="addTypeIncident" class="form-label">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Type d'Incident *
                                    </label>
                                    <select class="form-select" id="addTypeIncident" required>
                                        <option value="">Sélectionner...</option>
                                        <option value="vol">Vol</option>
                                        <option value="agression">Agression</option>
                                        <option value="degradation">Dégradation</option>
                                        <option value="perte">Perte</option>
                                        <option value="incendie">Incendie</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="addDescriptionIncident" class="form-label">
                                        <i class="fas fa-file-alt me-1"></i>Description de l'Incident *
                                    </label>
                                    <textarea class="form-control" id="addDescriptionIncident" rows="3" required></textarea>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="addLieuIncident" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>Lieu de l'Incident *
                                    </label>
                                    <input type="text" class="form-control" id="addLieuIncident" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="addDateIncident" class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Date de l'Incident *
                                    </label>
                                    <input type="date" class="form-control" id="addDateIncident" required value="<?php echo date('Y-m-d'); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="addHeureIncident" class="form-label">
                                        <i class="fas fa-clock me-1"></i>Heure de l'Incident *
                                    </label>
                                    <input type="time" class="form-control" id="addHeureIncident" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="addStatut" class="form-label">
                                        <i class="fas fa-info-circle me-1"></i>Statut *
                                    </label>
                                    <select class="form-select" id="addStatut" required>
                                        <option value="en_cours">En cours</option>
                                        <option value="traite">Traité</option>
                                    </select>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="addObservations" class="form-label">
                                        <i class="fas fa-sticky-note me-1"></i>Observations
                                    </label>
                                    <textarea class="form-control" id="addObservations" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-outline-dark" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                        <i class="fas fa-chevron-left me-1"></i>Précédent
                    </button>
                    <button type="button" class="btn btn-dark" id="nextBtn" onclick="changeStep(1)">
                        Suivant<i class="fas fa-chevron-right ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-dark" id="submitBtn" onclick="confirmSavePV()" style="display: none;">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation pour l'ajout -->
    <div class="modal fade" id="confirmSaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Confirmation de Création
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Êtes-vous sûr de vouloir créer ce procès-verbal de constat d'incident ?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Récapitulatif :</strong>
                        <div id="confirmSummary" class="mt-2">
                            <!-- Le récapitulatif sera rempli dynamiquement -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-success" id="confirmSaveBtn">
                        <i class="fas fa-save me-1"></i>Confirmer la création
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de succès pour l'ajout -->
    <div class="modal fade" id="successSaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Création Réussie
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h5 class="text-success mb-3">PV créé avec succès !</h5>
                        <p class="mb-3">Le procès-verbal de constat d'incident a été enregistré dans le système.</p>
                        <div class="alert alert-success">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Numéro PV :</strong> <span id="pvNumber">PV-CONSTAT-20260105-001</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                        <i class="fas fa-check me-1"></i>OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation pour la modification -->
    <div class="modal fade" id="confirmUpdateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Confirmation de Modification
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Êtes-vous sûr de vouloir modifier ce procès-verbal de constat d'incident ?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Récapitulatif des modifications :</strong>
                        <div id="updateSummary" class="mt-2">
                            <!-- Le récapitulatif sera rempli dynamiquement -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-warning" id="confirmUpdateBtn">
                        <i class="fas fa-save me-1"></i>Confirmer la modification
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modification -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-edit me-2 text-warning"></i>Modifier le Procès-Verbal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="editId">

                        <!-- Navigation par onglets pour modification -->
                        <ul class="nav nav-tabs mb-4" id="editTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="edit-general-tab" data-bs-toggle="tab" data-bs-target="#edit-general" type="button" role="tab">
                                    Informations Générales
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-incident-tab" data-bs-toggle="tab" data-bs-target="#edit-incident" type="button" role="tab">
                                    Détails Incident
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-blesses-tab" data-bs-toggle="tab" data-bs-target="#edit-blesses" type="button" role="tab">
                                    Blessés
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-dommages-tab" data-bs-toggle="tab" data-bs-target="#edit-dommages" type="button" role="tab">
                                    Dommages
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-assaillants-tab" data-bs-toggle="tab" data-bs-target="#edit-assaillants" type="button" role="tab">
                                    Assaillants
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-auditions-tab" data-bs-toggle="tab" data-bs-target="#edit-auditions" type="button" role="tab">
                                    Auditions
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-temoignages-tab" data-bs-toggle="tab" data-bs-target="#edit-temoignages" type="button" role="tab">
                                    Témoignages
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="edit-suites-tab" data-bs-toggle="tab" data-bs-target="#edit-suites" type="button" role="tab">
                                    Suites & Observations
                                </button>
                            </li>
                        </ul>

                        <!-- Contenu des onglets de modification -->
                        <div class="tab-content" id="editTabsContent">
                            <!-- Onglet 1: Informations Générales -->
                            <div class="tab-pane fade show active" id="edit-general" role="tabpanel">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="editCarteEtudiant" class="form-label">N° Piéce *</label>
                                                <input type="text" class="form-control" id="editCarteEtudiant" name="editCarteEtudiant" required placeholder="Ex: 123456789">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="editNom" class="form-label">Nom *</label>
                                                <input type="text" class="form-control" id="editNom" name="editNom" required>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="editPrenoms" class="form-label">Prénoms *</label>
                                                <input type="text" class="form-control" id="editPrenoms" name="editPrenoms" required>
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="editCampus" class="form-label">Campus/Résidence *</label>
                                                <input type="text" class="form-control" id="editCampus" name="editCampus" required>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="editTelephone" class="form-label">Téléphone</label>
                                                <input type="tel" class="form-control" id="editTelephone" name="editTelephone" placeholder="77 XXX XX XX">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Onglet 2: Détails de l'Incident -->
                            <div class="tab-pane fade" id="edit-incident" role="tabpanel">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="editTypeIncident" class="form-label">Type d'Incident *</label>
                                                <select class="form-select" id="editTypeIncident" name="editTypeIncident" required>
                                                    <option value="">Sélectionner...</option>
                                                    <option value="vol">Vol</option>
                                                    <option value="agression">Agression</option>
                                                    <option value="degradation">Dégradation</option>
                                                    <option value="perte">Perte</option>
                                                    <option value="incendie">Incendie</option>
                                                    <option value="autre">Autre</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="editDateIncident" class="form-label">Date de l'Incident *</label>
                                                <input type="date" class="form-control" id="editDateIncident" name="editDateIncident" required>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="editHeureIncident" class="form-label">Heure de l'Incident</label>
                                                <input type="time" class="form-control" id="editHeureIncident" name="editHeureIncident">
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="editLieuIncident" class="form-label">Lieu de l'Incident *</label>
                                                <input type="text" class="form-control" id="editLieuIncident" name="editLieuIncident" required>
                                            </div>

                                            <div class="col-md-12 mb-3">
                                                <label for="editDescriptionIncident" class="form-label">Description de l'Incident *</label>
                                                <textarea class="form-control" id="editDescriptionIncident" name="editDescriptionIncident" rows="3" required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Onglet 3: Blessés Physiques -->
                            <div class="tab-pane fade" id="edit-blesses" role="tabpanel">
                                <div class="card">
                                    <div class="card-body">
                                        <div id="editBlessesContainer">
                                            <!-- Les blessés seront ajoutés dynamiquement -->
                                        </div>
                                        <button type="button" class="btn btn-outline-danger" onclick="addEditBlesse()">
                                            Ajouter un blessé
                                        </button>
                                        <div class="mt-4">
                                            <label for="editSuitesBlesses" class="form-label">Suites blessés :</label>
                                            <textarea class="form-control" id="editSuitesBlesses" name="editSuitesBlesses" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Onglet 4: Dommages Matériels -->
                            <div class="tab-pane fade" id="edit-dommages" role="tabpanel">
                                <div class="card">
                                    <div class="card-body">
                                        <div id="editDommagesContainer">
                                            <!-- Les dommages seront ajoutés dynamiquement -->
                                        </div>
                                        <button type="button" class="btn btn-outline-warning" onclick="addEditDommage()">
                                            Ajouter un dommage
                                        </button>
                                        <div class="mt-4">
                                            <label for="editSuitesDommages" class="form-label">Suites dommages :</label>
                                            <textarea class="form-control" id="editSuitesDommages" name="editSuitesDommages" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Onglet 5: Assaillants Cités -->
                            <div class="tab-pane fade" id="edit-assaillants" role="tabpanel">
                                <div class="card">
                                    <div class="card-body">
                                        <div id="editAssaillantsContainer">
                                            <!-- Les assaillants seront ajoutés dynamiquement -->
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary" onclick="addEditAssaillant()">
                                            Ajouter un assaillant
                                        </button>
                                        <div class="mt-4">
                                            <label for="editSuitesAssaillants" class="form-label">Suites assaillants :</label>
                                            <textarea class="form-control" id="editSuitesAssaillants" name="editSuitesAssaillants" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Onglet 6: Auditions -->
                            <div class="tab-pane fade" id="edit-auditions" role="tabpanel">
                                <div class="card">
                                    <div class="card-body">
                                        <div id="editAuditionsContainer">
                                            <!-- Les auditions seront ajoutées dynamiquement -->
                                        </div>
                                        <button type="button" class="btn btn-outline-info" onclick="addEditAudition()">
                                            Ajouter une audition
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Onglet 7: Témoignages -->
                            <div class="tab-pane fade" id="edit-temoignages" role="tabpanel">
                                <div class="card">
                                    <div class="card-body">
                                        <div id="editTemoignagesContainer">
                                            <!-- Les témoignages seront ajoutés dynamiquement -->
                                        </div>
                                        <button type="button" class="btn btn-outline-primary" onclick="addEditTemoignage()">
                                            Ajouter un témoignage
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Onglet 8: Suites et Observations -->
                            <div class="tab-pane fade" id="edit-suites" role="tabpanel">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="editObservations" class="form-label">Observations générales :</label>
                                                <textarea class="form-control" id="editObservations" name="editObservations" rows="3"></textarea>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="editStatut" class="form-label">Statut du PV :</label>
                                                <select class="form-select" id="editStatut" name="editStatut">
                                                    <option value="en_cours">En cours</option>
                                                    <option value="traite">Traité</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="editDate" class="form-label">Date de création du PV</label>
                                                <input type="date" class="form-control" id="editDate" name="editDate">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Annuler
                    </button>
                    <button type="button" class="btn btn-primary" onclick="showConfirmEditModal()">
                        Enregistrer les modifications
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de succès de suppression -->
    <div class="modal fade" id="successDeleteModal" tabindex="-1" aria-labelledby="successDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successDeleteModalLabel">
                        Suppression réussie
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-trash-alt text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <p>Le procès-verbal a été supprimé avec succès !</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de modification -->
    <div class="modal fade" id="confirmEditModal" tabindex="-1" aria-labelledby="confirmEditModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title" id="confirmEditModalLabel">
                        <i class="fas fa-edit me-2 text-warning"></i>Confirmer la modification
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir modifier ce procès-verbal ?</p>
                    <p class="text-muted">Cette action mettra à jour toutes les informations du PV dans la base de données.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Annuler
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmEditBtn" onclick="confirmUpdatePV()">
                        Oui, modifier
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de succès -->
    <div class="modal fade" id="successEditModal" tabindex="-1" aria-labelledby="successEditModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title" id="successEditModalLabel">
                        <i class="fas fa-check-circle me-2 text-success"></i>Modification réussie
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                    </div>
                    <p>Le procès-verbal a été modifié avec succès !</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détails -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title" id="detailModalLabel">
                        <i class="fas fa-eye me-2"></i>Détails du Procès-Verbal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Navigation par onglets -->
                    <ul class="nav nav-tabs mb-4" id="detailTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                Informations Générales
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="incident-tab" data-bs-toggle="tab" data-bs-target="#incident" type="button" role="tab">
                                Incident
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="blesses-tab" data-bs-toggle="tab" data-bs-target="#blesses" type="button" role="tab">
                                Blessés <span class="badge bg-danger ms-1" id="blessesTabCount">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="dommages-tab" data-bs-toggle="tab" data-bs-target="#dommages" type="button" role="tab">
                                Dommages <span class="badge bg-warning text-dark ms-1" id="dommagesTabCount">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assaillants-tab" data-bs-toggle="tab" data-bs-target="#assaillants" type="button" role="tab">
                                Assaillants <span class="badge bg-secondary ms-1" id="assaillantsTabCount">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="auditions-tab" data-bs-toggle="tab" data-bs-target="#auditions" type="button" role="tab">
                                Auditions <span class="badge bg-info text-dark ms-1" id="auditionsTabCount">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="temoignages-tab" data-bs-toggle="tab" data-bs-target="#temoignages" type="button" role="tab">
                                Témoignages <span class="badge bg-primary ms-1" id="temoignagesTabCount">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="suites-tab" data-bs-toggle="tab" data-bs-target="#suites" type="button" role="tab">
                                Suites & Observations
                            </button>
                        </li>
                    </ul>

                    <!-- Contenu des onglets -->
                    <div class="tab-content" id="detailTabsContent">
                        <!-- Onglet 1: Informations Générales -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted">N° Piéce</label>
                                            <p class="form-control-plaintext fw-bold" id="detailCarteEtudiant">-</p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted">Nom & Prénom</label>
                                            <p class="form-control-plaintext fw-bold" id="detailNomPrenom">-</p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted">Campus/Résidence</label>
                                            <p class="form-control-plaintext" id="detailCampus">-</p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted">Téléphone</label>
                                            <p class="form-control-plaintext" id="detailTelephone">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet 2: Détails de l'Incident -->
                        <div class="tab-pane fade" id="incident" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted">Type d'Incident</label>
                                            <p class="form-control-plaintext fw-bold" id="detailTypeIncident">-</p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted">Date & Heure</label>
                                            <p class="form-control-plaintext fw-bold" id="detailDateHeure">-</p>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label text-muted">Lieu</label>
                                            <p class="form-control-plaintext" id="detailLieuIncident">-</p>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label text-muted">Description</label>
                                            <p class="form-control-plaintext" id="detailDescriptionIncident">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet 3: Blessés Physiques -->
                        <div class="tab-pane fade" id="blesses" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div id="detailBlesses">
                                        <p class="text-muted">Aucun blessé enregistré</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet 4: Dommages Matériels -->
                        <div class="tab-pane fade" id="dommages" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div id="detailDommages">
                                        <p class="text-muted">Aucun dommage matériel enregistré</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet 5: Assaillants Cités -->
                        <div class="tab-pane fade" id="assaillants" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div id="detailAssaillants">
                                        <p class="text-muted">Aucun assaillant cité</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet 6: Auditions -->
                        <div class="tab-pane fade" id="auditions" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div id="detailAuditions">
                                        <p class="text-muted">Aucune audition enregistrée</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet 7: Témoignages -->
                        <div class="tab-pane fade" id="temoignages" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div id="detailTemoignages">
                                        <p class="text-muted">Aucun témoignage enregistré</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Onglet 8: Suites et Observations -->
                        <div class="tab-pane fade" id="suites" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label text-muted">Suites blessés</label>
                                            <p class="form-control-plaintext" id="detailSuitesBlesses">-</p>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label text-muted">Suites dommages</label>
                                            <p class="form-control-plaintext" id="detailSuitesDommages">-</p>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label text-muted">Suites assaillants</label>
                                            <p class="form-control-plaintext" id="detailSuitesAssaillants">-</p>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label text-muted">Observations générales</label>
                                            <p class="form-control-plaintext" id="detailObservations">-</p>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label text-muted">Statut du PV</label>
                                            <div id="detailStatut">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Fermer
                    </button>
                    <button type="button" class="btn btn-primary" onclick="printPV()">
                        <i class="fas fa-print me-1"></i>Imprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation pour suppression -->
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
                            <strong>Nom :</strong> <span id="deletePvName">-</span><br>
                            <strong>Date :</strong> <span id="deletePvDate">-</span>
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

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/constat-steps.js?v=<?php echo filemtime(__DIR__.'/../../assets/js/constat-steps.js'); ?>"></script>
    <script>var USER_ROLE = '<?php echo $_SESSION["role"] ?? ""; ?>'; var USER_ID = <?php echo $_SESSION["utilisateur_id"] ?? 0; ?>;</script>
    <script src="../../assets/js/constat-database.js?v=<?php echo filemtime(__DIR__.'/../../assets/js/constat-database.js'); ?>"></script>

    <!-- Passer les données PHP au JavaScript -->
    <script>
        // Les données sont maintenant chargées via AJAX
        window.addEventListener('load', function() {
            // Initialiser les variables globales
            window.currentPage = <?php echo $pagination['currentPage']; ?>;
            window.itemsPerPage = <?php echo $pagination['itemsPerPage']; ?>;

            // Configurer l'événement pour le bouton de confirmation
            const confirmSaveBtn = document.getElementById('confirmSaveBtn');
            if (confirmSaveBtn) {
                confirmSaveBtn.addEventListener('click', function() {
                    savePV();
                });
            }
        });
    </script>

<?php if (!empty($_GET['view']) && is_numeric($_GET['view'])): ?>
<script>
window.addEventListener('load', function() {
    setTimeout(function() { viewPV(<?php echo (int)$_GET['view']; ?>); }, 700);
});
</script>
<?php endif; ?>
</body>

</html>
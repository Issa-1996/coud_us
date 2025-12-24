<?php
// Inclure le modèle
require_once __DIR__ . '/../../models/ConstatModel.php';

// Traitement des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = [
                'dateIncident' => $_POST['dateIncident'] ?? '',
                'heureIncident' => $_POST['heureIncident'] ?? '',
                'lieu' => $_POST['lieu'] ?? '',
                'blesses' => json_decode($_POST['blesses'] ?? '[]', true),
                'dommages' => json_decode($_POST['dommages'] ?? '[]', true),
                'assaillants' => json_decode($_POST['assaillants'] ?? '[]', true),
                'auditions' => json_decode($_POST['auditions'] ?? '[]', true),
                'temoignages' => json_decode($_POST['temoignages'] ?? '[]', true),
                'statut' => $_POST['statut'] ?? 'en_cours',
                'agentId' => $_POST['agentId'] ?? ''
            ];
            
            $errors = validatePV($data);
            if (empty($errors)) {
                createPV($data);
                header('Location: constat.php?success=1');
                exit;
            } else {
                $errors = $errors;
            }
            break;
            
        case 'update':
            $id = $_POST['id'] ?? '';
            $data = [
                'dateIncident' => $_POST['dateIncident'] ?? '',
                'heureIncident' => $_POST['heureIncident'] ?? '',
                'lieu' => $_POST['lieu'] ?? '',
                'blesses' => json_decode($_POST['blesses'] ?? '[]', true),
                'dommages' => json_decode($_POST['dommages'] ?? '[]', true),
                'assaillants' => json_decode($_POST['assaillants'] ?? '[]', true),
                'auditions' => json_decode($_POST['auditions'] ?? '[]', true),
                'temoignages' => json_decode($_POST['temoignages'] ?? '[]', true),
                'statut' => $_POST['statut'] ?? 'en_cours',
                'agentId' => $_POST['agentId'] ?? ''
            ];
            
            $errors = validatePV($data);
            if (empty($errors)) {
                updatePV($id, $data);
                header('Location: constat.php?success=2');
                exit;
            } else {
                $errors = $errors;
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? '';
            deletePV($id);
            header('Location: constat.php?success=3');
            exit;
            break;
            
        case 'generate':
            generateFakeData(30);
            header('Location: constat.php?success=4');
            exit;
            break;
    }
}

// Traitement des requêtes GET pour les détails
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
$result = getAllPV($currentPage, 10, $search, $status);
$pvData = $result['data'];
$pagination = [
    'total' => $result['total'],
    'totalPages' => $result['totalPages'],
    'currentPage' => $result['currentPage'],
    'itemsPerPage' => $result['itemsPerPage']
];
$statistics = getStatistics();

// Variables pour le header
$pageTitle = "Procès-Verbal de Constat d'Incident - Campus Social UCAD";
$bannerText = "Procès-Verbal: Constat d'Incident - USCOUD";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procès-Verbal de Constat d'Incident - Campus Social UCAD</title>
    <link rel="stylesheet" href="../../assets/css/constat.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


</head>

<body>
    <?php
    $bannerText = "Procès-Verbal: Constat d'Incident - USCOUD";
    include __DIR__ . '/../../includes/head.php';
    ?>

    <!-- Header -->
    <div class="header-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-file-medical me-2"></i>Procès-Verbal de Constat d'Incident</h2>
                    <p class="mb-0">Campus Social de l'UCAD</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus-square me-2"></i>Nouveau PV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Recherche et Filtres -->
        <div class="row mb-4">
            <div class="col-md-8">
                <input type="text" class="form-control search-box" id="searchInput" placeholder="Rechercher par nom, lieu, carte d'Ã©tudiant...">
            </div>
            <div class="col-md-4">
                <select class="form-select search-box" id="filterStatus">
                    <option value="">Tous les statuts</option>
                    <option value="en_cours">En cours</option>
                    <option value="traite">TraitÃ©</option>
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
                    <span class="badge bg-dark">Total: <span id="totalCount">0</span></span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="pvTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date & Heure</th>
                            <th>Lieu</th>
                            <th>Nature Incident</th>
                            <th>BlessÃ©s</th>
                            <th>Dommages</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pvTableBody">
                        <!-- Les donnÃ©es seront chargÃ©es ici -->
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
                            <div class="step-name">TÃ©moignages</div>
                        </div>
                    </div>

                    <form id="addForm">
                        <!-- STEP 1: Cas de BlessÃ©s Physiques -->
                        <div class="form-step active" id="step1">
                            <div class="section-title">
                                <i class="fas fa-user-injured me-2"></i>1. CAS DE BLESSÃ‰S PHYSIQUES
                            </div>

                            <div id="blessesContainer">
                                <!-- Les blessÃ©s seront ajoutÃ©s dynamiquement -->
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addBlesse()">
                                <i class="fas fa-plus me-1"></i>Ajouter un blessÃ©
                            </button>

                            <div class="mt-4">
                                <label for="addSuitesBlesses" class="form-label">Suites blessÃ©s :</label>
                                <textarea class="form-control" id="addSuitesBlesses" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- STEP 2: Cas de Dommages MatÃ©riels -->
                        <div class="form-step" id="step2">
                            <div class="section-title">
                                <i class="fas fa-tools me-2"></i>2. CAS DE DOMMAGES MATÃ‰RIELS
                            </div>

                            <div id="dommagesContainer">
                                <!-- Les dommages seront ajoutÃ©s dynamiquement -->
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addDommage()">
                                <i class="fas fa-plus me-1"></i>Ajouter un dommage
                            </button>

                            <div class="mt-4">
                                <label for="addSuitesDommages" class="form-label">Suites dommages :</label>
                                <textarea class="form-control" id="addSuitesDommages" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- STEP 3: Les Assaillants CitÃ©s -->
                        <div class="form-step" id="step3">
                            <div class="section-title">
                                <i class="fas fa-user-secret me-2"></i>3. LES ASSAILLANTS CITÃ‰S
                            </div>

                            <div id="assaillantsContainer">
                                <!-- Les assaillants seront ajoutÃ©s dynamiquement -->
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
                                <!-- Les auditions seront ajoutÃ©es dynamiquement -->
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addAudition()">
                                <i class="fas fa-plus me-1"></i>Ajouter une audition
                            </button>
                        </div>

                        <!-- STEP 5: TÃ©moignages et Finalisation -->
                        <div class="form-step" id="step5">
                            <div class="section-title">
                                <i class="fas fa-comments me-2"></i>5. TÃ‰MOIGNAGES OU INFORMATIONS COMPLÃ‰MENTAIRES
                            </div>

                            <div id="temoignagesContainer">
                                <!-- Les tÃ©moignages seront ajoutÃ©s dynamiquement -->
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary mb-4" onclick="addTemoignage()">
                                <i class="fas fa-plus me-1"></i>Ajouter un tÃ©moignage
                            </button>

                            <div class="section-title mt-4">
                                <i class="fas fa-info-circle me-2"></i>Informations GÃ©nÃ©rales du PV
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="addDateIncident" class="form-label">Date de l'incident *</label>
                                    <input type="date" class="form-control" id="addDateIncident" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="addHeureIncident" class="form-label">Heure de l'incident *</label>
                                    <input type="time" class="form-control" id="addHeureIncident" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="addLieuIncident" class="form-label">Lieu de l'incident *</label>
                                    <input type="text" class="form-control" id="addLieuIncident" placeholder="Ex: Campus Social ESP" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="addNatureIncident" class="form-label">Nature de l'incident *</label>
                                    <input type="text" class="form-control" id="addNatureIncident" placeholder="Ex: Bagarre, Vol, Accident..." required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="addChargeRenseignements" class="form-label">Le chargÃ© des renseignements</label>
                                    <input type="text" class="form-control" id="addChargeRenseignements">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="addCoordonnateurSecurite" class="form-label">Le coordonnateur de l'unitÃ© de sÃ©curitÃ©</label>
                                    <input type="text" class="form-control" id="addCoordonnateurSecurite">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="addStatut" class="form-label">Statut *</label>
                                    <select class="form-select" id="addStatut" required>
                                        <option value="en_cours">En cours</option>
                                        <option value="traite">TraitÃ©</option>
                                    </select>
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
                        <i class="fas fa-chevron-left me-1"></i>PrÃ©cÃ©dent
                    </button>
                    <button type="button" class="btn btn-dark" id="nextBtn" onclick="changeStep(1)">
                        Suivant<i class="fas fa-chevron-right ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-dark" id="submitBtn" onclick="savePV()" style="display: none;">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détails (sera similaire à add mais en lecture seule) -->
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
                    <button type="button" class="btn btn-dark" onclick="printPV()">
                        <i class="fas fa-print me-1"></i>Imprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/constat.js"></script>

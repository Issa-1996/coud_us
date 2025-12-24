<?php
// Inclure le modèle
require_once __DIR__ . '/../../models/DenonciationModel.php';

// Traitement des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = [
                'dateDenonciation' => $_POST['dateDenonciation'] ?? '',
                'denonciateur' => [
                    'nom' => $_POST['denonciateur_nom'] ?? '',
                    'prenoms' => $_POST['denonciateur_prenoms'] ?? '',
                    'telephone' => $_POST['denonciateur_telephone'] ?? '',
                    'email' => $_POST['denonciateur_email'] ?? '',
                    'adresse' => $_POST['denonciateur_adresse'] ?? ''
                ],
                'denonce' => [
                    'nom' => $_POST['denonce_nom'] ?? '',
                    'prenoms' => $_POST['denonce_prenoms'] ?? '',
                    'telephone' => $_POST['denonce_telephone'] ?? '',
                    'email' => $_POST['denonce_email'] ?? '',
                    'adresse' => $_POST['denonce_adresse'] ?? ''
                ],
                'motif' => $_POST['motif'] ?? '',
                'description' => $_POST['description'] ?? '',
                'preuves' => json_decode($_POST['preuves'] ?? '[]', true),
                'statut' => $_POST['statut'] ?? 'en_attente',
                'agentId' => $_POST['agentId'] ?? ''
            ];
            
            $errors = validatePV($data);
            if (empty($errors)) {
                createPV($data);
                header('Location: denonciation.php?success=1');
                exit;
            } else {
                $errors = $errors;
            }
            break;
            
        case 'update':
            $id = $_POST['id'] ?? '';
            $data = [
                'dateDenonciation' => $_POST['dateDenonciation'] ?? '',
                'denonciateur' => [
                    'nom' => $_POST['denonciateur_nom'] ?? '',
                    'prenoms' => $_POST['denonciateur_prenoms'] ?? '',
                    'telephone' => $_POST['denonciateur_telephone'] ?? '',
                    'email' => $_POST['denonciateur_email'] ?? '',
                    'adresse' => $_POST['denonciateur_adresse'] ?? ''
                ],
                'denonce' => [
                    'nom' => $_POST['denonce_nom'] ?? '',
                    'prenoms' => $_POST['denonce_prenoms'] ?? '',
                    'telephone' => $_POST['denonce_telephone'] ?? '',
                    'email' => $_POST['denonce_email'] ?? '',
                    'adresse' => $_POST['denonce_adresse'] ?? ''
                ],
                'motif' => $_POST['motif'] ?? '',
                'description' => $_POST['description'] ?? '',
                'preuves' => json_decode($_POST['preuves'] ?? '[]', true),
                'statut' => $_POST['statut'] ?? 'en_attente',
                'agentId' => $_POST['agentId'] ?? ''
            ];
            
            $errors = validatePV($data);
            if (empty($errors)) {
                updatePV($id, $data);
                header('Location: denonciation.php?success=2');
                exit;
            } else {
                $errors = $errors;
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? '';
            deletePV($id);
            header('Location: denonciation.php?success=3');
            exit;
            break;
            
        case 'generate':
            generateFakeData(30);
            header('Location: denonciation.php?success=4');
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
$pageTitle = "Procès-Verbal de Dénonciation - Campus Social UCAD";
$bannerText = "Procès-Verbal: Dénonciation - USCOUD";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Procès-Verbaux de Dénonciation</title>
    <link rel="stylesheet" href="../../assets/css/denonciation.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body>
    <?php
    $bannerText = "Procès-Verbal: Dénonciation - USCOUD";
    include __DIR__ . '/../../includes/head.php';
    ?>

    <!-- Header -->
    <div class="header-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-exclamation-triangle me-2"></i>Procès-Verbal de Dénonciation pour Personne</h2>
                    <p class="mb-0">Victime de Violence, Harcèlement, Diffamation et Vol</p>
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
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="pvTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>SoussignÃ© Monsieur</th>
                            <th>Agents en Action</th>
                            <th>Lieu</th>
                            <th>Type</th>
                            <th>Victime</th>
                            <th>TÃ©lÃ©phone</th>
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
                            <div class="step-name">Type & DÃ©tails</div>
                        </div>
                        <div class="stepper-item" data-step="3">
                            <div class="step-counter">3</div>
                            <div class="step-name">Victime & Auteur</div>
                        </div>
                        <div class="stepper-item" data-step="4">
                            <div class="step-counter">4</div>
                            <div class="step-name">Finalisation</div>
                        </div>
                    </div>

                    <form id="addForm">
                        <!-- STEP 1: Informations de base -->
                        <div class="form-step active" id="step1">
                            <div class="section-title">
                                <i class="fas fa-user me-2"></i>Informations de Base
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="addSoussigne" class="form-label">SoussignÃ© - Nom *</label>
                                    <input type="text" class="form-control" id="addSoussigne" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="addSoussignePrenom" class="form-label">SoussignÃ© - PrÃ©nom *</label>
                                    <input type="text" class="form-control" id="addSoussignePrenom" required>
                                </div>
                            </div>

                            <div class="section-title mt-4">
                                <i class="fas fa-user-shield me-2"></i>Agents en Action au Poste
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="addAgents" class="form-label">Agents (sÃ©parer par des virgules)</label>
                                    <textarea class="form-control" id="addAgents" rows="2" placeholder="Agent 1, Agent 2, Agent 3..."></textarea>
                                </div>
                            </div>

                            <div class="section-title mt-4">
                                <i class="fas fa-map-marker-alt me-2"></i>Lieu et Consolidations
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="addCampusSocial" class="form-label">Campus Social ou plus Mr Mme Mlle</label>
                                    <input type="text" class="form-control" id="addCampusSocial">
                                </div>
                                <div class="col-md-6">
                                    <label for="addLieuConsolidation" class="form-label">Consolidations de Mr/Mme</label>
                                    <input type="text" class="form-control" id="addLieuConsolidation">
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: Type et DÃ©tails -->
                        <div class="form-step" id="step2">
                            <div class="section-title">
                                <i class="fas fa-exclamation-circle me-2"></i>Type de DÃ©nonciation *
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label">Concernant :</label>
                                    <div class="checkbox-group">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="addViolence" value="violence">
                                            <label class="form-check-label" for="addViolence">
                                                <i class="fas fa-hand-fist text-danger me-1"></i>Violence
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="addHarcelement" value="harcelement">
                                            <label class="form-check-label" for="addHarcelement">
                                                <i class="fas fa-user-times text-warning me-1"></i>HarcÃ¨lement
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="addDiffamation" value="diffamation">
                                            <label class="form-check-label" for="addDiffamation">
                                                <i class="fas fa-comment-slash text-info me-1"></i>Diffamation
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="addVol" value="vol">
                                            <label class="form-check-label" for="addVol">
                                                <i class="fas fa-hands text-secondary me-1"></i>Vol
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="section-title">
                                <i class="fas fa-clipboard-list me-2"></i>DÃ©tails de la DÃ©nonciation
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label for="addMenaces" class="form-label">1. Menaces</label>
                                    <textarea class="form-control" id="addMenaces" rows="2"></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="addHarcelement2" class="form-label">2. HarcÃ¨lement</label>
                                    <textarea class="form-control" id="addHarcelement2" rows="2"></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="addDiffamation2" class="form-label">3. Diffamation</label>
                                    <textarea class="form-control" id="addDiffamation2" rows="2"></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="addVol2" class="form-label">4. Vol</label>
                                    <textarea class="form-control" id="addVol2" rows="2"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label for="addAutres" class="form-label">5. Autres</label>
                                    <textarea class="form-control" id="addAutres" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3: Victime et Auteur -->
                        <div class="form-step" id="step3">
                            <div class="section-title">
                                <i class="fas fa-user-injured me-2"></i>La Victime *
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label for="addVictimeNom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="addVictimeNom" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="addVictimePrenom" class="form-label">PrÃ©nom *</label>
                                    <input type="text" class="form-control" id="addVictimePrenom" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="addVictimeTel" class="form-label">TÃ©lÃ©phone (NÂ° 7...)</label>
                                    <input type="tel" class="form-control" id="addVictimeTel" placeholder="7X XXX XX XX">
                                </div>
                            </div>

                            <div class="section-title">
                                <i class="fas fa-user-secret me-2"></i>L'Auteur(e) des Faits
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="addAuteurNom" class="form-label">Nom</label>
                                    <input type="text" class="form-control" id="addAuteurNom">
                                </div>
                                <div class="col-md-6">
                                    <label for="addAuteurPrenom" class="form-label">PrÃ©nom</label>
                                    <input type="text" class="form-control" id="addAuteurPrenom">
                                </div>
                                <div class="col-md-12 mt-2">
                                    <label for="addAuteurDetails" class="form-label">DÃ©tails supplÃ©mentaires</label>
                                    <textarea class="form-control" id="addAuteurDetails" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 4: Finalisation -->
                        <div class="form-step" id="step4">
                            <div class="section-title">
                                <i class="fas fa-users me-2"></i>TÃ©moignages (facultatifs)
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <textarea class="form-control" id="addTemoignages" rows="3" placeholder="TÃ©moignages des personnes prÃ©sentes..."></textarea>
                                </div>
                            </div>

                            <div class="section-title">
                                <i class="fas fa-gavel me-2"></i>Les ResponsabilitÃ©s
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label for="addResponsabilites" class="form-label">Droit ou pas de vie victime en date du</label>
                                    <textarea class="form-control" id="addResponsabilites" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="section-title">
                                <i class="fas fa-calendar-check me-2"></i>Informations Finales
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="addDatePV" class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Date du PV *
                                    </label>
                                    <input type="date" class="form-control" id="addDatePV" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="addChargeEnquete" class="form-label">Le chargé d'enquêtes et suivis</label>
                                    <input type="text" class="form-control" id="addChargeEnquete">
                                </div>
                                <div class="col-md-4">
                                    <label for="addStatut" class="form-label">Statut *</label>
                                    <select class="form-select" id="addStatut" required>
                                        <option value="en_cours">En cours</option>
                                        <option value="traite">Traité</option>
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
                    <button type="button" class="btn btn-outline-danger" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                        <i class="fas fa-chevron-left me-1"></i>PrÃ©cÃ©dent
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
                        <i class="fas fa-edit me-2"></i>Modifier le Procès-Verbal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="editId">
                        <!-- Même structure que addForm mais avec préfixe "edit" -->
                        <div class="section-title">
                            <i class="fas fa-user me-2"></i>1. Soussigné Monsieur
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editSoussigne" class="form-label">Nom du soussigné</label>
                                <input type="text" class="form-control" id="editSoussigne" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editSoussignePrenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="editSoussignePrenom" required>
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-user-shield me-2"></i>2. Agents en Action au Poste
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="editAgents" class="form-label">Agents</label>
                                <textarea class="form-control" id="editAgents" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-map-marker-alt me-2"></i>3. Lieu et Consolidations
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editCampusSocial" class="form-label">Campus Social</label>
                                <input type="text" class="form-control" id="editCampusSocial">
                            </div>
                            <div class="col-md-6">
                                <label for="editLieuConsolidation" class="form-label">Consolidations</label>
                                <input type="text" class="form-control" id="editLieuConsolidation">
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-exclamation-circle me-2"></i>4. Type de DÃ©nonciation
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="checkbox-group">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="editViolence" value="violence">
                                        <label class="form-check-label" for="editViolence">Violence</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="editHarcelement" value="harcelement">
                                        <label class="form-check-label" for="editHarcelement">HarcÃ¨lement</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="editDiffamation" value="diffamation">
                                        <label class="form-check-label" for="editDiffamation">Diffamation</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="editVol" value="vol">
                                        <label class="form-check-label" for="editVol">Vol</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-clipboard-list me-2"></i>5. DÃ©tails
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editMenaces" class="form-label">Menaces</label>
                                <textarea class="form-control" id="editMenaces" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="editHarcelement2" class="form-label">HarcÃ¨lement</label>
                                <textarea class="form-control" id="editHarcelement2" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="editDiffamation2" class="form-label">Diffamation</label>
                                <textarea class="form-control" id="editDiffamation2" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="editVol2" class="form-label">Vol</label>
                                <textarea class="form-control" id="editVol2" rows="2"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label for="editAutres" class="form-label">Autres</label>
                                <textarea class="form-control" id="editAutres" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-user-injured me-2"></i>La Victime
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="editVictimeNom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="editVictimeNom">
                            </div>
                            <div class="col-md-4">
                                <label for="editVictimePrenom" class="form-label">PrÃ©nom</label>
                                <input type="text" class="form-control" id="editVictimePrenom">
                            </div>
                            <div class="col-md-4">
                                <label for="editVictimeTel" class="form-label">TÃ©lÃ©phone</label>
                                <input type="tel" class="form-control" id="editVictimeTel">
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-user-secret me-2"></i>L'Auteur des Faits
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editAuteurNom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="editAuteurNom">
                            </div>
                            <div class="col-md-6">
                                <label for="editAuteurPrenom" class="form-label">PrÃ©nom</label>
                                <input type="text" class="form-control" id="editAuteurPrenom">
                            </div>
                            <div class="col-md-12">
                                <label for="editAuteurDetails" class="form-label">DÃ©tails</label>
                                <textarea class="form-control" id="editAuteurDetails" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-users me-2"></i>TÃ©moignages
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <textarea class="form-control" id="editTemoignages" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="section-title">
                            <i class="fas fa-gavel me-2"></i>ResponsabilitÃ©s
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <textarea class="form-control" id="editResponsabilites" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="editDatePV" class="form-label">Date du PV</label>
                                <input type="date" class="form-control" id="editDatePV">
                            </div>
                            <div class="col-md-4">
                                <label for="editChargeEnquete" class="form-label">Chargé d'enquêtes</label>
                                <input type="text" class="form-control" id="editChargeEnquete">
                            </div>
                            <div class="col-md-4">
                                <label for="editStatut" class="form-label">Statut</label>
                                <select class="form-select" id="editStatut">
                                    <option value="en_cours">En cours</option>
                                    <option value="traite">Traité</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-danger" onclick="updatePV()">
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

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/denonciation.js"></script>

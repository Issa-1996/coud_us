<?php
// Inclure le modèle
require_once __DIR__ . '/../../models/FauxModel.php';

// Traitement des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = [
                'carteEtudiant' => $_POST['carteEtudiant'] ?? '',
                'nom' => $_POST['nom'] ?? '',
                'prenoms' => $_POST['prenoms'] ?? '',
                'campus' => $_POST['campus'] ?? '',
                'telephone7' => $_POST['telephone7'] ?? '',
                'telephoneResistant' => $_POST['telephoneResistant'] ?? '',
                'observations' => $_POST['observations'] ?? '',
                'statut' => $_POST['statut'] ?? 'en_cours',
                'date' => $_POST['date'] ?? date('Y-m-d')
            ];
            
            $errors = validatePV($data);
            if (empty($errors)) {
                createPV($data);
                header('Location: faux.php?success=1');
                exit;
            } else {
                $errors = $errors;
            }
            break;
            
        case 'update':
            $id = $_POST['id'] ?? '';
            $data = [
                'carteEtudiant' => $_POST['carteEtudiant'] ?? '',
                'nom' => $_POST['nom'] ?? '',
                'prenoms' => $_POST['prenoms'] ?? '',
                'campus' => $_POST['campus'] ?? '',
                'telephone7' => $_POST['telephone7'] ?? '',
                'telephoneResistant' => $_POST['telephoneResistant'] ?? '',
                'observations' => $_POST['observations'] ?? '',
                'statut' => $_POST['statut'] ?? 'en_cours',
                'date' => $_POST['date'] ?? date('Y-m-d')
            ];
            
            $errors = validatePV($data);
            if (empty($errors)) {
                updatePV($id, $data);
                header('Location: faux.php?success=2');
                exit;
            } else {
                $errors = $errors;
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? '';
            deletePV($id);
            header('Location: faux.php?success=3');
            exit;
            break;
            
        case 'generate':
            generateFakeData(30);
            header('Location: faux.php?success=4');
            exit;
            break;
            
        case 'export':
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            exportToCSV($search, $status);
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

// Utiliser les données du modèle directement sans fichier
$fakeData = generateFakeData(15);

$total = count($fakeData);
$totalPages = ceil($total / 10);
$offset = ($currentPage - 1) * 10;
$pvData = array_slice($fakeData, $offset, 10);

$pagination = [
    'total' => $total,
    'totalPages' => $totalPages,
    'currentPage' => $currentPage,
    'itemsPerPage' => 10
];

$statistics = [
    'total' => $total,
    'enCours' => count(array_filter($fakeData, function($pv) { return $pv['statut'] === 'en_cours'; })),
    'traites' => count(array_filter($fakeData, function($pv) { return $pv['statut'] === 'traite'; }))
];

// Variables pour le header
$pageTitle = "Gestion des Procès-Verbaux - Faux et Usage de Faux";
$bannerText = "Procès-Verbal: Faux et Usage de Faux - USCOUD";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Procès-Verbaux - Faux et Usage de Faux</title>
    <link rel="stylesheet" href="../../assets/css/faux.css">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php
    $bannerText = "Procès-Verbal: Faux et Usage de Faux - USCOUD";
    include __DIR__ . '/../../includes/head.php';
    ?>

    <!-- Header -->
    <div class="header-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-balance-scale me-2"></i>Procès-Verbal d'Appréhension pour Faux et Usage de Faux</h2>
                    <p class="mb-0">Système de gestion des procès-verbaux</p>
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
                <input type="text" class="form-control search-box" id="searchInput" placeholder="Rechercher par nom, prénom, carte, téléphone...">
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
                <h5><i class="fas fa-list me-2"></i>Liste des Procès-Verbaux</h5>
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
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="pvTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>N° Carte d'Étudiant</th>
                            <th>Nom & Prénom</th>
                            <th>Campus/Résidence</th>
                            <th>Téléphone (N° 7...)</th>
                            <th>Téléphone (résistante)</th>
                            <th>Identité de Faux</th>
                            <th>Type Document</th>
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

    <!-- Modal Ajout PV -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="fas fa-plus-circle me-2 text-success"></i>Nouveau Procès-Verbal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addForm">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="addCarteEtudiant" class="form-label">
                                    <i class="fas fa-id-card me-1"></i>N° Carte d'Étudiant (N°...)
                                </label>
                                <input type="text" class="form-control" id="addCarteEtudiant" required placeholder="Ex: 123456789">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="addNom" class="form-label">
                                    <i class="fas fa-user me-1"></i>Nom
                                </label>
                                <input type="text" class="form-control" id="addNom" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="addPrenom" class="form-label">
                                    <i class="fas fa-user me-1"></i>Prénom
                                </label>
                                <input type="text" class="form-control" id="addPrenom" required>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="addCampus" class="form-label">
                                    <i class="fas fa-building me-1"></i>Campus Social ou plus Mr Mme Mlle
                                </label>
                                <input type="text" class="form-control" id="addCampus" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="addTelephone7" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Téléphone (N° 7...)
                                </label>
                                <input type="tel" class="form-control" id="addTelephone7" placeholder="7X XXX XX XX">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="addTelephoneResistant" class="form-label">
                                    <i class="fas fa-phone-alt me-1"></i>Téléphone (résistante)
                                </label>
                                <input type="tel" class="form-control" id="addTelephoneResistant">
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="addIdentiteFaux" class="form-label">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Identité de Faux et Usage de Faux
                                </label>
                                <select class="form-select" id="addIdentiteFaux" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="M. Mme">M. Mme...</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="addTypeDocument" class="form-label">
                                    <i class="fas fa-file-alt me-1"></i>La carte/lecture pièce
                                </label>
                                <select class="form-select" id="addTypeDocument" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="carte_etudiant">Carte d'Étudiant</option>
                                    <option value="cni">Carte Nationale d'Identité</option>
                                    <option value="passeport">Passeport</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="addChargeEnquete" class="form-label">
                                    <i class="fas fa-users me-1"></i>Chargées des enquêtes du campus social
                                </label>
                                <textarea class="form-control" id="addChargeEnquete" rows="2"></textarea>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="addAgentAction" class="form-label">
                                    <i class="fas fa-user-shield me-1"></i>Agents en faction au poste
                                </label>
                                <textarea class="form-control" id="addAgentAction" rows="2"></textarea>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="addObservations" class="form-label">
                                    <i class="fas fa-sticky-note me-1"></i>Observations
                                </label>
                                <textarea class="form-control" id="addObservations" rows="3"></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="addStatut" class="form-label">
                                    <i class="fas fa-info-circle me-1"></i>Statut
                                </label>
                                <select class="form-select" id="addStatut" required>
                                    <option value="en_cours">En cours</option>
                                    <option value="traite">Traité</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="addDate" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Date PV
                                </label>
                                <input type="date" class="form-control" id="addDate" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-primary" onclick="savePV()">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modification -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-edit me-2"></i>Modifier le Procès-Verbal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="editId">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="editCarteEtudiant" class="form-label">
                                    <i class="fas fa-id-card me-1"></i>N° Carte d'Étudiant (N°...)
                                </label>
                                <input type="text" class="form-control" id="editCarteEtudiant" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="editNom" class="form-label">
                                    <i class="fas fa-user me-1"></i>Nom
                                </label>
                                <input type="text" class="form-control" id="editNom" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="editPrenom" class="form-label">
                                    <i class="fas fa-user me-1"></i>Prénom
                                </label>
                                <input type="text" class="form-control" id="editPrenom" required>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="editCampus" class="form-label">
                                    <i class="fas fa-building me-1"></i>Campus Social ou plus Mr Mme Mlle
                                </label>
                                <input type="text" class="form-control" id="editCampus" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="editTelephone7" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Téléphone (N° 7...)
                                </label>
                                <input type="tel" class="form-control" id="editTelephone7">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="editTelephoneResistant" class="form-label">
                                    <i class="fas fa-phone-alt me-1"></i>Téléphone (résistante)
                                </label>
                                <input type="tel" class="form-control" id="editTelephoneResistant">
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="editIdentiteFaux" class="form-label">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Identité de Faux et Usage de Faux
                                </label>
                                <select class="form-select" id="editIdentiteFaux" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="M. Mme">M. Mme...</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="editTypeDocument" class="form-label">
                                    <i class="fas fa-file-alt me-1"></i>La carte/lecture pièce
                                </label>
                                <select class="form-select" id="editTypeDocument" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="carte_etudiant">Carte d'Étudiant</option>
                                    <option value="cni">Carte Nationale d'Identité</option>
                                    <option value="passeport">Passeport</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="editChargeEnquete" class="form-label">
                                    <i class="fas fa-users me-1"></i>Chargées des enquêtes du campus social
                                </label>
                                <textarea class="form-control" id="editChargeEnquete" rows="2"></textarea>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="editAgentAction" class="form-label">
                                    <i class="fas fa-user-shield me-1"></i>Agents en faction au poste
                                </label>
                                <textarea class="form-control" id="editAgentAction" rows="2"></textarea>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="editObservations" class="form-label">
                                    <i class="fas fa-sticky-note me-1"></i>Observations
                                </label>
                                <textarea class="form-control" id="editObservations" rows="3"></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="editStatut" class="form-label">
                                    <i class="fas fa-info-circle me-1"></i>Statut
                                </label>
                                <select class="form-select" id="editStatut" required>
                                    <option value="en_cours">En cours</option>
                                    <option value="traite">Traité</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="editDate" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Date PV
                                </label>
                                <input type="date" class="form-control" id="editDate" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-primary" onclick="updatePV()">
                        <i class="fas fa-save me-1"></i>Modifier
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détails -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">
                        <i class="fas fa-eye me-2"></i>Détails du Procès-Verbal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title border-bottom pb-2 mb-3">
                                        <i class="fas fa-info-circle me-2"></i>Informations Générales
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <span class="detail-label">N° Carte d'Étudiant:</span><br>
                                            <span class="detail-value" id="detailCarteEtudiant">-</span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <span class="detail-label">Nom & Prénom:</span><br>
                                            <span class="detail-value" id="detailNomPrenom">-</span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <span class="detail-label">Campus/Résidence:</span><br>
                                            <span class="detail-value" id="detailCampus">-</span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <span class="detail-label">Date PV:</span><br>
                                            <span class="detail-value" id="detailDate">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title border-bottom pb-2 mb-3">
                                        <i class="fas fa-phone me-2"></i>Coordonnées
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <span class="detail-label">Téléphone (N° 7...):</span><br>
                                            <span class="detail-value" id="detailTelephone7">-</span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <span class="detail-label">Téléphone (résistante):</span><br>
                                            <span class="detail-value" id="detailTelephoneResistant">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title border-bottom pb-2 mb-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Détails du Faux
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <span class="detail-label">Identité de Faux:</span><br>
                                            <span class="detail-value" id="detailIdentiteFaux">-</span>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <span class="detail-label">Type Document:</span><br>
                                            <span class="detail-value" id="detailTypeDocument">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title border-bottom pb-2 mb-3">
                                        <i class="fas fa-users me-2"></i>Personnels Impliqués
                                    </h6>
                                    <div class="mb-2">
                                        <span class="detail-label">Chargées des enquêtes:</span><br>
                                        <span class="detail-value" id="detailChargeEnquete">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="detail-label">Agents en faction:</span><br>
                                        <span class="detail-value" id="detailAgentAction">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title border-bottom pb-2 mb-3">
                                        <i class="fas fa-sticky-note me-2"></i>Observations
                                    </h6>
                                    <div class="detail-value" id="detailObservations">-</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title border-bottom pb-2 mb-3">
                                        <i class="fas fa-flag me-2"></i>Statut
                                    </h6>
                                    <div id="detailStatut">-</div>
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

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/faux.js"></script>
    
    <!-- Passer les données PHP au JavaScript -->
    <script>
        const phpData = <?php echo json_encode($pvData ?? []); ?>;
        const phpPagination = <?php echo json_encode($pagination ?? []); ?>;
        const phpStatistics = <?php echo json_encode($statistics ?? []); ?>;
    </script>
    <script src="../../assets/js/faux-init.js"></script>
</body>
</html>

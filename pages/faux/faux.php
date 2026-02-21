<?php
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
require_once __DIR__ . '/../../models/FauxModel.php';

// Traitement des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userRole = $_SESSION['role'] ?? '';
    $userId = $_SESSION['utilisateur_id'] ?? 0;

    switch ($action) {
        case 'create':
            if ($userRole === 'operateur') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission de créer un PV']);
                exit;
            }
            // Activer le rapport d'erreurs pour le debug
            error_reporting(E_ALL);
            ini_set('display_errors', 0); // Ne pas afficher les erreurs dans la sortie
            
            // Forcer le retour JSON même en cas d'erreur
            header('Content-Type: application/json');
            
            try {
                // Gestion upload empreinte
                $empreintePath = null;
                if (!empty($_FILES['empreinte']['name'])) {
                    $uploadDir = __DIR__ . '/../../uploads/empreintes/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $ext = strtolower(pathinfo($_FILES['empreinte']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($ext, $allowed) && $_FILES['empreinte']['size'] <= 5 * 1024 * 1024) {
                        $filename = 'emp_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                        if (move_uploaded_file($_FILES['empreinte']['tmp_name'], $uploadDir . $filename)) {
                            $empreintePath = 'uploads/empreintes/' . $filename;
                        }
                    }
                }

                $data = [
                    'carteEtudiant' => $_POST['carteEtudiant'] ?? '',
                    'nom' => $_POST['nom'] ?? '',
                    'prenoms' => $_POST['prenoms'] ?? '',
                    'campus' => $_POST['campus'] ?? '',
                    'telephone7' => $_POST['telephone7'] ?? '',
                    'telephoneResistant' => $_POST['telephoneResistant'] ?? '',
                    'identiteFaux' => $_POST['identiteFaux'] ?? '',
                    'empreinte' => $empreintePath,
                    'typeDocument' => $_POST['typeDocument'] ?? '',
                    'chargeEnquete' => $_POST['chargeEnquete'] ?? '',
                    'agentAction' => $_POST['agentAction'] ?? '',
                    'observations' => $_POST['observations'] ?? '',
                    'statut' => $_POST['statut'] ?? 'en_cours',
                    'date' => $_POST['date'] ?? date('Y-m-d'),
                    'idAgent' => $_SESSION['utilisateur_id'] ?? 1
                ];

                $errors = validatePV($data);
                if (empty($errors)) {
                    $result = createPV($data);
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
                error_log('Erreur création PV Faux: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la création du PV']);
                exit;
            } catch (Error $e) {
                error_log('Erreur fatale création PV Faux: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la création du PV']);
                exit;
            }
            break;
            
        case 'update':
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
            header('Content-Type: application/json');

            if ($userRole === 'operateur') {
                echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission de modifier un PV']);
                exit;
            }

            try {
            $id = $_POST['id'] ?? '';

            // Agent : vérifier propriété du PV
            if ($userRole === 'agent') {
                $pvCheck = getPVById($id);
                if ($pvCheck && $pvCheck['id_agent'] != $userId) {
                    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez modifier que vos propres PV']);
                    exit;
                }
            }
            // Gestion upload empreinte (modification)
            $empreintePath = $_POST['empreinte_existante'] ?? null;
            if (!empty($_FILES['empreinte']['name'])) {
                $uploadDir = __DIR__ . '/../../uploads/empreintes/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['empreinte']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allowed) && $_FILES['empreinte']['size'] <= 5 * 1024 * 1024) {
                    $filename = 'emp_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['empreinte']['tmp_name'], $uploadDir . $filename)) {
                        $empreintePath = 'uploads/empreintes/' . $filename;
                    }
                }
            }
            $data = [
                'carteEtudiant' => $_POST['carteEtudiant'] ?? '',
                'nom' => $_POST['nom'] ?? '',
                'prenoms' => $_POST['prenoms'] ?? '',
                'campus' => $_POST['campus'] ?? '',
                'telephone7' => $_POST['telephone7'] ?? '',
                'telephoneResistant' => $_POST['telephoneResistant'] ?? '',
                'identiteFaux' => $_POST['identiteFaux'] ?? '',
                'empreinte' => $empreintePath,
                'typeDocument' => $_POST['typeDocument'] ?? '',
                'chargeEnquete' => $_POST['chargeEnquete'] ?? '',
                'agentAction' => $_POST['agentAction'] ?? '',
                'observations' => $_POST['observations'] ?? '',
                'statut' => $_POST['statut'] ?? 'en_cours',
                'date' => $_POST['date'] ?? date('Y-m-d'),
                'idAgent' => $_SESSION['utilisateur_id'] ?? 1
            ];

            $errors = validatePV($data);
            if (empty($errors)) {
                $result = updatePV($id, $data);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'PV modifié avec succès']);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification du PV']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                exit;
            }
            } catch (Exception $e) {
                error_log('Erreur modification PV Faux: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la modification du PV']);
                exit;
            } catch (Error $e) {
                error_log('Erreur fatale modification PV Faux: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la modification du PV']);
                exit;
            }
            break;
            
        case 'delete':
            header('Content-Type: application/json');
            if ($userRole === 'operateur' || $userRole === 'superviseur') {
                echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas la permission de supprimer un PV']);
                exit;
            }
            $id = $_POST['id'] ?? '';
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
            
        case 'generate':
            generateFakeData(30);
            header('Location: faux.php?success=4');
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
$pageTitle = "Gestion des Procès-Verbaux - Faux et Usage de Faux";
$bannerText = "Procès-Verbal: Faux et Usage de Faux - USCOUD";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Procès-Verbaux - Faux et Usage de Faux</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Styles COUD'MAINT -->
    <link rel="stylesheet" href="../../assets/css/common.css">
    <link rel="stylesheet" href="../../assets/css/faux.css">
</head>
<body>
    <?php
    $bannerText = "Procès-Verbal: Faux et Usage de Faux - USCOUD";
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
                    <div>
                        <strong>Total:</strong> <span id="totalCount"><?php echo $pagination['total']; ?></span>
                    </div>
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
                            <th>N° de pièce</th>
                            <th>Nom & Prénom</th>
                            <th>Campus/Résidence</th>
                            <th>Téléphone</th>
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
                                <label for="addTypeDocument" class="form-label">
                                    <i class="fas fa-id-card me-1"></i>Type de pièce
                                </label>
                                <select class="form-select" id="addTypeDocument" required onchange="toggleCarteEtudiantField('add')">
                                    <option value="">Sélectionner...</option>
                                    <option value="cni">CNI</option>
                                    <option value="passport">Passeport</option>
                                    <option value="carte_personnel">Carte personnelle</option>
                                    <option value="carte_etudiant">Carte étudiant</option>
                                </select>
                            </div>

                            <div class="col-md-12 mb-3" id="addCarteEtudiantGroup" style="display:none;">
                                <label for="addCarteEtudiant" class="form-label">
                                    <i class="fas fa-id-card me-1"></i><span id="addPieceLabelText">N° de pièce</span>
                                </label>
                                <input type="text" class="form-control" id="addCarteEtudiant" placeholder="">
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
                                    <i class="fas fa-building me-1"></i>Campus Social
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
                                <input type="text" class="form-control" id="addIdentiteFaux" placeholder="Ex: M. Diop Abdoulaye">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="addEmpreinte" class="form-label">
                                    <i class="fas fa-fingerprint me-1"></i>Empreinte de la personne
                                </label>
                                <input type="file" class="form-control" id="addEmpreinte" name="empreinte"
                                       accept="image/jpeg,image/png,image/gif,image/webp">
                                <div class="form-text text-muted">Image JPG, PNG ou GIF — max 5 Mo</div>
                                <div id="addEmpreintePreview" class="mt-2" style="display:none;">
                                    <img id="addEmpreinteImg" src="" alt="Aperçu empreinte"
                                         style="max-width:150px; max-height:150px; border:1px solid #dee2e6; border-radius:4px;">
                                </div>
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
                                <input type="date" class="form-control" id="addDate" required value="<?php echo date('Y-m-d'); ?>">
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
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-edit me-2 text-warning"></i>Modifier le Procès-Verbal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="editId">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="editTypeDocument" class="form-label">
                                    <i class="fas fa-id-card me-1"></i>Type de pièce
                                </label>
                                <select class="form-select" id="editTypeDocument" required onchange="toggleCarteEtudiantField('edit')">
                                    <option value="">Sélectionner...</option>
                                    <option value="cni">CNI</option>
                                    <option value="passport">Passeport</option>
                                    <option value="carte_personnel">Carte personnelle</option>
                                    <option value="carte_etudiant">Carte étudiant</option>
                                </select>
                            </div>

                            <div class="col-md-12 mb-3" id="editCarteEtudiantGroup" style="display:none;">
                                <label for="editCarteEtudiant" class="form-label">
                                    <i class="fas fa-id-card me-1"></i><span id="editPieceLabelText">N° de pièce</span>
                                </label>
                                <input type="text" class="form-control" id="editCarteEtudiant" placeholder="">
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
                                    <i class="fas fa-building me-1"></i>Campus Social
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
                                <input type="text" class="form-control" id="editIdentiteFaux" placeholder="Ex: M. Diop Abdoulaye">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="editEmpreinte" class="form-label">
                                    <i class="fas fa-fingerprint me-1"></i>Empreinte de la personne
                                </label>
                                <input type="file" class="form-control" id="editEmpreinte" name="empreinte"
                                       accept="image/jpeg,image/png,image/gif,image/webp">
                                <div class="form-text text-muted">Laisser vide pour conserver l'empreinte existante</div>
                                <input type="hidden" id="editEmpreinteExistante" name="empreinte_existante">
                                <div id="editEmpreintePreview" class="mt-2" style="display:none;">
                                    <img id="editEmpreinteImg" src="" alt="Aperçu empreinte"
                                         style="max-width:150px; max-height:150px; border:1px solid #dee2e6; border-radius:4px;">
                                </div>
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
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
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
                                        <div class="col-md-6 mb-2" id="detailCarteEtudiantRow">
                                            <span class="detail-label" id="detailPieceLabelText">N° de pièce :</span><br>
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
                                            <span class="detail-label">Téléphone:</span><br>
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
                                        <div class="col-md-12 mb-2" id="detailEmpreinteRow" style="display:none;">
                                            <span class="detail-label"><i class="fas fa-fingerprint me-1"></i>Empreinte :</span><br>
                                            <img id="detailEmpreinteImg" src="" alt="Empreinte"
                                                 style="max-width:180px; max-height:180px; border:1px solid #dee2e6; border-radius:4px; margin-top:4px;">
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
                            <strong>Numéro PV :</strong> <span id="deletePvNumber">-</span><br>
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

    <!-- Modal de confirmation pour modification -->
    <div class="modal fade" id="confirmUpdateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2 text-warning"></i>Confirmation de Modification
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Voulez-vous vraiment modifier ce procès-verbal ?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Les modifications seront enregistrées immédiatement dans la base de données.
                    </div>
                    <div class="text-center">
                        <div class="pv-info mb-2">
                            <strong>Numéro PV :</strong> <span id="updatePvNumber">-</span><br>
                            <strong>Nom :</strong> <span id="updatePvName">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-warning text-dark" id="confirmUpdateBtn">
                        <i class="fas fa-save me-1"></i>Confirmer la modification
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de succès pour ajout -->
    <div class="modal fade" id="successAddModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2 text-success"></i>Ajout Réussi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-check-circle fa-4x text-success"></i>
                        </div>
                        <h5 class="text-success">Procès-Verbal Ajouté avec Succès !</h5>
                        <p class="mb-2">Le nouveau procès-verbal a été enregistré dans la base de données.</p>
                        <div class="pv-info">
                            <strong>Numéro PV :</strong> <span id="addPvNumber">-</span><br>
                            <strong>Nom :</strong> <span id="addPvName">-</span>
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

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>var USER_ROLE = '<?php echo $_SESSION["role"] ?? ""; ?>'; var USER_ID = <?php echo $_SESSION["utilisateur_id"] ?? 0; ?>;</script>
    <script src="../../assets/js/faux-database.js?v=<?php echo filemtime(__DIR__ . '/../../assets/js/faux-database.js'); ?>"></script>
    
    <!-- Passer les données PHP au JavaScript -->
    <script>
        // Les données sont maintenant chargées via AJAX
        window.addEventListener('load', function() {
            // Initialiser les variables globales
            window.currentPage = <?php echo $pagination['currentPage']; ?>;
            window.itemsPerPage = <?php echo $pagination['itemsPerPage']; ?>;
        });
    </script>
</body>
</html>

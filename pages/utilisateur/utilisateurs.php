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
if (($_SESSION['role'] ?? '') !== 'admin') {
    redirect('/index');
    exit();
}

// Inclure le modèle
require_once __DIR__ . '/../../models/UtilisateurModel.php';

// Traitement des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lire les données JSON depuis le corps de la requête
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true) ?? [];
    
    $action = $data['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            // Activer le rapport d'erreurs pour le debug
            error_reporting(E_ALL);
            ini_set('display_errors', 0); // Ne pas afficher les erreurs dans la sortie
            
            // Forcer le retour JSON même en cas d'erreur
            header('Content-Type: application/json');
            
            try {
                $userData = [
                    'matricule' => $data['matricule'] ?? '',
                    'nom' => $data['nom'] ?? '',
                    'prenoms' => $data['prenoms'] ?? '',
                    'email' => $data['email'] ?? '',
                    'telephone' => $data['telephone'] ?? '',
                    'role' => $data['role'] ?? 'agent',
                    'statut' => 'actif', // Statut fixe par défaut
                    'mot_de_passe' => 'COUD' // Mot de passe fixe
                ];
                
                $result = createUtilisateur($userData);
                if ($result['success']) {
                    echo json_encode(['success' => true, 'message' => $result['message'] . ' (Mot de passe: COUD)']);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => $result['message'], 'errors' => $result['errors'] ?? []]);
                    exit;
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur PHP: ' . $e->getMessage()]);
                exit;
            } catch (Error $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur fatale PHP: ' . $e->getMessage()]);
                exit;
            }
            break;
            
        case 'update':
            $id = $data['id'] ?? '';
            $userData = [
                'matricule' => $data['matricule'] ?? '',
                'nom' => $data['nom'] ?? '',
                'prenoms' => $data['prenoms'] ?? '',
                'email' => $data['email'] ?? '',
                'telephone' => $data['telephone'] ?? '',
                'role' => $data['role'] ?? 'agent',
                'statut' => $data['statut'] ?? 'actif'
            ];
            
            // Pas de gestion du mot de passe (fixé à COUD)
            
            $result = updateUtilisateur($id, $userData);
            if ($result['success']) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $result['message']]);
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $result['message'], 'errors' => $result['errors'] ?? []]);
                exit;
            }
            break;
            
        case 'delete':
            $id = $data['id'] ?? '';
            $result = deleteUtilisateur($id);
            if ($result['success']) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $result['message']]);
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $result['message']]);
                exit;
            }
            break;
            
        case 'toggle_statut':
            $id = $data['id'] ?? '';
            $statut = $data['statut'] ?? '';
            $result = toggleUtilisateurStatut($id, $statut);
            if ($result) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du statut']);
                exit;
            }
            break;
            
        case 'reset_password':
            $id = $data['id'] ?? '';
            $result = resetPassword($id);
            if ($result['success']) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $result['message'], 'password' => $result['password']]);
                exit;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $result['message']]);
                exit;
            }
            break;
            
        case 'export':
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $statut = $_GET['statut'] ?? '';
            exportToCSV($search, $role, $statut);
            break;
    }
}

// Traitement des requêtes GET pour les détails
if (isset($_GET['action']) && $_GET['action'] === 'detail') {
    $id = $_GET['id'] ?? '';
    $utilisateur = getUtilisateurById($id);
    if ($utilisateur) {
        header('Content-Type: application/json');
        echo json_encode($utilisateur);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit;
    }
}

// Variables pour la vue
$currentPage = $_GET['page'] ?? 1;
$itemsPerPage = $_GET['itemsPerPage'] ?? 10;
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$statut = $_GET['statut'] ?? '';

// Obtenir les données des utilisateurs
$result = getAllUtilisateurs($currentPage, $itemsPerPage, $search, $role, $statut);
$utilisateurs = $result['data'];
$pagination = [
    'total' => $result['total'],
    'totalPages' => $result['totalPages'],
    'currentPage' => $result['currentPage'],
    'itemsPerPage' => $result['itemsPerPage']
];

// Obtenir les statistiques
$statistics = getStatisticsUtilisateurs();

// Traitement AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'utilisateurs' => $utilisateurs,
        'pagination' => $pagination,
        'statistics' => $statistics
    ]);
    exit;
}

// Variables pour le header
$pageTitle = "Gestion des Utilisateurs - USCOUD";
$bannerText = "Gestion des Utilisateurs - USCOUD";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - USCOUD</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Styles COUD'MAINT -->
    <link rel="stylesheet" href="../../assets/css/common.css">
    <link rel="stylesheet" href="../../assets/css/utilisateurs.css">
</head>
<body>
    <?php
    $bannerText = "Gestion des Utilisateurs - USCOUD";
    include __DIR__ . '/../../includes/head.php';
    ?>


    <div class="container">
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 id="totalCount"><?php echo $pagination['total']; ?></h3>
                    <p>Total Utilisateurs</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3 id="statActifs"><?php echo $statistics['actifs']; ?></h3>
                    <p>Utilisateurs Actifs</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon warning">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <h3 id="statInactifs"><?php echo $statistics['inactifs']; ?></h3>
                    <p>Utilisateurs Inactifs</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon danger">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3><?php echo count($statistics['par_role']); ?></h3>
                    <p>Types de Rôles</p>
                </div>
            </div>
        </div>


        <!-- Recherche et Filtres -->
        <div class="row mb-4">
            <div class="col-md-6">
                <input type="text" class="form-control search-box" id="searchInput" placeholder="Rechercher par nom, prénom, matricule, email...">
            </div>
            <div class="col-md-3">
                <select class="form-select search-box" id="roleFilter">
                    <option value="">Tous les rôles</option>
                    <?php foreach (['admin', 'superviseur', 'agent', 'operateur'] as $role): ?>
                        <option value="<?php echo $role; ?>" <?php echo $role === $_GET['role'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst($role); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select search-box" id="statutFilter">
                    <option value="">Tous les statuts</option>
                    <option value="actif" <?php echo $statut === 'actif' ? 'selected' : ''; ?>>Actif</option>
                    <option value="inactif" <?php echo $statut === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                </select>
            </div>
        </div>

        <!-- Tableau des utilisateurs -->
        <div class="table-wrapper">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-list me-2"></i>Liste des Utilisateurs</h5>
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
                    <span class="badge bg-primary">Total: <span id="totalCount"><?php echo $pagination['total']; ?></span></span>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-user-plus me-1"></i>Nouvel Utilisateur
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="utilisateursTable">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom & Prénom</th>
                            <th>Contact</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="utilisateursTableBody">
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
                    <ul class="pagination mb-0" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Utilisateur -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="fas fa-user-plus me-2 text-success"></i>Nouvel Utilisateur
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addMatricule" class="form-label">
                                    <i class="fas fa-id-badge me-1"></i>Matricule
                                </label>
                                <input type="text" class="form-control" id="addMatricule" name="matricule" required placeholder="Ex: AGT001">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addEmail" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email
                                </label>
                                <input type="email" class="form-control" id="addEmail" name="email" required placeholder="exemple@email.com">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addNom" class="form-label">
                                    <i class="fas fa-user me-1"></i>Nom
                                </label>
                                <input type="text" class="form-control" id="addNom" name="nom" required placeholder="Nom">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addPrenoms" class="form-label">
                                    <i class="fas fa-user me-1"></i>Prénoms
                                </label>
                                <input type="text" class="form-control" id="addPrenoms" name="prenoms" required placeholder="Prénoms">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="addTelephone" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Téléphone
                                </label>
                                <input type="tel" class="form-control" id="addTelephone" name="telephone" placeholder="770000000" maxlength="9" pattern="[0-9]{9}" title="Le numéro de téléphone doit contenir exactement 9 chiffres">
                                <small class="text-muted">9 chiffres uniquement (ex: 770000000)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="addRole" class="form-label">
                                    <i class="fas fa-user-tag me-1"></i>Rôle
                                </label>
                                <select class="form-select" id="addRole" name="role" required>
                                    <option value="agent">Agent</option>
                                    <option value="operateur">Opérateur</option>
                                    <option value="superviseur">Superviseur</option>
                                    <option value="admin">Administrateur</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-lock me-1"></i>Mot de passe
                                </label>
                                <div class="form-control-plaintext">
                                    <strong>COUD</strong>
                                    <small class="text-muted d-block">Mot de passe fixe pour tous les utilisateurs (Statut: Actif par défaut)</small>
                                </div>
                                <input type="hidden" id="addPassword" name="mot_de_passe" value="COUD">
                                <input type="hidden" id="addStatut" name="statut" value="actif">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" form="addForm">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modification Utilisateur -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="fas fa-edit me-2 text-warning"></i>Modifier l'Utilisateur
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="editId" name="id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editMatricule" class="form-label">
                                    <i class="fas fa-id-badge me-1"></i>Matricule
                                </label>
                                <input type="text" class="form-control" id="editMatricule" name="matricule" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editEmail" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email
                                </label>
                                <input type="email" class="form-control" id="editEmail" name="email" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editNom" class="form-label">
                                    <i class="fas fa-user me-1"></i>Nom
                                </label>
                                <input type="text" class="form-control" id="editNom" name="nom" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editPrenoms" class="form-label">
                                    <i class="fas fa-user me-1"></i>Prénoms
                                </label>
                                <input type="text" class="form-control" id="editPrenoms" name="prenoms" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editTelephone" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Téléphone
                                </label>
                                <input type="tel" class="form-control" id="editTelephone" name="telephone" maxlength="9" pattern="[0-9]{9}" title="Le numéro de téléphone doit contenir exactement 9 chiffres">
                                <small class="text-muted">9 chiffres uniquement (ex: 770000000)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editRole" class="form-label">
                                    <i class="fas fa-user-tag me-1"></i>Rôle
                                </label>
                                <select class="form-select" id="editRole" name="role" required>
                                    <option value="agent">Agent</option>
                                    <option value="operateur">Opérateur</option>
                                    <option value="superviseur">Superviseur</option>
                                    <option value="admin">Administrateur</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editStatut" class="form-label">
                                    <i class="fas fa-toggle-on me-1"></i>Statut
                                </label>
                                <select class="form-select" id="editStatut" name="statut" required>
                                    <option value="actif">Actif</option>
                                    <option value="inactif">Inactif</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" form="editForm">
                        <i class="fas fa-save me-1"></i>Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Vue Utilisateur -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
                    <h5 class="modal-title" id="viewModalLabel">
                        <i class="fas fa-user me-2 text-info"></i>Détails de l'Utilisateur
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="avatar-preview">
                            <span id="viewAvatar">U</span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Matricule:</strong> <span id="viewMatricule"></span></p>
                            <p><strong>Nom:</strong> <span id="viewNom"></span></p>
                            <p><strong>Prénoms:</strong> <span id="viewPrenoms"></span></p>
                            <p><strong>Email:</strong> <span id="viewEmail"></span></p>
                            <p><strong>Téléphone:</strong> <span id="viewTelephone"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Rôle:</strong> <span id="viewRole"></span></p>
                            <p><strong>Statut:</strong> <span id="viewStatut"></span></p>
                            <p><strong>Créé le:</strong> <span id="viewCreatedAt"></span></p>
                            <p><strong>Dernière modification:</strong> <span id="viewUpdatedAt"></span></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Statistiques d'activités -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Statistiques d'Activités</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <i class="fas fa-file-alt text-primary mb-2" style="font-size: 1.5rem;"></i>
                                            <h6 class="card-title">PV Faux</h6>
                                            <p class="card-text h4 mb-0" id="viewNbFaux">0</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <i class="fas fa-clipboard-check text-success mb-2" style="font-size: 1.5rem;"></i>
                                            <h6 class="card-title">PV Constat</h6>
                                            <p class="card-text h4 mb-0" id="viewNbConstat">0</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 1.5rem;"></i>
                                            <h6 class="card-title">PV Dénonciation</h6>
                                            <p class="card-text h4 mb-0" id="viewNbDenonciation">0</p>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmation Activation/Désactivation -->
    <div class="modal fade" id="toggleStatutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white" id="toggleStatutModalHeader">
                    <h5 class="modal-title">
                        <i class="fas fa-toggle-on me-2"></i>Confirmation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="toggleStatutModalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-primary" id="toggleStatutConfirmBtn">
                        <i class="fas fa-check me-1"></i>Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmation Réinitialisation Mot de Passe -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>R&eacute;initialisation du mot de passe
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="resetPasswordModalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-info text-white" id="resetPasswordConfirmBtn">
                        <i class="fas fa-key me-1"></i>R&eacute;initialiser
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmation Suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmation de suppression
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteModalMessage">Êtes-vous sûr de vouloir supprimer cet utilisateur ?</p>
                    <p class="text-muted mb-0"><small>Cette action est irréversible.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-danger" id="deleteConfirmBtn">
                        <i class="fas fa-trash me-1"></i>Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/utilisateurs-database.js"></script>
    
    <!-- Passer les données PHP au JavaScript -->
    <script>
        // Les données sont maintenant chargées via AJAX
        window.addEventListener('load', function() {
            // Initialiser les variables globales
            window.currentPage = <?php echo $currentPage; ?>;
            window.itemsPerPage = <?php echo $itemsPerPage; ?>;
            
            // Filtrer les caractères non numériques pour le téléphone
            const phoneInputs = document.querySelectorAll('input[name="telephone"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    // Supprimer tous les caractères non numériques
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    // Limiter à 9 chiffres
                    if (this.value.length > 9) {
                        this.value = this.value.slice(0, 9);
                    }
                });
            });
            
            // Ajouter les gestionnaires d'événements pour les formulaires
            const addForm = document.getElementById('addForm');
            if (addForm) {
                addForm.addEventListener('submit', handleAddUtilisateur);
            }
            
            const editForm = document.getElementById('editForm');
            if (editForm) {
                editForm.addEventListener('submit', handleUpdateUtilisateur);
            }
            
            // Initialiser les toggles de mot de passe
            setupPasswordToggles();
        });
    </script>
</body>
</html>

<?php
// Fonctions utilitaires
function getRoleColor($role) {
    $colors = [
        'admin' => 'danger',
        'superviseur' => 'warning', 
        'agent' => 'primary',
        'operateur' => 'success'
    ];
    return $colors[$role] ?? 'primary';
}

function exportToCSV($search = '', $role = '', $statut = '') {
    $result = getAllUtilisateurs(1, 10000, $search, $role, $statut);
    $utilisateurs = $result['data'];
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="utilisateurs_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-tête CSV
    fputcsv($output, [
        'Matricule',
        'Nom',
        'Prénoms', 
        'Email',
        'Téléphone',
        'Rôle',
        'Statut',
        'Date de création',
        'PV Faux',
        'PV Constat',
        'PV Dénonciation'
    ]);
    
    // Données
    foreach ($utilisateurs as $utilisateur) {
        fputcsv($output, [
            $utilisateur['matricule'],
            $utilisateur['nom'],
            $utilisateur['prenoms'],
            $utilisateur['email'],
            $utilisateur['telephone'],
            $utilisateur['role'],
            $utilisateur['statut'],
            $utilisateur['created_at'],
            $utilisateur['nb_faux'] ?? 0,
            $utilisateur['nb_constat'] ?? 0,
            $utilisateur['nb_denonciation'] ?? 0
        ]);
    }
    
    fclose($output);
    exit;
}
?>

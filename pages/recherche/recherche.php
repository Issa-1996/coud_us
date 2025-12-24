<?php
// Inclure le modèle
require_once __DIR__ . '/../../models/RechercheModel.php';

// Initialiser le modèle
$rechercheModel = new RechercheModel();

// Traitement des requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = [
                'etablissement' => $_POST['etablissement'] ?? '',
                'departement' => $_POST['departement'] ?? '',
                'niveauFormation' => $_POST['niveauFormation'] ?? '',
                'num_etu' => $_POST['num_etu'] ?? '',
                'nom' => $_POST['nom'] ?? '',
                'prenoms' => $_POST['prenoms'] ?? '',
                'dateNaissance' => $_POST['dateNaissance'] ?? '',
                'lieuNaissance' => $_POST['lieuNaissance'] ?? '',
                'sexe' => $_POST['sexe'] ?? '',
                'nationalite' => $_POST['nationalite'] ?? 'Sénégalaise',
                'numIdentite' => $_POST['numIdentite'] ?? '',
                'typeEtudiant' => $_POST['typeEtudiant'] ?? '',
                'moyenne' => $_POST['moyenne'] ?? '',
                'sessionId' => $_POST['sessionId'] ?? '',
                'niveau' => $_POST['niveau'] ?? '',
                'email_perso' => $_POST['email_perso'] ?? '',
                'email_ucad' => $_POST['email_ucad'] ?? '',
                'telephone' => $_POST['telephone'] ?? '',
                'var' => $_POST['var'] ?? '',
                'adresse' => $_POST['adresse'] ?? '',
                'urgenceContact' => $_POST['urgenceContact'] ?? '',
                'urgenceTel' => $_POST['urgenceTel'] ?? ''
            ];
            
            $errors = $rechercheModel->validateEtudiant($data);
            if (empty($errors)) {
                $rechercheModel->addEtudiant($data);
                header('Location: recherche.php?success=1');
                exit;
            } else {
                $errors = $errors;
            }
            break;
            
        case 'update':
            $id = $_POST['id'] ?? '';
            $data = [
                'etablissement' => $_POST['etablissement'] ?? '',
                'departement' => $_POST['departement'] ?? '',
                'niveauFormation' => $_POST['niveauFormation'] ?? '',
                'num_etu' => $_POST['num_etu'] ?? '',
                'nom' => $_POST['nom'] ?? '',
                'prenoms' => $_POST['prenoms'] ?? '',
                'dateNaissance' => $_POST['dateNaissance'] ?? '',
                'lieuNaissance' => $_POST['lieuNaissance'] ?? '',
                'sexe' => $_POST['sexe'] ?? '',
                'nationalite' => $_POST['nationalite'] ?? 'Sénégalaise',
                'numIdentite' => $_POST['numIdentite'] ?? '',
                'typeEtudiant' => $_POST['typeEtudiant'] ?? '',
                'moyenne' => $_POST['moyenne'] ?? '',
                'sessionId' => $_POST['sessionId'] ?? '',
                'niveau' => $_POST['niveau'] ?? '',
                'email_perso' => $_POST['email_perso'] ?? '',
                'email_ucad' => $_POST['email_ucad'] ?? '',
                'telephone' => $_POST['telephone'] ?? '',
                'var' => $_POST['var'] ?? '',
                'adresse' => $_POST['adresse'] ?? '',
                'urgenceContact' => $_POST['urgenceContact'] ?? '',
                'urgenceTel' => $_POST['urgenceTel'] ?? ''
            ];
            
            $errors = $rechercheModel->validateEtudiant($data);
            if (empty($errors)) {
                $rechercheModel->updateEtudiant($id, $data);
                header('Location: recherche.php?success=2');
                exit;
            } else {
                $errors = $errors;
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? '';
            $rechercheModel->deleteEtudiant($id);
            header('Location: recherche.php?success=3');
            exit;
            break;
            
        case 'generate':
            $rechercheModel->generateFakeData(100);
            header('Location: recherche.php?success=4');
            exit;
            break;
            
        case 'export':
            $search = $_GET['search'] ?? '';
            $rechercheModel->exportToCSV($search);
            break;
    }
}

// Traitement des requêtes GET pour les détails
if (isset($_GET['action']) && $_GET['action'] === 'detail') {
    $id = $_GET['id'] ?? '';
    $etudiant = $rechercheModel->getEtudiantById($id);
    if ($etudiant) {
        header('Content-Type: application/json');
        echo json_encode($etudiant);
        exit;
    }
}

// Traitement de la recherche
$search = $_GET['search'] ?? '';
$searchResults = [];
if (!empty($search)) {
    $searchResults = $rechercheModel->searchEtudiants($search);
}

// Variables pour la vue
$currentPage = $_GET['page'] ?? 1;
$result = $rechercheModel->getAllEtudiants($currentPage, 20);
$etudiants = $result['data'];
$pagination = [
    'total' => $result['total'],
    'totalPages' => $result['totalPages'],
    'currentPage' => $result['currentPage'],
    'itemsPerPage' => $result['itemsPerPage']
];
$statistics = $rechercheModel->getStatistics();

// Variables pour le header
$pageTitle = "Recherche d'Étudiants - Campus Social UCAD";
$bannerText = "Recherche d'Étudiants - USCOUD";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche d'Étudiants - USCOUD</title>
    <link rel="stylesheet" href="../../assets/css/recherche.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php
    $bannerText = "Recherche d'Étudiants - USCOUD";
    include __DIR__ . '/../../includes/head.php';
    ?>

    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-search me-3"></i>Recherche d'Étudiant</h1>
                    <p>Retrouvez rapidement les informations essentielles d'un étudiant grâce à son numéro, son nom ou tout autre identifiant.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="text-white-50">
                        <p class="mb-1"><i class="fas fa-database me-2"></i>Base documentaire USCOUD</p>
                        <p class="mb-0"><i class="fas fa-user-graduate me-2"></i>Profil administratif & académique</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container mb-5">
        <div class="card search-container">
            <div class="card-body">
                <h5><i class="fas fa-search me-2"></i>Recherche d'Étudiant</h5>
                <form id="searchForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-9">
                            <label for="searchInput" class="form-label fw-semibold">Critères acceptés : N° étudiant, nom, prénom, département, niveau, email…</label>
                            <input type="text" class="form-control search-input" id="searchInput" placeholder="Ex : ETU012345, Ndiaye, Informatique, L3, 778889900...">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn search-btn w-100">
                                <i class="fas fa-search me-2"></i>Lancer la recherche
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mt-4 g-4">
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users text-primary mb-3" style="font-size: 2rem;"></i>
                        <h3 id="statTotal">0</h3>
                        <p class="text-muted mb-0">Étudiants indexés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock text-warning mb-3" style="font-size: 2rem;"></i>
                        <h3 id="statDerniere">-</h3>
                        <p class="text-muted mb-0">Dernière recherche</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle text-success mb-3" style="font-size: 2rem;"></i>
                        <h3 id="statResultats">0</h3>
                        <p class="text-muted mb-0">Résultats trouvés</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card result-card mt-4">
            <div class="card-body" id="resultContainer">
                <div class="empty-state">
                    <i class="fas fa-user-search"></i>
                    <h5>Aucun étudiant recherché pour le moment</h5>
                    <p>Saisissez un mot-clé pour afficher les informations détaillées d'un étudiant.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/recherche.js"></script>
</body>

</html>
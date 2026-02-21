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

// Inclure les fonctions de recherche bdcodif
require_once __DIR__ . '/../../data/codif_recherche_functions.php';

// Recherche
$etudiants = [];
$search = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = trim($_GET['search']);

    $etudiants = rechercherEtudiantsCodif($search);
}

// Variables pour le header
$pageTitle = "Recherche d'Étudiants Résidents";
$bannerText = "Recherche d'Étudiants Résidents - USCOUD";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche d'Étudiants Résidents - USCOUD</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Styles COUD'MAINT -->
    <link rel="stylesheet" href="../../assets/css/common.css">
    <link rel="stylesheet" href="../../assets/css/recherche.css">
</head>

<body>
    <?php
    $bannerText = "Recherche d'Étudiants Résidents - USCOUD";
    include __DIR__ . '/../../includes/head.php';
    ?>

    <div class="container mb-5">
        <div class="card search-container">
            <div class="card-body">
                <h5><i class="fas fa-search me-2"></i>Recherche d'Étudiant Résident</h5>
                <form method="get" action="" autocomplete="off">
                    <label for="searchInput" class="form-label fw-semibold">Rechercher un étudiant</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control search-input" id="searchInput"
                            placeholder="Rechercher par nom, prénom, numéro carte ou téléphone"
                            value="<?php echo htmlspecialchars($search); ?>"
                            required>
                        <button type="submit" class="btn search-btn">
                            <i class="fas fa-search me-2"></i>Rechercher
                        </button>
                    </div>
                    <div class="form-text text-muted mt-1">
                        <i class="fas fa-info-circle me-1"></i>Recherchez par nom, prénom, numéro étudiant ou téléphone.
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($search)): ?>
        <div class="card result-card mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Résultats de la recherche</h5>
                    <span class="badge bg-primary fs-6"><?php echo count($etudiants); ?> étudiant(s) trouvé(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered text-center align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Numéro Carte</th>
                                <th>Téléphone</th>
                                <th>Sexe</th>
                                <th>Date Naissance</th>
                                <th>Pavillon</th>
                                <th>Chambre</th>
                                <th>Lit</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($etudiants)): ?>
                                <?php $i = 1; foreach ($etudiants as $etu): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($etu['nom'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($etu['prenoms'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($etu['num_etu'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($etu['telephone'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($etu['sexe'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($etu['dateNaissance'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($etu['pavillon'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($etu['chambre'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($etu['lit'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($etu['statut'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-danger text-center py-4">
                                        <i class="fas fa-user-times fa-2x mb-2 d-block"></i>
                                        Aucun étudiant trouvé pour "<strong><?php echo htmlspecialchars($search); ?></strong>"
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card result-card mt-4">
            <div class="card-body">
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h5>Recherche d'étudiants résidents</h5>
                    <p>Saisissez un nom, prénom, numéro étudiant ou téléphone pour rechercher un étudiant résident du campus.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

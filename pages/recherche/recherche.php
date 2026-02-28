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

// Inclure les fonctions de recherche
require_once __DIR__ . '/../../data/codif_recherche_functions.php';

// Déterminer le type de recherche actif
$allowedTypes = ['codif', 'ucad', 'infractions'];
$type   = in_array($_GET['type'] ?? '', $allowedTypes) ? $_GET['type'] : 'codif';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Résultats
$etudiants    = [];
$etudiantUCAD = null;
$erreurUCAD   = '';
$erreurCodif  = '';
$infractions  = ['faux' => [], 'constat' => [], 'denonciation' => []];

if (!empty($search)) {
    if ($type === 'ucad') {
        $resultatUCAD = rechercherEtudiantUCAD($search);
        $etudiantUCAD = $resultatUCAD['data'];
        if ($resultatUCAD['error']) {
            $erreurUCAD = $resultatUCAD['error'];
        } elseif ($etudiantUCAD === null) {
            $erreurUCAD = "Aucun étudiant trouvé pour le numéro de carte <strong>" . htmlspecialchars($search) . "</strong>.";
        }
    } elseif ($type === 'infractions') {
        $infractions = rechercherInfractionsEtudiant($search);
    } else {
        try {
            $etudiants = rechercherEtudiantsCodif($search);
        } catch (mysqli_sql_exception $e) {
            $erreurCodif = $e->getMessage();
        }
    }
}

// Fusionner toutes les infractions dans un tableau unifié
$toutesInfractions = [];

foreach ($infractions['faux'] as $pv) {
    $toutesInfractions[] = [
        'type'        => 'faux',
        'type_label'  => 'Faux et Usage de Faux',
        'id'          => $pv['id'],
        'url'         => BASE_URL . '/pages/faux/faux?view=' . $pv['id'],
        'numero_pv'   => $pv['numero_pv'],
        'nom'         => $pv['nom'],
        'prenoms'     => $pv['prenoms'],
        'carte'       => $pv['carte_etudiant'],
        'telephone'   => $pv['telephone_principal'],
        'detail'      => str_replace('_', ' ', ucfirst($pv['type_document'])),
        'date'        => $pv['date_pv'],
        'statut'      => $pv['statut'],
    ];
}

foreach ($infractions['constat'] as $pv) {
    $toutesInfractions[] = [
        'type'        => 'constat',
        'type_label'  => 'Constat d\'Incident',
        'id'          => $pv['id'],
        'url'         => BASE_URL . '/pages/constat/constat?view=' . $pv['id'],
        'numero_pv'   => $pv['numero_pv'],
        'nom'         => $pv['nom'],
        'prenoms'     => $pv['prenoms'],
        'carte'       => $pv['carte_etudiant'],
        'telephone'   => $pv['telephone'],
        'detail'      => ucfirst($pv['type_incident']) . ' — ' . $pv['lieu_incident'],
        'date'        => date('Y-m-d', strtotime($pv['date_incident'])),
        'statut'      => $pv['statut'],
    ];
}

foreach ($infractions['denonciation'] as $pv) {
    $nomDenon    = $pv['denonciateur_anonyme'] ? 'Anonyme' : ($pv['denonciateur_nom'] . ' ' . $pv['denonciateur_prenoms']);
    $carteAffich = $pv['sujet_carte'] ?? '—';
    $sujetAffich = $pv['sujet_nom'] ? ($pv['sujet_nom'] . ' ' . $pv['sujet_prenoms']) : '—';
    $toutesInfractions[] = [
        'type'        => 'denon',
        'type_label'  => 'Dénonciation',
        'id'          => $pv['id'],
        'url'         => BASE_URL . '/pages/denonciation/denonciation?view=' . $pv['id'],
        'numero_pv'   => $pv['numero_pv'],
        'nom'         => $nomDenon,
        'prenoms'     => $pv['denonciateur_anonyme'] ? '' : '(dénonciateur)',
        'carte'       => $carteAffich,
        'telephone'   => $pv['denonciateur_telephone'] ?? '—',
        'detail'      => ucfirst($pv['type_denonciation']) . ($pv['sujet_nom'] ? ' → Sujet : ' . $sujetAffich : ''),
        'date'        => $pv['date_denonciation'],
        'statut'      => $pv['statut'],
    ];
}

// Trier par date décroissante
usort($toutesInfractions, fn($a, $b) => strcmp($b['date'], $a['date']));

$totalInfractions = count($toutesInfractions);

$bannerText = "Recherche d'Étudiants Résidents - USCOUD";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche d'Étudiants - USCOUD</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/common.css">
    <link rel="stylesheet" href="../../assets/css/recherche.css">
    <style>
        .ucad-badge {
            background: linear-gradient(135deg, #006633 0%, #009900 100%);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
        }
        .info-block {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
            padding: 12px 16px;
        }
        .info-block .label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .info-block .value {
            font-weight: 600;
            color: #1f2933;
            font-size: 1rem;
        }
        .nav-tabs .nav-link {
            font-weight: 600;
            color: #6c757d;
        }
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
        }
        .badge-etat-1  { background-color: #27ae60; }
        .badge-etat-0  { background-color: #e74c3c; }

        /* Infractions */
        .statut-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            color: white;
        }
        .statut-en_cours   { background:#f39c12; }
        .statut-traite     { background:#27ae60; }
        .statut-archive    { background:#7f8c8d; }
        .statut-en_attente { background:#3498db; }
        .type-faux    { background: linear-gradient(135deg,#c0392b,#e74c3c); }
        .type-constat { background: linear-gradient(135deg,#d35400,#f39c12); }
        .type-denon   { background: linear-gradient(135deg,#1a5276,#2980b9); }
        .total-infractions-badge {
            background: linear-gradient(135deg,#c0392b,#e74c3c);
            color: white;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/head.php'; ?>

    <div class="container mb-5">

        <!-- Onglets de sélection du type de recherche -->
        <ul class="nav nav-tabs mb-0" id="searchTabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $type === 'codif' ? 'active' : ''; ?>"
                   href="?type=codif<?php echo !empty($search) && $type === 'codif' ? '&search=' . urlencode($search) : ''; ?>">
                    <i class="fas fa-database me-2"></i>Résidents (Campus COUD)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $type === 'ucad' ? 'active' : ''; ?>"
                   href="?type=ucad<?php echo !empty($search) && $type === 'ucad' ? '&search=' . urlencode($search) : ''; ?>">
                    <i class="fas fa-university me-2"></i>UCAD (API)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $type === 'infractions' ? 'active' : ''; ?>"
                   href="?type=infractions<?php echo !empty($search) && $type === 'infractions' ? '&search=' . urlencode($search) : ''; ?>">
                    <i class="fas fa-exclamation-triangle me-2"></i>Infractions
                    <?php if ($type === 'infractions' && !empty($search) && $totalInfractions > 0): ?>
                        <span class="total-infractions-badge ms-1"><?php echo $totalInfractions; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <!-- Formulaire de recherche -->
        <div class="card search-container" style="border-radius: 0 15px 15px 15px;">
            <div class="card-body">
                <?php if ($type === 'codif'): ?>
                    <h5><i class="fas fa-search me-2"></i>Recherche dans la base des résidents</h5>
                    <form method="get" action="" autocomplete="off">
                        <input type="hidden" name="type" value="codif">
                        <label for="searchInput" class="form-label fw-semibold">Rechercher un étudiant résident</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control search-input" id="searchInput"
                                placeholder="Nom, prénom, numéro carte ou téléphone"
                                value="<?php echo htmlspecialchars($search); ?>" required>
                            <button type="submit" class="btn search-btn">
                                <i class="fas fa-search me-2"></i>Rechercher
                            </button>
                        </div>
                        <div class="form-text text-muted mt-1">
                            <i class="fas fa-info-circle me-1"></i>Recherche dans la base locale des résidents du campus.
                        </div>
                    </form>
                <?php elseif ($type === 'infractions'): ?>
                    <h5><i class="fas fa-search me-2"></i>Recherche d'infractions</h5>
                    <form method="get" action="" autocomplete="off">
                        <input type="hidden" name="type" value="infractions">
                        <label for="searchInputInf" class="form-label fw-semibold">Rechercher les infractions d'un étudiant</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control search-input" id="searchInputInf"
                                placeholder="Nom, prénom, numéro carte ou téléphone"
                                value="<?php echo htmlspecialchars($search); ?>" required>
                            <button type="submit" class="btn search-btn">
                                <i class="fas fa-search me-2"></i>Rechercher
                            </button>
                        </div>
                        <div class="form-text text-muted mt-1">
                            <i class="fas fa-info-circle me-1"></i>Vérifie si l'étudiant est impliqué dans un PV (faux, constat d'incident, dénonciation).
                        </div>
                    </form>
                <?php else: ?>
                    <h5>
                        <i class="fas fa-search me-2"></i>Recherche via l'API UCAD
                    </h5>
                    <form method="get" action="" autocomplete="off">
                        <input type="hidden" name="type" value="ucad">
                        <label for="searchInputUcad" class="form-label fw-semibold">Numéro de carte étudiant UCAD</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control search-input" id="searchInputUcad"
                                placeholder="Ex : 20240CNVU"
                                value="<?php echo htmlspecialchars($search); ?>" required>
                            <button type="submit" class="btn search-btn">
                                <i class="fas fa-search me-2"></i>Rechercher
                            </button>
                        </div>
                        <div class="form-text text-muted mt-1">
                            <i class="fas fa-info-circle me-1"></i>Interroge directement le système d'information de l'UCAD.
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Résultats BDCodif -->
        <?php if ($type === 'codif' && !empty($search)): ?>
        <div class="card result-card mt-4">
            <div class="card-body">

                <?php if ($erreurCodif): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
                    <div>
                        <strong>Erreur de connexion</strong><br>
                        <?php echo htmlspecialchars($erreurCodif); ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Résultats — Base Résidents</h5>
                    <span class="badge bg-primary fs-6"><?php echo count($etudiants); ?> étudiant(s) trouvé(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered text-center align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>N° Carte</th>
                                <!-- <th>Téléphone</th> -->
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
                                    <!-- <td><?php echo htmlspecialchars($etu['telephone'] ?? ''); ?></td> -->
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
                <?php endif; ?>
            </div>
        </div>

        <!-- Résultats API UCAD -->
        <?php elseif ($type === 'ucad' && !empty($search)): ?>
        <div class="card result-card mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Résultats — UCAD (API)</h5>
                    <span class="badge bg-primary fs-6"><?php echo $etudiantUCAD ? '1' : '0'; ?> étudiant(s) trouvé(s)</span>
                </div>

                <?php if (!empty($erreurUCAD)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $erreurUCAD; ?>
                    </div>
                <?php else: ?>
                    <?php
                    $etat = $etudiantUCAD['etat'] ?? 0;
                    $etatLabel = $etat == 1 ? 'Actif' : 'Inactif';
                    $etatClass = $etat == 1 ? 'bg-success' : 'bg-danger';

                    $regimeVal = $etudiantUCAD['payant'] ?? '';
                    if (stripos((string)$regimeVal, 'non') !== false) {
                        $regimeLabel = 'Non payant';
                        $regimeClass = 'bg-success';
                    } elseif (!empty($regimeVal)) {
                        $regimeLabel = 'Payant';
                        $regimeClass = 'bg-danger';
                    } else {
                        $regimeLabel = '-';
                        $regimeClass = 'bg-secondary';
                    }
                    ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered text-center align-middle">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <!-- <th>N° Carte</th> -->
                                    <!-- <th>Téléphone</th> -->
                                    <!-- <th>Email UCAD</th> -->
                                    <th>Faculté</th>
                                    <!-- <th>Département</th> -->
                                    <th>Niveau</th>
                                    <!-- <th>Inscription</th> -->
                                    <th>Année Académique</th>
                                    <th>Régime</th>
                                    <!-- <th>Sexe</th> -->
                                    <!-- <th>Date Naissance</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo htmlspecialchars($etudiantUCAD['nom'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($etudiantUCAD['prenom'] ?? '-'); ?></td>
                                    <!-- <td><?php echo htmlspecialchars($etudiantUCAD['numero_carte'] ?? '-'); ?></td> -->
                                    <!-- <td><?php echo htmlspecialchars($etudiantUCAD['telephone'] ?? '-'); ?></td> -->
                                    <!-- <td><?php echo htmlspecialchars($etudiantUCAD['email_ucad'] ?? '-'); ?></td> -->
                                    <td><?php echo htmlspecialchars($etudiantUCAD['faculte'] ?? '-'); ?></td>
                                    <!-- <td><?php echo htmlspecialchars($etudiantUCAD['departement'] ?? '-'); ?></td> -->
                                    <td><?php echo htmlspecialchars($etudiantUCAD['niveau_formation'] ?? '-'); ?></td>
                                    <!-- <td><?php echo htmlspecialchars($etudiantUCAD['date_inscription'] ?? '-'); ?></td> -->
                                    <td><span style="color:#FFFFFF;font-weight:600; background-color:#b8860b;"><?php echo htmlspecialchars($etudiantUCAD['annee'] ?? '-'); ?></span></td>
                                    <td><span class="badge <?php echo $regimeClass; ?>"><?php echo $regimeLabel; ?></span></td>
                                    <!-- <td><?php echo htmlspecialchars($etudiantUCAD['sexe'] ?? '-'); ?></td> -->
                                    <!-- <td><?php echo htmlspecialchars($etudiantUCAD['date_naissance'] ?? '-'); ?></td> -->
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Résultats Infractions -->
        <?php elseif ($type === 'infractions' && !empty($search)): ?>
        <div class="card result-card mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-end align-items-center mb-4">
                    <?php if ($totalInfractions > 0): ?>
                        <span class="total-infractions-badge"><?php echo $totalInfractions; ?> infraction(s) trouvée(s)</span>
                    <?php else: ?>
                        <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>Aucune infraction</span>
                    <?php endif; ?>
                </div>

                <?php if ($totalInfractions === 0): ?>
                    <div class="text-center py-4 text-success">
                        <i class="fas fa-shield-alt fa-3x mb-3"></i>
                        <h5>Aucune infraction trouvée</h5>
                        <p class="text-muted">Cet étudiant n'apparaît dans aucun procès-verbal enregistré.</p>
                    </div>
                <?php else: ?>
                    <!-- Tableau unifié -->
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered text-center align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Type d'infraction</th>
                                    <th>N° PV</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>N° Carte</th>
                                    <th>Téléphone</th>
                                    <th>Détail</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($toutesInfractions as $i => $inf): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td>
                                        <span class="statut-badge type-<?php echo $inf['type']; ?>">
                                            <?php if ($inf['type'] === 'faux'): ?>
                                                <i class="fas fa-file-times me-1"></i>Faux
                                            <?php elseif ($inf['type'] === 'constat'): ?>
                                                <i class="fas fa-clipboard-list me-1"></i>Constat
                                            <?php else: ?>
                                                <i class="fas fa-bullhorn me-1"></i>Dénonciation
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($inf['numero_pv']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($inf['nom']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($inf['prenoms']); ?></td>
                                    <td><?php echo htmlspecialchars($inf['carte']); ?></td>
                                    <td><?php echo htmlspecialchars($inf['telephone']); ?></td>
                                    <td class="text-start" style="max-width:200px; white-space:normal;">
                                        <?php echo htmlspecialchars($inf['detail']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($inf['date']))); ?></td>
                                    <td>
                                        <span class="statut-badge statut-<?php echo $inf['statut']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $inf['statut'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($inf['url']); ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Voir le détail du PV">
                                            <i class="fas fa-eye me-1"></i>Voir
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- État vide (aucune recherche lancée) -->
        <?php else: ?>
        <div class="card result-card mt-4">
            <div class="card-body">
                <div class="empty-state">
                    <?php if ($type === 'ucad'): ?>
                        <i class="fas fa-university"></i>
                        <h5>Recherche via l'API UCAD</h5>
                        <p>Saisissez le numéro de carte étudiant pour interroger directement le système d'information de l'UCAD.</p>
                    <?php elseif ($type === 'infractions'): ?>
                        <i class="fas fa-user-shield"></i>
                        <h5>Recherche d'infractions</h5>
                        <p>Saisissez le nom, prénom, numéro de carte ou téléphone pour vérifier si un étudiant est impliqué dans un procès-verbal.</p>
                    <?php else: ?>
                        <i class="fas fa-users"></i>
                        <h5>Recherche dans la base des résidents</h5>
                        <p>Saisissez un nom, prénom, numéro étudiant ou téléphone pour rechercher un étudiant résident du campus.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

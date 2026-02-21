<?php
session_start();
require_once __DIR__ . '/config/paths.php';

// Protection : rediriger vers login si non connecté
if (!isset($_SESSION['utilisateur_id'])) {
    redirect('/login');
    exit();
}

// Forcer le changement de mot de passe si première connexion
if (!empty($_SESSION['doit_changer_mdp'])) {
    redirect('/change-password');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USCOUD - Tableau de Bord</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Common CSS -->
    <link rel="stylesheet" href="assets/css/common.css">

    <style>
        /* Footer Styles */
        .footer-custom {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 40px 0 20px 0;
            margin-top: 60px;
        }

        .footer-custom h5 {
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .footer-custom a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }

        .footer-custom a:hover {
            color: var(--primary-color);
            padding-left: 5px;
        }

        .footer-custom ul {
            list-style: none;
            padding: 0;
        }

        .footer-custom ul li {
            margin-bottom: 10px;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Stats Cards */
        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-card .card-body {
            padding: 25px;
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stats-card p {
            font-size: 0.95rem;
            color: #6c757d;
            margin-bottom: 0;
        }

        .stats-card .icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .card-primary {
            border-left: 4px solid var(--primary-color);
        }

        .card-warning {
            border-left: 4px solid var(--warning-color);
        }

        .card-success {
            border-left: 4px solid var(--success-color);
        }

        .card-danger {
            border-left: 4px solid var(--danger-color);
        }

        /* Module Cards */
        .module-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            overflow: hidden;
            height: 100%;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .module-card .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px;
            border: none;
        }

        .module-card .card-header h5 {
            margin: 0;
            font-weight: 600;
        }

        .module-card .card-body {
            padding: 25px;
        }

        .module-card .btn-module {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .module-card .btn-module:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .chart-container h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
        }

        /* Recent Activity */
        .activity-item {
            padding: 15px;
            border-left: 3px solid var(--primary-color);
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 0 8px 8px 0;
        }

        .activity-item:hover {
            background: #e9ecef;
        }

        .activity-time {
            font-size: 0.85rem;
            color: #6c757d;
        }

        /* Quick Actions */
        .quick-action-btn {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
            margin-bottom: 15px;
        }

        .quick-action-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <?php
require_once __DIR__ . '/data/dashboard_functions.php';

// Récupérer les statistiques réelles
$globalStats = getGlobalStatistics();
$latestPVs = getLatestPVs();
$monthlyStats = getMonthlyStatistics();
$recentActivities = getRecentActivities();
$incidentTypes = getIncidentTypes();

$bannerText = "Système de Gestion des Procès-Verbaux - USCOUD";
include __DIR__ . '/includes/head.php';
?>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-chart-line me-3"></i>Tableau de Bord</h1>
                    <p>Vue d'ensemble de la gestion des procès-verbaux</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <p class="mb-0"><i class="fas fa-calendar-alt me-2"></i><?php echo date('d/m/Y'); ?></p>
                    <p class="mb-0"><i class="fas fa-clock me-2"></i><?php echo date('H:i'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistiques Globales -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card card-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">Total PV</p>
                                <h3 id="totalGlobal"><?php echo number_format($globalStats['total_pv']); ?></h3>
                            </div>
                            <div class="icon-wrapper bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stats-card card-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">En cours</p>
                                <h3 class="text-warning" id="enCoursGlobal"><?php echo number_format($globalStats['en_cours']); ?></h3>
                            </div>
                            <div class="icon-wrapper bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stats-card card-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">Traités</p>
                                <h3 class="text-success" id="traitesGlobal"><?php echo number_format($globalStats['traites']); ?></h3>
                            </div>
                            <div class="icon-wrapper bg-success bg-opacity-10 text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stats-card card-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">Ce mois</p>
                                <h3 class="text-danger" id="ceMois"><?php echo number_format($globalStats['ce_mois']); ?></h3>
                            </div>
                            <div class="icon-wrapper bg-danger bg-opacity-10 text-danger">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aperçu Rapide -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="chart-container">
                    <h5><i class="fas fa-list me-2"></i>Top 5 - Faux et Usage de Faux</h5>
                    <ul class="list-group list-group-flush" id="listFaux">
                        <?php if (empty($latestPVs['faux'])): ?>
                            <li class="list-group-item text-center text-muted">Aucun PV enregistré</li>
                        <?php else: ?>
                            <?php foreach ($latestPVs['faux'] as $pv): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($pv['nom'] . ' ' . $pv['prenoms']); ?></strong>
                                        <span class="badge bg-<?php echo $pv['statut_color']; ?> ms-2">
                                            <?php echo $pv['statut']; ?>
                                        </span>
                                    </div>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($pv['created_at'])); ?></small>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h5><i class="fas fa-list me-2"></i>Top 5 - Constat d'Incident</h5>
                    <ul class="list-group list-group-flush" id="listConstat">
                        <?php if (empty($latestPVs['constat'])): ?>
                            <li class="list-group-item text-center text-muted">Aucun PV enregistré</li>
                        <?php else: ?>
                            <?php foreach ($latestPVs['constat'] as $pv): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($pv['lieu_incident']); ?></strong>
                                        <span class="badge bg-<?php echo $pv['statut_color']; ?> ms-2">
                                            <?php echo $pv['statut']; ?>
                                        </span>
                                    </div>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($pv['created_at'])); ?></small>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h5><i class="fas fa-list me-2"></i>Top 5 - Dénonciations</h5>
                    <ul class="list-group list-group-flush" id="listDenonciation">
                        <?php if (empty($latestPVs['denonciation'])): ?>
                            <li class="list-group-item text-center text-muted">Aucun PV enregistré</li>
                        <?php else: ?>
                            <?php foreach ($latestPVs['denonciation'] as $pv): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($pv['denonciateur_nom']); ?></strong>
                                        <span class="badge bg-<?php echo $pv['statut_color']; ?> ms-2">
                                            <?php echo $pv['statut']; ?>
                                        </span>
                                    </div>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($pv['created_at'])); ?></small>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>


        <!-- Statistiques et Graphiques -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-bar me-2"></i>Répartition des Procès-Verbaux</h5>
                    <div class="text-center">
                        <div class="d-flex justify-content-around align-items-end" style="height: 200px;">
                            <div class="text-center">
                                <div style="width: 60px; height: <?php echo max(20, min(120, $globalStats['details']['faux']['total'] * 10)); ?>px; background: rgba(55, 119, 176, 0.8); border-radius: 4px; margin: 0 auto;"></div>
                                <small class="d-block mt-2">Faux</small>
                                <strong><?php echo $globalStats['details']['faux']['total']; ?></strong>
                            </div>
                            <div class="text-center">
                                <div style="width: 60px; height: <?php echo max(20, min(120, $globalStats['details']['constat']['total'] * 10)); ?>px; background: rgba(243, 156, 18, 0.8); border-radius: 4px; margin: 0 auto;"></div>
                                <small class="d-block mt-2">Constat</small>
                                <strong><?php echo $globalStats['details']['constat']['total']; ?></strong>
                            </div>
                            <div class="text-center">
                                <div style="width: 60px; height: <?php echo max(20, min(120, $globalStats['details']['denonciation']['total'] * 10)); ?>px; background: rgba(39, 174, 96, 0.8); border-radius: 4px; margin: 0 auto;"></div>
                                <small class="d-block mt-2">Dénonciation</small>
                                <strong><?php echo $globalStats['details']['denonciation']['total']; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-pie me-2"></i>Statuts Globaux</h5>
                    <div class="text-center">
                        <?php 
                        $totalForPie = $globalStats['en_cours'] + $globalStats['traites'];
                        $enCoursPercent = $totalForPie > 0 ? ($globalStats['en_cours'] / $totalForPie) * 100 : 0;
                        $traitesPercent = $totalForPie > 0 ? ($globalStats['traites'] / $totalForPie) * 100 : 0;
                        ?>
                        <div style="width: 150px; height: 150px; margin: 0 auto; position: relative;">
                            <div style="width: 100%; height: 100%; border-radius: 50%; background: conic-gradient(rgba(243, 156, 18, 0.8) 0% <?php echo $enCoursPercent; ?>%, rgba(39, 174, 96, 0.8) <?php echo $enCoursPercent; ?>% 100%);"></div>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <strong><?php echo $totalForPie; ?></strong>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="badge me-2" style="background: rgba(243, 156, 18, 0.8);">En cours: <?php echo $globalStats['en_cours']; ?></span>
                            <span class="badge" style="background: rgba(39, 174, 96, 0.8);">Traités: <?php echo $globalStats['traites']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques supplémentaires -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-line me-2"></i>Évolution Mensuelle</h5>
                    <div style="height: 200px; position: relative; padding: 20px;">
                        <div style="position: absolute; bottom: 40px; left: 20px; right: 20px; height: 120px; display: flex; align-items: flex-end; justify-content: space-between;">
                            <?php 
                            $maxMonthly = max(array_column($monthlyStats, 'total'));
                            foreach ($monthlyStats as $month): 
                                $height = $maxMonthly > 0 ? ($month['total'] / $maxMonthly) * 120 : 0;
                            ?>
                                <div style="width: 30px; height: <?php echo max(2, $height); ?>px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;" title="<?php echo $month['month_name'] . ': ' . $month['total']; ?> PV"></div>
                            <?php endforeach; ?>
                        </div>
                        <div style="position: absolute; bottom: 20px; left: 20px; right: 20px; display: flex; justify-content: space-between; font-size: 11px; color: #666;">
                            <span>Jan</span><span>Fév</span><span>Mar</span><span>Avr</span><span>Mai</span><span>Jun</span><span>Jul</span><span>Aoû</span><span>Sep</span><span>Oct</span><span>Nov</span><span>Déc</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-doughnut me-2"></i>Types d'Incidents</h5>
                    <div class="text-center">
                        <div style="width: 150px; height: 150px; margin: 0 auto; position: relative;">
                            <div style="width: 100%; height: 100%; border-radius: 50%; background: conic-gradient(rgba(231, 76, 60, 0.8) 0% 25%, rgba(243, 156, 18, 0.8) 25% 50%, rgba(52, 152, 219, 0.8) 50% 75%, rgba(149, 165, 166, 0.8) 75% 100%);"></div>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <strong>5</strong>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="small">
                                <span class="badge me-1" style="background: rgba(231, 76, 60, 0.8);">Bagarre: 1</span>
                                <span class="badge me-1" style="background: rgba(243, 156, 18, 0.8);">Vol: 1</span>
                                <span class="badge me-1" style="background: rgba(52, 152, 219, 0.8);">Accident: 1</span>
                                <span class="badge" style="background: rgba(149, 165, 166, 0.8);">Incendie: 2</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Rapides -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="chart-container">
                    <h5><i class="fas fa-bolt me-2"></i>Actions Rapides</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <a href="pages/faux/faux" class="quick-action-btn">
                                <i class="fas fa-plus-square me-2 text-success"></i>Nouveau PV Faux
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="pages/constat/constat" class="quick-action-btn">
                                <i class="fas fa-plus-square me-2 text-success"></i>Nouveau Constat
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="pages/denonciation/denonciation" class="quick-action-btn">
                                <i class="fas fa-plus-square me-2 text-success"></i>Nouvelle Dénonciation
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="#" class="quick-action-btn" onclick="exportData()">
                                <i class="fas fa-download me-2"></i>Exporter
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Fonctions utilitaires
        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };

        // Fonctions de génération de données fictives
        function generateFakePvFaux() {
            const noms = ['Diop', 'Ndiaye', 'Fall', 'Sarr', 'Sow'];
            const prenoms = ['Moussa', 'Fatou', 'Aminata', 'Abdou', 'Cheikh'];
            const statuts = ['en_cours', 'traite'];
            
            return Array.from({length: 5}, (_, i) => {
                const date = new Date(2024, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) + 1);
                return {
                    id: Date.now() + i,
                    nom: noms[Math.floor(Math.random() * noms.length)],
                    prenom: prenoms[Math.floor(Math.random() * prenoms.length)],
                    statut: statuts[Math.floor(Math.random() * statuts.length)],
                    date: date.toISOString().split('T')[0]
                };
            });
        }

        function generateFakePvConstat() {
            const lieux = ['Campus Social ESP', 'Campus Social UCAD', 'Résidence Claudel', 'Cité Mixte', 'FASTEF'];
            const natures = ['Bagarre', 'Vol', 'Accident', 'Incendie', 'Vandalisme'];
            const statuts = ['en_cours', 'traite'];
            
            return Array.from({length: 5}, (_, i) => {
                const date = new Date(2024, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) + 1);
                return {
                    id: Date.now() + 100 + i,
                    lieuIncident: lieux[Math.floor(Math.random() * lieux.length)],
                    natureIncident: natures[Math.floor(Math.random() * natures.length)],
                    statut: statuts[Math.floor(Math.random() * statuts.length)],
                    dateIncident: date.toISOString().split('T')[0]
                };
            });
        }

        function generateFakePvDenonciation() {
            const noms = ['Diouf', 'Mbaye', 'Sy', 'Kane', 'Diallo'];
            const prenoms = ['Mariama', 'Ibrahima', 'Ousmane', 'Khady', 'Serigne'];
            const statuts = ['en_cours', 'traite'];
            
            return Array.from({length: 5}, (_, i) => {
                const date = new Date(2024, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) + 1);
                return {
                    id: Date.now() + 200 + i,
                    soussigne: noms[Math.floor(Math.random() * noms.length)],
                    soussignePrenom: prenoms[Math.floor(Math.random() * prenoms.length)],
                    statut: statuts[Math.floor(Math.random() * statuts.length)],
                    datePV: date.toISOString().split('T')[0]
                };
            });
        }

        // Fonction pour créer les graphiques
        function createCharts(pvFaux, pvConstat, pvDenonciation) {
            // Désactiver les animations pour éviter les boucles infinies
            Chart.defaults.animation.duration = 0;
            Chart.defaults.animation.easing = 'linear';
            Chart.defaults.animation.loop = false;

            // Graphique 1: Répartition des Procès-Verbaux
            const pvCtx = document.getElementById('pvChart');
            if (pvCtx) {
                // Vérifier si le graphique existe déjà et le détruire
                if (pvCtx.chart) {
                    pvCtx.chart.destroy();
                }
                
                pvCtx.chart = new Chart(pvCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['Faux et Usage', 'Constat d\'Incident', 'Dénonciation'],
                        datasets: [{
                            label: 'Total',
                            data: [pvFaux.length, pvConstat.length, pvDenonciation.length],
                            backgroundColor: ['rgba(55, 119, 176, 0.8)', 'rgba(243, 156, 18, 0.8)', 'rgba(39, 174, 96, 0.8)'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }

            // Graphique 2: Statuts Globaux
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                // Vérifier si le graphique existe déjà et le détruire
                if (statusCtx.chart) {
                    statusCtx.chart.destroy();
                }
                
                const totalEnCours = pvFaux.filter(p => p.statut === 'en_cours').length + 
                                   pvConstat.filter(p => p.statut === 'en_cours').length + 
                                   pvDenonciation.filter(p => p.statut === 'en_cours').length;
                const totalTraites = pvFaux.filter(p => p.statut === 'traite').length + 
                                    pvConstat.filter(p => p.statut === 'traite').length + 
                                    pvDenonciation.filter(p => p.statut === 'traite').length;

                statusCtx.chart = new Chart(statusCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['En cours', 'Traités'],
                        datasets: [{
                            data: [totalEnCours, totalTraites],
                            backgroundColor: ['rgba(243, 156, 18, 0.8)', 'rgba(39, 174, 96, 0.8)'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }

            // Graphique 3: Évolution Mensuelle
            const evolutionCtx = document.getElementById('evolutionChart');
            if (evolutionCtx) {
                // Vérifier si le graphique existe déjà et le détruire
                if (evolutionCtx.chart) {
                    evolutionCtx.chart.destroy();
                }
                
                const monthlyData = generateMonthlyData([...pvFaux, ...pvConstat, ...pvDenonciation]);
                
                evolutionCtx.chart = new Chart(evolutionCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                        datasets: [{
                            label: 'PV Créés',
                            data: monthlyData,
                            borderColor: 'rgba(55, 119, 176, 1)',
                            backgroundColor: 'rgba(55, 119, 176, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }

            // Graphique 4: Types d'Incidents
            const incidentCtx = document.getElementById('incidentChart');
            if (incidentCtx) {
                // Vérifier si le graphique existe déjà et le détruire
                if (incidentCtx.chart) {
                    incidentCtx.chart.destroy();
                }
                
                const incidentTypes = {};
                pvConstat.forEach(pv => {
                    const nature = pv.natureIncident || 'Non spécifié';
                    incidentTypes[nature] = (incidentTypes[nature] || 0) + 1;
                });

                incidentCtx.chart = new Chart(incidentCtx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: Object.keys(incidentTypes),
                        datasets: [{
                            data: Object.values(incidentTypes),
                            backgroundColor: [
                                'rgba(231, 76, 60, 0.8)', 'rgba(243, 156, 18, 0.8)', 
                                'rgba(52, 152, 219, 0.8)', 'rgba(149, 165, 166, 0.8)',
                                'rgba(52, 73, 94, 0.8)', 'rgba(155, 89, 182, 0.8)'
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
        }

        // Fonction pour générer les données mensuelles
        function generateMonthlyData(allPV) {
            const monthlyData = new Array(12).fill(0);
            allPV.forEach(pv => {
                const dateField = pv.date || pv.datePV || pv.dateIncident;
                if (dateField) {
                    const date = new Date(dateField);
                    if (!isNaN(date.getTime())) {
                        monthlyData[date.getMonth()]++;
                    }
                }
            });
            return monthlyData;
        }

        // Fonction pour exporter les données
        function exportData() {
            const pvFaux = JSON.parse(localStorage.getItem('pvData')) || [];
            const pvConstat = JSON.parse(localStorage.getItem('pvConstat')) || [];
            const pvDenonciation = JSON.parse(localStorage.getItem('pvDenonciation')) || [];
            
            const allData = {
                faux: pvFaux,
                constat: pvConstat,
                denonciation: pvDenonciation,
                exportDate: new Date().toISOString()
            };
            
            const dataStr = JSON.stringify(allData, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = `uscoud_export_${new Date().toISOString().split('T')[0]}.json`;
            link.click();
            
            URL.revokeObjectURL(url);
        }

        // Fonction pour formater les dates
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'Date invalide';
            return date.toLocaleDateString('fr-FR');
        }

        // Initialisation principale
        document.addEventListener('DOMContentLoaded', function() {
            // Générer des données fictives si nécessaire
            if (!localStorage.getItem('pvMockSeeded')) {
                localStorage.setItem('pvData', JSON.stringify(generateFakePvFaux()));
                localStorage.setItem('pvConstat', JSON.stringify(generateFakePvConstat()));
                localStorage.setItem('pvDenonciation', JSON.stringify(generateFakePvDenonciation()));
                localStorage.setItem('pvMockSeeded', 'true');
            }

            // Récupérer les données
            const pvFaux = JSON.parse(localStorage.getItem('pvData')) || [];
            const pvConstat = JSON.parse(localStorage.getItem('pvConstat')) || [];
            const pvDenonciation = JSON.parse(localStorage.getItem('pvDenonciation')) || [];

            // Calculer les statistiques globales
            const totalGlobal = pvFaux.length + pvConstat.length + pvDenonciation.length;
            const enCoursGlobal = pvFaux.filter(p => p.statut === 'en_cours').length +
                                pvConstat.filter(p => p.statut === 'en_cours').length +
                                pvDenonciation.filter(p => p.statut === 'en_cours').length;
            const traitesGlobal = pvFaux.filter(p => p.statut === 'traite').length +
                                 pvConstat.filter(p => p.statut === 'traite').length +
                                 pvDenonciation.filter(p => p.statut === 'traite').length;

            // Mettre à jour l'interface
            setText('totalGlobal', totalGlobal);
            setText('enCoursGlobal', enCoursGlobal);
            setText('traitesGlobal', traitesGlobal);
            setText('fauxTotal', pvFaux.length);
            setText('fauxEnCours2', pvFaux.filter(p => p.statut === 'en_cours').length);
            setText('fauxTraites2', pvFaux.filter(p => p.statut === 'traite').length);
            setText('constatTotal', pvConstat.length);
            setText('constatEnCours2', pvConstat.filter(p => p.statut === 'en_cours').length);
            setText('constatTraites2', pvConstat.filter(p => p.statut === 'traite').length);
            setText('denonciationTotal', pvDenonciation.length);
            setText('denonciationEnCours2', pvDenonciation.filter(p => p.statut === 'en_cours').length);
            setText('denonciationTraites2', pvDenonciation.filter(p => p.statut === 'traite').length);

            // Créer les graphiques
            createCharts(pvFaux, pvConstat, pvDenonciation);
        });
    </script>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-0 text-white-50">
                        &copy; <?php echo date('Y'); ?> USCOUD - Tous droits r&eacute;serv&eacute;s
                    </p>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>
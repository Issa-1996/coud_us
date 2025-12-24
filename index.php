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

    <style>
        :root {
            --primary-color: #3777B0;
            --secondary-color: #2c5f8d;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 140px;
        }

        /* Navbar Styles - Style COUD'MAINT */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            flex-wrap: wrap;
            z-index: 1000;
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo-container img {
            height: 40px;
        }

        .logo-container span {
            margin-left: 10px;
            font-weight: bold;
            font-size: 18px;
            color: #333;
        }

        /* Navigation desktop */
        .desktop-nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .desktop-nav li {
            margin: 0 10px;
        }

        .desktop-nav a {
            text-decoration: none;
            color: #333;
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .desktop-nav a:hover {
            background-color: #e9ecef;
        }

        .desktop-nav a.active {
            background-color: var(--primary-color);
            color: white;
        }

        .desktop-nav i {
            margin-right: 5px;
        }

        /* Menu hamburger */
        .hamburger {
            display: none;
            cursor: pointer;
            font-size: 24px;
            color: #333;
        }

        /* Navigation mobile */
        .mobile-nav {
            display: none;
            width: 100%;
        }

        .mobile-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .mobile-nav li {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .mobile-nav a {
            text-decoration: none;
            color: #333;
            display: flex;
            align-items: center;
        }

        .mobile-nav a.active {
            color: var(--primary-color);
            font-weight: 600;
        }

        .mobile-nav i {
            margin-right: 10px;
        }

        .mobile-nav.active {
            display: block;
        }

        /* Banner */
        .banner {
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            text-align: center;
            padding: 10px;
            background: var(--primary-color);
            color: white;
            z-index: 999;
        }

        /* Media queries */
        @media (max-width: 768px) {
            .desktop-nav {
                display: none;
            }

            .hamburger {
                display: block;
            }

            body {
                padding-top: 120px;
            }
        }

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
                                <h3 id="totalGlobal">0</h3>
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
                                <h3 class="text-warning" id="enCoursGlobal">0</h3>
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
                                <h3 class="text-success" id="traitesGlobal">0</h3>
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
                                <h3 class="text-danger" id="ceMois">0</h3>
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
                    <ul class="list-group list-group-flush" id="listFaux"></ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h5><i class="fas fa-list me-2"></i>Top 5 - Constat d'Incident</h5>
                    <ul class="list-group list-group-flush" id="listConstat"></ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h5><i class="fas fa-list me-2"></i>Top 5 - Dénonciations</h5>
                    <ul class="list-group list-group-flush" id="listDenonciation"></ul>
                </div>
            </div>
        </div>

        <!-- Modules d'Accès Rapide -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card module-card">
                    <div class="card-header">
                        <h5><i class="fas fa-id-card me-2"></i>Faux et Usage de Faux</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-4">Gestion des procès-verbaux d'appréhension pour faux documents (cartes d'étudiant, CNI, passeports).</p>
                        <div id="detailsFaux" class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-circle text-primary me-2" style="font-size: 0.5rem;"></i>Total</span>
                                <strong id="fauxTotal">0</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-circle text-warning me-2" style="font-size: 0.5rem;"></i>En cours</span>
                                <strong id="fauxEnCours2">0</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-circle text-success me-2" style="font-size: 0.5rem;"></i>Traités</span>
                                <strong id="fauxTraites2">0</strong>
                            </div>
                        </div>
                        <a href="pages/faux/faux.php" class="btn btn-module w-100">
                            <i class="fas fa-arrow-right me-2"></i>Accéder
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card module-card">
                    <div class="card-header">
                        <h5><i class="fas fa-file-medical me-2"></i>Constat d'Incident</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-4">Gestion des constats d'incidents avec blessés physiques, dommages matériels, assaillants et témoignages.</p>
                        <div id="detailsConstat" class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-circle text-primary me-2" style="font-size: 0.5rem;"></i>Total</span>
                                <strong id="constatTotal">0</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-circle text-warning me-2" style="font-size: 0.5rem;"></i>En cours</span>
                                <strong id="constatEnCours2">0</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-circle text-success me-2" style="font-size: 0.5rem;"></i>Traités</span>
                                <strong id="constatTraites2">0</strong>
                            </div>
                        </div>
                        <a href="pages/constat/constat.php" class="btn btn-module w-100">
                            <i class="fas fa-arrow-right me-2"></i>Accéder
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card module-card">
                    <div class="card-header">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Dénonciation</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-4">Gestion des dénonciations pour violence, harcèlement, diffamation et vol avec suivi des victimes et auteurs.</p>
                        <div id="detailsDenonciation" class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-circle text-primary me-2" style="font-size: 0.5rem;"></i>Total</span>
                                <strong id="denonciationTotal">0</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-circle text-warning me-2" style="font-size: 0.5rem;"></i>En cours</span>
                                <strong id="denonciationEnCours2">0</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="fas fa-circle text-success me-2" style="font-size: 0.5rem;"></i>Traités</span>
                                <strong id="denonciationTraites2">0</strong>
                            </div>
                        </div>
                        <a href="pages/denonciation/denonciation.php" class="btn btn-module w-100">
                            <i class="fas fa-arrow-right me-2"></i>Accéder
                        </a>
                    </div>
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
                                <div style="width: 60px; height: 120px; background: rgba(55, 119, 176, 0.8); border-radius: 4px; margin: 0 auto;"></div>
                                <small class="d-block mt-2">Faux</small>
                                <strong>5</strong>
                            </div>
                            <div class="text-center">
                                <div style="width: 60px; height: 120px; background: rgba(243, 156, 18, 0.8); border-radius: 4px; margin: 0 auto;"></div>
                                <small class="d-block mt-2">Constat</small>
                                <strong>5</strong>
                            </div>
                            <div class="text-center">
                                <div style="width: 60px; height: 120px; background: rgba(39, 174, 96, 0.8); border-radius: 4px; margin: 0 auto;"></div>
                                <small class="d-block mt-2">Dénonciation</small>
                                <strong>5</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-pie me-2"></i>Statuts Globaux</h5>
                    <div class="text-center">
                        <div style="width: 150px; height: 150px; margin: 0 auto; position: relative;">
                            <div style="width: 100%; height: 100%; border-radius: 50%; background: conic-gradient(rgba(243, 156, 18, 0.8) 0% 50%, rgba(39, 174, 96, 0.8) 50% 100%);"></div>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <strong>15</strong>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="badge me-2" style="background: rgba(243, 156, 18, 0.8);">En cours: 8</span>
                            <span class="badge" style="background: rgba(39, 174, 96, 0.8);">Traités: 7</span>
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
                            <div style="width: 30px; height: 40px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;"></div>
                            <div style="width: 30px; height: 60px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;"></div>
                            <div style="width: 30px; height: 45px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;"></div>
                            <div style="width: 30px; height: 80px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;"></div>
                            <div style="width: 30px; height: 70px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;"></div>
                            <div style="width: 30px; height: 90px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;"></div>
                            <div style="width: 30px; height: 75px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;"></div>
                            <div style="width: 30px; height: 85px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;"></div>
                            <div style="width: 30px; height: 95px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;"></div>
                            <div style="width: 30px; height: 65px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;"></div>
                            <div style="width: 30px; height: 55px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;"></div>
                            <div style="width: 30px; height: 50px; background: rgba(55, 119, 176, 0.3); border-radius: 2px;"></div>
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
                            <a href="pages/faux/faux.php" class="quick-action-btn">
                                <i class="fas fa-plus-square me-2 text-success"></i>Nouveau PV Faux
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="pages/constat/constat.php" class="quick-action-btn">
                                <i class="fas fa-plus-square me-2 text-success"></i>Nouveau Constat
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="pages/denonciation/denonciation.php" class="quick-action-btn">
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

        <!-- Activités Récentes -->
        <div class="row">
            <div class="col-md-8">
                <div class="chart-container">
                    <h5><i class="fas fa-history me-2"></i>Activités Récentes</h5>
                    <div id="recentActivities">
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-primary me-2">
                                        <i class="fas fa-id-card me-1"></i>Faux et Usage de Faux
                                    </span>
                                    <strong>Diop Moussa</strong>
                                    <span class="badge bg-warning ms-2">En cours</span>
                                </div>
                                <span class="activity-time">23/12/2024</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-success me-2">
                                        <i class="fas fa-file-medical me-1"></i>Constat d'Incident
                                    </span>
                                    <strong>Campus Social ESP - Bagarre</strong>
                                    <span class="badge bg-success ms-2">Traité</span>
                                </div>
                                <span class="activity-time">22/12/2024</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-danger me-2">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Dénonciation
                                    </span>
                                    <strong>Mbaye Ibrahima</strong>
                                    <span class="badge bg-warning ms-2">En cours</span>
                                </div>
                                <span class="activity-time">21/12/2024</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-primary me-2">
                                        <i class="fas fa-id-card me-1"></i>Faux et Usage de Faux
                                    </span>
                                    <strong>Fall Fatou</strong>
                                    <span class="badge bg-success ms-2">Traité</span>
                                </div>
                                <span class="activity-time">20/12/2024</span>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-success me-2">
                                        <i class="fas fa-file-medical me-1"></i>Constat d'Incident
                                    </span>
                                    <strong>Résidence Claudel - Vol</strong>
                                    <span class="badge bg-warning ms-2">En cours</span>
                                </div>
                                <span class="activity-time">19/12/2024</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="chart-container">
                    <h5><i class="fas fa-bolt me-2"></i>Actions Rapides</h5>
                    <a href="pages/faux/faux.php" class="quick-action-btn">
                        <i class="fas fa-plus-circle me-2"></i>Nouveau PV Faux
                    </a>
                    <a href="pages/constat/constat.php" class="quick-action-btn">
                        <i class="fas fa-plus-circle me-2"></i>Nouveau Constat
                    </a>
                    <a href="pages/denonciation/denonciation.php" class="quick-action-btn">
                        <i class="fas fa-plus-circle me-2"></i>Nouvelle Dénonciation
                    </a>
                    <a href="#" class="quick-action-btn">
                        <i class="fas fa-download me-2"></i>Export Global
                    </a>
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
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-shield-alt me-2"></i>USCOUD</h5>
                    <p class="text-white-50">
                        Système de Gestion des Procès-Verbaux<br>
                        Unité de Sécurité du Centre des Œuvres Universitaires de Dakar
                    </p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-link me-2"></i>Liens Rapides</h5>
                    <ul>
                        <li><a href="pages/faux/faux.php"><i class="fas fa-chevron-right me-2"></i>Faux et Usage de Faux</a></li>
                        <li><a href="pages/constat/constat.php"><i class="fas fa-chevron-right me-2"></i>Constat d'Incident</a></li>
                        <li><a href="pages/denonciation/denonciation.php"><i class="fas fa-chevron-right me-2"></i>Dénonciation</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right me-2"></i>Statistiques</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-envelope me-2"></i>Contact</h5>
                    <ul>
                        <li><i class="fas fa-map-marker-alt me-2"></i>UCAD, Dakar, Sénégal</li>
                        <li><i class="fas fa-phone me-2"></i>+221 33 XXX XX XX</li>
                        <li><i class="fas fa-envelope me-2"></i>contact@uscoud.sn</li>
                    </ul>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1)">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-0 text-white-50">
                        &copy; <?php echo date('Y'); ?> USCOUD - Tous droits réservés | Développé avec <i class="fas fa-heart text-danger"></i>
                    </p>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>
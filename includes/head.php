<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$basePath = '';

// Test de redirection selon le nom de la page
switch($currentPage) {
    case 'faux.php':
        $basePath = '../../'; // Depuis pages/faux/, il faut remonter de 2 niveaux
        break;
    case 'constat.php':
        $basePath = '../../'; // Depuis pages/constat/, il faut remonter de 2 niveaux
        break;
    case 'denonciation.php':
        $basePath = '../../'; // Depuis pages/denonciation/, il faut remonter de 2 niveaux
        break;
    case 'recherche.php':
        $basePath = '../../'; // Depuis pages/recherche/, il faut remonter de 2 niveaux
        break;
    default:
        $basePath = ''; // Pour index.php et autres pages à la racine
        break;
}

if (!isset($bannerText)) {
    $bannerText = "Système de Gestion des Procès-Verbaux - USCOUD";
}
?>
<!-- Navbar - Style COUD'MAINT -->
<header>
    <div class="logo-container">
        <img src="<?php echo $basePath; ?>assets/logo.png" alt="Logo COUD">
        <span>USCOUD PV</span>
    </div>

    <div class="hamburger" onclick="toggleMenu()">☰</div>

    <nav class="desktop-nav">
        <ul>
            <li><a href="<?php echo $basePath; ?>index.php" <?php if($currentPage == 'index.php') echo 'class="active"'; ?>><i class="fa fa-home"></i>Accueil</a></li>
            <li><a href="<?php echo $basePath; ?>pages/faux/faux.php" <?php if($currentPage == 'faux.php') echo 'class="active"'; ?>><i class="fas fa-id-card"></i>Faux</a></li>
            <li><a href="<?php echo $basePath; ?>pages/constat/constat.php" <?php if($currentPage == 'constat.php') echo 'class="active"'; ?>><i class="fas fa-file-medical"></i>Constat</a></li>
            <li><a href="<?php echo $basePath; ?>pages/denonciation/denonciation.php" <?php if($currentPage == 'denonciation.php') echo 'class="active"'; ?>><i class="fas fa-exclamation-triangle"></i>Dénonciation</a></li>
            <li><a href="<?php echo $basePath; ?>pages/recherche/recherche.php" <?php if($currentPage == 'recherche.php') echo 'class="active"'; ?>><i class="fas fa-search"></i>Recherche</a></li>
            <li><a href="#"><i class="fas fa-right-from-bracket"></i>Déconnexion</a></li>
        </ul>
    </nav>

    <nav class="mobile-nav" id="mobileNav">
        <ul>
            <li><a href="<?php echo $basePath; ?>index.php"><i class="fa fa-home"></i>Accueil</a></li>
            <li><a href="<?php echo $basePath; ?>pages/faux/faux.php"><i class="fas fa-id-card"></i>Faux et Usage de Faux</a></li>
            <li><a href="<?php echo $basePath; ?>pages/constat/constat.php"><i class="fas fa-file-medical"></i>Constat d'Incident</a></li>
            <li><a href="<?php echo $basePath; ?>pages/denonciation/denonciation.php"><i class="fas fa-exclamation-triangle"></i>Dénonciation</a></li>
            <li><a href="<?php echo $basePath; ?>pages/recherche/recherche.php"><i class="fas fa-search"></i>Recherche</a></li>
            <li><a href="#"><i class="fas fa-right-from-bracket"></i>Déconnexion</a></li>
        </ul>
    </nav>
</header>

<div class="banner">
    <p><?php echo htmlspecialchars($bannerText, ENT_QUOTES, 'UTF-8'); ?></p>
</div>

<script>
    function toggleMenu() {
        const mobileNav = document.getElementById('mobileNav');
        mobileNav.classList.toggle('active');
    }
</script>

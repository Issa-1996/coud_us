<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/paths.php';

// Protection : rediriger vers login si non connecté
if (!isset($_SESSION['utilisateur_id'])) {
    redirect('/login');
    exit();
}

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
    case 'utilisateurs.php':
        $basePath = '../../'; // Depuis pages/utilisateur/, il faut remonter de 2 niveaux
        break;
    default:
        $basePath = ''; // Pour index.php et autres pages à la racine
        break;
}

if (!isset($bannerText)) {
    $bannerText = "Système de Gestion des Procès-Verbaux - USCOUD";
}

// Informations utilisateur connecté
$nomUtilisateur = htmlspecialchars(($_SESSION['nom'] ?? '') . ' ' . ($_SESSION['prenoms'] ?? ''));
$roleUtilisateur = htmlspecialchars($_SESSION['role'] ?? '');
?>
<!-- Navbar - Style COUD'MAINT -->
<header>
    <div class="logo-container">
        <img src="<?php echo $basePath; ?>assets/logo.png" alt="Logo COUD">
        <span>USCOUD PV</span>
    </div>

    <div class="hamburger" onclick="toggleMenu()">&#9776;</div>

    <nav class="desktop-nav">
        <ul>
            <li><a href="<?php echo $basePath; ?>index" <?php if($currentPage == 'index.php') echo 'class="active"'; ?>><i class="fa fa-home"></i>Accueil</a></li>
            <li><a href="<?php echo $basePath; ?>pages/faux/faux" <?php if($currentPage == 'faux.php') echo 'class="active"'; ?>><i class="fas fa-id-card"></i>Faux</a></li>
            <li><a href="<?php echo $basePath; ?>pages/constat/constat" <?php if($currentPage == 'constat.php') echo 'class="active"'; ?>><i class="fas fa-file-medical"></i>Constat</a></li>
            <li><a href="<?php echo $basePath; ?>pages/denonciation/denonciation" <?php if($currentPage == 'denonciation.php') echo 'class="active"'; ?>><i class="fas fa-exclamation-triangle"></i>D&eacute;nonciation</a></li>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <li><a href="<?php echo $basePath; ?>pages/recherche/recherche" <?php if($currentPage == 'recherche.php') echo 'class="active"'; ?>><i class="fas fa-search"></i>Recherche</a></li>
            <?php endif; ?>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <li><a href="<?php echo $basePath; ?>pages/utilisateur/utilisateurs" <?php if($currentPage == 'utilisateurs.php') echo 'class="active"'; ?>><i class="fas fa-users-cog"></i>Utilisateurs</a></li>
            <?php endif; ?>
            <li class="nav-user-dropdown">
                <a href="#" onclick="toggleUserDropdown(event)"><i class="fas fa-user-circle"></i><?php echo $nomUtilisateur; ?> <i class="fas fa-caret-down"></i></a>
                <ul class="user-dropdown-menu" id="userDropdownDesktop" style="display:none;">
                    <li><a href="<?php echo $basePath; ?>change-password"><i class="fas fa-key"></i>Modifier mot de passe</a></li>
                    <li><a href="#" onclick="showLogoutModal(); return false;"><i class="fas fa-right-from-bracket"></i>D&eacute;connexion</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <nav class="mobile-nav" id="mobileNav">
        <ul>
            <li><a href="<?php echo $basePath; ?>index"><i class="fa fa-home"></i>Accueil</a></li>
            <li><a href="<?php echo $basePath; ?>pages/faux/faux"><i class="fas fa-id-card"></i>Faux et Usage de Faux</a></li>
            <li><a href="<?php echo $basePath; ?>pages/constat/constat"><i class="fas fa-file-medical"></i>Constat d'Incident</a></li>
            <li><a href="<?php echo $basePath; ?>pages/denonciation/denonciation"><i class="fas fa-exclamation-triangle"></i>D&eacute;nonciation</a></li>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <li><a href="<?php echo $basePath; ?>pages/recherche/recherche"><i class="fas fa-search"></i>Recherche</a></li>
            <?php endif; ?>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <li><a href="<?php echo $basePath; ?>pages/utilisateur/utilisateurs"><i class="fas fa-users-cog"></i>Utilisateurs</a></li>
            <?php endif; ?>
            <li class="mobile-user-name"><i class="fas fa-user-circle"></i><?php echo $nomUtilisateur; ?></li>
            <li><a href="<?php echo $basePath; ?>change-password"><i class="fas fa-key"></i>Modifier mot de passe</a></li>
            <li><a href="#" onclick="showLogoutModal(); return false;"><i class="fas fa-right-from-bracket"></i>D&eacute;connexion</a></li>
        </ul>
    </nav>
</header>

<!-- Modal Confirmation Déconnexion -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #3777B0 0%, #2c5f8d 100%); color: white;">
                <h5 class="modal-title" id="logoutModalLabel">
                    <i class="fas fa-right-from-bracket me-2"></i>Confirmation de d&eacute;connexion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Voulez-vous vraiment vous d&eacute;connecter ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <a href="<?php echo $basePath; ?>logout" class="btn btn-primary">
                    <i class="fas fa-right-from-bracket me-1"></i>Se d&eacute;connecter
                </a>
            </div>
        </div>
    </div>
</div>

<div class="banner">
    <p><?php echo htmlspecialchars($bannerText, ENT_QUOTES, 'UTF-8'); ?></p>
</div>

<script>
    function toggleMenu() {
        const mobileNav = document.getElementById('mobileNav');
        mobileNav.classList.toggle('active');
    }

    function showLogoutModal() {
        var dd = document.getElementById('userDropdownDesktop');
        if (dd) dd.style.display = 'none';
        var modal = new bootstrap.Modal(document.getElementById('logoutModal'));
        modal.show();
    }

    function toggleUserDropdown(e) {
        e.preventDefault();
        e.stopPropagation();
        var dd = document.getElementById('userDropdownDesktop');
        if (dd) dd.style.display = (dd.style.display === 'none') ? 'block' : 'none';
    }

    document.addEventListener('click', function() {
        var dd = document.getElementById('userDropdownDesktop');
        if (dd) dd.style.display = 'none';
    });
</script>

<?php
/**
 * Page de changement de mot de passe - Première connexion
 * L'utilisateur doit changer son mot de passe par défaut "COUD"
 */

session_start();
require_once __DIR__ . '/config/paths.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    redirect('/login');
    exit();
}

$premiereConnexion = !empty($_SESSION['doit_changer_mdp']);

require_once __DIR__ . '/data/database.php';
require_once __DIR__ . '/models/UtilisateurModel.php';

$errors = [];
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mot_de_passe_actuel = $_POST['mot_de_passe_actuel'] ?? '';
    $nouveau_mot_de_passe = $_POST['nouveau_mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    // Récupérer l'utilisateur en base
    $utilisateur = getUtilisateurById($_SESSION['utilisateur_id']);

    if (!$utilisateur) {
        $errors[] = 'Erreur : utilisateur introuvable';
    } elseif (empty($mot_de_passe_actuel) || empty($nouveau_mot_de_passe) || empty($confirmation)) {
        $errors[] = 'Veuillez remplir tous les champs';
    } elseif (!password_verify($mot_de_passe_actuel, $utilisateur['mot_de_passe'])) {
        $errors[] = 'Le mot de passe actuel est incorrect';
    } elseif (strlen($nouveau_mot_de_passe) < 6) {
        $errors[] = 'Le nouveau mot de passe doit contenir au moins 6 caractères';
    } elseif (strtoupper($nouveau_mot_de_passe) === 'COUD') {
        $errors[] = 'Le nouveau mot de passe ne peut pas être "COUD"';
    } elseif ($nouveau_mot_de_passe !== $confirmation) {
        $errors[] = 'Les mots de passe ne correspondent pas';
    } else {
        $result = changerMotDePasse($_SESSION['utilisateur_id'], $nouveau_mot_de_passe);

        if ($result['success']) {
            if ($premiereConnexion) {
                unset($_SESSION['doit_changer_mdp']);
                redirect('/index');
                exit();
            } else {
                $success = 'Mot de passe modifié avec succès';
            }
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changement de mot de passe - USCOUD PV</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #3777B0;
            --secondary-color: #2c5f8d;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
        }

        .login-header {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }

        .login-header .logo-container {
            display: flex;
            align-items: center;
        }

        .login-header .logo-container img {
            height: 40px;
        }

        .login-header .logo-container span {
            margin-left: 10px;
            font-weight: bold;
            font-size: 18px;
            color: #333;
        }

        .login-banner {
            text-align: center;
            padding: 10px;
            background: var(--primary-color);
            color: white;
        }

        .connexion-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            flex: 1;
        }

        .connexion-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .connexion-card-header {
            background: linear-gradient(135deg, #3777B0 0%, #2c5f8d 100%);
            color: white;
            text-align: center;
            padding: 25px 20px;
        }

        .connexion-card-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .connexion-card-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .connexion-body {
            padding: 30px 25px;
        }

        .form-floating {
            margin-bottom: 25px;
        }

        .form-floating label {
            color: #3777B0;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 1rem 0.75rem;
        }

        .form-control {
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            padding: 15px 18px;
            transition: all 0.3s ease;
            font-size: 1rem;
            height: 60px;
        }

        .form-control:focus {
            border-color: #3777B0;
            box-shadow: 0 0 0 0.2rem rgba(55, 119, 176, 0.25);
        }

        .btn-connexion {
            background: linear-gradient(135deg, #3777B0 0%, #2c5f8d 100%);
            color: white;
            border: none;
            padding: 18px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            width: 100%;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 1rem;
            margin-top: 10px;
        }

        .btn-connexion:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(55, 119, 176, 0.4);
            color: white;
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 25px;
            font-size: 0.95rem;
            padding: 15px 18px;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(185, 28, 28, 0.1) 100%);
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(243, 156, 18, 0.1) 100%);
            color: #856404;
            border-left: 4px solid #f39c12;
        }

        .footer-custom {
            background: linear-gradient(135deg, #3777B0 0%, #2c5f8d 100%);
            color: white;
            padding: 15px 0 10px;
            margin-top: auto;
            flex-shrink: 0;
        }

        .footer-custom p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.75rem;
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .connexion-container {
                padding: 10px;
            }

            .connexion-card {
                margin: 10px;
                max-width: 450px;
            }

            .connexion-card-header {
                padding: 18px 15px;
            }

            .connexion-card-header h1 {
                font-size: 1.5rem;
            }

            .connexion-body {
                padding: 25px 20px;
            }

            .form-control {
                font-size: 0.95rem;
                height: 55px;
            }

            .btn-connexion {
                font-size: 0.95rem;
                padding: 15px 25px;
            }
        }

        @media (max-width: 480px) {
            .connexion-card {
                margin: 8px;
                max-width: 400px;
            }

            .connexion-card-header h1 {
                font-size: 1.3rem;
            }

            .connexion-body {
                padding: 20px 15px;
            }

            .form-control {
                font-size: 0.9rem;
                height: 50px;
            }

            .btn-connexion {
                font-size: 0.9rem;
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="login-header">
        <div class="logo-container">
            <img src="assets/logo.png" alt="Logo USCOUD" onerror="this.style.display='none'">
            <span>USCOUD PV</span>
        </div>
    </header>

    <!-- Banner -->
    <div class="login-banner">
        <p><?php echo $premiereConnexion ? 'Changement de mot de passe obligatoire' : 'Modification du mot de passe'; ?></p>
    </div>

    <!-- Formulaire -->
    <div class="connexion-container">
        <div class="connexion-card">
            <div class="connexion-card-header">
                <i class="fas fa-key fa-3x mb-3"></i>
                <h1>Nouveau mot de passe</h1>
                <p>Bienvenue <?php echo htmlspecialchars($_SESSION['prenoms'] . ' ' . $_SESSION['nom']); ?></p>
            </div>

            <div class="connexion-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" style="background: linear-gradient(135deg, rgba(39,174,96,0.1) 0%, rgba(34,139,34,0.1) 100%); color: #27ae60; border-left: 4px solid #27ae60; border-radius: 12px; border-top: none; border-right: none; border-bottom: none;">
                        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($premiereConnexion): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    Pour des raisons de s&eacute;curit&eacute;, vous devez changer votre mot de passe par d&eacute;faut avant de continuer.
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-floating">
                        <input type="password" class="form-control" id="mot_de_passe_actuel" name="mot_de_passe_actuel"
                               placeholder="Mot de passe actuel"
                               required>
                        <label for="mot_de_passe_actuel">
                            <i class="fas fa-lock me-2"></i>Mot de passe actuel *
                        </label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe"
                               placeholder="Nouveau mot de passe"
                               minlength="6"
                               required>
                        <label for="nouveau_mot_de_passe">
                            <i class="fas fa-key me-2"></i>Nouveau mot de passe *
                        </label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="confirmation" name="confirmation"
                               placeholder="Confirmer le mot de passe"
                               minlength="6"
                               required>
                        <label for="confirmation">
                            <i class="fas fa-check-double me-2"></i>Confirmer le mot de passe *
                        </label>
                    </div>

                    <small class="text-muted d-block mb-3">
                        <i class="fas fa-info-circle me-1"></i>Le mot de passe doit contenir au moins 6 caract&egrave;res.
                    </small>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn-connexion">
                            <i class="fas fa-save me-2"></i>Changer le mot de passe
                        </button>
                        <?php if (!$premiereConnexion): ?>
                        <a href="index" class="btn btn-outline-secondary w-100 mt-3" style="border-radius: 12px; padding: 15px;">
                            <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> USCOUD - Tous droits r&eacute;serv&eacute;s
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * Page de connexion - USCOUD
 * Connexion sécurisée pour les agents et administrateurs
 */

session_start();
require_once __DIR__ . '/config/paths.php';

// Si déjà connecté, rediriger vers le tableau de bord
if (isset($_SESSION['utilisateur_id'])) {
    redirect('/index');
    exit();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/data/database.php';
require_once __DIR__ . '/models/UtilisateurModel.php';

$errors = [];

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');

    if (empty($email) || empty($mot_de_passe)) {
        $errors[] = 'Veuillez remplir tous les champs';
    } else {
        // Vérifier les identifiants
        $utilisateur = getUtilisateurByEmail($email);

        if (!$utilisateur) {
            $errors[] = 'Email ou mot de passe incorrect';
        } elseif (!password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            $errors[] = 'Email ou mot de passe incorrect';
        } elseif ($utilisateur['statut'] !== 'actif') {
            $errors[] = 'Votre compte est désactivé. Contactez un administrateur.';
        } else {
            // Connexion réussie - Créer la session
            $_SESSION['utilisateur_id'] = $utilisateur['id'];
            $_SESSION['matricule'] = $utilisateur['matricule'];
            $_SESSION['nom'] = $utilisateur['nom'];
            $_SESSION['prenoms'] = $utilisateur['prenoms'];
            $_SESSION['email'] = $utilisateur['email'];
            $_SESSION['role'] = $utilisateur['role'];

            // Mettre à jour la dernière connexion
            mettreAJourDerniereConnexion($utilisateur['id']);

            // Vérifier si l'utilisateur doit changer son mot de passe (première connexion)
            if (!empty($utilisateur['doit_changer_mdp'])) {
                $_SESSION['doit_changer_mdp'] = true;
                redirect('/change-password');
                exit();
            }

            // Rediriger vers le tableau de bord
            redirect('/index');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - USCOUD PV</title>

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

        /* Header */
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

        /* Banner */
        .login-banner {
            text-align: center;
            padding: 10px;
            background: var(--primary-color);
            color: white;
        }

        /* Connexion Container */
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

        .alert-info {
            background: linear-gradient(135deg, rgba(23, 162, 184, 0.1) 0%, rgba(13, 110, 253, 0.1) 100%);
            color: #17a2b8;
            border-left: 4px solid #17a2b8;
        }

        /* Footer */
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

        /* Responsive */
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
        <p>Syst&egrave;me de Gestion des Proc&egrave;s-Verbaux - USCOUD</p>
    </div>

    <!-- Formulaire de connexion -->
    <div class="connexion-container">
        <div class="connexion-card">
            <div class="connexion-card-header">
                <i class="fas fa-shield-alt fa-3x mb-3"></i>
                <h1>USCOUD PV</h1>
                <p>Connexion au syst&egrave;me</p>
            </div>

            <div class="connexion-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Veuillez vous connecter avec vos identifiants.
                </div>

                <form method="POST" action="">
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email"
                               placeholder="votre@email.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required>
                        <label for="email">
                            <i class="fas fa-envelope me-2"></i>Email *
                        </label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                               placeholder="Mot de passe"
                               required>
                        <label for="mot_de_passe">
                            <i class="fas fa-lock me-2"></i>Mot de passe *
                        </label>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn-connexion">
                            <i class="fas fa-sign-in-alt me-2"></i>Se Connecter
                        </button>
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

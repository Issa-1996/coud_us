<?php
/**
 * Page de déconnexion - USCOUD
 */

session_start();
require_once __DIR__ . '/config/paths.php';

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session
session_destroy();

// Supprimer le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

$loginUrl = BASE_URL . '/login';

// Redirection immédiate sur localhost
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    header('Location: ' . $loginUrl);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>D&eacute;connexion - USCOUD PV</title>
    <meta http-equiv="refresh" content="3;url=<?php echo $loginUrl; ?>">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #3777B0;
            --secondary-color: #2c5f8d;
        }

        body {
            background: linear-gradient(135deg, #3777B0 0%, #2c5f8d 100%);
            min-height: 100vh;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .logout-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .logout-icon {
            font-size: 4rem;
            color: #27ae60;
            margin-bottom: 20px;
        }

        .logout-title {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .logout-message {
            color: #6c757d;
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(34, 139, 34, 0.1) 100%);
            border-left: 4px solid #28a745;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        @keyframes slideInDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert { animation: slideInDown 0.5s ease-out; }

        .btn-logout {
            background: linear-gradient(135deg, #3777B0 0%, #2c5f8d 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(55, 119, 176, 0.3);
            color: white;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3777B0;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .logout-container { padding: 30px 20px; margin: 20px; }
            .logout-icon { font-size: 3rem; }
            .logout-title { font-size: 1.5rem; }
        }

        @media (max-width: 480px) {
            .logout-container { padding: 25px 15px; margin: 10px; }
            .logout-icon { font-size: 2.5rem; }
            .logout-title { font-size: 1.3rem; }
            .btn-logout { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>

        <h1 class="logout-title">D&eacute;connexion R&eacute;ussie</h1>

        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>D&eacute;connexion confirm&eacute;e !</strong><br>
            <small>Vous avez &eacute;t&eacute; d&eacute;connect&eacute; avec succ&egrave;s du syst&egrave;me USCOUD PV.</small>
        </div>

        <p class="logout-message">
            Vous allez &ecirc;tre redirig&eacute; automatiquement vers la page de connexion...
        </p>

        <div class="text-center">
            <a href="<?php echo $loginUrl; ?>" class="btn-logout">
                <i class="fas fa-sign-in-alt me-2"></i>
                Retour &agrave; la Connexion
            </a>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const loginUrl = '<?php echo $loginUrl; ?>';

        setTimeout(function() {
            window.location.href = loginUrl;
        }, 3000);

        let seconds = 3;
        const countdownElement = document.createElement('div');
        countdownElement.style.marginTop = '20px';
        countdownElement.style.fontSize = '0.9rem';
        countdownElement.style.color = '#6c757d';
        countdownElement.innerHTML = 'Redirection dans <span id="countdown">' + seconds + '</span> secondes...';

        document.querySelector('.logout-message').appendChild(countdownElement);

        const countdownInterval = setInterval(function() {
            seconds--;
            const countdownSpan = document.getElementById('countdown');
            if (countdownSpan) countdownSpan.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(countdownInterval);
                countdownElement.innerHTML = '<span class="spinner"></span>Redirection en cours...';
            }
        }, 1000);
    </script>
</body>
</html>

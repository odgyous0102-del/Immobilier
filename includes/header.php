<?php
// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agence Immobilière U-Auben</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Styles de base pour la responsivité */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 50px;
            margin-right: 10px;
        }
        
        .logo a {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            flex-wrap: wrap;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 8px 0;
        }
        
        nav ul li a i {
            margin-right: 8px;
            font-size: 0.9rem;
        }
        
        .flash-message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            color: white;
        }
        
        .flash-message.success {
            background-color: #2ecc71;
        }
        
        .flash-message.error {
            background-color: #e74c3c;
        }
        
        /* Menu mobile */
        .menu-toggle {
            display: none;
            cursor: pointer;
            font-size: 1.5rem;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .menu-toggle {
                display: block;
                position: absolute;
                top: 15px;
                right: 15px;
            }
            
            nav {
                width: 100%;
                display: none;
            }
            
            nav.active {
                display: block;
            }
            
            nav ul {
                flex-direction: column;
                width: 100%;
                padding-top: 15px;
            }
            
            nav ul li {
                margin: 5px 0;
                margin-left: 0;
            }
            
            nav ul li a {
                padding: 10px 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <div class="logo">
                <!-- Ajout du logo - remplacez src par le chemin de votre logo -->
                <img src="<?php echo BASE_URL; ?>assets/images/logo" alt="Logo U-Auben Immobilier">
                <a href="<?php echo BASE_URL; ?>index.php">  ImmoElite</a>
            </div>
            
            <div class="menu-toggle" id="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
            
            <nav id="main-nav">
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>index.php"><i class="fas fa-home"></i> Accueil</a></li>
                    <li><a href="<?php echo BASE_URL; ?>proprietes/biensimmobilier.php"><i class="fas fa-building"></i> Propriétés</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_role'] === 'client'): ?>
                            <li><a href="<?php echo BASE_URL; ?>client/dashboard.php"><i class="fas fa-user"></i> Mon compte</a></li>
                        <?php elseif ($_SESSION['user_role'] === 'bailleur'): ?>
                            <li><a href="<?php echo BASE_URL; ?>bailleur/dashboard.php"><i class="fas fa-user-tie"></i> Mon espace</a></li>
                         <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
                        <li><a href="<?php echo BASE_URL; ?>register.php"><i class="fas fa-user-plus"></i> Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- Affichage des messages flash -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message <?php echo $_SESSION['flash_type'] ?? 'success'; ?>">
                <?php echo $_SESSION['flash_message']; ?>
                <?php unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
            </div>
        <?php endif; ?>

    <script>
        // Script pour le menu mobile
        document.getElementById('mobile-menu').addEventListener('click', function() {
            const nav = document.getElementById('main-nav');
            nav.classList.toggle('active');
        });
    </script>
</body>
</html>
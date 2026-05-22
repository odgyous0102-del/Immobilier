<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = isset($_POST['identifiant']) ? trim($_POST['identifiant']) : '';
    $motDePasse = isset($_POST['motDePasse']) ? trim($_POST['motDePasse']) : '';

    if (loginUser($identifiant, $motDePasse)) {
        // Redirection en fonction du rôle
        switch ($_SESSION['user_role']) {
            case 'client':
                redirect('client/dashboard.php');
                break;
            case 'bailleur':
                redirect('bailleur/dashboard.php');
                break;
            default:
                redirect('index.php');
        }
    } else {
        $error = "Identifiant ou mot de passe incorrect";
    }
}

require_once 'includes/header.php';
?>

<div class="login-container">
    <h2>Connexion</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label for="identifiant">Identifiant*</label>
            <input type="text" id="identifiant" name="identifiant" required>
        </div>
        
        <div class="form-group">
            <label for="motDePasse">Mot de passe*</label>
            <input type="password" id="motDePasse" name="motDePasse" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>
    
    <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = sanitize($_POST['nom']);
    $prenom = sanitize($_POST['prenom']);
    $identifiant = sanitize($_POST['identifiant']);
    $email = sanitize($_POST['email']);
    $motDePasse = $_POST['motDePasse'];
    $confirm_motDePasse = $_POST['confirm_motDePasse'];
    $telephone = sanitize($_POST['telephone']);
    $adresse = sanitize($_POST['adresse']);
    
    if ($motDePasse !== $confirm_motDePasse) {
        $error = "Les mots de passe ne correspondent pas";
    } else {
        if (registerUser($nom, $prenom, $identifiant, $email, $motDePasse, $telephone, $adresse)) {
            $_SESSION['success'] = "Inscription réussie. Vous pouvez maintenant vous connecter.";
            redirect('login.php');
        } else {
            $error = "Une erreur s'est produite lors de l'inscription";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="register-container">
    <h2>Inscription Client</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
     <p class="text-info">Les champs suivis d'une <span style="color:red">*</span> sont obligatoires.</p>
    
    <form method="post">
        <div class="form-group">
            <label for="nom">Nom <span style="color:red">*</span></label>
            <input type="text" id="nom" name="nom" required>
        </div>
        
        <div class="form-group">
            <label for="prenom">Prénom <span style="color:red">*</span></label>
            <input type="text" id="prenom" name="prenom" required>
        </div>
        
        <div class="form-group">
            <label for="identifiant">Identifiant <span style="color:red">*</span></label>
            <input type="text" id="identifiant" name="identifiant" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email <span style="color:red">*</span></label>
            <input type="email" id="email" name="email">
        </div>
        
        <div class="form-group">
            <label for="telephone">Téléphone <span style="color:red">*</span></label>
            <input type="tel" id="telephone" name="telephone" required>
        </div>
        
        <div class="form-group">
            <label for="adresse">Adresse </label>
            <textarea id="adresse" name="adresse"></textarea>
        </div>
        
        <div class="form-group">
            <label for="motDePasse">Mot de passe <span style="color:red">*</span></label>
            <input type="password" id="motDePasse" name="motDePasse" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_motDePasse">Confirmer le mot de passe <span style="color:red">*</span></label>
            <input type="password" id="confirm_motDePasse" name="confirm_motDePasse" required>
        </div>
        
        <button type="submit" class="btn btn-primary">S'inscrire</button>
    </form>
    
    <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
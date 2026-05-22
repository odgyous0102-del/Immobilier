<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Vérifier que c'est bien un client connecté
if (!isClient()) {
    header("Location: ../login.php");
    exit();
}

// Vérifier que l'id du bien est passé correctement
if (isset($_GET['id_bien']) && is_numeric($_GET['id_bien'])) {
    $bien_id = (int) $_GET['id_bien'];
    $client_id = $_SESSION['user_id'];

    // Récupérer les informations du bien avec ses images
    $stmt = $db->prepare("SELECT b.*, 
                         (SELECT p.url FROM photo p WHERE p.id_bien = b.id_bien LIMIT 1) as image_url
                         FROM biensimmobilier b 
                         WHERE b.id_bien = ?");
    $stmt->execute([$bien_id]);
    $bien = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bien) {
        // Vérifier si le bien est déjà en favoris
        $stmt = $db->prepare("SELECT 1 FROM bienfavoris WHERE id_utilisateur = ? AND id_bien = ?");
        $stmt->execute([$client_id, $bien_id]);

        if (!$stmt->fetchColumn()) {
            // Ajouter le bien en favoris
            $stmt = $db->prepare("INSERT INTO bienfavoris (id_utilisateur, id_bien) VALUES (?, ?)");
            $stmt->execute([$client_id, $bien_id]);
            
            // Message de succès avec l'image du bien
            $_SESSION['success_message'] = [
                'message' => 'Le bien a été ajouté à vos favoris',
                'image' => $bien['image_url'] ?? 'default.jpg',
                'type' => $bien['type'],
                'prix' => number_format($bien['prix'], 0, ',', ' ') . ' FCFA'
            ];
        } else {
            $_SESSION['info_message'] = 'Ce bien est déjà dans vos favoris';
        }
    } else {
        $_SESSION['error_message'] = 'Bien immobilier non trouvé';
    }
} else {
    $_SESSION['error_message'] = 'ID du bien non spécifié';
}
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert success">';
    echo '<img src="../assets/uploads/'.$_SESSION['success_message']['image'].'" alt="'.$_SESSION['success_message']['type'].'" width="100">';
    echo '<p>'.$_SESSION['success_message']['message'].'</p>';
    echo '<p>'.$_SESSION['success_message']['type'].' - '.$_SESSION['success_message']['prix'].'</p>';
    echo '</div>';
    unset($_SESSION['success_message']);
}
// Rediriger vers le tableau de bord
header("Location: ../client/dashboard.php");
exit();
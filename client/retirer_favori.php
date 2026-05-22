<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Vérifier que l'utilisateur est connecté et est un client
if (!isClient()) {
    header("Location: ../login.php");
    exit();
}

// Vérifier que l'ID du bien est présent et valide
if (!isset($_GET['id_bien']) || !is_numeric($_GET['id_bien'])) {
    $_SESSION['error_message'] = "Identifiant du bien invalide";
    header("Location: dashboard.php");
    exit();
}

$bien_id = (int)$_GET['id_bien'];
$client_id = $_SESSION['user_id'];

try {
    // Vérifier d'abord si le bien existe
    $stmt = $db->prepare("SELECT 1 FROM biensimmobilier WHERE id_bien = ?");
    $stmt->execute([$bien_id]);
    
    if (!$stmt->fetchColumn()) {
        $_SESSION['error_message'] = "Le bien demandé n'existe pas";
        header("Location: dashboard.php");
        exit();
    }

    // Vérifier si le bien est dans les favoris du client
    $stmt = $db->prepare("SELECT 1 FROM bienfavoris WHERE id_utilisateur = ? AND id_bien = ?");
    $stmt->execute([$client_id, $bien_id]);
    
    if ($stmt->fetchColumn()) {
        // Retirer le bien des favoris
        $stmt = $db->prepare("DELETE FROM bienfavoris WHERE id_utilisateur = ? AND id_bien = ?");
        $stmt->execute([$client_id, $bien_id]);
        
        // Récupérer les infos du bien pour le message de confirmation
        $stmt = $db->prepare("SELECT b.type, p.url as image_url 
                             FROM biensimmobilier b
                             LEFT JOIN photo p ON b.id_bien = p.id_bien AND p.id_photo = (
                                 SELECT MIN(id_photo) FROM photo WHERE id_bien = b.id_bien
                             )
                             WHERE b.id_bien = ?");
        $stmt->execute([$bien_id]);
        $bien = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $_SESSION['success_message'] = [
            'message' => 'Le bien a été retiré de vos favoris',
            'type' => $bien['type'],
            'image' => $bien['image_url'] ?? 'default.jpg'
        ];
    } else {
        $_SESSION['info_message'] = "Ce bien n'était pas dans vos favoris";
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Une erreur est survenue : " . $e->getMessage();
}

// Rediriger vers le tableau de bord
header("Location: dashboard.php");
exit();
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
    header("Location: dashboard.php?error=id_invalide");
    exit();
}

// Si la confirmation n'a pas encore été donnée
if (!isset($_GET['confirm'])) {
    // Stocker l'ID du bien dans la session pour le retrouver après confirmation
    $_SESSION['pending_favorite_removal'] = $_GET['id_bien'];
    header("Location: dashboard.php?ask_confirm=remove_favorite");
    exit();
}

// Si on arrive ici, c'est que la confirmation a été donnée
$id_bien = (int) $_GET['id_bien'];
$id_utilisateur = $_SESSION['user_id'];

try {
    // Supprimer l'entrée dans la table bienfavoris
    $stmt = $db->prepare("DELETE FROM bienfavoris WHERE id_utilisateur = ? AND id_bien = ?");
    $stmt->execute([$id_utilisateur, $id_bien]);

    // Redirection avec succès
    header("Location: dashboard.php?success=favori_retire");
    exit();
} catch (PDOException $e) {
    // Redirection en cas d'erreur
    header("Location: dashboard.php?error=erreur_bdd");
    exit();
}
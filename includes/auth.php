<?php
require_once 'config.php';

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkRole($requiredRole) {
    if (!isLoggedIn() || $_SESSION['user_role'] !== $requiredRole) {
        redirect('login.php');
    }
}

function isClient() {
    return isLoggedIn() && $_SESSION['user_role'] === 'client';
}

function isBailleur() {
    return isLoggedIn() && $_SESSION['user_role'] === 'bailleur';
}

function registerUser($nom, $prenom, $identifiant, $email, $motDePasse, $telephone, $adresse) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Insertion dans utilisateur (mot de passe en clair)
        $stmt = $db->prepare("INSERT INTO utilisateur (nom, prenom, identifiant, email, motDePasse, telephone, adresse) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $identifiant, $email, $motDePasse, $telephone, $adresse]);
        $userId = $db->lastInsertId();
        
        // Insertion dans client
        $stmt = $db->prepare("INSERT INTO client (id_utilisateur) VALUES (?)");
        $stmt->execute([$userId]);
        
        $db->commit();
        return $userId;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function loginUser($identifiant, $password) {
    global $db;
    
    $stmt = $db->prepare("
        SELECT u.*, 
               CASE
                   WHEN b.id_utilisateur IS NOT NULL THEN 'bailleur'
                   WHEN c.id_utilisateur IS NOT NULL THEN 'client'
               END AS role
        FROM utilisateur u
        LEFT JOIN bailleur b ON u.id_utilisateur = b.id_utilisateur
        LEFT JOIN client c ON u.id_utilisateur = c.id_utilisateur
        WHERE u.identifiant = ?
    ");
    $stmt->execute([$identifiant]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Comparaison directe du mot de passe (non sécurisé - seulement pour développement)
    if ($user && $password === $user['motDePasse']) {
        $_SESSION['user_id'] = $user['id_utilisateur'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_prenom'] = $user['prenom'];
        return true;
    }
    
    return false;
}

function logout() {
    session_unset();
    session_destroy();
    redirect('index.php');
}
?>
<?php
define('BASE_URL', '/Immobilier/'); // adapte selon ton dossier sous htdocs
session_start();
// ENVIRONNEMENT DE DÉVELOPPEMENT SEULEMENT
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Puis le reste de votre configuration (connexion DB, etc.)
// Configuration de la DB
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_gestimmobilier');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion PDO
try {
    $db = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonction de redirection

// Fonction pour sécuriser les entrées
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>

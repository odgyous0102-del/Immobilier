<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

if (!isBailleur()) {
    header("Location: ../login.php");
    exit();
}

global $db;
$bailleur_id = $_SESSION['user_id'];
$properties = [];

try {
    $query = "SELECT b.*, p.url as image 
              FROM biensimmobilier b 
              LEFT JOIN photo p ON b.id_bien = p.id_bien 
              WHERE b.id_bailleur = ? 
              GROUP BY b.id_bien";
    $stmt = $db->prepare($query);
    $stmt->execute([$bailleur_id]);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des propriétés: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Propriétés</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .property-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .property-card { border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
        .property-card img { 
            width: 100%; 
            height: 200px; 
            object-fit: cover; 
            transition: transform 0.3s; 
        }
        .property-card:hover img { transform: scale(1.05); }
        .property-content { padding: 15px; }
        .property-actions { 
            display: flex; 
            gap: 10px; 
            margin-top: 15px; 
            flex-wrap: wrap; 
        }
        .btn { 
            padding: 8px 15px; 
            border-radius: 4px; 
            text-decoration: none; 
            display: inline-block; 
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Mes Propriétés</h1>
      <a href="dashboard.php" class="btn btn-secondary">Retour mon espace de travail</a><br><br>
        <a href="ajouter_propriete.php" class="btn btn-primary">Ajouter une Propriété</a>
        
        <?php if (empty($properties)): ?>
            <p class="no-results">Aucune propriété enregistrée pour le moment.</p>
        <?php else: ?>
            <div class="property-list">
                <?php foreach ($properties as $p): ?>
                    <div class="property-card">
                        <img src="<?= !empty($p['image']) ? '../assets/uploads/'.htmlspecialchars($p['image']) : '../assets/images/default-property.jpg' ?>" 
                             alt="<?= htmlspecialchars($p['type']) ?>">
                        
                        <div class="property-content">
                            <h3><?= htmlspecialchars($p['type']) ?></h3>
                            <p><strong>Option:</strong> <?= htmlspecialchars($p['opt']) ?></p>
                            <p><strong>Adresse:</strong> <?= htmlspecialchars($p['adresse']) ?></p>
                            <p><strong>Prix:</strong> <?= number_format($p['prix'], 0, ',', ' ') ?> FR</p>
                            <p><strong>Statut:</strong> <?= htmlspecialchars($p['statut']) ?></p>
                            
                            <div class="property-actions">
                                <a href="../proprietes/details.php?id_bien=<?= $p['id_bien'] ?>" class="btn btn-primary">Voir</a>
                                <a href="modifier_propriete.php?id=<?= $p['id_bien'] ?>" class="btn btn-secondary">Modifier</a>
                                <a href="supprimer_propriete.php?id=<?= $p['id_bien'] ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
                            </div>

                        </div>
                        
                    </div>

                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
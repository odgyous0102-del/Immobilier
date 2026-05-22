<?php
require_once '../includes/config.php';

if (!isset($_GET['id_bien']) || !is_numeric($_GET['id_bien'])) {
    header("Location: biensimmobilier.php");
    exit();
}

$property_id = (int) $_GET['id_bien'];

// Récupération des infos du bien
$query = "SELECT * FROM biensimmobilier WHERE id_bien = ?";
$stmt = $db->prepare($query);
$stmt->execute([$property_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header("Location: biensimmobilier.php");
    exit();
}

// Récupération de toutes les photos du bien
$query_photos = "SELECT * FROM photo WHERE id_bien = ?";
$stmt_photos = $db->prepare($query_photos);
$stmt_photos->execute([$property_id]);
$photos = $stmt_photos->fetchAll(PDO::FETCH_ASSOC);

// Vérification si le bien est dans les favoris pour l'utilisateur connecté
$is_favori = false;
if (isset($_SESSION['user']) && $_SESSION['role'] === 'client') {
    $query_favori = "SELECT * FROM bienfavoris WHERE id_utilisateur = ? AND id_bien = ?";
    $stmt_favori = $db->prepare($query_favori);
    $stmt_favori->execute([$_SESSION['user_id'], $property_id]);
    $is_favori = $stmt_favori->rowCount() > 0;
}

require_once '../includes/auth.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du Bien</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .property-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
            justify-content: center;
        }
        .property-gallery img {
            width: 350px;
            height: 250px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .property-gallery img:hover {
            transform: scale(1.05);
        }
        .property-details {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .property-details p {
            margin: 10px 0;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px 0 0;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1 style="text-align: center; margin-bottom: 20px;"><?php echo htmlspecialchars($property['type']); ?></h1>
        
        <div class="property-details">
            <?php if (!empty($photos)): ?>
                <div class="property-gallery">
                    <?php foreach ($photos as $photo): ?>
                        <img src="../assets/uploads/<?php echo htmlspecialchars($photo['url']); ?>" 
                             alt="<?php echo htmlspecialchars($property['type']); ?>">
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="../assets/uploads/default.jpg" 
                         alt="<?php echo htmlspecialchars($property['type']); ?>"
                         style="width: 350px; height: 250px; object-fit: cover; border-radius: 8px;">
                </div>
            <?php endif; ?>

            <p><strong>Adresse:</strong> <?php echo htmlspecialchars($property['adresse'] ?? 'Non spécifiée'); ?></p>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($property['type'] ?? ''); ?></p>
            <p><strong>Utilisation:</strong> <?php echo htmlspecialchars($property['usag'] ?? ''); ?></p>
            <p><strong>Taille:</strong> <?php echo htmlspecialchars($property['taille'] ?? ''); ?> m²</p>
            <p><strong>Prix:</strong> <?php echo number_format($property['prix'], 0, ',', ' '); ?> FCFA</p>
            <p><strong>Statut:</strong> <?php echo htmlspecialchars($property['statut'] ?? ''); ?></p>

            <?php if (isset($_SESSION['user']) && $_SESSION['role'] === 'client'): ?>
                <div style="margin-top: 20px;">
                    <?php if ($is_favori): ?>
                        <a href="../client/remove_favorite.php?id_bien=<?php echo $property['id_bien']; ?>" class="btn btn-danger">✖ Retirer des Favoris</a>
                    <?php else: ?>
                        <a href="../client/ajouter_favoris.php?id_bien=<?php echo $property['id_bien']; ?>" class="btn btn-secondary">★ Ajouter aux Favoris</a>
                    <?php endif; ?>

                    <a href="../client/prendre_rdv.php?id_bien=<?php echo $property['id_bien']; ?>" class="btn btn-primary">Prendre Rendez-vous</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
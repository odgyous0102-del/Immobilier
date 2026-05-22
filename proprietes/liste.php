<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Vérifier si l'utilisateur est connecté (sans restriction de rôle)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Récupérer la connexion à la base de données
global $db;

// Récupérer les propriétés du bailleur
$bailleur_ids = $_SESSION['user_id'];
// Récupérer les paramètres de filtrage
$search = $_GET['search'] ?? '';
$categorie = $_GET['categorie'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// Construire la requête de base avec jointures
$query = "SELECT b.*, c.libelle AS categorie, 
          (SELECT url FROM photo p WHERE p.id_bien = b.id_bien LIMIT 1) AS image
          FROM biensimmobilier b
          LEFT JOIN categorie c ON b.id_categorie = c.id_categorie
          WHERE b.id_bailleur=$bailleur_ids";

// Ajouter les conditions de filtrage
$params = [];
if (!empty($search)) {
    $query .= " AND (b.type LIKE ? OR b.adresse LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($categorie)) {
    $query .= " AND b.id_categorie = ?";
    $params[] = $categorie;
}
if (!empty($min_price)) {
    $query .= " AND b.prix >= ?";
    $params[] = $min_price;
}
if (!empty($max_price)) {
    $query .= " AND b.prix <= ?";
    $params[] = $max_price;
}



try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Récupérer les catégories pour le filtre
try {
    $categories = $db->query("SELECT * FROM categorie")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toutes les Propriétés Disponibles</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .form-group {
            margin-bottom: 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0069d9;
        }
        .property-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        .property-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .property-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .property-details {
            padding: 15px;
        }
        .property-details h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .property-price {
            font-weight: bold;
            color: #e74c3c;
            font-size: 1.2em;
            margin: 10px 0;
        }
        .property-meta {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .property-actions {
            margin-top: 15px;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            grid-column: 1 / -1;
        }
        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
            .property-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Toutes les Propriétés Disponibles</h1>
        
        <div class="filter-section">
            <form method="get" class="filter-form">
                <div class="form-group">
                    <label for="search">Recherche</label>
                    <input type="text" id="search" name="search" placeholder="Type ou adresse" value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="form-group">
                    <label for="categorie">Catégorie</label>
                    <select id="categorie" name="categorie">
                        <option value="">Toutes catégories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id_categorie'] ?>" <?= $categorie == $cat['id_categorie'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['libelle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="min_price">Prix min (XOF)</label>
                    <input type="number" id="min_price" name="min_price" placeholder="Prix minimum" value="<?= htmlspecialchars($min_price) ?>">
                </div>
                
                <div class="form-group">
                    <label for="max_price">Prix max (XOF)</label>
                    <input type="number" id="max_price" name="max_price" placeholder="Prix maximum" value="<?= htmlspecialchars($max_price) ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Filtrer</button>
                    <a href="liste.php" class="btn" style="background-color: #6c757d;">Réinitialiser</a>
                </div>
                        <a href="../bailleur/dashboard.php" class="btn btn-secondary">Retour mon espace de travail</a>

            </form>
        </div>

        <div class="property-list">
            <?php if (empty($properties)): ?>
                <div class="no-results">
                    <p>Aucune propriété disponible ne correspond à vos critères de recherche.</p>
                </div>
            <?php else: ?>
                <?php foreach ($properties as $property): ?>
                    <div class="property-card">
                        <?php if (!empty($property['image'])): ?>
                            

                            <img src="../assets/uploads/<?= htmlspecialchars($property['image']) ?>" alt="<?= htmlspecialchars($property['type']) ?>" class="property-image">
                        <?php else: ?>
                            <img src="../assets/images/default-property.jpg" alt="Image par défaut" class="property-image">
                        <?php endif; ?>
                        
                        <div class="property-details">
                            <h3><?= htmlspecialchars($property['type']) ?></h3>
                            <p class="property-meta">
                                <strong>Catégorie:</strong> <?= htmlspecialchars($property['categorie'] ?? 'Non spécifiée') ?><br>
                                <strong>Taille:</strong> <?= htmlspecialchars($property['taille']) ?> m²<br>
                                <strong>Localisation:</strong> <?= htmlspecialchars($property['adresse']) ?>
                            </p>
                            
                            <p class="property-price"><?= number_format($property['prix'], 0, ',', ' ') ?> FCFA</p>
                            
                            <div class="property-actions">
                            <a href="details.php?id_bien=<?php echo $property['id_bien']; ?>" class="btn">Voir Détails</a>
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client'): ?>
                                    <a href="../client/prendre_rdv.php?id_bien=<?= $property['id_bien'] ?>" class="btn" style="background-color: #28a745;">Rendez-vous</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
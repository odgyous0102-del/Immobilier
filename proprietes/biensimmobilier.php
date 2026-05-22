<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';

// Vérifier la connexion
if (!isset($db)) {
    die("Erreur de connexion à la base de données");
}

// Gestion de la recherche
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

try {
    // Construction de la requête de base
    $sql = "SELECT p.*, 
                   (SELECT url FROM photo pp WHERE pp.id_bien = p.id_bien LIMIT 1) AS image,
                   c.libelle AS categorie_nom
            FROM biensimmobilier p 
            LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
            WHERE p.statut='validé' and 1=1";
    
    $params = [];
    
    // Filtre de recherche
    if (!empty($search)) {
        $sql .= " AND (p.type LIKE ? OR p.adresse LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Filtre par catégorie
    if (!empty($category)) {
        $sql .= " AND p.id_categorie = ?";
        $params[] = $category;
    }
    
    // Filtre par prix
    if (!empty($min_price)) {
        $sql .= " AND p.prix >= ?";
        $params[] = $min_price;
    }
    
    if (!empty($max_price)) {
        $sql .= " AND p.prix <= ?";
        $params[] = $max_price;
    }
    
    $sql .= " ORDER BY p.id_bien DESC";
    
    // Préparation et exécution de la requête
    $query = $db->prepare($sql);
    $query->execute($params);
    $properties = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupération des catégories pour le filtre
    $categories = $db->query("SELECT * FROM categorie")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propriétés Disponibles</title>
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
            display: flex;
            gap: 10px;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            grid-column: 1 / -1;
        }
        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .bg-secondary {
            background-color: #6c757d!important;
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
    <div class="container">
        <!-- Message pour les visiteurs non connectés -->
<?php if (!isset($_SESSION['user'])): ?>

<?php endif; ?>
        <h1>Propriétés Disponibles</h1>
        
        <div class="filter-section">
            <form method="get" class="filter-form">
                <div class="form-group">
                    <label for="search">Recherche</label>
                    <input type="text" id="search" name="search" placeholder="Type ou adresse" value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="form-group">
                    <label for="category">Catégorie</label>
                    <select id="category" name="category">
                        <option value="">Toutes catégories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id_categorie'] ?>" <?= $category == $cat['id_categorie'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['libelle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="min_price">Prix min (FCFA)</label>
                    <input type="number" id="min_price" name="min_price" placeholder="Prix minimum" value="<?= htmlspecialchars($min_price) ?>">
                </div>
                
                <div class="form-group">
                    <label for="max_price">Prix max (FCFA)</label>
                    <input type="number" id="max_price" name="max_price" placeholder="Prix maximum" value="<?= htmlspecialchars($max_price) ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Filtrer</button>
                    <a href="biensimmobilier.php" class="btn" style="background-color: #6c757d;">Réinitialiser</a>
                </div>
            </form>
        </div>

        <div class="property-list">
            <?php if (empty($properties)): ?>
                <div class="no-results">
                    <p>Aucune propriété disponible ne correspond à vos critères de recherche.</p>
                </div>
            <?php else: ?>
                <?php foreach ($properties as $property): ?>
                    <?php
                    $is_favori = false;
                    if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'client') {
                        $stmt = $db->prepare("SELECT 1 FROM bienfavoris WHERE id_utilisateur = ? AND id_bien = ?");
                        $stmt->execute([$_SESSION['user_id'], $property['id_bien']]);
                        $is_favori = $stmt->fetchColumn() ? true : false;
                    }
                    ?>
                    
                    <div class="property-card">
                        <?php if (!empty($property['image'])): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($property['image']) ?>" alt="<?= htmlspecialchars($property['type']) ?>" class="property-image">
                        <?php else: ?>
                            <img src="../assets/images/default-property.jpg" alt="Image par défaut" class="property-image">
                        <?php endif; ?>
                        
                        <div class="property-details">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <h3><?= htmlspecialchars($property['type']) ?></h3>
                                <span class="badge bg-secondary"><?= htmlspecialchars($property['categorie_nom']) ?></span>
                            </div>

                            <p class="property-meta">
                            
                                <strong>Taille:</strong> <?= htmlspecialchars($property['taille']) ?> m²<br>
                                <strong>Localisation:</strong> <?= htmlspecialchars($property['adresse']) ?>
                            </p>
                            
                            <p class="property-price"><?= number_format($property['prix'], 0, ',', ' ') ?> FCFA</p>
                            
                            <div class="property-actions">
                                <a href="details.php?id_bien=<?= $property['id_bien'] ?>" class="btn">Voir Détails</a>
                                
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'client'): ?>
                                    <?php if ($is_favori): ?>
                                        <a href="../client/retirer_favori.php?id_bien=<?= $property['id_bien'] ?>" class="btn" style="background-color: #dc3545;">
                                            Retirer Favori
                                        </a>
                                    <?php else: ?>
                                        <a href="../client/ajouter_favoris.php?id_bien=<?= $property['id_bien'] ?>" class="btn" style="background-color: #28a745;">
                                            Ajouter Favori
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="../client/prendre_rdv.php?id_bien=<?= $property['id_bien'] ?>" class="btn" style="background-color: #17a2b8;">
                                        Rendez-vous
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
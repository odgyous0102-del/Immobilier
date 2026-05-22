<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Vérifier si l'utilisateur est connecté et est un client
if (!isClient()) {
    header("Location: ../login.php");
    exit();
}

// Récupérer les propriétés favorites du client
$client_id = $_SESSION['user_id'];
$query = "SELECT p.* FROM biensimmobilier p 
          JOIN bienfavoris f ON p.id_bien = f.id_bien
          WHERE f.id_utilisateur = ?";

$stmt = $db->prepare($query);
$stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
$stmt->execute([$client_id]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<script>
document.querySelectorAll('.retirer-favori').forEach(button => {
    button.addEventListener('click', function() {
        const proprieteId = this.dataset.id;
        const confirmation = confirm("Voulez-vous vraiment retirer cette propriété de vos favoris ?");

        if (confirmation) {
            fetch('retirer_favori.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id=' + encodeURIComponent(proprieteId)
            })
            .then(response => {
                if (response.ok) {
                    // Supprimer la carte du DOM
                    this.closest('.property-card').remove();
                } else {
                    alert("Erreur lors de la suppression du favori.");
                }
            });
        }
    });
});
</script>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Favoris</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Mes Propriétés Favorites</h1>
        
        <?php if (empty($favorites)): ?>
            <p>Aucune propriété favorite pour le moment.</p>
        <?php else: ?>
            <div class="property-list">
                <?php foreach ($favorites as $property): ?>
                    <div class="property-card">
                        <img src="../assets/uploads/<?php echo htmlspecialchars($property['image'] ?? 'default.jpg'); ?>" alt="Villa">
                        <h3><?php echo htmlspecialchars($property['type']); ?></h3>
                        <p><?php echo htmlspecialchars($property['situation_geographique']); ?></p>
                        <p>Prix: <?php echo htmlspecialchars($property['prix']); ?> €</p>
                        <a href="../proprietes/details.php?id_bien=<?php echo $property['id']; ?>" class="btn">Voir Détails</a>
                        <a href="remove_favorite.php?id_bien=<?php echo $property['id']; ?>" class="btn btn-danger">Retirer</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
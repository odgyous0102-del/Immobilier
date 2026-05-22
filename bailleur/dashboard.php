<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Vérifier si l'utilisateur est connecté et est un bailleur
if (!isBailleur()) {
    header("Location: ../login.php");
    exit();
}

// Récupérer les statistiques des propriétés
$id_bailleur = $_SESSION['user_id'];
$stats = [
    'total' => 0,
    'vente_disponible' => 0,
    'location_disponible' => 0,
    'vente_total' => 0,
    'location_total' => 0,
    'vente_indisponible_sum' => 0
];

try {
    // Connexion à la base de données
    global $db;
    
    // Nombre total de propriétés
    $query = "SELECT COUNT(*) FROM biensimmobilier WHERE id_bailleur = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_bailleur]);
    $stats['total'] = $stmt->fetchColumn();

    // Propriétés disponibles en VENTE
    $query = "SELECT COUNT(*) FROM biensimmobilier 
              WHERE id_bailleur = ? AND `opt` = 'VENTE' AND statut = 'validé'";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_bailleur]);
    $stats['vente_disponible'] = $stmt->fetchColumn();

    // Propriétés disponibles en LOCATION
    $query = "SELECT COUNT(*) FROM biensimmobilier 
              WHERE id_bailleur = ? AND `opt` = 'LOCATION' AND statut = 'validé'";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_bailleur]);
    $stats['location_disponible'] = $stmt->fetchColumn();

    // Total des propriétés en VENTE
    $query = "SELECT COUNT(*) FROM biensimmobilier 
              WHERE id_bailleur = ? AND `opt` = 'VENTE'";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_bailleur]);
    $stats['vente_total'] = $stmt->fetchColumn();

    // Total des propriétés en LOCATION
    $query = "SELECT COUNT(*) FROM biensimmobilier 
              WHERE id_bailleur = ? AND `opt` = 'LOCATION'";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_bailleur]);
    $stats['location_total'] = $stmt->fetchColumn();

    // Somme des prix des biens en VENTE avec statut indisponible
    $query = "SELECT SUM(prix) FROM biensimmobilier 
              WHERE id_bailleur = ? AND `opt` = 'VENTE' AND statut = 'indisponible'";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_bailleur]);
    $sum = $stmt->fetchColumn();
    $stats['vente_indisponible_sum'] = $sum ? $sum : 0;

} catch (PDOException $e) {
    $error = $e->getMessage();
}

// Fonction pour formater le prix en format lisible
function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' FCFA';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Bailleur</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Chart.js pour les diagrammes -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-primary {
            background-color: #007bff;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 15px 0;
            color: #3498db;
        }
        .chart-container {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
        .chart-box {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .chart-box canvas {
            width: 100% !important;
            height: 300px !important;
        }
        .error {
            color: #d9534f;
            background-color: #f8d7da;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        .price-summary {
            margin-top: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .price-summary h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .price-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 10px 0;
            color: #2ecc71;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1>Tableau de Bord - Bailleur <span><?php echo htmlspecialchars($_SESSION['user_prenom']); ?></span></h1>
        
        <div class="dashboard-actions">
            <a href="mes_proprietes.php" class="btn">Gérer mes Propriétés</a>
            <a href="ajouter_propriete.php" class="btn btn-primary">Ajouter une Propriété</a>
            <a href="../proprietes/liste.php" class="btn">Voir Toutes les Propriétés</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="error">Erreur: <?= htmlspecialchars($error) ?></div>
        <?php else: ?>
            <div class="stats-container">
                <!-- Carte 1: Total des propriétés -->
                <div class="stat-card">
                    <h3>Total des propriétés</h3>
                    <div class="stat-value"><?= $stats['total'] ?></div>
                    <p>Toutes vos propriétés immobilières</p>
                </div>

                <!-- Carte 2: Propriétés disponibles en VENTE -->
                <div class="stat-card">
                    <h3>Disponibles en VENTE</h3>
                    <div class="stat-value"><?= $stats['vente_disponible'] ?></div>
                    <p>Sur <?= $stats['vente_total'] ?> propriétés en VENTE</p>
                </div>

                <!-- Carte 3: Propriétés disponibles en LOCATION -->
                <div class="stat-card">
                    <h3>Disponibles en LOCATION</h3>
                    <div class="stat-value"><?= $stats['location_disponible'] ?></div>
                    <p>Sur <?= $stats['location_total'] ?> propriétés en LOCATION</p>
                </div>
            </div>

            <!-- Section des diagrammes -->
            <div class="chart-container">
                <!-- Diagramme 1: Répartition VENTE/LOCATION -->
                <div class="chart-box">
                    <h3>Répartition par type de transaction</h3>
                    <canvas id="transactionChart"></canvas>
                </div>
            </div>

            <!-- Nouvelle section pour le calcul des prix -->
            <div class="price-summary">
                <h3>Valeur des biens  vendue </h3>
                <div class="price-value"><?= formatPrice($stats['vente_indisponible_sum']) ?></div>
                <p>Ce montant correspond à la totalité des biens qui ont été vendus.  </p>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Données pour les graphiques
                    const transactionData = {
                        labels: ['VENTE', 'LOCATION'],
                        datasets: [{
                            data: [<?= $stats['vente_total'] ?>, <?= $stats['location_total'] ?>],
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(54, 162, 235, 0.7)'
                            ],
                            borderColor: [
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)'
                            ],
                            borderWidth: 1
                        }]
                    };

                    // Options communes
                    const commonOptions = {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    };

                    // Diagramme de répartition
                    const transactionCtx = document.getElementById('transactionChart').getContext('2d');
                    new Chart(transactionCtx, {
                        type: 'pie',
                        data: transactionData,
                        options: commonOptions
                    });
                });
            </script>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
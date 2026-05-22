<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Vérifier si l'utilisateur est connecté et est un bailleur
if (!isBailleur()) {
    header("Location: ../login.php");
    exit();
}

$bailleur_id = $_SESSION['user_id'];
$id_bien = $_GET['id'] ?? null;

// Vérifier que l'ID du bien est présent
if (!$id_bien || !ctype_digit($id_bien)) {
    header("Location: mes_proprietes.php");
    exit();
}

$id_bien = intval($id_bien);

// Vérifier que le bien appartient bien au bailleur
try {
    // Récupérer les informations du bien
    $query = "SELECT * FROM biensimmobilier WHERE id_bien = ? AND id_bailleur = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_bien, $bailleur_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        $_SESSION['error_message'] = "Propriété non trouvée ou accès refusé";
        header("Location: mes_proprietes.php");
        exit();
    }

    // Récupérer les images associées
    $query = "SELECT * FROM photo WHERE id_bien = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_bien]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vérifier si la suppression a été confirmée
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
        $db->beginTransaction();

        try {
            // Supprimer les images associées (d'abord les fichiers physiques)
            foreach ($images as $image) {
                $file_path = '../assets/uploads/' . $image['url'];
                $thumbnail_path = '../assets/uploads/thumbs/' . $image['url'];
                
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                if (file_exists($thumbnail_path)) {
                    unlink($thumbnail_path);
                }
            }

            // Supprimer les entrées dans la table photo
            $query = "DELETE FROM photo WHERE id_bien = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id_bien]);

            // Supprimer les rendez-vous associés
            $query = "DELETE FROM rendezvous WHERE id_bien = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id_bien]);

            // Supprimer les favoris associés
            $query = "DELETE FROM bienfavoris WHERE id_bien = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id_bien]);

            // Enfin, supprimer le bien lui-même
            $query = "DELETE FROM biensimmobilier WHERE id_bien = ? AND id_bailleur = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id_bien, $bailleur_id]);

            $db->commit();

            // Rediriger avec un message de succès
            $_SESSION['success_message'] = "La propriété a été supprimée avec succès.";
            header("Location: mes_proprietes.php");
            exit();
        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['error_message'] = "Erreur lors de la suppression de la propriété: " . $e->getMessage();
            header("Location: supprimer_propriete.php?id=" . $id_bien);
            exit();
        }
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la vérification de la propriété: " . $e->getMessage();
    header("Location: mes_proprietes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer une Propriété</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --danger-color: #dc3545;
            --secondary-color: #6c757d;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .confirmation-box {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-top: 1.5rem;
        }

        .property-info {
            margin-bottom: 1.5rem;
        }

        .property-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .property-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .property-image:hover {
            transform: scale(1.05);
        }

        .property-detail {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .property-detail i {
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            gap: 0.5rem;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            opacity: 0.9;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #b91c1c;
            border-left: 4px solid #b91c1c;
        }

        .alert-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
        }

        .alert-close:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .property-images {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1><i class="fas fa-trash-alt"></i> Supprimer une Propriété</h1>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <div>
                    <h3>Erreur</h3>
                    <p><?= htmlspecialchars($_SESSION['error_message']) ?></p>
                </div>
                <button class="alert-close">&times;</button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if ($property): ?>
            <div class="confirmation-box">
                <h2><i class="fas fa-exclamation-triangle"></i> Confirmer la suppression</h2>
                <p>Êtes-vous sûr de vouloir supprimer définitivement cette propriété ? Cette action est irréversible.</p>
                
                <div class="property-info">
                    <h3><?= htmlspecialchars($property['type']) ?></h3>
                    
                    <?php if (!empty($images)): ?>
                        <div class="property-images">
                            <?php foreach ($images as $image): ?>
                                <?php 
                                $image_path = '../assets/uploads/' . htmlspecialchars($image['url']);
                                $default_image = '../assets/images/default-property.jpg';
                                ?>
                                <img src="<?= file_exists($image_path) ? $image_path : $default_image ?>" 
                                     alt="Image de la propriété" 
                                     class="property-image">
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="property-images">
                            <img src="../assets/images/default-property.jpg" 
                                 alt="Image par défaut" 
                                 class="property-image">
                        </div>
                    <?php endif; ?>
                    
                    <div class="property-details">
                        <p class="property-detail">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($property['adresse']) ?></span>
                        </p>
                        <p class="property-detail">
                            <i class="fas fa-euro-sign"></i>
                            <span><?= number_format($property['prix'], 0, ',', ' ') ?> FR</span>
                        </p>
                        <p class="property-detail">
                            <i class="fas fa-ruler-combined"></i>
                            <span><?= htmlspecialchars($property['taille']) ?> m²</span>
                        </p>
                        <p class="property-detail">
                            <i class="fas fa-info-circle"></i>
                            <span>Statut: <?= htmlspecialchars($property['statut']) ?></span>
                        </p>
                    </div>
                </div>

                <form method="post">
                    <div class="btn-group">
                        <button type="submit" name="confirm_delete" class="btn btn-danger">
                            <i class="fas fa-check"></i> Confirmer la suppression
                        </button>
                        <a href="mes_proprietes.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="confirmation-box">
                <p>Propriété non trouvée ou vous n'avez pas les droits pour la supprimer.</p>
                <a href="mes_proprietes.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à mes propriétés
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fermer les alertes
            document.querySelectorAll('.alert-close').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.alert').style.opacity = '0';
                    setTimeout(() => {
                        this.closest('.alert').remove();
                    }, 300);
                });
            });

            // Confirmation avant suppression
            const deleteForm = document.querySelector('form');
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(e) {
                    if (!confirm('Êtes-vous absolument sûr de vouloir supprimer cette propriété ? Cette action ne peut pas être annulée.')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>
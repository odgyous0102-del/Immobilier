<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérification de l'authentification et du rôle
if (!isClient()) {
    header("Location: ../login.php");
    exit();
}

// Validation de l'ID du bien
if (!isset($_GET['id_bien']) || !ctype_digit($_GET['id_bien'])) {
    $_SESSION['error_message'] = "Aucun bien valide spécifié.";
    header("Location: ../proprietes/biensimmobilier.php");
    exit();
}

$id_bien = intval($_GET['id_bien']);
$id_client = $_SESSION['user_id'];
$success = false;
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage des données
    $objet = trim($_POST['objet'] ?? '');
    $date_rdv = trim($_POST['date_rdv'] ?? '');

    // Validation
    if (empty($objet)) {
        $errors[] = "L'objet du rendez-vous est requis.";
    }

    if (empty($date_rdv)) {
        $errors[] = "La date du rendez-vous est requise.";
    } elseif (strtotime($date_rdv) < strtotime('+1 day')) {
        $errors[] = "La date doit être au moins demain.";
    } elseif (strtotime($date_rdv) > strtotime('+3 months')) {
        $errors[] = "La date ne peut pas dépasser 3 mois.";
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Vérification que l'utilisateur est bien un client
            $stmt = $db->prepare("SELECT 1 FROM client WHERE id_utilisateur = ?");
            $stmt->execute([$id_client]);
            
            if (!$stmt->fetch()) {
                $errors[] = "Vous devez être un client enregistré pour prendre rendez-vous.";
            } else {
                // Récupération de l'agent assigné au bien
                $stmt = $db->prepare("SELECT id_employe FROM biensimmobilier WHERE id_bien = ?");
                $stmt->execute([$id_bien]);
                $id_employe = $stmt->fetchColumn();

                if (!$id_employe) {
                    $errors[] = "Aucun agent n'est actuellement assigné à ce bien.";
                } else {
                    // Vérification que l'employé est bien un manager
                    $stmt = $db->prepare("SELECT 1 FROM manager WHERE id_utilisateur = ?");
                    $stmt->execute([$id_employe]);
                    
                    if (!$stmt->fetch()) {
                        $errors[] = "L'employé assigné n'est pas un agent valide.";
                    } else {
                        // S'assurer que l'agent est aussi dans la table client (pour la contrainte étrangère)
                        $stmt = $db->prepare("INSERT IGNORE INTO client (id_utilisateur) VALUES (?)");
                        $stmt->execute([$id_employe]);
                        
                        // Insertion du rendez-vous
                        $stmt = $db->prepare("INSERT INTO rendezvous (objet, date_rdv, id_client, id_agent, id_bien) 
                                             VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$objet, $date_rdv, $id_client, $id_employe, $id_bien]);
                        
                        $db->commit();
                        $_SESSION['success_message'] = "Votre rendez-vous a été enregistré avec succès.";
                        header("Location: ../client/dashboard.php");
                        exit();
                    }
                }
            }
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Une erreur est survenue lors de la prise de rendez-vous. Veuillez réessayer.";
            error_log("Erreur prise de rendez-vous: " . $e->getMessage());
        }
    }
}

// Récupération des informations du bien
try {
    $stmt = $db->prepare("SELECT b.*, c.libelle AS categorie, 
                         CONCAT(u.prenom, ' ', u.nom) AS agent_nom
                         FROM biensimmobilier b 
                         LEFT JOIN categorie c ON b.id_categorie = c.id_categorie
                         LEFT JOIN utilisateur u ON b.id_employe = u.id_utilisateur
                         WHERE b.id_bien = ?");
    $stmt->execute([$id_bien]);
    $bien = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bien) {
        $_SESSION['error_message'] = "Le bien demandé n'existe pas.";
        header("Location: ../proprietes/biensimmobilier.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur DB: " . $e->getMessage());
    die("Une erreur est survenue lors de la récupération des informations du bien.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prendre Rendez-vous | <?= htmlspecialchars($bien['type']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-size: 2rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
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
        }

        .property-details {
            padding: 15px;
        }

        .property-title {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.5rem;
        }

        .property-meta {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .property-price {
            font-weight: bold;
            color: #e74c3c;
            font-size: 1.2em;
            margin: 10px 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
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

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background-color: #f0f4ff;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
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
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="flash-message error">
            <?= $_SESSION['error_message'] ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="flash-message success">
            <?= $_SESSION['success_message'] ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Prendre Rendez-vous</h1>
            <p>Planifiez une visite ou une rencontre pour ce bien immobilier</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div>
                    <h3>Erreur</h3>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <button class="alert-close">&times;</button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="property-card">
                <?php 
                // Récupérer la première image du bien
                $image_stmt = $db->prepare("SELECT url FROM photo WHERE id_bien = ? LIMIT 1");
                $image_stmt->execute([$id_bien]);
                $image = $image_stmt->fetchColumn();
                ?>
                <img src="../assets/uploads/<?= htmlspecialchars($image ?: 'default-property.jpg') ?>" 
                     alt="<?= htmlspecialchars($bien['type']) ?>" 
                     class="property-image">

                <div class="property-details">
                    <h2 class="property-title"><?= htmlspecialchars($bien['type']) ?></h2>
                    
                    <div class="property-meta">
                        <p><strong>Catégorie:</strong> <?= htmlspecialchars($bien['categorie'] ?? 'Non spécifiée') ?></p>
                        <p><strong>Adresse:</strong> <?= htmlspecialchars($bien['adresse']) ?></p>
                        <p><strong>Taille:</strong> <?= htmlspecialchars($bien['taille']) ?> m²</p>
                        <?php if ($bien['agent_nom']): ?>
                        <p><strong>Agent:</strong> <?= htmlspecialchars($bien['agent_nom']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="property-price">
                        <?= number_format($bien['prix'], 0, ',', ' ') ?> XOF
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <form method="post" id="rdv-form">
                <div class="form-group">
                    <label for="objet" class="form-label">Objet du rendez-vous *</label>
                    <select name="objet" id="objet" class="form-control" required>
                        <option value="">-- Sélectionnez un motif --</option>
                        <option value="Visite du bien" <?= (isset($_POST['objet']) && $_POST['objet'] === 'Visite du bien') ? 'selected' : '' ?>>Visite du bien</option>
                        <option value="Signature contrat" <?= (isset($_POST['objet']) && $_POST['objet'] === 'Signature contrat') ? 'selected' : '' ?>>Finaliser la transaction</option>
                        <option value="Autre" <?= (isset($_POST['objet']) && $_POST['objet'] === 'Autre') ? 'selected' : '' ?>>Autre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_rdv" class="form-label">Date souhaitée *</label>
                    <input type="date" name="date_rdv" id="date_rdv" class="form-control" 
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>" 
                           max="<?= date('Y-m-d', strtotime('+3 months')) ?>"
                           value="<?= htmlspecialchars($_POST['date_rdv'] ?? '') ?>" 
                           required>
                    <small class="text-muted">Les rendez-vous doivent être pris entre demain et 3 mois maximum</small>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> Confirmer le rendez-vous
                    </button>
                    <a href="../proprietes/details.php?id_bien=<?= $id_bien ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour au bien
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    
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

            // Validation du formulaire
            const form = document.getElementById('rdv-form');
            form.addEventListener('submit', function(e) {
                const dateRdv = new Date(document.getElementById('date_rdv').value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (dateRdv <= today) {
                    e.preventDefault();
                    alert('Veuillez choisir une date future pour votre rendez-vous.');
                    return false;
                }

                const threeMonthsLater = new Date();
                threeMonthsLater.setMonth(threeMonthsLater.getMonth() + 3);
                threeMonthsLater.setHours(0, 0, 0, 0);

                if (dateRdv > threeMonthsLater) {
                    e.preventDefault();
                    alert('La date ne peut pas dépasser 3 mois à partir d\'aujourd\'hui.');
                    return false;
                }

                return true;
            });

            // Désactiver les dates passées dans le sélecteur
            const dateInput = document.getElementById('date_rdv');
            dateInput.addEventListener('focus', function() {
                this.min = new Date().toISOString().split('T')[0];
            });
        });
    </script>
</body>
</html>
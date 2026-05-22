<?php
require_once '../includes/config.php'; // La session est déjà démarrée ici (ligne 3 de config.php)
require_once '../includes/auth.php';

// Ne pas appeler session_start() ici car déjà démarré dans config.php

// Vérifier que l'utilisateur est un client connecté
checkRole('client');

$userId = $_SESSION['user_id'];

// Vérifier que l'ID du rendez-vous est fourni et valide
if (!isset($_GET['id_rdv']) || !is_numeric($_GET['id_rdv'])) {
    $_SESSION['error_message'] = "Aucun rendez-vous spécifié ou ID invalide";
    header("Location: dashboard.php");
    exit();
}

$rdvId = (int)$_GET['id_rdv'];

// Récupérer les détails du rendez-vous
try {
    $stmt = $db->prepare("SELECT r.*, b.type, b.adresse, b.id_bien,
                         CONCAT(u.prenom, ' ', u.nom) as agent_name,
                         (SELECT p.url FROM photo p WHERE p.id_bien = b.id_bien LIMIT 1) as photo_url
                         FROM rendezvous r
                         JOIN biensimmobilier b ON r.id_bien = b.id_bien
                         JOIN utilisateur u ON r.id_agent = u.id_utilisateur
                         WHERE r.id_rdv = ? AND r.id_client = ?");
    $stmt->execute([$rdvId, $userId]);
    $rdv = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rdv) {
        $_SESSION['error_message'] = "Rendez-vous introuvable ou vous n'avez pas les droits";
        header("Location: dashboard.php");
        exit();
    }

    // Vérifier que le rendez-vous peut être annulé (statut en_attente)
    if ($rdv['statut'] !== 'en_attente') {
        $_SESSION['error_message'] = "Seuls les rendez-vous en attente peuvent être annulés";
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur de base de données: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}

// Traitement de l'annulation si formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Mettre à jour le statut du rendez-vous
        $updateStmt = $db->prepare("UPDATE rendezvous SET statut = 'refuser' WHERE id_rdv = ?");
        $updateStmt->execute([$rdvId]);

        // Préparer le message de succès avec les détails du bien
        $_SESSION['success_message'] = [
            'message' => "Votre rendez-vous du " . date('d/m/Y', strtotime($rdv['date_rdv'])) . 
                         " pour " . $rdv['type'] . " a été annulé avec succès",
            'image' => $rdv['photo_url'] ?? 'default.jpg',
            'type' => $rdv['type']
        ];

        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de l'annulation: " . $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
}

// Inclure le header après toutes les opérations susceptibles de rediriger
require_once '../includes/header.php';
?>

<div class="dashboard-container">
    <h1>Annuler un rendez-vous</h1>
    
    <div class="confirmation-box">
        <h2>Confirmation d'annulation</h2>
        
        <div class="rdv-summary">
            <div class="property-image-container">
                <img src="../assets/uploads/<?php echo htmlspecialchars($rdv['photo_url'] ?? 'default.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($rdv['type']); ?>"
                     class="property-image">
            </div>
            
            <div class="rdv-details">
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value"><?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Objet:</span>
                    <span class="detail-value"><?= htmlspecialchars($rdv['objet']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Bien:</span>
                    <span class="detail-value"><?= htmlspecialchars($rdv['type']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Adresse:</span>
                    <span class="detail-value"><?= htmlspecialchars($rdv['adresse']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Agent:</span>
                    <span class="detail-value"><?= htmlspecialchars($rdv['agent_name']) ?></span>
                </div>
            </div>
        </div>
        
        <form method="post" class="annulation-form">
            <div class="form-group">
                <label for="raison">Raison de l'annulation (optionnel):</label>
                <textarea name="raison" id="raison" rows="3" placeholder="Pourquoi souhaitez-vous annuler ce rendez-vous?"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous?')">
                    <i class="fas fa-calendar-times"></i> Confirmer l'annulation
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<style>
.dashboard-container {
    max-width: 800px;
    margin: 30px auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}

.confirmation-box {
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    padding: 25px;
    margin-top: 20px;
}

.confirmation-box h2 {
    color: #333;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    text-align: center;
}

.rdv-summary {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.property-image-container {
    flex: 0 0 200px;
}

.property-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
}

.rdv-details {
    flex: 1;
    background: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
}

.detail-row {
    display: flex;
    margin-bottom: 10px;
}

.detail-label {
    font-weight: bold;
    width: 100px;
    color: #555;
}

.detail-value {
    flex: 1;
}

.annulation-form {
    margin-top: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 16px;
    transition: background-color 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

@media (max-width: 768px) {
    .rdv-summary {
        flex-direction: column;
    }
    
    .property-image-container {
        flex: 0 0 auto;
        text-align: center;
    }
    
    .property-image {
        max-width: 100%;
        height: auto;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
}
</style>
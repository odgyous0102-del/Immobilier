<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/header.php'; // ✅ EN-TÊTE

// Vérifier si l'utilisateur est connecté et est un client
if (!isClient()) {
    header("Location: ../login.php");
    exit();
}

// Vérifier que l'id_bien est fourni
if (!isset($_GET['id_bien']) || empty($_GET['id_bien'])) {
    die("Aucun bien spécifié.");
}

$id_bien = intval($_GET['id_bien']);
$id_client = $_SESSION['user_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $objet = $_POST['objet'] ?? '';
    $date_rdv = $_POST['date_rdv'] ?? '';

    if (empty($objet) || empty($date_rdv)) {
        $message = "Veuillez remplir tous les champs.";
    } else {
        try {
            // Récupérer l'agent lié au bien
            $stmt = $db->prepare("SELECT id_employe FROM biensimmobilier WHERE id_bien = ?");
            $stmt->execute([$id_bien]);
            $id_agent = $stmt->fetchColumn();

            if (!$id_agent) {
                $message = "Aucun agent n'est assigné à ce bien.";
            } else {
                // Insérer le rendez-vous
                $stmt = $db->prepare("INSERT INTO rendezvous (objet, date_rdv, id_client, id_agent, id_bien) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$objet, $date_rdv, $id_client, $id_agent, $id_bien]);
                header("Location: ../client/dashboard.php?success=rdv");
                exit();
            }
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h1 style="text-align: center;">Prendre Rendez-vous</h1>

    <?php if ($message): ?>
        <p class="error" style="color:red;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="post" class="form-rdv" style="max-width: 500px;">
        <label for="objet">Objet du rendez-vous :</label>
        <select name="objet" id="objet" required>
            <option value="">-- Choisissez --</option>
            <option value="Visite bien">🔍 Visite du bien</option>
            <option value="Finalisation transaction">✅ Finalisation de la transaction</option>
        </select>

        <label for="date_rdv">Date souhaitée :</label>
        <input type="date" name="date_rdv" id="date_rdv" required min="<?= date('Y-m-d') ?>">

        <div style="margin-top: 15px;">
            <button type="submit" class="btn btn-primary">📅 Confirmer le Rendez-vous</button>
            <a href="../proprietes/biensimmobilier.php" class="btn btn-secondary">Retour</a>
            
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php';?>
<style>
    .form-rdv {
    background: #fff;
    padding: 25px;
    margin: 30px auto;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    font-family: 'Segoe UI', sans-serif;
}

.form-rdv label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-rdv select,
.form-rdv input[type="date"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 18px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 16px;
    background-color: #f9f9f9;
    transition: border-color 0.3s ease;
}

.form-rdv select:focus,
.form-rdv input[type="date"]:focus {
    border-color: #007BFF;
    outline: none;
    background-color: #fff;
}

.form-rdv .btn {
    padding: 10px 20px;
    margin-right: 10px;
    font-size: 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.form-rdv .btn-primary {
    background-color: #007BFF;
    color: white;
}

.form-rdv .btn-primary:hover {
    background-color: #0056b3;
}

.form-rdv .btn-secondary {
    background-color: #6c757d;
    color: white;
}

.form-rdv .btn-secondary:hover {
    background-color: #5a6268;
}

@media screen and (max-width: 600px) {
    .form-rdv {
        padding: 20px;
    }

    .form-rdv .btn {
        width: 100%;
        margin-bottom: 10px;
    }
}

</style>

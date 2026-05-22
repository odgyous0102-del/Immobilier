<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';

// Vérifier si l'utilisateur est connecté et est un bailleur
if (!isBailleur()) {
    header("Location: ../login.php");
    exit();
}

$bailleur_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Récupérer l'ID de la propriété à modifier
$id_bien = $_GET['id'] ?? null;

// Vérifier que la propriété appartient bien au bailleur
$property = null;
$existing_images = [];

if ($id_bien) {
    try {
        // Récupérer les informations de la propriété avec le nom de la catégorie
        $query = "SELECT b.*, c.libelle AS categorie_libelle 
                 FROM biensimmobilier b
                 LEFT JOIN categorie c ON b.id_categorie = c.id_categorie
                 WHERE b.id_bien = ? AND b.id_bailleur = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id_bien, $bailleur_id]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$property) {
            header("Location: mes_proprietes.php");
            exit();
        }

        // Récupérer les images existantes
        $query = "SELECT * FROM photo WHERE id_bien = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id_bien]);
        $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $errors[] = "Erreur lors de la récupération des données: " . $e->getMessage();
    }
} else {
    header("Location: mes_proprietes.php");
    exit();
}

// Récupérer les catégories disponibles
$categories = [];
try {
    $stmt = $db->query("SELECT * FROM categorie");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Erreur lors de la récupération des catégories: " . $e->getMessage();
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des données
    $type = trim($_POST['type'] ?? '');
    $opt = trim($_POST['opt'] ?? ''); // Nouveau champ opt
    $prix = trim($_POST['prix'] ?? '');
    $usag = trim($_POST['usag'] ?? '');
    $taille = trim($_POST['taille'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $id_categorie = trim($_POST['id_categorie'] ?? '');
    $statut = trim($_POST['statut'] ?? 'En attente');

    // Validation
    if (empty($type)) $errors[] = "Le type de bien est requis.";
    if (empty($opt)) $errors[] = "L'option de bien est requise."; // Validation pour opt
    if (empty($prix) || !is_numeric($prix)) $errors[] = "Un prix valide est requis.";
    if (empty($taille) || !is_numeric($taille)) $errors[] = "Une taille valide est requise.";
    if (empty($adresse)) $errors[] = "L'adresse est requise.";
    if (empty($id_categorie)) $errors[] = "La catégorie est requise.";

    // Traitement des images (nouvelles et existantes)
    $uploaded_images = [];
    $images_to_keep = $_POST['existing_images'] ?? [];

    // Supprimer les images non sélectionnées
    foreach ($existing_images as $image) {
        if (!in_array($image['id_photo'], $images_to_keep)) {
            try {
                // Supprimer de la base de données
                $query = "DELETE FROM photo WHERE id_photo = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$image['id_photo']]);

                // Supprimer le fichier
                if (file_exists('../assets/uploads/' . $image['url'])) {
                    unlink('../assets/uploads/' . $image['url']);
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur lors de la suppression de l'image: " . $e->getMessage();
            }
        }
    }

    // Traitement des nouvelles images
    if (empty($errors) && !empty($_FILES['images']['tmp_name'][0])) {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $max_files = 10; // Maximum 10 images
        
        // Vérifier qu'on ne dépasse pas le nombre maximum de fichiers
        $remaining_slots = $max_files - count($images_to_keep);
        if (count($_FILES['images']['tmp_name']) > $remaining_slots) {
            $errors[] = "Vous ne pouvez ajouter que $remaining_slots images maximum (10 maximum au total).";
        } else {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['images']['name'][$key];
                    $file_tmp = $_FILES['images']['tmp_name'][$key];
                    $file_size = $_FILES['images']['size'][$key];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Vérifier l'extension et la taille
                    if (in_array($file_ext, $allowed_types)) {
                        if ($file_size <= $max_size) {
                            // Conserver le nom original du fichier
                            $new_name = $file_name;
                            $upload_path = '../assets/uploads/' . $new_name;
                            
                            // Vérifier si le fichier existe déjà
                            $counter = 1;
                            while (file_exists($upload_path)) {
                                $file_info = pathinfo($file_name);
                                $new_name = $file_info['filename'] . '_' . $counter . '.' . $file_info['extension'];
                                $upload_path = '../assets/uploads/' . $new_name;
                                $counter++;
                            }
                            
                            // Déplacer le fichier uploadé
                            if (move_uploaded_file($file_tmp, $upload_path)) {
                                $uploaded_images[] = $new_name;
                            } else {
                                $errors[] = "Erreur lors de l'upload de l'image: " . htmlspecialchars($file_name);
                            }
                        } else {
                            $errors[] = "L'image " . htmlspecialchars($file_name) . " dépasse la taille maximale (2MB)";
                        }
                    } else {
                        $errors[] = "Type de fichier non autorisé pour " . htmlspecialchars($file_name) . " (seuls JPG, PNG, GIF sont acceptés)";
                    }
                } elseif ($_FILES['images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Erreur lors de l'upload de l'image: " . getUploadError($_FILES['images']['error'][$key]);
                }
            }
        }
    }

    // Mise à jour en base de données si aucune erreur
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Mise à jour du bien immobilier (ajout du champ opt)
            $query = "UPDATE biensimmobilier 
                     SET type = ?, opt = ?, prix = ?, statut = ?, usag = ?, taille = ?, adresse = ?, id_categorie = ?
                     WHERE id_bien = ? AND id_bailleur = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $type, 
                $opt, // Nouveau champ opt
                $prix, 
                $statut, 
                $usag, 
                $taille, 
                $adresse, 
                $id_categorie, 
                $id_bien, 
                $bailleur_id
            ]);

            // Insertion des nouvelles images
            foreach ($uploaded_images as $image) {
                $query = "INSERT INTO photo (url, id_bien) VALUES (?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$image, $id_bien]);
            }

            $db->commit();
            $success = true;

            // Recharger les données après modification
            $query = "SELECT b.*, c.libelle AS categorie_libelle 
                     FROM biensimmobilier b
                     LEFT JOIN categorie c ON b.id_categorie = c.id_categorie
                     WHERE b.id_bien = ? AND b.id_bailleur = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id_bien, $bailleur_id]);
            $property = $stmt->fetch(PDO::FETCH_ASSOC);

            // Recharger les images
            $query = "SELECT * FROM photo WHERE id_bien = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id_bien]);
            $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Erreur lors de la mise à jour du bien: " . $e->getMessage();
            
            // Supprimer les images uploadées en cas d'échec
            foreach ($uploaded_images as $image) {
                if (file_exists('../assets/uploads/' . $image)) {
                    unlink('../assets/uploads/' . $image);
                }
            }
        }
    }
}

// Fonction pour obtenir les messages d'erreur d'upload
function getUploadError($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return "La taille du fichier dépasse la limite autorisée par le serveur";
        case UPLOAD_ERR_FORM_SIZE:
            return "La taille du fichier dépasse la limite spécifiée dans le formulaire";
        case UPLOAD_ERR_PARTIAL:
            return "Le téléchargement du fichier a été interrompu";
        case UPLOAD_ERR_NO_FILE:
            return "Aucun fichier n'a été téléchargé";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Dossier temporaire manquant";
        case UPLOAD_ERR_CANT_WRITE:
            return "Échec de l'écriture du fichier sur le disque";
        case UPLOAD_ERR_EXTENSION:
            return "Une extension PHP a arrêté le téléchargement du fichier";
        default:
            return "Erreur inconnue lors du téléchargement";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Propriété</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* [Le reste du CSS reste inchangé] */
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <h1 style="text-align: center; margin-bottom: 20px;">Modifier la propriété: <?php echo htmlspecialchars($property['type']); ?></h1>
        
        <a href="mes_proprietes.php" class="btn btn-secondary">Retour à mes propriétés</a>

        <?php if ($success): ?>
            <div class="success">
                <p>✅ La propriété a été modifiée avec succès!</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <h3>❌ Erreurs :</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="property-form">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="type">Type de bien*</label>
                    <input type="text" id="type" name="type" required 
                           value="<?php echo htmlspecialchars($property['type'] ?? ''); ?>">
                </div>

                <!-- Nouveau champ pour l'option (opt) -->
                <div class="form-group">
                    <label for="opt">Option*</label>
                    <select id="opt" name="opt" required>
                        <option value="">-- Sélectionnez une option --</option>
                        <option value="VENTE" <?= ($property['opt'] ?? '') === 'VENTE' ? 'selected' : '' ?>>Vente</option>
                        <option value="LOCATION" <?= ($property['opt'] ?? '') === 'LOCATION' ? 'selected' : '' ?>>Location</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="prix">Prix (FCFA)*</label>
                    <input type="number" id="prix" name="prix" required 
                           value="<?php echo htmlspecialchars($property['prix'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="usag">Usage</label>
                    <input type="text" id="usag" name="usag" 
                           value="<?php echo htmlspecialchars($property['usag'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="taille">Superficie (m²)*</label>
                    <input type="number" id="taille" name="taille" required 
                           value="<?php echo htmlspecialchars($property['taille'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="adresse">Adresse*</label>
                    <textarea id="adresse" name="adresse" required><?php echo htmlspecialchars($property['adresse'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="id_categorie">Catégorie*</label>
                    <select id="id_categorie" name="id_categorie" required>
                        <option value="">-- Sélectionnez une catégorie --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id_categorie']; ?>"
                                <?php if (($property['id_categorie'] ?? '') == $category['id_categorie']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($category['libelle']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Images existantes</label>
                    <?php if (!empty($existing_images)): ?>
                        <div class="image-gallery">
                            <?php foreach ($existing_images as $image): ?>
                                <div class="image-item">
                                    <img src="../assets/uploads/<?php echo htmlspecialchars($image['url']); ?>" 
                                         alt="Image du bien">
                                    <label>
                                        <input type="checkbox" name="existing_images[]" 
                                               value="<?php echo $image['id_photo']; ?>" checked>
                                        Conserver cette image
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Aucune image existante</p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Ajouter de nouvelles images</label>
                    <div class="image-upload">
                        <p>Glissez-déposez vos images ici ou cliquez pour sélectionner</p>
                        <input type="file" id="images" name="images[]" multiple 
                               accept="image/jpeg,image/png,image/gif">
                    </div>
                    <div class="image-preview" id="image-preview"></div>
                    <small>Formats acceptés: JPG, PNG, GIF. Taille maximale: 2MB par image.</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    <a href="mes_proprietes.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // [Le reste du JavaScript reste inchangé]
    </script>
</body>
</html>
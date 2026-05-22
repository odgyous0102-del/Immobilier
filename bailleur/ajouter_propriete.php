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
$uploaded_images = [];

// Récupérer les catégories disponibles
$categories = [];
try {
    $stmt = $db->query("SELECT * FROM categorie");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Erreur lors de la récupération des catégories: " . $e->getMessage();
}

// Vérifier et créer le dossier de téléchargement
$upload_dir = '../uploads/';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        $errors[] = "Impossible de créer le dossier de téléchargement.";
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des données
    $type = trim($_POST['type'] ?? '');
    $opt = trim($_POST['opt'] ?? ''); // Changé de 'option' à 'opt'
    $prix = trim($_POST['prix'] ?? '');
    $usag = trim($_POST['usag'] ?? '');
    $taille = trim($_POST['taille'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $id_categorie = trim($_POST['id_categorie'] ?? '');
    $statut = 'En attente'; // Statut par défaut

    // Validation
    if (empty($type)) $errors[] = "Le type de bien est requis.";
    if (empty($opt)) $errors[] = "L'option de bien est requise."; // Changé de 'option' à 'opt'
    if (empty($prix) || !is_numeric($prix) || $prix <= 0) $errors[] = "Un prix valide est requis.";
    if (empty($taille) || !is_numeric($taille) || $taille <= 0) $errors[] = "Une taille valide est requise.";
    if (empty($adresse)) $errors[] = "L'adresse est requise.";
    if (empty($id_categorie)) $errors[] = "La catégorie est requise.";

    // Traitement des images
    $has_valid_images = false;
    if (empty($errors) && !empty($_FILES['images']['tmp_name'][0])) {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $max_files = 10; // Maximum 10 images
        
        // Vérifier qu'on ne dépasse pas le nombre maximum de fichiers
        if (count($_FILES['images']['tmp_name']) > $max_files) {
            $errors[] = "Vous ne pouvez télécharger que $max_files images maximum.";
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
                            // Générer un nom de fichier unique si le fichier existe déjà
                            $upload_path = '../assets/uploads/'. $file_name;
                            $counter = 1;
                            
                            while (file_exists($upload_path)) {
                                $file_info = pathinfo($file_name);
                                $new_name = $file_info['filename'] . '_' . $counter . '.' . $file_info['extension'];
                                $upload_path ='../assets/uploads/' . $new_name;
                                $counter++;
                            }
                            
                            // Déplacer le fichier uploadé
                            if (move_uploaded_file($file_tmp, $upload_path)) {
                                $uploaded_images[] = $file_name;
                                $has_valid_images = true;
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
    
    if (!$has_valid_images && empty($errors)) {
        $errors[] = "Veuillez sélectionner au moins une image valide.";
    }

    // Insertion en base de données si aucune erreur
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insertion du bien immobilier (changement de 'option' à 'opt')
            $query = "INSERT INTO biensimmobilier 
                     (type, `opt`, prix, statut, usag, taille, adresse, id_bailleur, id_categorie) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            // Convertir l'option en majuscules pour correspondre à la base de données
            $opt_db = strtoupper($opt); // Changé de $option_db à $opt_db
            $stmt->execute([$type, $opt_db, $prix, $statut, $usag, $taille, $adresse, $bailleur_id, $id_categorie]);
            $bien_id = $db->lastInsertId();

            // Insertion des images avec leur nom original (ou modifié si doublon)
            foreach ($uploaded_images as $image) {
                $query = "INSERT INTO photo (url, id_bien) VALUES (?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$image, $bien_id]);
            }

            $db->commit();
            $success = true;
            
            // Réinitialiser les données du formulaire après succès
            $_POST = [];
            $uploaded_images = [];
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Erreur lors de l'ajout du bien: " . $e->getMessage();
            
            // Supprimer les images uploadées en cas d'échec
            foreach ($uploaded_images as $image) {
                if (file_exists($upload_dir . $image)) {
                    unlink($upload_dir . $image);
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
    <title>Ajouter une Propriété</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group { 
            margin-bottom: 1.5rem;
        }
        label { 
            display: block; 
            margin-bottom: 0.5rem; 
            font-weight: bold;
        }
        input, select, textarea { 
            width: 100%; 
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        textarea { 
            min-height: 100px; 
        }
        .error { 
            color: #d9534f;
            background-color: #f8d7da;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            border-left: 4px solid #d9534f;
        }
        .success { 
            color: #28a745;
            background-color: #d4edda;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            border-left: 4px solid #28a745;
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
        .image-upload-container {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            border-radius: 4px;
            margin-bottom: 1rem;
            transition: border-color 0.3s;
        }
        .image-upload-container:hover {
            border-color: #999;
        }
        #image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        #image-preview img {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="form-container">
            <h1>Ajouter une Propriété</h1>
            <a href="mes_proprietes.php" class="btn btn-secondary">Retour à mes propriétés</a>

            <?php if ($success): ?>
                <div class="success">
                    <p>✅ La propriété a été ajoutée avec succès!</p>
                    <p><a href="ajouter_propriete.php" class="btn btn-primary">Ajouter une autre propriété</a></p>
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

            <form method="post" enctype="multipart/form-data" class="property-form">
                <div class="form-group">
                    <label for="type">Type de bien*</label>
                    <input type="text" id="type" name="type" required 
                           value="<?php echo htmlspecialchars($_POST['type'] ?? ''); ?>"
                           placeholder="Ex: Appartement, Maison, Entrepôt...">
                </div>

                <div class="form-group">
                    <label for="opt">Option*</label> <!-- Changé de id="option" à id="opt" -->
                    <select id="opt" name="opt" required> <!-- Changé de name="option" à name="opt" -->
                        <option value="">-- Sélectionnez une option --</option>
                        <option value="LOCATION" <?= (isset($_POST['opt']) && $_POST['opt'] === 'LOCATION') ? 'selected' : '' ?>>Location</option>
                        <option value="VENTE" <?= (isset($_POST['opt']) && $_POST['opt'] === 'VENTE') ? 'selected' : '' ?>>Vente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="prix">Prix (FCFA)*</label>
                    <input type="number" id="prix" name="prix" step="1000" min="0" required 
                           value="<?php echo htmlspecialchars($_POST['prix'] ?? ''); ?>"
                           placeholder="Ex: 250000">
                </div>

                <div class="form-group">
                    <label for="usag">Usage</label>
                    <input type="text" id="usag" name="usag" 
                           value="<?php echo htmlspecialchars($_POST['usag'] ?? ''); ?>"
                           placeholder="Ex: Résidentiel, Commercial...">
                </div>

                <div class="form-group">
                    <label for="taille">Superficie (m²)*</label>
                    <input type="number" id="taille" name="taille" step="0.5" min="0" required 
                           value="<?php echo htmlspecialchars($_POST['taille'] ?? ''); ?>"
                           placeholder="Ex: 120">
                </div>

                <div class="form-group">
                    <label for="adresse">Adresse complète*</label>
                    <textarea id="adresse" name="adresse" rows="3" required
                              placeholder="Ex: 123 Rue du Commerce, Ouagadougou, Burkina Faso"><?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="id_categorie">Catégorie*</label>
                    <select id="id_categorie" name="id_categorie" required>
                        <option value="">-- Sélectionnez une catégorie --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id_categorie']; ?>"
                                <?php if (($_POST['id_categorie'] ?? '') == $category['id_categorie']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($category['libelle']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Images* (maximum 10)</label>
                    <div class="image-upload-container">
                        <p>Glissez-déposez vos images ici ou cliquez pour sélectionner</p>
                        <input type="file" id="images" name="images[]" multiple 
                               accept="image/jpeg,image/png,image/gif" required>
                    </div>
                    <div id="image-preview" class="preview-images"></div>
                    <small>Formats acceptés: JPG, PNG, GIF. Taille maximale: 2MB par image.</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Ajouter la propriété</button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Afficher un aperçu des images sélectionnées
        document.getElementById('images').addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            
            if (this.files) {
                const files = Array.from(this.files);
                
                // Limiter à 10 images
                if (files.length > 10) {
                    alert('Vous ne pouvez sélectionner que 10 images maximum.');
                    this.value = '';
                    return;
                }
                
                files.forEach(file => {
                    if (file.type.match('image.*')) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            preview.appendChild(img);
                        }
                        
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
        
        // Gestion du drag and drop
        const dropArea = document.querySelector('.image-upload-container');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.style.borderColor = '#007bff';
            dropArea.style.backgroundColor = 'rgba(0, 123, 255, 0.1)';
        }
        
        function unhighlight() {
            dropArea.style.borderColor = '#ccc';
            dropArea.style.backgroundColor = '';
        }
        
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            const input = document.getElementById('images');
            
            // Créer un nouveau DataTransfer pour assigner les fichiers
            const dataTransfer = new DataTransfer();
            
            // Ajouter les fichiers existants (si déjà sélectionnés)
            if (input.files) {
                for (let i = 0; i < input.files.length; i++) {
                    if (dataTransfer.items.length < 10) {
                        dataTransfer.items.add(input.files[i]);
                    }
                }
            }
            
            // Ajouter les nouveaux fichiers (jusqu'à 10 maximum)
            for (let i = 0; i < files.length; i++) {
                if (dataTransfer.items.length < 10 && files[i].type.match('image.*')) {
                    dataTransfer.items.add(files[i]);
                }
            }
            
            // Assigner les fichiers à l'input
            input.files = dataTransfer.files;
            
            // Déclencher l'événement change pour afficher les aperçus
            const event = new Event('change');
            input.dispatchEvent(event);
        }
    </script>
</body>
</html>
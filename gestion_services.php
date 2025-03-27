<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once './bd/fonction.php';
require_once './bd/conBD.php';

// Check if the user is an admin
// if (!is_admin()) {
//     exit('Access denied: Only admins can access this page.');
// }

// Handle service deletion
if (isset($_GET['delete'])) {
    $serviceId = (int)$_GET['delete'];
    if (delete_service($serviceId)) {
        echo "Service with ID $serviceId deleted successfully.";
    } else {
        echo "Failed to delete service with ID $serviceId.";
    }
}

// Handle adding or updating a service
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['idpres']) ? (int)$_POST['idpres'] : null;
    $category = sanitize_input($_POST['idcat']);
    $name = sanitize_input($_POST['nompres']);
    $description = sanitize_input($_POST['description']);
    $price = sanitize_input($_POST['prix']);
    $image = null;

   // Handle image upload securely
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = "categorie/uploads/"; // Ensure trailing slash
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    // Create directory if missing
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Validate file type using finfo
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedType = finfo_file($fileInfo, $_FILES['image']['tmp_name']);
    finfo_close($fileInfo);

    if (!in_array($detectedType, $allowedTypes)) {
        die("Error: Invalid file type. Only JPG, PNG, and GIF are allowed.");
    }

    // Validate file size
    if ($_FILES['image']['size'] > $maxSize) {
        die("Error: File too large. Maximum 2MB allowed.");
    }

    // Generate unique filename
    $imageName = uniqid() . '-' . basename($_FILES['image']['name']);
    $imagePath = $uploadDir . $imageName;

    // Move uploaded file
    if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
        $image = $imagePath; // Store the relative path in the database
    } else {
        die("Error: Failed to upload image.");
    }
} elseif (!empty($id)) { // Keep existing image when editing
    $existingService = get_service_by_id($id);
    $image = $existingService['image'] ?? null;
} else {
    $image = null; // No image for new service
}

    // Add or update the service
    if ($id) {
        if (update_service($id, $category, $name, $description, $price, $image)) {
            echo "Service updated successfully.";
        } else {
            echo "Failed to update the service.";
        }
    } else {
        if (add_service($name, $description, $price, $category, $image)) {
            echo "Service added successfully.";
        } else {
            echo "Failed to add the service.";
        }
    }
}

// Fetch all services
try {
    $services = get_all_services();
} catch (Exception $e) {
    die("Error fetching services: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Prestations</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<style>
    /* Style général */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f6fa;
    margin: 0;
    padding: 0;
    color: #2c3e50;
}

/* En-tête admin */
.admin-header {
    background-color: #2c3e50;
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.admin-header h1 {
    margin: 0;
    font-size: 24px;
}

.admin-header nav a {
    color: white;
    text-decoration: none;
    margin-left: 20px;
    font-weight: bold;
}

.admin-header nav a:hover {
    color: #3498db;
}

/* Contenu principal */
.content {
    padding: 30px;
}

/* Formulaire */
.service-form {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.service-form input, .service-form select, .service-form textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.service-form button {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

.service-form button:hover {
    background-color: #2980b9;
}

/* Tableaux */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #2c3e50;
    color: white;
}

tr:hover {
    background-color: #f5f6fa;
}

/* Images dans le tableau */
table img {
    width: 100px;
    height: auto;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

/* Actions */
.actions a {
    margin-right: 10px;
    color: #3498db;
    text-decoration: none;
}

.actions a:hover {
    text-decoration: underline;
}

.actions a.delete {
    color: #e74c3c;
}

.actions a.delete:hover {
    color: #c0392b;
}

/* Prix */
.price {
    color: #2ecc71;
    font-weight: bold;
}

/* Responsive Design */
@media (max-width: 768px) {
    .content {
        padding: 15px;
    }

    table, th, td {
        font-size: 14px;
    }

    .service-form {
        padding: 15px;
    }

    table img {
        width: 80px;
    }
    /* Style du formulaire */
.service-form {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.service-form input,
.service-form select,
.service-form textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.service-form input:focus,
.service-form select:focus,
.service-form textarea:focus {
    border-color: #3498db;
    outline: none;
}

.service-form textarea {
    resize: vertical;
    min-height: 100px;
}

.service-form select {
    appearance: none;
    background: url('data:image/svg+xml;utf8,<svg fill="%232c3e50" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 10px center;
    background-size: 12px;
    padding-right: 30px;
}

.service-form button {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

.service-form button:hover {
    background-color: #2980b9;
}

/* Style du champ de fichier (upload d'image) */
.service-form input[type="file"] {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background: #f9f9f9;
    font-size: 14px;
}

.service-form input[type="file"]:focus {
    border-color: #3498db;
}

/* Responsive Design */
@media (max-width: 768px) {
    .service-form {
        padding: 15px;
    }

    .service-form input,
    .service-form select,
    .service-form textarea {
        font-size: 14px;
    }

    .service-form button {
        width: 100%;
    }
}
}
</style>
<body>
    <header class="admin-header">
        <h1>Tableau de Bord Admin</h1>
        <nav>
            <a href="admin_dashbord.php">Dashboard</a>
            <a href="gestion_utilisateur.php">Utilisateurs</a>
            <a href="gestion_services.php">Prestations</a>
            <a href="gestion_commande.php">Commandes</a>
            <a href="logout.php">Déconnexion</a>
        </nav>
    </header>
    </header>

    <?php
// Fetch categories from database
$categories = get_all_categories(); // You need to implement this function
?>

<div class="content">
    <h1>Gestion des Prestations</h1>

    <div class="service-form">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="idpres" id="edit-id">
            <input type="text" name="nompres" placeholder="Nom de la prestation" required>
            <textarea name="description" placeholder="Description"></textarea>
            <input type="number" name="prix" step="0.01" placeholder="Prix" required>
            
            <select name="idcat" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['idcat'] ?>">
                        <?= htmlspecialchars($category['nomcat']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="file-input">
                <label for="image">Image (JPEG/PNG/GIF, max 2MB):</label>
                <input type="file" name="image" id="image" accept="image/*">
                <?php if (isset($id) && $id): ?>
                    <div class="current-image">
                        <small>Image actuelle :</small>
                        <img src="<?= htmlspecialchars($image ?? '') ?>" alt="Image actuelle" style="max-width: 100px;">
                    </div>
                <?php endif; ?>
            </div>
            
            <button type="submit">Enregistrer</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Catégorie</th>
                <th>Nom</th>
                <th>Description</th>
                <th>Prix</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $service): ?>
            <tr>
                <td>
                    <?php if ($service['image']): ?>
                    <img src="<?= htmlspecialchars($service['image']) ?>" 
                         alt="<?= htmlspecialchars($service['nompres']) ?>" 
                         style="width: 100px; height: auto;">
                    <?php else: ?>
                    Aucune image
                    <?php endif; ?>
                </td>
                <td>
                    <?= htmlspecialchars(
                        get_category_name($service['idcat']) // Update this function to fetch from categories table
                    ) ?>
                </td>
                <td><?= htmlspecialchars($service['nompres']) ?></td>
                <td><?= htmlspecialchars($service['description']) ?></td>
                <td class="price"><?= number_format($service['prix'], 2) ?>€</td>
                <td class="actions">
                    <a href="#" onclick="editService(
                        <?= $service['idpres'] ?>, 
                        <?= $service['idcat'] ?>, 
                        '<?= htmlspecialchars($service['nompres']) ?>', 
                        `<?= htmlspecialchars(str_replace('`', "'", $service['description'])) ?>`, 
                        <?= $service['prix'] ?>
                    )">✏️</a>
                    <a href="?delete=<?= $service['idpres'] ?>" 
                       class="delete" 
                       onclick="return confirm('Confirmer la suppression ?')">❌</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function editService(id, category, name, description, price) {
    const form = document.forms[0];
    form.elements['idpres'].value = id;
    form.elements['idcat'].value = category;
    form.elements['nompres'].value = name;
    form.elements['description'].value = description;
    form.elements['prix'].value = price;
    window.scrollTo(0, 0);
    
    // Update image preview if editing
    const imgPreview = document.querySelector('.current-image img');
    if (imgPreview) {
        imgPreview.src = document.querySelector(`tr[data-id="${id}"] img`)?.src || '';
    }
}
</script>
</body>
</html>

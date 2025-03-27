<?php
session_start();
require_once './bd/conBD.php';
require_once './bd/fonction.php';


// -----------------------------
// PROCESS ORDER STATUS UPDATE
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    // Sanitize inputs
    $order_id   = isset($_POST['order_id']) ? filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT) : null;
    $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';

    if (empty($order_id) || empty($new_status)) {
        echo "<script>alert('Données invalides. Veuillez vérifier les informations.');</script>";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE commandes SET status = ? WHERE idcommande = ?");
            $stmt->execute([$new_status, $order_id]);
            echo "<script>alert('Statut de la commande mis à jour avec succès.'); window.location.href='dashboard.php';</script>";
            exit();
        } catch (PDOException $e) {
            die("Erreur lors de la mise à jour de la commande: " . $e->getMessage());
        }
    }
}

// -----------------------------
// BUILD THE FILTER QUERY
// -----------------------------
$sql = "SELECT 
            c.idcommande, 
            c.iduser, 
            c.idpres, 
            c.quantity, 
            c.status, 
            c.datecom,
            p.nompres, 
            p.image, 
            p.prix 
        FROM commandes c 
        LEFT JOIN prestations p ON c.idpres = p.idpres
        WHERE 1";  // Always true (for easier concatenation)
$params = [];

// Filter by status if provided
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $sql .= " AND c.status = ?";
    $params[] = $_GET['status'];
}

// Filter by minimum price if provided
if (isset($_GET['price_min']) && $_GET['price_min'] !== '') {
    $sql .= " AND p.prix >= ?";
    $params[] = $_GET['price_min'];
}

// Filter by maximum price if provided
if (isset($_GET['price_max']) && $_GET['price_max'] !== '') {
    $sql .= " AND p.prix <= ?";
    $params[] = $_GET['price_max'];
}

// Filter by order date range if provided
if (isset($_GET['date_from']) && $_GET['date_from'] !== '') {
    $sql .= " AND c.datecom >= ?";
    // Append time portion to include the whole day
    $params[] = $_GET['date_from'] . " 00:00:00";
}
if (isset($_GET['date_to']) && $_GET['date_to'] !== '') {
    $sql .= " AND c.datecom <= ?";
    $params[] = $_GET['date_to'] . " 23:59:59";
}

$sql .= " ORDER BY c.datecom DESC";

// -----------------------------
// FETCH ORDERS WITH FILTERS
// -----------------------------
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des commandes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Gestion des Commandes</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            margin-top: 20px;
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
        /* Filter Form Styling */
        .filter-form {
            background: #ffffff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }
        .filter-form .form-group {
            margin-right: 15px;
        }
        .filter-form label {
            font-weight: 500;
        }
        .filter-form input, .filter-form select {
            margin-bottom: 0;
        }
        /* Orders Table Image */
        .order-img {
            width: 80px;
            height: auto;
        }
        .table th, .table td {
            vertical-align: middle !important;
        }
    </style>
</head>
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
    <div class="container">
        <h1 class="mb-4">Dashboard - Gestion des Commandes</h1>
        
        <!-- Filter Form -->
        <form method="GET" class="filter-form form-inline mb-4">
            <div class="form-group mr-2">
                <label for="status" class="mr-2">Statut:</label>
                <select name="status" id="status" class="form-control">
                    <option value="">Tous</option>
                    <option value="pending" <?= (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>En attente</option>
                    <option value="processing" <?= (isset($_GET['status']) && $_GET['status'] == 'processing') ? 'selected' : ''; ?>>En cours</option>
                    <option value="completed" <?= (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'selected' : ''; ?>>Terminée</option>
                    <option value="cancelled" <?= (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'selected' : ''; ?>>Annulée</option>
                </select>
            </div>
            <div class="form-group mr-2">
                <label for="price_min" class="mr-2">Prix min:</label>
                <input type="number" name="price_min" id="price_min" class="form-control" placeholder="Min" value="<?= isset($_GET['price_min']) ? htmlspecialchars($_GET['price_min']) : '' ?>">
            </div>
            <div class="form-group mr-2">
                <label for="price_max" class="mr-2">Prix max:</label>
                <input type="number" name="price_max" id="price_max" class="form-control" placeholder="Max" value="<?= isset($_GET['price_max']) ? htmlspecialchars($_GET['price_max']) : '' ?>">
            </div>
            <div class="form-group mr-2">
                <label for="date_from" class="mr-2">Date depuis:</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?= isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : '' ?>">
            </div>
            <div class="form-group mr-2">
                <label for="date_to" class="mr-2">Date jusqu'à:</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="<?= isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : '' ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filtrer</button>
        </form>
        
        <!-- Orders Table -->
        <table class="table table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>ID Commande</th>
                    <th>ID Utilisateur</th>
                    <th>Prestation</th>
                    <th>Image</th>
                    <th>Quantité</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Date de Commande</th>
                    <th>Mettre à Jour le Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9" class="text-center">Aucune commande trouvée.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['idcommande']); ?></td>
                            <td><?= htmlspecialchars($order['iduser']); ?></td>
                            <td><?= htmlspecialchars($order['nompres']); ?></td>
                            <td>
                                <?php if (!empty($order['image'])): ?>
                                    <img src="<?= htmlspecialchars($order['image']); ?>" alt="<?= htmlspecialchars($order['nompres']); ?>" class="order-img">
                                <?php else: ?>
                                    <span>Aucune image</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($order['quantity']); ?></td>
                            <td><?= htmlspecialchars($order['prix']); ?>€</td>
                            <td><?= htmlspecialchars($order['status']); ?></td>
                            <td><?= htmlspecialchars($order['datecom']); ?></td>
                            <td>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['idcommande']); ?>">
                                    <select name="new_status" class="form-control form-control-sm mr-2">
                                        <option value="">Sélectionnez</option>
                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : ''; ?>>En cours</option>
                                        <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : ''; ?>>Terminée</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                                    </select>
                                    <button type="submit" name="update_order" class="btn btn-primary btn-sm">Mettre à Jour</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

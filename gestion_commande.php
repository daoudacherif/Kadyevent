<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once './bd/fonction.php';
require_once './bd/ConBD.php';

// (Optional) Check if the user is an admin
// if (!function_exists('is_admin') || !is_admin()) {
//     exit('Access denied: Only admins can access this page.');
// }

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    // Note: Use the correct POST field "idcommande"
    $orderId = $_POST['idcommande'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($orderId && $status) {
        if (!update_order_status($orderId, $status)) {
            echo "Error: Failed to update the order status.";
        }
    } else {
        echo "Error: Missing order ID or status.";
    }
}

// Handle order deletion
if (isset($_GET['delete'])) {
    $orderId = (int)$_GET['delete'];
    if (!delete_order($orderId)) {
        echo "Error: Failed to delete order with ID $orderId.";
    }
}

// Build orders filter query based on GET parameters
$status_filter   = isset($_GET['order_status_filter']) ? $_GET['order_status_filter'] : '';
$date_from_filter = isset($_GET['order_date_from']) ? $_GET['order_date_from'] : '';
$date_to_filter   = isset($_GET['order_date_to']) ? $_GET['order_date_to'] : '';

try {
    $sql = "SELECT * FROM commandes WHERE 1";  // Always true for easier concatenation
    $params = [];
    if (!empty($status_filter)) {
        $sql .= " AND status = ?";
        $params[] = $status_filter;
    }
    if (!empty($date_from_filter)) {
        $sql .= " AND datecom >= ?";
        $params[] = $date_from_filter . " 00:00:00";
    }
    if (!empty($date_to_filter)) {
        $sql .= " AND datecom <= ?";
        $params[] = $date_to_filter . " 23:59:59";
    }
    $sql .= " ORDER BY datecom DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching orders: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Commandes</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* General Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
            margin: 0;
            padding: 0;
            color: #2c3e50;
        }
        /* Admin Header */
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
        /* Main Content */
        .content {
            padding: 30px;
        }
        /* Filter Form Styles */
        .filter-form {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0px 2px 4px rgba(0,0,0,0.1);
        }
        .filter-form .form-group {
            margin-right: 15px;
            margin-bottom: 10px;
            display: inline-block;
        }
        .filter-form label {
            font-weight: bold;
            margin-right: 5px;
        }
        .filter-form input,
        .filter-form select {
            padding: 5px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        .filter-form button {
            padding: 6px 12px;
            border: none;
            background: #1abc9c;
            color: #fff;
            border-radius: 3px;
            cursor: pointer;
        }
        .filter-form button:hover {
            background: #16a085;
        }
        /* Tables */
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
        /* Order Status Styles */
        .status-form select {
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        .status-en-attente {
            background-color: #f1c40f;
            color: black;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .status-en-cours {
            background-color: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .status-termine {
            background-color: #2ecc71;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
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
        /* Responsive Design */
        @media (max-width: 768px) {
            .content {
                padding: 15px;
            }
            table, th, td {
                font-size: 14px;
            }
            .filter-form .form-group {
                display: block;
                margin-right: 0;
            }
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

    <div class="content">
        <h1>Gestion des Commandes</h1>
        
        <!-- Filter Form -->
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="order_status_filter">Statut:</label>
                <select name="order_status_filter" id="order_status_filter">
                    <option value="">Tous</option>
                    <option value="en_attente" <?= (isset($_GET['order_status_filter']) && $_GET['order_status_filter'] == 'en_attente') ? 'selected' : ''; ?>>En attente</option>
                    <option value="en_cours" <?= (isset($_GET['order_status_filter']) && $_GET['order_status_filter'] == 'en_cours') ? 'selected' : ''; ?>>En cours</option>
                    <option value="termine" <?= (isset($_GET['order_status_filter']) && $_GET['order_status_filter'] == 'termine') ? 'selected' : ''; ?>>Terminé</option>
                </select>
            </div>
            <div class="form-group">
                <label for="order_date_from">Date depuis:</label>
                <input type="date" name="order_date_from" id="order_date_from" value="<?= isset($_GET['order_date_from']) ? htmlspecialchars($_GET['order_date_from']) : '' ?>">
            </div>
            <div class="form-group">
                <label for="order_date_to">Date jusqu'à:</label>
                <input type="date" name="order_date_to" id="order_date_to" value="<?= isset($_GET['order_date_to']) ? htmlspecialchars($_GET['order_date_to']) : '' ?>">
            </div>
            <button type="submit">Filtrer</button>
        </form>
        
        <!-- Orders Table -->
        <?php if (!empty($orders)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Prestation</th>
                    <th>Quantité</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order):
                    $user = get_user_by_id($order['iduser']);
                    $service = get_service_by_id($order['idpres']);
                ?>
                <tr>
                    <td>#<?= htmlspecialchars($order['idcommande']) ?></td>
                    <td><?= htmlspecialchars($user['name'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($service['nompres'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($order['quantity']) ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($order['datecom']))) ?></td>
                    <td>
                        <form method="POST" class="status-form" style="display:inline;">
                            <input type="hidden" name="idcommande" value="<?= htmlspecialchars($order['idcommande']) ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="en_attente" <?= $order['status'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="en_cours" <?= $order['status'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="termine" <?= $order['status'] === 'termine' ? 'selected' : '' ?>>Terminé</option>
                            </select>
                        </form>
                    </td>
                    <td class="actions">
                        <a href="?delete=<?= htmlspecialchars($order['idcommande']) ?>" class="delete" onclick="return confirm('Confirmer la suppression ?')">❌</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>Aucune commande disponible.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
require_once './bd/fonction.php';
require_once './bd/conBD.php';

session_start();
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: checkout.php");
    exit();
}


// Basic Statistics
$total_users        = count_users();
$total_services     = count_services();
$total_commandes    = count_commandes();
$today_commandes    = count_today_commandes();

// Additional statistics queries
$stmt = $conn->query("SELECT COUNT(*) FROM commandes WHERE status = 'pending'");
$pending_orders = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM commandes WHERE status = 'completed'");
$completed_orders = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM users 
                      WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                        AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$new_users_month = $stmt->fetchColumn();

// ------------------
// Orders Filter Query
// ------------------
$order_sql = "SELECT 
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
              WHERE 1";  // 1 = true for easier concatenation
$order_params = [];

// Use GET parameters with prefix "order_"
if (isset($_GET['order_status']) && $_GET['order_status'] !== '') {
    $order_sql .= " AND c.status = ?";
    $order_params[] = $_GET['order_status'];
}
if (isset($_GET['order_price_min']) && $_GET['order_price_min'] !== '') {
    $order_sql .= " AND p.prix >= ?";
    $order_params[] = $_GET['order_price_min'];
}
if (isset($_GET['order_price_max']) && $_GET['order_price_max'] !== '') {
    $order_sql .= " AND p.prix <= ?";
    $order_params[] = $_GET['order_price_max'];
}
if (isset($_GET['order_date_from']) && $_GET['order_date_from'] !== '') {
    $order_sql .= " AND c.datecom >= ?";
    $order_params[] = $_GET['order_date_from'] . " 00:00:00";
}
if (isset($_GET['order_date_to']) && $_GET['order_date_to'] !== '') {
    $order_sql .= " AND c.datecom <= ?";
    $order_params[] = $_GET['order_date_to'] . " 23:59:59";
}
$order_sql .= " ORDER BY c.datecom DESC";

try {
    $stmt = $conn->prepare($order_sql);
    $stmt->execute($order_params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des commandes: " . $e->getMessage());
}

// ------------------
// Users Filter Query
// ------------------
$user_sql = "SELECT * FROM users WHERE 1";
$user_params = [];

// Use GET parameters with prefix "user_"
if (isset($_GET['user_role']) && $_GET['user_role'] !== '') {
    $user_sql .= " AND role = ?";
    $user_params[] = $_GET['user_role'];
}
if (isset($_GET['user_date_from']) && $_GET['user_date_from'] !== '') {
    $user_sql .= " AND created_at >= ?";
    $user_params[] = $_GET['user_date_from'] . " 00:00:00";
}
if (isset($_GET['user_date_to']) && $_GET['user_date_to'] !== '') {
    $user_sql .= " AND created_at <= ?";
    $user_params[] = $_GET['user_date_to'] . " 23:59:59";
}
if (isset($_GET['user_search']) && $_GET['user_search'] !== '') {
    $user_sql .= " AND (name LIKE ? OR email LIKE ?)";
    $search_term = "%" . $_GET['user_search'] . "%";
    $user_params[] = $search_term;
    $user_params[] = $search_term;
}
$user_sql .= " ORDER BY created_at DESC";

try {
    $stmt = $conn->prepare($user_sql);
    $stmt->execute($user_params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin</title>
    <style>
        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f7f9;
            color: #333;
            line-height: 1.6;
        }
        /* Dashboard Container */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        /* Sidebar Styles */
        .sidebar {
            width: 220px;
            background: #2c3e50;
            padding: 20px;
            color: #ecf0f1;
            position: fixed;
            height: 100%;
        }
        .sidebar .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
        }
        .nav-menu {
            list-style: none;
        }
        .nav-menu li {
            margin-bottom: 15px;
        }
        .nav-menu li a {
            color: #ecf0f1;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        .nav-menu li a:hover {
            color: #1abc9c;
        }
        /* Main Content Styles */
        .content {
            flex: 1;
            padding: 20px;
            background: #ecf0f1;
            MARGIN-LEFT: 16%;
        }
        .content h1, .content h2 {
            margin-bottom: 20px;
            color: #333;
        }
        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }
        .stat-card .number {
            font-size: 20px;
            font-weight: bold;
            color: #1abc9c;
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
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-bottom: 30px;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background: #f7f7f7;
        }
        /* Status Label Styles */
        .status {
            padding: 5px 10px;
            border-radius: 3px;
            text-transform: capitalize;
            display: inline-block;
        }
        .status.pending {
            background: #f39c12;
            color: #fff;
        }
        .status.processing {
            background: #3498db;
            color: #fff;
        }
        .status.completed {
            background: #2ecc71;
            color: #fff;
        }
        .status.cancelled {
            background: #e74c3c;
            color: #fff;
        }
        /* Orders Table Image */
        .order-img {
            width: 80px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">Kady Event</div>
            <ul class="nav-menu">
                <li><a href="admin_dashbord.php">Dashboard</a></li>
                <li><a href="gestion_utilisateur.php">Gérer les Utilisateurs</a></li>
                <li><a href="gestion_services.php">Gérer les Prestations</a></li>
                <li><a href="gestion_commande.php">Commandes</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="content">
            <h1>Tableau de Bord Administrateur</h1>
            
            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Utilisateurs</h3>
                    <div class="number"><?= $total_users ?></div>
                </div>
                <div class="stat-card">
                    <h3>Prestations</h3>
                    <div class="number"><?= $total_services ?></div>
                </div>
                <div class="stat-card">
                    <h3>Commandes Total</h3>
                    <div class="number"><?= $total_commandes ?></div>
                </div>
                <div class="stat-card">
                    <h3>Commandes Aujourd'hui</h3>
                    <div class="number"><?= $today_commandes ?></div>
                </div>
                <div class="stat-card">
                    <h3>Commandes En Attente</h3>
                    <div class="number"><?= $pending_orders ?></div>
                </div>
                <div class="stat-card">
                    <h3>Commandes Terminées</h3>
                    <div class="number"><?= $completed_orders ?></div>
                </div>
                <div class="stat-card">
                    <h3>Nouveaux Utilisateurs (Ce Mois)</h3>
                    <div class="number"><?= $new_users_month ?></div>
                </div>
            </div>

            <!-- Orders Filter Form -->
            <h2>Filtrer les Commandes</h2>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="order_status">Statut:</label>
                    <select name="order_status" id="order_status">
                        <option value="">Tous</option>
                        <option value="pending" <?= (isset($_GET['order_status']) && $_GET['order_status'] == 'pending') ? 'selected' : ''; ?>>En attente</option>
                        <option value="processing" <?= (isset($_GET['order_status']) && $_GET['order_status'] == 'processing') ? 'selected' : ''; ?>>En cours</option>
                        <option value="completed" <?= (isset($_GET['order_status']) && $_GET['order_status'] == 'completed') ? 'selected' : ''; ?>>Terminée</option>
                        <option value="cancelled" <?= (isset($_GET['order_status']) && $_GET['order_status'] == 'cancelled') ? 'selected' : ''; ?>>Annulée</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="order_price_min">Prix min:</label>
                    <input type="number" name="order_price_min" id="order_price_min" placeholder="Min" value="<?= isset($_GET['order_price_min']) ? htmlspecialchars($_GET['order_price_min']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="order_price_max">Prix max:</label>
                    <input type="number" name="order_price_max" id="order_price_max" placeholder="Max" value="<?= isset($_GET['order_price_max']) ? htmlspecialchars($_GET['order_price_max']) : '' ?>">
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
            <h2>Dernières Commandes</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Commande</th>
                        <th>ID Utilisateur</th>
                        <th>Prestation</th>
                        <th>Image</th>
                        <th>Quantité</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Date de Commande</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Aucune commande trouvée.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($order['idcommande']); ?></td>
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
                                <td>
                                    <span class="status <?= htmlspecialchars($order['status']); ?>">
                                        <?= str_replace('_', ' ', htmlspecialchars($order['status'])); ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($order['datecom'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Users Filter Form -->
            <h2>Filtrer les Utilisateurs</h2>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="user_role">Rôle:</label>
                    <select name="user_role" id="user_role">
                        <option value="">Tous</option>
                        <option value="admin" <?= (isset($_GET['user_role']) && $_GET['user_role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?= (isset($_GET['user_role']) && $_GET['user_role'] == 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="prestataire" <?= (isset($_GET['user_role']) && $_GET['user_role'] == 'prestataire') ? 'selected' : ''; ?>>Prestataire</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="user_search">Recherche:</label>
                    <input type="text" name="user_search" id="user_search" placeholder="Nom ou email" value="<?= isset($_GET['user_search']) ? htmlspecialchars($_GET['user_search']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="user_date_from">Date depuis:</label>
                    <input type="date" name="user_date_from" id="user_date_from" value="<?= isset($_GET['user_date_from']) ? htmlspecialchars($_GET['user_date_from']) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="user_date_to">Date jusqu'à:</label>
                    <input type="date" name="user_date_to" id="user_date_to" value="<?= isset($_GET['user_date_to']) ? htmlspecialchars($_GET['user_date_to']) : '' ?>">
                </div>
                <button type="submit">Filtrer</button>
            </form>

            <!-- Users Table -->
            <h2>Derniers Utilisateurs</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Date d'Inscription</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Aucun utilisateur trouvé.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['name']); ?></td>
                                <td><?= htmlspecialchars($user['email']); ?></td>
                                <td><?= ucfirst(htmlspecialchars($user['role'])); ?></td>
                                <td><?= date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div><!-- End Main Content -->
    </div><!-- End Dashboard Container -->
</body>
</html>

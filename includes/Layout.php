<?php
require_once './bd/fonction.php';
require_once './bd/conBD.php';

if (!is_admin()) {
   
}

// Récupérer les données
$total_users = count_users();
$total_services = count_services();
$total_commandes = count_commandes();
$today_commandes = count_today_commandes();
$recent_commandes = get_recent_commandes(5);
$recent_users = get_recent_users(5);
$recent_services = get_recent_services(5);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin</title>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --light-bg: #f5f6fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: var(--primary-color);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100%;
        }

        .logo {
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-menu li {
            margin: 15px 0;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .nav-menu a:hover {
            background: var(--secondary-color);
        }

        .content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            background: var(--light-bg);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 32px;
            color: var(--primary-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            background: var(--primary-color);
            color: white;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
        }

        .status.en-attente { background: #f1c40f; color: black; }
        .status.en-cours { background: #3498db; color: white; }
        .status.termine { background: #2ecc71; color: white; }
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
                <li><a href="../logout.php">Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="content">
            <h1>Tableau de Bord Administrateur</h1>
            
            <!-- Statistiques -->
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
            </div>

            <!-- Dernières Commandes -->
            <h2>Dernières Commandes</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Prestation</th>
                        <th>Date</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_commandes as $order): ?>
                    <tr>
                        <td>#<?= $order['id'] ?></td>
                        <td><?= get_user_by_id($order['user_id'])['name'] ?></td>
                        <td><?= get_service_by_id($order['prestation_id'])['name'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></td>
                        <td>
                            <span class="status <?= $order['status'] ?>">
                                <?= str_replace('_', ' ', $order['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Derniers Utilisateurs -->
            <h2 style="margin-top: 30px;">Derniers Utilisateurs</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Inscription</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                    <tr>
                        <td><?= $user['name'] ?></td>
                        <td><?= $user['email'] ?></td>
                        <td><?= ucfirst($user['role']) ?></td>
                        <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
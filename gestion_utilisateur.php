
<?php
session_start();
require_once 'bd/conBD.php'; // Your DB connection
 require_once 'bd/fonction.php'; // Include any additional functions if needed

// PROCESS FORM SUBMISSION: CREATE / UPDATE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($role)) {
        $error = "Veuillez remplir tous les champs requis.";
    } else {
        // If a password was provided, hash it
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        }
        
        try {
            if (empty($id)) {
                // Create new user
                $stmt = $conn->prepare("INSERT INTO users (name, email, role, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $role, $hashedPassword]);
            } else {
                // Update existing user
                if (!empty($password)) {
                    // Update including password change
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE iduser = ?");
                    $stmt->execute([$name, $email, $role, $hashedPassword, $id]);
                } else {
                    // Update without changing password
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE iduser = ?");
                    $stmt->execute([$name, $email, $role, $id]);
                }
            }
            header("Location: admin_dashbord.php");
            exit();
        } catch (PDOException $e) {
            error_log("Error while inserting/updating user: " . $e->getMessage());
            $error = "Une erreur s'est produite. Veuillez réessayer.";
        }
    }
}

// PROCESS DELETE REQUEST
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE iduser = ?");
        $stmt->execute([$deleteId]);
        header("Location: admin_dashbord.php");
        exit();
    } catch (PDOException $e) {
        error_log("Error while deleting user: " . $e->getMessage());
        $error = "Erreur lors de la suppression de l'utilisateur.";
    }
}

// FETCH ALL USERS
try {
    $stmt = $conn->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching users: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Utilisateurs</title>
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
.user-form, .service-form {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.user-form input, .service-form input, .service-form select, .service-form textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.user-form button, .service-form button {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

.user-form button:hover, .service-form button:hover {
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

/* Actions */
.actions a {
    margin-right: 10px;
    color: #3498db;
    text-decoration: none;
}

.actions a:hover {
    text-decoration: underline;
}

/* Statuts des commandes */
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

/* Prix */
.price {
    color: #2ecc71;
    font-weight: bold;
}

/* Boutons de suppression */
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

    .user-form, .service-form {
        padding: 15px;
    }
}
</style>

    <script>
        // Simple JavaScript to fill the form for editing a user
        function editUser(id, name, email, role) {
            document.getElementById('edit-id').value = id;
            document.querySelector('input[name="name"]').value = name;
            document.querySelector('input[name="email"]').value = email;
            document.querySelector('select[name="role"]').value = role;
            // Optionally, you may want to clear the password field when editing
            document.getElementById('password-field').value = "";
        }
    </script>
</head>
<body>
<div class="content">
    <h1>Gestion des Utilisateurs</h1>

    <!-- Display any errors -->
    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Formulaire Ajout/Édition -->
    <div class="user-form">
        <form method="POST">
            <input type="hidden" name="id" id="edit-id">
            <input type="text" name="name" placeholder="Nom complet" required>
            <input type="email" name="email" placeholder="Email" required>
            <select name="role" required>
                <option value="client">Client</option>
                <option value="prestataire">Prestataire</option>
                <option value="admin">Admin</option>
            </select>
            <input type="password" name="password" placeholder="Mot de passe" id="password-field">
            <button type="submit">Enregistrer</button>
        </form>
    </div>

    <!-- Liste des utilisateurs -->
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                        <td class="actions">
                            <a href="#" onclick="editUser('<?= $user['iduser'] ?>', '<?= addslashes($user['name']) ?>', '<?= addslashes($user['email']) ?>', '<?= $user['role'] ?>')">✏️</a>
                            <a href="?delete=<?= $user['iduser'] ?>" class="delete" onclick="return confirm('Confirmer la suppression ?')">❌</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">Aucun utilisateur trouvé.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>

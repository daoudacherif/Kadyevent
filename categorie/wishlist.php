<?php
session_start();
require_once '../bd/conBD.php'; // Include database connection
require_once '../bd/fonction.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to login if user is not logged in
if (!isset($_SESSION['iduser'])) {
    header('Location: login.php');
    exit();
}

$iduser = $_SESSION['iduser']; // Get the logged-in user's ID

// Function to add an order
function add_order($conn, $iduser, $idpres, $quantity) {
    try {
        if (!$conn || !($conn instanceof PDO)) {
            throw new Exception("Invalid database connection");
        }

        if (!is_numeric($iduser) || !is_numeric($idpres) || !is_numeric($quantity) || $quantity < 1) {
            throw new Exception("Invalid input: iduser=$iduser, idpres=$idpres, quantity=$quantity");
        }

        // Fetch the latest idlimit from limit_journalier
        $stmt = $conn->prepare("SELECT idlimit FROM limit_journalier ORDER BY created_at DESC LIMIT 1");
        $stmt->execute();
        $limitRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$limitRow || empty($limitRow['idlimit'])) {
            throw new Exception("No valid idlimit found in limit_journalier.");
        }

        $idlimit = $limitRow['idlimit'];

        $sql = "INSERT INTO commandes (iduser, idpres, quantity, status, idlimit, datecom) 
                VALUES (?, ?, ?, 'pending', ?, NOW())";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . implode(" ", $conn->errorInfo()));
        }

        $success = $stmt->execute([$iduser, $idpres, $quantity, $idlimit]);

        if (!$success) {
            throw new Exception("SQL Execute failed: " . implode(" ", $stmt->errorInfo()));
        }

        return true; 
    } catch (Exception $e) {
        die("Erreur: " . $e->getMessage());
        error_log("Order Error: " . $e->getMessage());
        return false;
    }
}


// Function to remove from wishlist
function remove_from_wishlist($conn, $iduser, $idpres) {
    try {
        // Ensure the database connection is valid
        if (!$conn || !($conn instanceof PDO)) {
            throw new Exception("Invalid database connection");
        }

        // Validate input parameters
        if (!is_numeric($iduser) || !is_numeric($idpres)) {
            throw new Exception("Invalid parameter types");
        }

        // Prepare and execute the SQL query
        $sql = "DELETE FROM wishlist WHERE iduser = ? AND idpres = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . implode(" ", $conn->errorInfo()));
        }

        $success = $stmt->execute([$iduser, $idpres]);

        if (!$success) {
            throw new Exception("Execute failed: " . implode(" ", $stmt->errorInfo()));
        }

        return true; // Success
    } catch (Exception $e) {
        error_log("Wishlist Removal Error: " . $e->getMessage());
        return false; // Failure
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_order'])) {
        // Sanitize inputs
        $idpres = isset($_POST['idpres']) ? filter_var($_POST['idpres'], FILTER_SANITIZE_NUMBER_INT) : null;
        $quantity = isset($_POST['quantity']) ? filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT) : 1;

        // Validate inputs
        if (empty($idpres) || empty($quantity) || $quantity < 1) {
            echo "<script>alert('Données invalides. Veuillez vérifier les informations.');</script>";
            exit();
        }

        // Fetch the latest idlimit from limit_journalier
        $stmt = $conn->prepare("SELECT idlimit FROM limit_journalier ORDER BY created_at DESC LIMIT 1");
        $stmt->execute();
        $limitRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$limitRow || empty($limitRow['idlimit'])) {
            echo "<script>alert('Erreur: Aucun idlimit trouvé. Veuillez contacter l\'administrateur.');</script>";
            exit();
        }

        $idlimit = $limitRow['idlimit'];

        // Add order
        if (add_order($conn, $iduser, $idpres, $quantity, $idlimit)) {
            echo "<script>
                    alert('Commande passée avec succès.');
                    window.location.href = 'wishlist.php'; // Redirect instead of reload
                  </script>";
            exit();
        } else {
            echo "<script>alert('Erreur lors de la commande. Veuillez réessayer.');</script>";
        }

    } elseif (isset($_POST['remove_wishlist'])) {
        // Sanitize inputs
        $idpres = isset($_POST['idpres']) ? filter_var($_POST['idpres'], FILTER_SANITIZE_NUMBER_INT) : null;

        // Validate inputs
        if (empty($idpres)) {
            echo "<script>alert('Données invalides. Veuillez vérifier les informations.');</script>";
            exit();
        }

        // Remove from wishlist
        if (remove_from_wishlist($conn, $iduser, $idpres)) {
            echo "<script>
                    alert('Produit retiré de la wishlist avec succès.');
                    window.location.href = 'wishlist.php'; // Redirect instead of reload
                  </script>";
            exit();
        } else {
            echo "<script>alert('Erreur lors de la suppression. Veuillez réessayer.');</script>";
        }
    }
}

// Fetch wishlist items
try {
    // Ensure the database connection is valid
    if (!$conn || !($conn instanceof PDO)) {
        throw new Exception("Invalid database connection");
    }

    $sql = "SELECT p.idpres, p.nompres, p.image, p.prix 
            FROM wishlist w 
            JOIN prestations p ON w.idpres = p.idpres 
            WHERE w.iduser = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . implode(" ", $conn->errorInfo()));
    }

    $stmt->execute([$iduser]);
    $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching wishlist items: " . $e->getMessage());
}
?>

<?php include '../includes/headercat.php'; ?>

<div class="wishlist-section section pt-120 pb-90">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2>Ma Wishlist</h2>
                <div class="table-responsive">
                    <table class="table text-center">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Produit</th>
                                <th>Prix</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($wishlistItems)): ?>
                                <tr>
                                    <td colspan="4">Votre wishlist est vide.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($wishlistItems as $item): ?>
                                    <tr>
                                        <td><img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['nompres']) ?>" width="50"></td>
                                        <td><?= htmlspecialchars($item['nompres']) ?></td>
                                        <td><?= htmlspecialchars($item['prix']) ?>€</td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="idpres" value="<?= $item['idpres'] ?>">
                                                <button type="submit" name="remove_wishlist" class="btn btn-danger">Supprimer</button>
                                            </form>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="idpres" value="<?= $item['idpres'] ?>">
                                                <input type="number" name="quantity" value="1" min="1" class="form-control" style="width: 60px; display:inline;">
                                                <button type="submit" name="add_order" class="btn btn-success">Commander</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footercat.php'; ?>

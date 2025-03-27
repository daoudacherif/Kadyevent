<?php
session_start();
require_once '../bd/conBD.php';
require_once '../bd/fonction.php';

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to login if user is not logged in
if (!isset($_SESSION['iduser'])) {
    header('Location: ../checkout.php');
    exit();
}

$iduser = $_SESSION['iduser']; // Get the logged-in user's ID


// Handle adding to wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    $idpres = filter_var($_POST['idpres'], FILTER_SANITIZE_NUMBER_INT); // Sanitize input

    try {
        // Check if the product is already in the wishlist
        $checkStmt = $conn->prepare("SELECT * FROM wishlist WHERE iduser = ? AND idpres = ?");
        $checkStmt->execute([$iduser, $idpres]);

        if ($checkStmt->rowCount() === 0) {
            // Add to wishlist if not already present
            $insertStmt = $conn->prepare("INSERT INTO wishlist (iduser, idpres) VALUES (?, ?)");
            $insertStmt->execute([$iduser, $idpres]);
        }

        // Redirect back to the wishlist page
        header('Location: wishlist.php');
        exit();
    } catch (PDOException $e) {
        die("Error processing wishlist: " . $e->getMessage());
    }
}

// Fetch all decoration products from the database
try {
    $stmt = $conn->query("SELECT p.*, c.nomcat 
                          FROM prestations p
                          JOIN categorie c ON p.idcat = c.idcat
                          WHERE LOWER(c.nomcat) = 'decoration'");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}

?>

<?php include '../includes/headercat.php'; ?>

<div class="container mt-5">
    <h2 class="text-center">Nos Produits</h2>
    <div class="row">
        <?php if (empty($products)): ?>
            <div class="col-12">
                <p class="text-center">Aucun produit disponible pour le moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <?php $imagePath = getImageById($product['idpres'], $conn); ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="image">
                            <a href="product-details.php?id=<?= htmlspecialchars($product['idpres']) ?>" class="img">
                                <img src="<?= htmlspecialchars($imagePath) ?>" 
                                     alt="<?= htmlspecialchars($product['nompres']) ?>" 
                                     class="img-fluid">
                            </a>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['nompres']) ?></h5>
                            <p class="card-text">Prix: <?= htmlspecialchars($product['prix']) ?>€</p>

                            <!-- Add to Wishlist Form -->
                            <form action="deco.php" method="POST">
                                <input type="hidden" name="idpres" value="<?= htmlspecialchars($product['idpres']) ?>">
                                <button type="submit" name="add_to_wishlist" class="btn btn-outline-primary">
                                    Ajouter à la Wishlist
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footercat.php'; ?>
</body>
</html>

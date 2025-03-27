<?php
session_start();
if (isset($_SESSION["logged_in"])) {
   header("Location: index.php");
}
?>


<?php
// Handle Login
if (isset($_POST["login"])) {
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST["password"]);

    // Vérification des champs
    if (empty($email) || empty($password)) {
        echo '<div class="alert alert-danger">Tous les champs sont obligatoires.</div>';
        exit();
    }

    require_once "./bd/conBD.php";

    if (!$conn) {
        echo '<div class="alert alert-danger">Erreur : Impossible de se connecter à la base de données.</div>';
        exit();
    }

    try {
        // Recherche de l'utilisateur dans la table `users`
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification du mot de passe et traitement
        if ($user && password_verify($password, $user["password"])) {
            // Démarrer la session et stocker les informations utilisateur
            $_SESSION["iduser"] = $user["iduser"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["logged_in"] = true;

            // Redirection en fonction du rôle
            switch ($user["role"]) {
                case "admin":
                    header("Location: admin_dashbord.php");
                    break;
                case "client":
                    header("Location: index.php");
                    break;
                case "prestataire":
                    header("Location: prestataire_dashbord.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            echo '<div class="alert alert-danger">Email ou mot de passe incorrect.</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Erreur de conn : ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Handle Registration
if (isset($_POST["submit"])) {
    // Récupération des données
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST["password"]);
    $password2 = trim($_POST["password2"]);
    $fullname = trim($_POST["name"]);
    $role = trim($_POST["role"]); // Récupérer le rôle

    // Validation des rôles autorisés
    $allowed_roles = ['client', 'prestataire','admin']; // Ne pas permettre 'admin'
    if (!in_array($role, $allowed_roles)) {
        echo "<div class='alert alert-danger'>Rôle invalide.</div>";
        exit();
    }

    // Validation des autres champs
    $errors = [];
    if (empty($email) || empty($password) || empty($password2) || empty($fullname)) {
        $errors[] = "Tous les champs sont obligatoires.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit comporter au moins 8 caractères.";
    }
    if ($password !== $password2) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Si aucune erreur, procéder à l'inscription
    if (empty($errors)) {
        try {
            require_once "./bd/conBD.php";

            // Vérifier si l'email existe déjà
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo "<div class='alert alert-danger'>Cet email est déjà utilisé.</div>";
            } else {
                // Hashage du mot de passe
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insertion des données dans la table
                $sql = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':name', $fullname, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
                $stmt->bindParam(':role', $role, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Inscription réussie.</div>";
                    header("Location: index.php");
                    exit();
                } else {
                    echo "<div class='alert alert-danger'>Une erreur est survenue lors de l'enregistrement.</div>";
                }
            }
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        foreach ($errors as $error) {
            echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<!-- Checkout Section Start-->
<div class="cart-section section pt-120 pb-90">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-12 mb-30">
                <div id="checkout-accordion" class="panel-group">
                    <div class="panel single-accordion">
                        <a class="accordion-head" data-toggle="collapse" data-parent="#checkout-accordion" href="#checkout-method">1. Checkout Method</a>
                        <div id="checkout-method" class="collapse show">
                            <div class="checkout-method accordion-body fix">
                                <ul class="checkout-method-list">
                                    <li class="active" data-form="checkout-login-form">Login</li>
                                    <li data-form="checkout-register-form">Register</li>
                                </ul>
                                <form action="" method="POST" class="checkout-login-form">
                                    <div class="row">
                                        <div class="input-box col-md-6 col-12 mb-20"><input type="email" name="email" placeholder="Email Address"></div>
                                        <div class="input-box col-md-6 col-12 mb-20"><input type="password" name="password" placeholder="Password"></div>
                                        <div class="input-box col-md-6 col-12 mb-20"><input type="submit" name="login" value="Login"></div>
                                    </div>
                                </form>
                                <form action="" method="POST" class="checkout-register-form" style="display: none;">
                                    <div class="row">
                                        <div class="input-box col-md-6 col-12 mb-20">
                                            <input type="text" name="name" placeholder="Your Name" required>
                                        </div>
                                        <div class="input-box col-md-6 col-12 mb-20">
                                            <input type="email" name="email" placeholder="Email Address" required>
                                        </div>
                                        <div class="input-box col-md-6 col-12 mb-20">
                                            <input type="password" name="password" placeholder="Password" required>
                                        </div>
                                        <div class="input-box col-md-6 col-12 mb-20">
                                            <input type="password" name="password2" placeholder="Confirm Password" required>
                                        </div>
                                        <div class="input-box col-md-6 col-12 mb-20">
                                            <select name="role" required>
                                                <option value="client">Client</option>
                                                <option value="prestataire">Prestataire</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                        </div>
                                        <div class="input-box col-md-6 col-12 mb-20">
                                            <input type="checkbox" name="agree" value="yes" required> Accept Terms
                                        </div>
                                        <div class="input-box col-md-6 col-12 mb-20">
                                            <input type="submit" name="submit" value="Register">
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php
ob_end_flush(); // Send the output to the browser
?><?php include 'includes/footer.php'; ?>
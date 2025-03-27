<?php
// functions.php

require_once 'conBD.php'; // Inclure la connexion à la base

/**************************************
 * FONCTIONS D'AUTHENTIFICATION
 **************************************/

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';
}

function is_client() {
    return is_logged_in() && isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'client';
}

function is_team() {
    return is_logged_in() && isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'prestataire';
}

/**************************************
 * GESTION DES UTILISATEURS
 **************************************/

function get_all_users() {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_user_by_id($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE iduser = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function add_user($name, $email, $password, $role) {
    global $conn;
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$name, $email, $hashed_password, $role]);
}

function update_user($id, $name, $email, $role) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
    return $stmt->execute([$name, $email, $role, $id]);
}

function delete_user($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$id]);
}

function email_exists($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

/**************************************
 * GESTION DES PRESTATIONS
 **************************************/

function get_all_services() {
    global $conn;
    $stmt = $conn->query("SELECT * FROM prestations");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_service_by_id($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM prestations WHERE idpres = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// function add_service removed to avoid redeclaration error

// function update_service removed to avoid redeclaration error

function delete_service($idpres ) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM prestations WHERE idpres = ?");
    return $stmt->execute([$idpres ]);
}

function get_services_by_category($category) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM prestations WHERE category = ?");
    $stmt->execute([$category]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**************************************
 * GESTION DES COMMANDES
 **************************************/

//  function add_order($iduser, $idpres, $quantity) {
//     global $conn; // Ensure the database connection is available
//     try {
//         $stmt = $conn->prepare("INSERT INTO orders (iduser, idpres, quantity) VALUES (?, ?, ?)");
//         $stmt->execute([$iduser, $idpres, $quantity]);
//         return true; // Success
//     } catch (PDOException $e) {
//         error_log("Error in add_order: " . $e->getMessage()); // Log the error
//         return false; // Failure
//     }
// }

function get_all_commandes() {
    global $conn;
    $stmt = $conn->query("SELECT * FROM commandes");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_user_commandes($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM commandes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function update_order_status($order_id, $status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE commandes SET status = ? WHERE idcommande = ?");
    return $stmt->execute([$status, $order_id]);
}

function delete_order($order_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM commandes WHERE id = ?");
    return $stmt->execute([$order_id]);
}

/**************************************
 * GESTION DES LIMITES D'EQUIPE
 **************************************/

function get_team_commandes_today($team_member_id) {
    global $conn;
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT commandes_processed FROM team_limits WHERE team_member_id = ? AND date = ?");
    $stmt->execute([$team_member_id, $today]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['commandes_processed'] : 0;
}

function increment_team_commandes($team_member_id) {
    global $conn;
    $today = date('Y-m-d');
    $current = get_team_commandes_today($team_member_id);
    
    if ($current === 0) {
        $stmt = $conn->prepare("INSERT INTO team_limits (team_member_id, date, commandes_processed) VALUES (?, ?, 1)");
        return $stmt->execute([$team_member_id, $today]);
    } else {
        $stmt = $conn->prepare("UPDATE team_limits SET commandes_processed = commandes_processed + 1 WHERE team_member_id = ? AND date = ?");
        return $stmt->execute([$team_member_id, $today]);
    }
}

function can_process_more_commandes($team_member_id) {
    return get_team_commandes_today($team_member_id) < 5;
}

/**************************************
 * FONCTIONS UTILITAIRES
 **************************************/

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function display_error($message) {
    return '<div class="alert alert-danger">' . $message . '</div>';
}

function display_success($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
}

// Gestion des messages flash
function flash_message($name, $message = '') {
    if (!empty($message)) {
        $_SESSION[$name] = $message;
    } else {
        if (isset($_SESSION[$name])) {
            $message = $_SESSION[$name];
            unset($_SESSION[$name]);
            return $message;
        }
        return null;
    }
}
function count_users() {
    global $conn;

    if (!$conn) {
        throw new Exception("Connexion à la base de données non initialisée.");
    }

    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        throw new Exception("Erreur lors du comptage des utilisateurs : " . $e->getMessage());
    }
}

function count_services() {
    global $conn;
    $stmt = $conn->query("SELECT COUNT(*) FROM prestations");
    return $stmt->fetchColumn();
}

function count_commandes() {
    global $conn;
    $stmt = $conn->query("SELECT COUNT(*) FROM commandes");
    return $stmt->fetchColumn();
}

function count_today_commandes() {
    global $conn;
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) FROM commandes WHERE DATE(datecom) = ?");
    $stmt->execute([$today]);
    return $stmt->fetchColumn();
}

function get_recent_commandes($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM commandes ORDER BY datecom DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_recent_users($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_recent_services($limit = 5) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM prestations ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonctions pour les commandes
function get_all_orders() {
    global $conn;
    $stmt = $conn->query("SELECT * FROM commandes");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function add_service($nompres, $description, $prix, $idcat, $image) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO prestations (nompres, description, prix, idcat, image) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$nompres, $description, $prix, $idcat, $image]);
}

function update_service($idpres, $nompres, $description, $prix, $idcat, $image) {
    global $conn;
    if ($image) {
        $stmt = $conn->prepare("UPDATE prestations SET nompres = ?, description = ?, prix = ?, idcat = ?, image = ? WHERE idpres = ?");
        return $stmt->execute([$nompres, $description, $prix, $idcat, $image, $idpres]);
    } else {
        $stmt = $conn->prepare("UPDATE prestations SET nompres = ?, description = ?, prix = ?, idcat = ? WHERE idpres = ?");
        return $stmt->execute([$nompres, $description, $prix, $idcat, $idpres]);
    }
}
function get_user_orders($iduser) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM commandes WHERE iduser = ?");
    $stmt->execute([$iduser]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Duplicate function get_service_by_id removed

function add_to_wishlist($iduser, $idpres, $quantity = 1) {
    global $conn;
    try {
        $stmt = $conn->prepare("INSERT INTO commandes (iduser, idpres, quantity, status) VALUES (?, ?, ?, 'en_attente')");
        $stmt->execute([$iduser, $idpres, $quantity]);
    } catch (PDOException $e) {
        throw $e; // Throw exception to handle in the caller
    }
}
// In fonctions.php
function get_all_categories() {
    global $conn;
    $stmt = $conn->query("SELECT idcat, nomcat FROM categorie ORDER BY nomcat");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_category_name($idcat) {
    global $conn;
    $stmt = $conn->prepare("SELECT nomcat FROM categorie WHERE idcat = ?");
    $stmt->execute([$idcat]);
    return $stmt->fetchColumn();
}
// Function to get the correct image path
function getImageById($idpres, $conn) {
    try {
        $stmt = $conn->prepare("SELECT image FROM prestations WHERE idpres = ?");
        $stmt->execute([$idpres]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && !empty($result['image'])) {
            // Ensure the correct base path
            return '../' . $result['image']; // Correct path
        } else {
            return '../images/default-image.jpg'; // Default image if none found
        }
    } catch (PDOException $e) {
        die("Error fetching image: " . $e->getMessage());
    }
}

<?php
session_start();

// Liste des pages autorisées
$pages = [
    'index' => 'index.php', // Page d'accueil par défaut
    'checkout' => 'checkout.php',
    'cart' => 'cart.php',
    'under-construction' => 'under-construction.php',
    'contact' => 'contact.php',
    'about' => 'about.php',
    'wishlist' => 'wishlist.php',
    'product-details' => 'product-details.php',
    'shop' => 'shop.php',
];

// Récupère la page demandée, avec protection contre les entrées malveillantes
$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING) ?? 'index';

// Vérifie si la page demandée existe dans la liste
if (array_key_exists($page, $pages)) {
    require_once $pages[$page];
} else {
    // Page d'erreur par défaut
    http_response_code(404); // Définit le code d'erreur HTTP
    require_once 'under-construction.php';
}

// Redirection

// Vérifiez si une redirection est définie dans la session
if (isset($_SESSION['redirect_to'])) {
    $redirectTo = $_SESSION['redirect_to'];
    unset($_SESSION['redirect_to']); // Supprimez la variable pour éviter les redirections répétées
    header("Location: $redirectTo");
    exit();
} else {
    // Si aucune redirection n'est définie, redirigez vers une page par défaut
    header("Location: index.php");
    exit();
}
?>


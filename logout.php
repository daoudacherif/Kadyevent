<?php
// Start the session
session_start();

// Clear all session data
$_SESSION = [];

// If the session uses cookies, clear the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),    // The session name
        '',                // Empty the cookie value
        time() - 42000,    // Set the expiration date to the past
        $params["path"],   // Path from session settings
        $params["domain"], // Domain from session settings
        $params["secure"], // HTTPS-only if applicable
        $params["httponly"] // HTTP-only flag
    );
}

// Completely destroy the session
session_destroy();

// Redirect the user to the homepage or login page
header("Location: index.php");
exit(); // Ensure no further code runs after the redirection
?>
<?php
// Initialize the session
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Check if a redirect parameter is set
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $redirect = htmlspecialchars($_GET['redirect']);
    header("location: $redirect");
} else {
    // Default redirect to login page
    header("location: admin_login.php");
}
exit;
?> 
<?php
// Start session and set security headers
if (session_status() === PHP_SESSION_NONE) {
    session_start();

    // Generate a CSRF token if it doesn't already exist
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a secure random token
    }
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");

// Restrict access to the dashboard if MFA is not completed
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage === 'dashboard.php' && (!isset($_SESSION['mfa_completed']) || $_SESSION['mfa_completed'] !== true)) {
    header("Location: mfa.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Of Tamerial</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <h1>Bank Of Tamerial</h1>
    <nav>
        <a href="index.php">Home</a>
        <?php if (isset($_SESSION['fullName'])): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>
<main>
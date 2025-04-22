<?php
session_start();
require 'dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_type = $_POST['account_type'];
    $user_id = $_SESSION['user_id'];

    // Validate input
    if (empty($account_type) || !in_array($account_type, ['checking', 'savings'])) {
        header("Location: dashboard.php?error=Invalid account type.");
        exit;
    }

    // Check if the user already has 2 accounts of the same type
    $stmt = $pdo->prepare("SELECT COUNT(*) AS account_count FROM accounts WHERE user_id = ? AND account_type = ?");
    $stmt->execute([$user_id, $account_type]);
    $account_count = $stmt->fetchColumn();

    if ($account_count >= 2) {
        echo "<script>
            alert('You cannot create more than 2 $account_type accounts.');
            window.location.href = 'dashboard.php';
        </script>";
        exit;
    }

    // Generate a unique 8-digit account number
    do {
        $account_number = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT id FROM accounts WHERE account_number = ?");
        $stmt->execute([$account_number]);
    } while ($stmt->fetch());

    try {
        // Insert the new account into the database
        $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, account_type, balance) VALUES (?, ?, ?, 0)");
        $stmt->execute([$user_id, $account_number, $account_type]);

        header("Location: dashboard.php?success=Account created successfully.");
        exit;
    } catch (Exception $e) {
        header("Location: dashboard.php?error=" . urlencode("Failed to create account: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: dashboard.php");
    exit;
}
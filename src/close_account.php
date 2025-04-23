<?php
session_start();
require 'dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: dashboard.php?error=Invalid CSRF token.");
        exit;
    }

    $account_id = $_POST['account_id'];

    // Validate input
    if (empty($account_id)) {
        header("Location: dashboard.php?error=Invalid account ID.");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Ensure the account belongs to the logged-in user
        $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$account_id, $_SESSION['user_id']]);
        $account = $stmt->fetch();

        if (!$account) {
            throw new Exception("Unauthorized access or account not found.");
        }

        // Ensure the account balance is zero
        if ($account['balance'] > 0) {
            throw new Exception("Account balance must be zero to close the account.");
        }

        // Delete the account
        $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
        $stmt->execute([$account_id]);

        $pdo->commit();
        header("Location: dashboard.php?success=Account closed successfully.");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = urlencode("Something went wrong. Please try again.");
        header("Location: dashboard.php?error=$error_message");
        exit;
    }
} else {
    header("Location: dashboard.php");
    exit;
}
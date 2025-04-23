<?php
session_start();
require 'dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: dashboard.php?error=Invalid CSRF token.");
        exit;
    }

    $sender_account_id = $_POST['sender_account_id'];
    $recipient_account_number = $_POST['recipient_account_number'];
    $amount = $_POST['amount'];
    $description = $_POST['description'] ?? ''; // Optional description

    // Validate input
    if (empty($sender_account_id) || empty($recipient_account_number) || empty($amount) || $amount <= 0) {
        header("Location: dashboard.php?error=Invalid input.");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Ensure the sender account belongs to the logged-in user
        $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$sender_account_id, $_SESSION['user_id']]);
        $sender_account = $stmt->fetch();

        if (!$sender_account) {
            throw new Exception("Unauthorized access or account not found.");
        }

        // Check if the sender has sufficient funds
        if ($sender_account['balance'] < $amount) {
            throw new Exception("Insufficient funds.");
        }

        // Get recipient account ID
        $stmt = $pdo->prepare("SELECT id FROM accounts WHERE account_number = ?");
        $stmt->execute([$recipient_account_number]);
        $recipient_account = $stmt->fetch();

        if (!$recipient_account) {
            throw new Exception("Recipient account not found.");
        }

        $recipient_account_id = $recipient_account['id'];

        // Deduct amount from sender atomically
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND balance >= ?");
        $stmt->execute([$amount, $sender_account_id, $amount]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Insufficient funds or invalid account.");
        }

        // Add amount to recipient
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $recipient_account_id]);

        // Record transaction for sender
        $stmt = $pdo->prepare("INSERT INTO transactions (account_id, type, amount, description) VALUES (?, 'transfer', ?, ?)");
        $stmt->execute([$sender_account_id, -$amount, $description]);

        // Record transaction for recipient
        $stmt = $pdo->prepare("INSERT INTO transactions (account_id, type, amount, description) VALUES (?, 'transfer', ?, ?)");
        $stmt->execute([$recipient_account_id, $amount, $description]);

        $pdo->commit();
        header("Location: dashboard.php?success=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        // Redirect back to dashboard with error message
        $error_message = urlencode("Something went wrong. Please try again.");
        header("Location: dashboard.php?error=$error_message");
        exit;
    }
} else {
    header("Location: dashboard.php");
    exit;
}
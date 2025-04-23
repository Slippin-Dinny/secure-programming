<?php
require 'dbcon.php';

if (!isset($_GET['account_id'])) {
    echo "<p class='error'>Invalid account ID.</p>";
    exit;
}

$accountId = intval($_GET['account_id']);

// Fetch transactions for the account
$stmt = $pdo->prepare("SELECT type, amount, description, created_at FROM transactions WHERE account_id = ? ORDER BY created_at DESC");
$stmt->execute([$accountId]);
$transactions = $stmt->fetchAll();

if (empty($transactions)) {
    echo "<p>No transactions found for this account.</p>";
} else {
    echo "<table class='transactions-table'>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>";
    foreach ($transactions as $transaction) {
        echo "<tr>
                <td>" . htmlspecialchars($transaction['type']) . "</td>
                <td>€" . htmlspecialchars(number_format($transaction['amount'], 2)) . "</td>
                <td>" . htmlspecialchars($transaction['description'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($transaction['created_at']) . "</td>
              </tr>";
    }
    echo "</tbody></table>";
}
?>
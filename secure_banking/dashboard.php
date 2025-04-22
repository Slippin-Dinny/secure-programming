<?php
session_start();
require 'dbcon.php';
include 'header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user accounts
$stmt = $pdo->prepare("SELECT id, account_number, account_type, balance FROM accounts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$accounts = $stmt->fetchAll();
?>

<?php if (isset($_GET['error'])): ?>
    <script>
        alert("<?= htmlspecialchars(urldecode($_GET['error'])) ?>");
    </script>
<?php endif; ?>

<div class="dashboard-container">
    <h2>Welcome to your Dashboard</h2>

    <p>Hello, <strong><?= htmlspecialchars($_SESSION['fullName']) ?></strong>!</p>

    <p>This is your basic dashboard. You’re successfully logged in.</p>

    <h3>Your Accounts</h3>
    <?php if (!empty($accounts)): ?>
        <table class="accounts-table">
            <thead>
                <tr>
                    <th>Account Number</th>
                    <th>Account Type</th>
                    <th>Balance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($accounts as $account): ?>
                <tr>
                    <td><?= htmlspecialchars($account['account_number']) ?></td>
                    <td><?= htmlspecialchars($account['account_type']) ?></td>
                    <td>€<?= htmlspecialchars(number_format($account['balance'], 2)) ?></td>
                    <td>
                        <button class="transfer-funds-btn" onclick="openModal(<?= htmlspecialchars($account['id']) ?>)">Transfer Funds</button>
                        <button class="close-account-btn" onclick="openCloseAccountModal(<?= htmlspecialchars($account['id']) ?>, <?= htmlspecialchars($account['balance']) ?>)">Close Account</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        </table>
    <?php else: ?>
        <p>You have no accounts.</p>
    <?php endif; ?>

    <!-- Create Account Button -->
    <button class="create-account-btn" onclick="openCreateAccountModal()">Create Account</button>
</div>

<!-- Create Account Modal -->
<div id="createAccountModal" class="modal">
    <div class="modal-content">
        <h3>Create New Account</h3>
        <form method="POST" action="create_account.php">
            <!-- Include CSRF token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="account_type">Account Type:</label>
            <select name="account_type" id="account_type" required>
                <option value="checking">Checking</option>
                <option value="savings">Savings</option>
            </select>
            <br><br>
            <button type="submit" class="modal-submit-btn">Create Account</button>
            <button type="button" class="modal-cancel-btn" onclick="closeCreateAccountModal()">Cancel</button>
        </form>
    </div>
</div>

<!-- Close Account Modal -->
<div id="closeAccountModal" class="modal">
    <div class="modal-content">
        <h3>Close Account</h3>
        <p id="closeAccountWarning"></p>
        <form method="POST" action="close_account.php">
            <!-- Include CSRF token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="account_id" id="closeAccountId">
            <button type="submit" class="modal-submit-btn">Confirm</button>
            <button type="button" class="modal-cancel-btn" onclick="closeCloseAccountModal()">Cancel</button>
        </form>
    </div>
</div>

<!-- Transfer Funds Modal -->
<div id="transferFundsModal" class="modal">
    <div class="modal-content">
        <h3>Transfer Funds</h3>
        <form method="POST" action="transfer_funds.php">
            <!-- Include CSRF token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="sender_account_id" id="senderAccountId">
            <label for="recipient_account_number">Recipient Account Number:</label>
            <input type="text" name="recipient_account_number" id="recipient_account_number" required>
            <br><br>
            <label for="amount">Amount:</label>
            <input type="number" name="amount" id="amount" step="0.01" required>
            <br><br>
            <label for="description">Description:</label>
            <textarea name="description" id="description" rows="3" placeholder="Enter transaction description (optional)"></textarea>
            <br><br>
            <div class="modal-buttons">
                <button type="submit" class="modal-submit-btn">Transfer</button>
                <button type="button" class="modal-cancel-btn" onclick="closeTransferFundsModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeCreateAccountModal(); closeCloseAccountModal(); closeTransferFundsModal();"></div>

<script>
    function openCreateAccountModal() {
        document.getElementById('createAccountModal').style.display = 'block';
        document.getElementById('modalOverlay').style.display = 'block';
    }

    function closeCreateAccountModal() {
        document.getElementById('createAccountModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
    }

    function openCloseAccountModal(accountId, balance) {
        if (balance > 0) {
            alert("You cannot close an account with funds in it. Please transfer or withdraw the funds first.");
            return;
        }
        document.getElementById('closeAccountId').value = accountId;
        document.getElementById('closeAccountWarning').textContent = "Are you sure you want to close this account? This action cannot be undone.";
        document.getElementById('closeAccountModal').style.display = 'block';
        document.getElementById('modalOverlay').style.display = 'block';
    }

    function closeCloseAccountModal() {
        document.getElementById('closeAccountModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
    }

    function openModal(accountId) {
        document.getElementById('senderAccountId').value = accountId;
        document.getElementById('transferFundsModal').style.display = 'block';
        document.getElementById('modalOverlay').style.display = 'block';
    }

    function closeTransferFundsModal() {
        document.getElementById('transferFundsModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
    }
</script>

<?php include 'footer.php'; ?>
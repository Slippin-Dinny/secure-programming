<?php
session_start();
require 'header.php';
require 'dbcon.php';

// Redirect if MFA is already completed
if (isset($_SESSION['mfa_completed']) && $_SESSION['mfa_completed'] === true) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null;
    $enteredToken = $_POST['mfa_token'];

    if (!$userId || empty($enteredToken)) {
        die("<p class='error'>Invalid request.</p>");
    }

    // Fetch the MFA token from the database
    $stmt = $pdo->prepare("SELECT token, expires_at FROM mfa_tokens WHERE user_id = ? AND token = ?");
    $stmt->execute([$userId, $enteredToken]);
    $mfaToken = $stmt->fetch();

    if ($mfaToken) {
        // Check if the token has expired
        if (new DateTime() > new DateTime($mfaToken['expires_at'])) {
            echo "<script>
                alert('MFA token has expired. Please log in again.');
                window.location.href = 'login.php';
            </script>";
            exit;
        }

        // Delete the token after successful verification
        $stmt = $pdo->prepare("DELETE FROM mfa_tokens WHERE user_id = ? AND token = ?");
        $stmt->execute([$userId, $enteredToken]);

        // Mark MFA as completed
        $_SESSION['mfa_completed'] = true;

        // Redirect to the dashboard
        header("Location: dashboard.php");
        exit;
    } else {
        echo "<script>
            alert('Invalid MFA token. Please try again.');
            window.location.href = 'mfa.php';
        </script>";
        exit;
    }
}
?>

<div class="mfa-container">
    <h2 class="mfa-title">MFA Verification</h2>

    <form method="POST" class="mfa-form">
        <label for="mfa_token" class="mfa-label">Enter the 6-digit MFA token sent to your email:</label><br>
        <input type="text" name="mfa_token" id="mfa_token" class="mfa-input" required><br><br>
        <button type="submit" class="mfa-button">Verify</button>
    </form>
</div>

<?php include 'footer.php'; ?>
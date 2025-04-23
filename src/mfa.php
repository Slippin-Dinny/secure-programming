<?php
session_start();
require 'header.php';
require 'dbcon.php';

// Function to clean up expired MFA tokens
function cleanExpiredTokens($pdo) {
    $stmt = $pdo->prepare("DELETE FROM mfa_tokens WHERE expires_at < NOW()");
    $stmt->execute();
}

// Function to fetch an existing valid MFA token
function getValidMfaToken($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT token, expires_at FROM mfa_tokens WHERE user_id = ? AND expires_at > NOW()");
    $stmt->execute([$userId]);
    $token = $stmt->fetch();

    // Ensure the token is not expired
    if ($token && new DateTime() < new DateTime($token['expires_at'])) {
        return $token;
    }

    return null; // No valid token found
}

// Function to generate a new MFA token
function generateNewMfaToken($pdo, $userId) {
    // Generate a random 6-digit token
    $mfaToken = random_int(100000, 999999);

    // Set the expiration time to 5 minutes from now
    $expiresAt = (new DateTime())->add(new DateInterval('PT5M'))->format('Y-m-d H:i:s');

    // Insert the new token into the database
    $stmt = $pdo->prepare("INSERT INTO mfa_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $mfaToken, $expiresAt]);

    // Simulate sending the token to the user (e.g., via email)
    // mail($userEmail, "Your MFA Token", "Your MFA token is: $mfaToken");

    return $mfaToken;
}

// Function to delete MFA token after login
function deleteMfaToken($pdo, $userId) {
    $stmt = $pdo->prepare("DELETE FROM mfa_tokens WHERE user_id = ?");
    $stmt->execute([$userId]);
}

// Clean up expired tokens on every page load
cleanExpiredTokens($pdo);

// Redirect if MFA is already completed
if (isset($_SESSION['mfa_completed']) && $_SESSION['mfa_completed'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Handle new token request
if (isset($_POST['request_new_token'])) {
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        die("<p class='error'>Invalid request. User not logged in.</p>");
    }

    // Check for an existing valid token
    $existingToken = getValidMfaToken($pdo, $userId);
    if ($existingToken) {
        echo "<script>
            alert('A valid MFA token already exists. Please check your email.');
            window.location.href = 'mfa.php';
        </script>";
        exit;
    }

    // Generate a new token
    $newToken = generateNewMfaToken($pdo, $userId);
    echo "<script>
        alert('A new MFA token has been sent to your email.');
        window.location.href = 'mfa.php';
    </script>";
    exit;
}

// Handle MFA token verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mfa_token'])) {
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
                alert('MFA token has expired. Please request a new one.');
                window.location.href = 'mfa.php';
            </script>";
            exit;
        }

        // Mark MFA as completed
        $_SESSION['mfa_completed'] = true;

        // Delete the MFA token after successful login
        deleteMfaToken($pdo, $userId);

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
        <p class="mfa-info">Note: The token is valid for 5 minutes.</p>
        <input type="text" name="mfa_token" id="mfa_token" class="mfa-input" required><br><br>
        <button type="submit" class="mfa-button">Verify</button>
    </form>
    
    <form method="POST" class="mfa-form">
        <button type="submit" name="request_new_token" class="mfa-button">Request New Token</button>
    </form>
</div>

<?php include 'footer.php'; ?>
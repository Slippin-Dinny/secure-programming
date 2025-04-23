<?php 
include 'header.php'; 
require 'dbcon.php'; 

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to get the user's IP address
function getUserIpAddr() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("<p class='error'>Invalid CSRF token.</p>");
    }

    $firstName = trim($_POST['first_name']);
    $lastName  = trim($_POST['last_name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        echo "<p class='error'>All fields are required.</p>";
    } else {
        // Check credentials
        $stmt = $pdo->prepare("SELECT * FROM users WHERE first_name = ? AND last_name = ? AND email = ?");
        $stmt->execute([$firstName, $lastName, $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullName'] = $user['first_name'] . ' ' . $user['last_name']; 
            $_SESSION['email'] = $user['email'];
            $_SESSION['mfa_completed'] = false; // MFA not yet completed

            // Generate a random 6-digit MFA token
            $mfaToken = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes')); // Token expires in 5 minutes

            // Insert MFA token into the database
            $stmt = $pdo->prepare("INSERT INTO mfa_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $mfaToken, $expiresAt]);

            // Redirect to MFA verification page
            header("Location: mfa.php");
            exit;
        } else {
            echo "<p class='error'>Invalid login credentials.</p>";
        }
    }
}
?>

<h2>Login</h2>

<form method="POST">
    <!-- Include CSRF token -->
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

    <label>First Name:</label><br>
    <input type="text" name="first_name" required><br><br>

    <label>Last Name:</label><br>
    <input type="text" name="last_name" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Login</button>
</form>

<?php include 'footer.php'; ?>
<?php 
include 'header.php'; 
require 'dbcon.php'; 

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    $confirmPassword = $_POST['confirm_password'];

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        echo "<p class='error'>All fields are required.</p>";
    } elseif ($password !== $confirmPassword) {
        echo "<p class='error'>Passwords do not match.</p>";
    } else {
        // Check if the email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $emailExists = $stmt->fetchColumn();

        if ($emailExists) {
            echo "<p class='error'>This email is already registered. Please use a different email or <a href='login.php'>login</a>.</p>";
        } else {
            // Hash the password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Insert user into the database
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, created_at) VALUES (?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$firstName, $lastName, $email, $passwordHash]);

            if ($result) {
                echo "<p class='success'>Registration successful. You can now <a href='login.php'>login</a>.</p>";
            } else {
                echo "<p class='error'>An error occurred. Please try again.</p>";
            }
        }
    }
}
?>

<h2>Register</h2>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

    <label>First Name:</label><br>
    <input type="text" name="first_name" required><br><br>

    <label>Last Name:</label><br>
    <input type="text" name="last_name" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <label>Confirm Password:</label><br>
    <input type="password" name="confirm_password" required><br><br>

    <button type="submit">Register</button>
</form>

<?php include 'footer.php'; ?>
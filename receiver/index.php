<?php
// Start the session. This must be the very first thing in your script.
session_start();

// If a receiver is already logged in, redirect them to their dashboard
if (isset($_SESSION['receiver_logged_in']) && $_SESSION['receiver_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Include the database connection file AFTER the initial session check.
require_once '../config/db_connection.php';

// Variable to hold our error message
$login_error = '';

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get the submitted email and password from the form
    $submitted_email = $_POST['email'];
    $submitted_password = $_POST['password'];
    
    // --- DATABASE LOGIN LOGIC ---
    // Prepare SQL to find a user with the given email AND the 'recipient' role
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ? AND role = 'recipient'");
    $stmt->bind_param("s", $submitted_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify the submitted password against the hash stored in the database
        if (password_verify($submitted_password, $user['password'])) {
            // Password is correct. Set the session variables.
            $_SESSION['receiver_logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            
            // Redirect the receiver to their dashboard
            header('Location: dashboard.php');
            exit; // Important to prevent further code execution
        } else {
            // Password was incorrect
            $login_error = 'Invalid email or password. Please try again.';
        }
    } else {
        // No user found with that email or role
        $login_error = 'Invalid email or password. Please try again.';
    }
    $stmt->close();
    // --- END OF DATABASE LOGIC ---
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title>Receiver Login - Blood Bank</title>
    <!-- Link to the CSS file named to match your working example -->
    <link rel="stylesheet" href="log_style.css">
</head>
<body>

    <div class="login-container">
        <h2>Receiver Portal Login</h2>
        <p>Please enter your credentials to log in.</p>
        
        <form action="index.php" method="POST">
            
            <!-- This PHP block will display the error message if login fails -->
            <?php if (!empty($login_error)): ?>
                <div class="error-message">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="login-button">Login</button>
        </form>
        
        <div class="form-links">
            Don't have an account? <a href="register.php">Register as a Receiver</a>
        </div>

        <div class="back-link">
            <a href="../index.html">&laquo; Back to Home</a>
        </div>
    </div>

</body>
</html>
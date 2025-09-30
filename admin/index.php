<?php
// Start the session. This must be the very first thing in your script.
session_start();

// If the admin is already logged in, redirect them to the dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Define the correct username and password
$correct_username = 'Admin';
$correct_password = '123';

// Variable to hold our error message
$login_error = '';

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get the submitted username and password from the form
    $submitted_username = $_POST['username'];
    $submitted_password = $_POST['password'];
    
    // Check if the submitted credentials match the correct ones
    if ($submitted_username === $correct_username && $submitted_password === $correct_password) {
        
        // Credentials are correct, so set the session variable
        $_SESSION['admin_logged_in'] = true;
        
        // Redirect the admin to the dashboard
        header('Location: dashboard.php');
        exit; // Important to prevent further code execution
        
    } else {
        
        // Credentials are incorrect, set the error message
        $login_error = 'Invalid username or password. Please try again.';
        
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Blood Bank</title>
    <!-- Link to the specific CSS file for the admin section -->
    <link rel="stylesheet" href="login_style.css">
</head>
<body>

    <div class="login-container">
        <h2>Admin Panel Login</h2>
        <p>Please enter your credentials to log in.</p>
        
        <form action="index.php" method="POST">
            
            <!-- This PHP block will display the error message if login fails -->
            <?php if (!empty($login_error)): ?>
                <div class="error-message">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="login-button">Login</button>
        </form>
        
        <div class="back-link">
            <a href="../index.html">&laquo; Back to Home</a>
        </div>
    </div>

</body>
</html>
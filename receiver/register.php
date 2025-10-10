<?php
session_start();
require_once '../config/db_connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $blood_type = intval($_POST['blood_type']);
    $urgency_level = $_POST['urgency_level'];
    $hospital = trim($_POST['hospital']);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        // Check if username or email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert into users table
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, password, email, role) VALUES (?, ?, ?, ?, ?, 'recipient')");
            $stmt->bind_param("sssss", $first_name, $last_name, $username, $hashed_password, $email);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Insert into recipients table
                $recipient_stmt = $conn->prepare("INSERT INTO recipients (user_id, blood_type_id, urgency_level, hospital) VALUES (?, ?, ?, ?)");
                $recipient_stmt->bind_param("iiss", $user_id, $blood_type, $urgency_level, $hospital);
                
                if ($recipient_stmt->execute()) {
                    $success = "Registration successful! You can now login.";
                    // Redirect after 2 seconds
                    header("refresh:2;url=index.php");
                } else {
                    $error = "Error creating recipient profile: " . $conn->error;
                }
                $recipient_stmt->close();
            } else {
                $error = "Error creating account: " . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

// Get blood types for dropdown
$blood_types_query = "SELECT id, type FROM blood_types ORDER BY id";
$blood_types_result = $conn->query($blood_types_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receiver Registration - BloodConnect</title>
    <link rel="stylesheet" href="register_style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="register-container">
        <div class="register-box">
            <div class="register-header">
                <i class="fas fa-hand-holding-medical"></i>
                <h2>Receiver Registration</h2>
                <p>Request blood when you need it most</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="register-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">
                            <i class="fas fa-user"></i> First Name
                        </label>
                        <input type="text" id="first_name" name="first_name" required 
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">
                            <i class="fas fa-user"></i> Last Name
                        </label>
                        <input type="text" id="last_name" name="last_name" required
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user-circle"></i> Username
                    </label>
                    <input type="text" id="username" name="username" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="blood_type">
                            <i class="fas fa-tint"></i> Blood Group Needed
                        </label>
                        <select id="blood_type" name="blood_type" required>
                            <option value="">Select Blood Group</option>
                            <?php 
                            $blood_types_result->data_seek(0); // Reset pointer
                            while ($blood_type = $blood_types_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $blood_type['id']; ?>"
                                        <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == $blood_type['id']) ? 'selected' : ''; ?>>
                                    <?php echo $blood_type['type']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="urgency_level">
                            <i class="fas fa-exclamation-triangle"></i> Urgency Level
                        </label>
                        <select id="urgency_level" name="urgency_level" required>
                            <option value="">Select Urgency</option>
                            <option value="Low" <?php echo (isset($_POST['urgency_level']) && $_POST['urgency_level'] == 'Low') ? 'selected' : ''; ?>>Low</option>
                            <option value="Medium" <?php echo (isset($_POST['urgency_level']) && $_POST['urgency_level'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="High" <?php echo (isset($_POST['urgency_level']) && $_POST['urgency_level'] == 'High') ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="hospital">
                        <i class="fas fa-hospital"></i> Hospital Name
                    </label>
                    <input type="text" id="hospital" name="hospital" required 
                           placeholder="e.g., Dhaka Medical College Hospital"
                           value="<?php echo isset($_POST['hospital']) ? htmlspecialchars($_POST['hospital']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <small>Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-user-plus"></i> Register as Receiver
                </button>

                <div class="form-footer">
                    <p>Already have an account? <a href="index.php">Login here</a></p>
                    <p><a href="../index.php">Back to Home</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
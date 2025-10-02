<?php
session_start();
require_once '../config/db_connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data and trim whitespace
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $age = intval($_POST['age']);
    $gender = $_POST['gender'];
    $blood_type = intval($_POST['blood_type']);
    $health_status = trim($_POST['health_status']);
    
    // **NEW**: Get additional donor data
    $location = trim($_POST['location']);
    $phone = trim($_POST['phone']);
    $last_donation_date = $_POST['last_donation_date']; // This can be empty

    // Validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password) || empty($location) || empty($phone)) {
        $error = "All fields except 'Last Donation Date' are required!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } elseif ($age < 18 || $age > 65) {
        $error = "Donor age must be between 18 and 65 years!";
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
            
            // Use a transaction for inserting into two tables
            $conn->begin_transaction();

            try {
                // Insert into users table
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, password, email, role) VALUES (?, ?, ?, ?, ?, 'donor')");
                $stmt->bind_param("sssss", $first_name, $last_name, $username, $hashed_password, $email);
                $stmt->execute();
                $user_id = $conn->insert_id;

                // **Handle optional date**: Set to NULL if empty
                $last_donation_date_for_db = !empty($last_donation_date) ? $last_donation_date : NULL;

                // **UPDATED**: Insert into donors table with new fields
                $donor_stmt = $conn->prepare("INSERT INTO donors (user_id, age, blood_type_id, gender, health_status, location, phone, last_donation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $donor_stmt->bind_param("iiisssss", $user_id, $age, $blood_type, $gender, $health_status, $location, $phone, $last_donation_date_for_db);
                $donor_stmt->execute();

                // If all goes well, commit the transaction
                $conn->commit();
                
                $success = "Registration successful! You can now login.";
                // Redirect after 2 seconds
                header("refresh:2;url=index.php");

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback(); // Rollback on error
                $error = "Error during registration: " . $exception->getMessage();
            } finally {
                if (isset($stmt)) $stmt->close();
                if (isset($donor_stmt)) $donor_stmt->close();
            }
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
    <title>Donor Registration - BloodConnect</title>
    <link rel="stylesheet" href="register_style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="register-container">
        <div class="register-box">
            <div class="register-header">
                <i class="fas fa-hand-holding-heart"></i>
                <h2>Donor Registration</h2>
                <p>Join our life-saving community</p>
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
                        <label for="age">
                            <i class="fas fa-birthday-cake"></i> Age
                        </label>
                        <input type="number" id="age" name="age" min="18" max="65" required
                               value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="gender">
                            <i class="fas fa-venus-mars"></i> Gender
                        </label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="blood_type">
                            <i class="fas fa-tint"></i> Blood Group
                        </label>
                        <select id="blood_type" name="blood_type" required>
                            <option value="">Select Blood Group</option>
                            <?php while ($blood_type_row = $blood_types_result->fetch_assoc()): ?>
                                <option value="<?php echo $blood_type_row['id']; ?>"
                                        <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == $blood_type_row['id']) ? 'selected' : ''; ?>>
                                    <?php echo $blood_type_row['type']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="last_donation_date">
                            <i class="fas fa-calendar-alt"></i> Last Donation Date
                        </label>
                        <input type="date" id="last_donation_date" name="last_donation_date"
                               value="<?php echo isset($_POST['last_donation_date']) ? htmlspecialchars($_POST['last_donation_date']) : ''; ?>">
                        <small>Leave blank if you're a new donor.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">
                            <i class="fas fa-map-marker-alt"></i> Location
                        </label>
                        <input type="text" id="location" name="location" placeholder="e.g., Dhaka" required
                               value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="text" id="phone" name="phone" placeholder="e.g., 01712345678" required
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="health_status">
                        <i class="fas fa-heartbeat"></i> Health Status
                    </label>
                    <textarea id="health_status" name="health_status" rows="3" 
                              placeholder="Brief description of your health status"><?php echo isset($_POST['health_status']) ? htmlspecialchars($_POST['health_status']) : ''; ?></textarea>
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
                    <i class="fas fa-user-plus"></i> Register as Donor
                </button>

                <div class="form-footer">
                    <p>Already have an account? <a href="index.php">Login here</a></p>
                    <p><a href="../index.html">Back to Home</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
<?php
session_start();

// Security check
if (!isset($_SESSION['receiver_logged_in']) || $_SESSION['receiver_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

require_once '../config/db_connection.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get recipient ID
$recipient_query = "SELECT id FROM recipients WHERE user_id = ?";
$stmt = $conn->prepare($recipient_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recipient_result = $stmt->get_result();
$recipient_data = $recipient_result->fetch_assoc();
$recipient_id = $recipient_data['id'];
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $blood_type_id = intval($_POST['blood_type_id']);
    $quantity_ml = intval($_POST['quantity_ml']);
    $request_date = date('Y-m-d');
    $status = 'Open';
    
    // Validation
    if ($blood_type_id <= 0 || $quantity_ml <= 0) {
        $error = "Please fill all required fields correctly!";
    } elseif ($quantity_ml < 100 || $quantity_ml > 2000) {
        $error = "Quantity must be between 100ml and 2000ml";
    } else {
        // Insert blood request
        $insert_query = "INSERT INTO requests (recipient_id, blood_type_id, quantity_ml, request_date, status) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiiss", $recipient_id, $blood_type_id, $quantity_ml, $request_date, $status);
        
        if ($stmt->execute()) {
            $success = "Blood request created successfully! Donors will be notified.";
            // Redirect after 2 seconds
            header("refresh:2;url=dashboard.php");
        } else {
            $error = "Error creating request: " . $conn->error;
        }
        $stmt->close();
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
    <title>Create Blood Request - BloodConnect</title>
    <link rel="stylesheet" href="receiver_style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="request-form-container">
        <div class="request-form-box">
            <div class="form-header">
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <div class="form-title">
                <i class="fas fa-hand-holding-medical"></i>
                <h2>Create Blood Request</h2>
                <p>Fill in the details below to request blood from our donor network</p>
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

            <form method="POST" action="" class="blood-request-form">
                <div class="form-group">
                    <label for="blood_type_id">
                        <i class="fas fa-tint"></i> Blood Type Needed *
                    </label>
                    <select id="blood_type_id" name="blood_type_id" required>
                        <option value="">Select Blood Group</option>
                        <?php while ($blood_type = $blood_types_result->fetch_assoc()): ?>
                            <option value="<?php echo $blood_type['id']; ?>"
                                    <?php echo (isset($_POST['blood_type_id']) && $_POST['blood_type_id'] == $blood_type['id']) ? 'selected' : ''; ?>>
                                <?php echo $blood_type['type']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small>Select the blood type you need</small>
                </div>

                <div class="form-group">
                    <label for="quantity_ml">
                        <i class="fas fa-vial"></i> Quantity Needed (ml) *
                    </label>
                    <input 
                        type="number" 
                        id="quantity_ml" 
                        name="quantity_ml" 
                        min="100" 
                        max="2000" 
                        step="50"
                        placeholder="e.g., 450"
                        required
                        value="<?php echo isset($_POST['quantity_ml']) ? htmlspecialchars($_POST['quantity_ml']) : ''; ?>">
                    <small>Standard donation is 450ml. Range: 100-2000ml</small>
                </div>

                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>What happens next?</strong>
                        <ul>
                            <li>Your request will be sent to matching donors in your area</li>
                            <li>Eligible donors will be notified via email</li>
                            <li>You'll receive updates when donors respond</li>
                            <li>Your request status can be tracked from your dashboard</li>
                        </ul>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
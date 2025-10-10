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

// --- 1. Get recipient ID, current phone number, hospital, and urgency for pre-filling ---
// Added urgency_level to the SELECT query based on the database schema
$recipient_query = "SELECT id, phone, hospital, urgency_level FROM recipients WHERE user_id = ?";
$stmt = $conn->prepare($recipient_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recipient_result = $stmt->get_result();
$recipient_data = $recipient_result->fetch_assoc();
$recipient_id = $recipient_data['id'];

// Initial values for pre-filling the form
$current_phone = $recipient_data['phone'] ?? '';
$current_hospital = $recipient_data['hospital'] ?? ''; 
$current_urgency = $recipient_data['urgency_level'] ?? 'Low'; // Default to 'Low'
$stmt->close();

// Variables to hold submitted values on error for form re-fill
$submitted_urgency = $current_urgency;
$submitted_phone = $current_phone;
$submitted_hospital = $current_hospital;


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $blood_type_id = intval($_POST['blood_type_id']);
    $quantity_ml = intval($_POST['quantity_ml']);
    $phone_number = trim($_POST['phone_number']);
    $hospital_name = trim($_POST['hospital_name']);
    $urgency_level = trim($_POST['urgency_level']);

    // Update variables for potential form re-fill on error
    $submitted_urgency = $urgency_level;
    $submitted_phone = $phone_number;
    $submitted_hospital = $hospital_name;
    
    $request_date = date('Y-m-d');
    $status = 'Open';
    
    // Define valid urgency levels based on the recipients table ENUM('Low','Medium','High')
    $valid_urgencies = ['Low', 'Medium', 'High'];

    // --- 2. Validation ---
    if ($blood_type_id <= 0 || $quantity_ml <= 0 || empty($hospital_name) || empty($urgency_level)) {
        $error = "Please fill all required fields correctly (Blood Type, Quantity, Hospital, Urgency)!";
    } elseif (!in_array($urgency_level, $valid_urgencies)) {
        // Validation now correctly rejects anything outside of Low, Medium, High
        $error = "Invalid urgency level selected. Please choose Low, Medium, or High.";
    } elseif ($quantity_ml < 100 || $quantity_ml > 2000) {
        $error = "Quantity must be between 100ml and 2000ml";
    } elseif (!empty($phone_number) && !preg_match("/^\+?[0-9\s\-\(\)]{7,20}$/", $phone_number)) { 
        $error = "Invalid phone number format. Please enter a valid number.";
    } else {
        
        // --- 3. Update Recipient's Profile (Phone, Hospital, AND Urgency Level) ---
        $phone_to_update = empty($phone_number) ? NULL : $phone_number;
        
        // Note: $recipient_data holds the original values fetched from DB
        $update_required = ($phone_to_update !== $recipient_data['phone']) 
                           || ($hospital_name !== $recipient_data['hospital'])
                           || ($urgency_level !== $recipient_data['urgency_level']);

        if ($update_required) {
            // Updated query to include urgency_level
            $update_recipient_query = "UPDATE recipients SET phone = ?, hospital = ?, urgency_level = ? WHERE id = ?";
            $stmt = $conn->prepare($update_recipient_query);
            
            // 'sssi' for phone (s), hospital (s), urgency_level (s), recipient_id (i)
            $stmt->bind_param("sssi", $phone_to_update, $hospital_name, $urgency_level, $recipient_id);
            
            if (!$stmt->execute()) {
                error_log("Error updating recipient profile: " . $conn->error);
                $error .= " Failed to update contact information."; // Append non-blocking error
            } else {
                // Update the current variables for consistency
                $current_phone = $phone_to_update;
                $current_hospital = $hospital_name;
                $current_urgency = $urgency_level;
            }
            $stmt->close();
        }

        // --- 4. Insert Blood Request (CORRECTED: Removed urgency_level from requests table) ---
        // The requests table only accepts: recipient_id, blood_type_id, quantity_ml, request_date, status
        $insert_query = "INSERT INTO requests (recipient_id, blood_type_id, quantity_ml, request_date, status) 
                         VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        // 'iiiss' for recipient_id(i), blood_type_id(i), quantity_ml(i), request_date(s), status(s)
        $stmt->bind_param("iiiss", $recipient_id, $blood_type_id, $quantity_ml, $request_date, $status);
        
        if ($stmt->execute()) {
            $success = "Blood request created successfully! Donors will be notified. Your contact and hospital information has been updated.";
            // Redirect after 2 seconds
            header("refresh:2;url=dashboard.php");
        } else {
            // Only show the request creation error if profile update succeeded or was not attempted
            $error .= " Error creating request: " . $conn->error;
        }
        $stmt->close();
    }
    
    // If there was an error, use the submitted values to re-fill the form (already done above)
    if ($error) {
        $current_phone = $submitted_phone;
        $current_hospital = $submitted_hospital;
        $current_urgency = $submitted_urgency;
    }
} else {
    // If it's a fresh GET request, set form re-fill variables to current DB values
    $submitted_urgency = $current_urgency;
    $submitted_phone = $current_phone;
    $submitted_hospital = $current_hospital;
}

// Get blood types for dropdown (Need to re-run in case of POST error)
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
    <style>
        /* Basic Form Styling (Assuming receiver_style.css is generic) */
        body { font-family: sans-serif; background-color: #f7f7f7; color: #333; }
        .request-form-container { max-width: 600px; margin: 50px auto; padding: 20px; }
        .request-form-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .form-title h2 { color: #d9534f; margin-bottom: 5px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #d9534f; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .form-actions { display: flex; justify-content: space-between; margin-top: 30px; }
        .btn { padding: 10px 15px; border-radius: 5px; text-decoration: none; cursor: pointer; }
        .btn-primary { background-color: #d9534f; color: white; border: none; }
        .btn-secondary { background-color: #6c757d; color: white; border: none; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-error { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
        .alert-success { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .info-box { background-color: #f9f9e5; border-left: 5px solid #f0ad4e; padding: 15px; border-radius: 5px; display: flex; gap: 10px; margin-top: 20px; }
        .info-box i { color: #f0ad4e; font-size: 1.5em; }
        .back-btn { text-decoration: none; color: #6c757d; display: block; margin-bottom: 15px; }
        .form-header { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
    </style>
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
                
                <!-- Hospital Name: Pre-filled with current DB value or submitted value on error -->
                <div class="form-group">
                    <label for="hospital_name">
                        <i class="fas fa-hospital"></i> Hospital Name *
                    </label>
                    <input 
                        type="text" 
                        id="hospital_name" 
                        name="hospital_name" 
                        placeholder="e.g., Central General Hospital"
                        required
                        value="<?php echo htmlspecialchars($current_hospital); ?>">
                    <small>This is where the donor will be directed. Your profile will be updated with this name.</small>
                </div>

                <!-- Urgency Level: Pre-filled with current DB value or submitted value on error -->
                <div class="form-group">
                    <label for="urgency_level">
                        <i class="fas fa-exclamation-triangle"></i> Urgency Level *
                    </label>
                    <select id="urgency_level" name="urgency_level" required>
                        <option value="">Select Urgency</option>
                        <!-- Options limited to Low, Medium, High to match database schema -->
                        <option value="Low" <?php echo ($current_urgency == 'Low') ? 'selected' : ''; ?>>Low (Routine/Scheduled)</option>
                        <option value="Medium" <?php echo ($current_urgency == 'Medium') ? 'selected' : ''; ?>>Medium (Needs attention soon)</option>
                        <option value="High" <?php echo ($current_urgency == 'High') ? 'selected' : ''; ?>>High (Time-sensitive)</option>
                    </select>
                    <small>How urgently the blood is needed (Low, Medium, or High only).</small>
                </div>

                <div class="form-group">
                    <label for="blood_type_id">
                        <i class="fas fa-tint"></i> Blood Type Needed *
                    </label>
                    <select id="blood_type_id" name="blood_type_id" required>
                        <option value="">Select Blood Group</option>
                        <?php 
                        // Reset pointer and re-fetch if needed (in case of error on POST)
                        if ($blood_types_result->num_rows > 0) {
                            $blood_types_result->data_seek(0);
                        }
                        while ($blood_type = $blood_types_result->fetch_assoc()): ?>
                            <option value="<?php echo $blood_type['id']; ?>"
                                        <?php echo (isset($_POST['blood_type_id']) && $_POST['blood_type_id'] == $blood_type['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($blood_type['type']); ?>
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
                
                <!-- Contact Phone Number: Pre-filled with current DB value or submitted value on error -->
                <div class="form-group">
                    <label for="phone_number">
                        <i class="fas fa-phone"></i> Contact Phone Number (Optional)
                    </label>
                    <input 
                        type="tel" 
                        id="phone_number" 
                        name="phone_number" 
                        placeholder="e.g., +8801XXXXXXXXX"
                        maxlength="20"
                        value="<?php echo htmlspecialchars($current_phone); ?>">
                    <small>Provide a number for donors to contact you directly. This will update your profile.</small>
                </div>
                
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>What happens next?</strong>
                        <ul>
                            <li>Your request will be sent to matching donors in your area.</li>
                            <li>Your profile's information will be updated.</li>
                            <li>The urgency level helps donors prioritize their responses.</li>
                            <li>You'll receive updates when donors respond, and you can track status on your dashboard.</li>
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

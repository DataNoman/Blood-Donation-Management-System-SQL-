<?php
session_start();

// Security check
if (!isset($_SESSION['donor_logged_in']) || $_SESSION['donor_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

require_once '../config/db_connection.php';

$user_id = $_SESSION['user_id'];
$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($request_id <= 0) {
    die("Invalid request.");
}

// Get donor_id from user_id
$donor_query = "SELECT id FROM donors WHERE user_id = ?";
$stmt = $conn->prepare($donor_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$donor = $result->fetch_assoc();
$stmt->close();

if (!$donor) {
    die("Donor record not found.");
}
$donor_id = $donor['id'];

// Check if request exists and is still open
$request_query = "SELECT * FROM requests WHERE id = ? AND status = 'Open'";
$stmt = $conn->prepare($request_query);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request_result = $stmt->get_result();
$request = $request_result->fetch_assoc();
$stmt->close();

if (!$request) {
    die("This request is no longer available.");
}

// Check if donor already responded
$check_response = "SELECT * FROM responses WHERE donor_id = ? AND request_id = ?";
$stmt = $conn->prepare($check_response);
$stmt->bind_param("ii", $donor_id, $request_id);
$stmt->execute();
$already = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($already) {
    $message = "⚠️ You have already responded to this request.";
    $status = "warning";
} else {
    // Insert new response
    $insert_query = "INSERT INTO responses (donor_id, request_id, response_date, status) VALUES (?, ?, NOW(), 'Pending')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ii", $donor_id, $request_id);
    
    if ($stmt->execute()) {
        $message = "✅ Thank you! Your response has been recorded. The recipient will be notified.";
        $status = "success";
    } else {
        $message = "❌ Something went wrong. Please try again.";
        $status = "error";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respond to Request - BloodConnect</title>
    <link rel="stylesheet" href="donor_style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-hand-holding-heart"></i>
                <h1>Respond to Request</h1>
            </div>
            <div class="dashboard-nav">
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>

        <div class="section-card" style="text-align:center; padding:40px;">
            <?php if ($status === "success"): ?>
                <i class="fas fa-check-circle" style="font-size:60px; color:#2a9d8f;"></i>
            <?php elseif ($status === "warning"): ?>
                <i class="fas fa-exclamation-circle" style="font-size:60px; color:#f4a261;"></i>
            <?php else: ?>
                <i class="fas fa-times-circle" style="font-size:60px; color:#e76f51;"></i>
            <?php endif; ?>

            <h2 style="margin-top:20px;"><?php echo $message; ?></h2>
            <p style="margin-top:10px; color:#555;">You can view more requests from your <a href="dashboard.php">dashboard</a>.</p>
        </div>
    </div>
</body>
</html>

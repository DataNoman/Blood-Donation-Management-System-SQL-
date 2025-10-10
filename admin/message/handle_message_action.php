<?php
session_start();

// Security check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Return unauthorized status if not logged in
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

header('Content-Type: application/json');
require_once '../../config/db_connection.php';

// Check for required POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'], $_POST['new_status'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request data.']);
    exit();
}

$message_id = (int)$_POST['id'];
$new_status = trim($_POST['new_status']);

// Validate the status against the allowed ENUM values in the database
$valid_statuses = ['unread', 'read', 'replied'];

if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid status value provided.']);
    exit();
}

try {
    // Prepare the update statement
    $stmt = $conn->prepare("UPDATE contact_messages SET status = ?, updated_at = NOW() WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("si", $new_status, $message_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Success: status updated
        echo json_encode(['status' => 'success', 'new_status' => $new_status]);
    } elseif ($stmt->affected_rows === 0) {
        // No change needed (status was already the requested status)
        echo json_encode(['status' => 'no_change', 'message' => 'Status was already set to ' . $new_status]);
    } else {
        // Failed for other reason (e.g., message ID not found)
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Message not found or update failed.']);
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Message Status Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error during update.']);
}

$conn->close();
?>

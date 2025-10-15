<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require_once 'db_connection.php';

$current_user_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

// --- 1. Get Messages ---
if ($action === 'get_messages' && isset($_GET['request_id'])) {
    $request_id = intval($_GET['request_id']);

    // Verify that current user is part of this request (either donor or recipient)
    $verify_query = "SELECT 
                        rec.user_id as recipient_user_id,
                        GROUP_CONCAT(DISTINCT d.user_id) as donor_user_ids
                     FROM requests req
                     JOIN recipients rec ON req.recipient_id = rec.id
                     LEFT JOIN responses res ON req.id = res.request_id AND res.status = 'Accepted'
                     LEFT JOIN donors d ON res.donor_id = d.id
                     WHERE req.id = ?
                     GROUP BY rec.user_id";
    
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $verify_result = $stmt->get_result();
    $verify_data = $verify_result->fetch_assoc();
    $stmt->close();

    if (!$verify_data) {
        echo json_encode(['status' => 'error', 'message' => 'Request not found']);
        exit();
    }

    $donor_ids = $verify_data['donor_user_ids'] ? explode(',', $verify_data['donor_user_ids']) : [];
    $is_participant = ($current_user_id == $verify_data['recipient_user_id']) || in_array($current_user_id, $donor_ids);

    if (!$is_participant) {
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit();
    }

    // Get messages
    $query = "SELECT sender_user_id, message, sent_at 
              FROM chat 
              WHERE request_id = ?
              ORDER BY sent_at ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    $stmt->close();
    echo json_encode(['status' => 'success', 'messages' => $messages]);
    
// --- 2. Send Message ---
} elseif ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id'] ?? 0);
    $receiver_user_id = intval($_POST['receiver_user_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($request_id <= 0 || $receiver_user_id <= 0 || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing data']);
        exit();
    }

    // Verify user is part of this request
    $verify_query = "SELECT 
                        rec.user_id as recipient_user_id,
                        GROUP_CONCAT(DISTINCT d.user_id) as donor_user_ids
                     FROM requests req
                     JOIN recipients rec ON req.recipient_id = rec.id
                     LEFT JOIN responses res ON req.id = res.request_id AND res.status = 'Accepted'
                     LEFT JOIN donors d ON res.donor_id = d.id
                     WHERE req.id = ?
                     GROUP BY rec.user_id";
    
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $verify_result = $stmt->get_result();
    $verify_data = $verify_result->fetch_assoc();
    $stmt->close();

    if (!$verify_data) {
        echo json_encode(['status' => 'error', 'message' => 'Request not found']);
        exit();
    }

    $donor_ids = $verify_data['donor_user_ids'] ? explode(',', $verify_data['donor_user_ids']) : [];
    $is_participant = ($current_user_id == $verify_data['recipient_user_id']) || in_array($current_user_id, $donor_ids);

    if (!$is_participant) {
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit();
    }

    // Insert message
    $query = "INSERT INTO chat (request_id, sender_user_id, receiver_user_id, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiis", $request_id, $current_user_id, $receiver_user_id, $message);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message_id' => $conn->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }

    $stmt->close();
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

$conn->close();
?>
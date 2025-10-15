<?php
session_start();

// ✅ Require login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once '../../config/db_connection.php';

// Check if db_connection was successful
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . (isset($conn) ? $conn->connect_error : "Connection object not created."));
}

// ✅ Normal page load
// Fetch all contact messages, ordered by creation date (newest first)
$result = $conn->query("SELECT id, name, email, message, status, created_at FROM contact_messages ORDER BY created_at DESC");

if ($result) {
    $messages = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Log and handle database error gracefully
    error_log("Database error fetching contact messages: " . $conn->error);
    $messages = [];
}
$conn->close();

// Define all valid statuses for the dropdown
$all_statuses = ['unread', 'read', 'replied'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Contact Messages</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Using the dedicated style files -->
    <link rel="stylesheet" href="../admin_style.css">
    <link rel="stylesheet" href="message.css">
</head>
<body>
    <div class="container">
        <header class="page-header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <i class="fas fa-envelope-open-text"></i>
                <h1>Contact Messages</h1>
            </div>
            
            <div class="header-actions">
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <form action="../logout.php" method="post">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </header>

        <?php if (empty($messages)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No contact messages have been received yet.
            </div>
        <?php else: ?>
            <table class="messages-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Received</th>
                        <th style="width: 15%;">Name</th>
                        <th style="width: 15%;">Email</th>
                        <th style="width: 35%;">Message</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 10%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $message): ?>
                        <tr id="message-row-<?php echo $message['id']; ?>" class="<?php echo ($message['status'] == 'unread') ? 'unread-row' : ''; ?>">
                            <td><?php echo date('M j, Y, g:i a', strtotime($message['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($message['name']); ?></td>
                            <td><a href="mailto:<?php echo htmlspecialchars($message['email']); ?>"><?php echo htmlspecialchars($message['email']); ?></a></td>
                            <!-- The title attribute is helpful for viewing the full message on hover -->
                            <td class="message-content" title="<?php echo htmlspecialchars($message['message']); ?>"><?php echo htmlspecialchars($message['message']); ?></td>
                            <td>
                                <span id="status-badge-<?php echo $message['id']; ?>" class="status-badge status-<?php echo htmlspecialchars($message['status']); ?>">
                                    <?php echo htmlspecialchars($message['status']); ?>
                                </span>
                            </td>
                            <td>
                                <!-- Status Dropdown for Action -->
                                <select 
                                    class="status-action-select" 
                                    data-id="<?php echo $message['id']; ?>"
                                >
                                    <option value="" disabled selected>Update Status</option>
                                    <?php foreach ($all_statuses as $status_option): ?>
                                        <?php if ($status_option !== $message['status']): ?>
                                            <option value="<?php echo $status_option; ?>">
                                                Mark as <?php echo ucfirst($status_option); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const table = document.querySelector('.messages-table');
            
            if (table) {
                // Use a 'change' event listener on the table to capture changes from all dropdowns
                table.addEventListener('change', (event) => {
                    const selectElement = event.target.closest('.status-action-select[data-id]');
                    
                    // Stop if the target wasn't the dropdown or no value was selected
                    if (!selectElement || selectElement.value === '') return;

                    const messageId = selectElement.dataset.id;
                    const newStatus = selectElement.value;
                    const originalText = selectElement.options[0].textContent; 

                    // Visually disable and show loading feedback
                    selectElement.disabled = true;
                    selectElement.options[0].textContent = '...Updating';

                    const formData = new FormData();
                    formData.append('id', messageId);
                    formData.append('new_status', newStatus);

                    // Fetch request to the dedicated handler file
                    fetch('handle_message_action.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw new Error(err.message || 'Server Error'); });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success' || data.status === 'no_change') {
                            const updatedStatus = data.new_status || newStatus;
                            const row = document.getElementById(`message-row-${messageId}`);
                            const badge = document.getElementById(`status-badge-${messageId}`);
                            
                            // 1. Update the row class (unread-row for visual distinction)
                            row.classList.remove('unread-row');
                            if (updatedStatus === 'unread') {
                                row.classList.add('unread-row');
                            }
                            
                            // 2. Update the status badge text and color class
                            badge.textContent = updatedStatus;
                            // Reset class list to ensure only the correct status class is present
                            badge.className = `status-badge status-${updatedStatus}`;

                            // 3. Reset and rebuild the dropdown options based on the new status
                            
                            // Clear all existing options
                            while(selectElement.options.length > 0) {
                                selectElement.remove(0);
                            }
                            
                            // Add the default 'Change Status' option
                            const defaultOption = document.createElement('option');
                            defaultOption.value = "";
                            defaultOption.textContent = originalText;
                            defaultOption.disabled = true;
                            defaultOption.selected = true;
                            selectElement.appendChild(defaultOption);

                            // Re-populate options, excluding the new current status
                            const allStatuses = ['unread', 'read', 'replied'];
                            allStatuses.forEach(status => {
                                if (status !== updatedStatus) {
                                    const option = document.createElement('option');
                                    option.value = status;
                                    option.textContent = `Mark as ${status.charAt(0).toUpperCase() + status.slice(1)}`;
                                    selectElement.appendChild(option);
                                }
                            });
                            
                        } else {
                            // Use an error message box instead of alert()
                            console.error('Update failed:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                    })
                    .finally(() => {
                        // Restore interactivity and default text
                        selectElement.disabled = false;
                        selectElement.options[0].textContent = originalText;
                        selectElement.value = ''; // Ensure the default option is visible/selected
                    });
                });
            }
        });
    </script>
</body>
</html>

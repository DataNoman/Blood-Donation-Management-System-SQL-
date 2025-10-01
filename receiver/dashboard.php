<?php
session_start();

// Security check - redirect to login if not logged in
if (!isset($_SESSION['receiver_logged_in']) || $_SESSION['receiver_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

require_once '../config/db_connection.php';

// Get receiver information
$user_id = $_SESSION['user_id'];

// Fetch user data
$user_query = "SELECT u.*, rec.urgency_level, rec.hospital, bt.type as blood_type_needed 
               FROM users u 
               JOIN recipients rec ON u.id = rec.user_id 
               JOIN blood_types bt ON rec.blood_type_id = bt.id 
               WHERE u.id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get recipient ID
$recipient_query = "SELECT id FROM recipients WHERE user_id = ?";
$stmt = $conn->prepare($recipient_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recipient_result = $stmt->get_result();
$recipient_data = $recipient_result->fetch_assoc();
$recipient_id = $recipient_data['id'];
$stmt->close();

// Get my blood requests
$my_requests_query = "SELECT r.*, bt.type as blood_type 
                      FROM requests r 
                      JOIN blood_types bt ON r.blood_type_id = bt.id 
                      WHERE r.recipient_id = ? 
                      ORDER BY r.request_date DESC";
$stmt = $conn->prepare($my_requests_query);
$stmt->bind_param("i", $recipient_id);
$stmt->execute();
$my_requests_result = $stmt->get_result();
$stmt->close();

// Count active requests
$active_count_query = "SELECT COUNT(*) as active_count 
                       FROM requests 
                       WHERE recipient_id = ? AND status = 'Open'";
$stmt = $conn->prepare($active_count_query);
$stmt->bind_param("i", $recipient_id);
$stmt->execute();
$active_count_result = $stmt->get_result();
$active_count = $active_count_result->fetch_assoc();
$stmt->close();

// Count fulfilled requests
$fulfilled_count_query = "SELECT COUNT(*) as fulfilled_count 
                          FROM requests 
                          WHERE recipient_id = ? AND status = 'Fulfilled'";
$stmt = $conn->prepare($fulfilled_count_query);
$stmt->bind_param("i", $recipient_id);
$stmt->execute();
$fulfilled_count_result = $stmt->get_result();
$fulfilled_count = $fulfilled_count_result->fetch_assoc();
$stmt->close();

// Get matched donations
$matches_query = "SELECT m.*, d.donation_date, d.quantity_ml, don.age, u.first_name, u.last_name, bt.type as blood_type
                  FROM matches m
                  JOIN donations d ON m.donation_id = d.id
                  JOIN donors don ON d.donor_id = don.id
                  JOIN users u ON don.user_id = u.id
                  JOIN blood_types bt ON don.blood_type_id = bt.id
                  JOIN requests r ON m.request_id = r.id
                  WHERE r.recipient_id = ?
                  ORDER BY m.match_date DESC
                  LIMIT 5";
$stmt = $conn->prepare($matches_query);
$stmt->bind_param("i", $recipient_id);
$stmt->execute();
$matches_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receiver Dashboard - BloodConnect</title>
    <link rel="stylesheet" href="receiver_style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-hand-holding-medical"></i>
                <h1>Receiver Dashboard</h1>
            </div>
            <div class="dashboard-nav">
                <a href="../index.html" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="dashboard-content">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <h2>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                <p>You are registered as a blood receiver. Need blood? Create a request and we'll connect you with donors.</p>
            </div>

            <!-- Quick Action Card -->
            <div class="quick-action-card">
                <div class="action-content">
                    <i class="fas fa-plus-circle"></i>
                    <div>
                        <h3>Need Blood Urgently?</h3>
                        <p>Create a new blood request and notify available donors in your area</p>
                    </div>
                </div>
                <button class="btn btn-primary btn-large" onclick="alert('Request form feature coming soon!')">
                    <i class="fas fa-hand-holding-medical"></i> Create Blood Request
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <i class="fas fa-tint"></i>
                    <h3><?php echo $user['blood_type_needed']; ?></h3>
                    <p>Blood Type Needed</p>
                </div>
                <div class="stat-card warning">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $active_count['active_count']; ?></h3>
                    <p>Active Requests</p>
                </div>
                <div class="stat-card success">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $fulfilled_count['fulfilled_count']; ?></h3>
                    <p>Fulfilled Requests</p>
                </div>
                <div class="stat-card info">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3><?php echo ucfirst($user['urgency_level']); ?></h3>
                    <p>Priority Level</p>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-user"></i> Your Profile</h3>
                </div>
                <div class="profile-grid">
                    <div class="profile-item">
                        <strong>Full Name:</strong>
                        <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Email:</strong>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Username:</strong>
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Hospital:</strong>
                        <span><?php echo htmlspecialchars($user['hospital']); ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Urgency Level:</strong>
                        <span class="urgency-badge urgency-<?php echo strtolower($user['urgency_level']); ?>">
                            <?php echo $user['urgency_level']; ?>
                        </span>
                    </div>
                    <div class="profile-item">
                        <strong>Member Since:</strong>
                        <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- My Blood Requests -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> My Blood Requests</h3>
                </div>
                <?php if ($my_requests_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Blood Type</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($request = $my_requests_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                        <td>
                                            <span class="blood-type-badge">
                                                <?php echo $request['blood_type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $request['quantity_ml']; ?> ml</td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                                <?php echo $request['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($request['status'] == 'Open'): ?>
                                                <button class="btn-small btn-danger" onclick="if(confirm('Cancel this request?')) alert('Cancel feature coming soon!')">
                                                    Cancel
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No blood requests yet. Create your first request to find donors!</p>
                        <button class="btn btn-primary" onclick="alert('Request form coming soon!')">
                            Create Request
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Matched Donors -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-users"></i> Matched Donors</h3>
                </div>
                <?php if ($matches_result->num_rows > 0): ?>
                    <div class="donors-list">
                        <?php while ($match = $matches_result->fetch_assoc()): ?>
                            <div class="donor-card">
                                <div class="donor-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="donor-info">
                                    <h4><?php echo htmlspecialchars($match['first_name'] . ' ' . $match['last_name']); ?></h4>
                                    <p><i class="fas fa-tint"></i> Blood Type: <strong><?php echo $match['blood_type']; ?></strong></p>
                                    <p><i class="fas fa-calendar"></i> Donated: <?php echo date('M d, Y', strtotime($match['donation_date'])); ?></p>
                                    <p><i class="fas fa-vial"></i> Quantity: <?php echo $match['quantity_ml']; ?> ml</p>
                                </div>
                                <div class="donor-badge">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Matched</span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-friends"></i>
                        <p>No matched donors yet. Create a blood request to find donors.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Help Section -->
            <div class="help-card">
                <div class="help-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="help-content">
                    <h3>Need Help?</h3>
                    <p>Contact our support team for assistance with your blood request or any questions.</p>
                    <a href="tel:+880170000000" class="btn btn-secondary">
                        <i class="fas fa-phone"></i> Call Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
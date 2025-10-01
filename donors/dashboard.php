<?php
session_start();

// Security check - redirect to login if not logged in
if (!isset($_SESSION['donor_logged_in']) || $_SESSION['donor_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

require_once '../config/db_connection.php';

// Get donor information
$user_id = $_SESSION['user_id'];

// Fetch user data
$user_query = "SELECT u.*, d.age, d.gender, bt.type as blood_type, d.last_donation_date, d.health_status 
               FROM users u 
               JOIN donors d ON u.id = d.user_id 
               JOIN blood_types bt ON d.blood_type_id = bt.id 
               WHERE u.id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get donation history
$donations_query = "SELECT donation_date, quantity_ml, status, bb.name as blood_bank_name 
                    FROM donations d 
                    JOIN donors don ON d.donor_id = don.id 
                    JOIN blood_banks bb ON d.blood_bank_id = bb.id 
                    WHERE don.user_id = ? 
                    ORDER BY donation_date DESC 
                    LIMIT 5";
$stmt = $conn->prepare($donations_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$donations_result = $stmt->get_result();
$stmt->close();

// Calculate eligibility for next donation (90 days gap)
$eligible_for_donation = true;
$days_until_eligible = 0;
if ($user['last_donation_date']) {
    $last_donation = new DateTime($user['last_donation_date']);
    $today = new DateTime();
    $diff = $today->diff($last_donation);
    $days_passed = $diff->days;
    
    if ($days_passed < 90) {
        $eligible_for_donation = false;
        $days_until_eligible = 90 - $days_passed;
    }
}

// Get total donation count
$count_query = "SELECT COUNT(*) as total_donations 
                FROM donations d 
                JOIN donors don ON d.donor_id = don.id 
                WHERE don.user_id = ? AND d.status = 'Completed'";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$count_result = $stmt->get_result();
$donation_count = $count_result->fetch_assoc();
$stmt->close();

// Get active blood requests matching donor's blood type
$requests_query = "SELECT r.*, rec.hospital, u.first_name, u.last_name, bt.type as blood_type_needed 
                   FROM requests r 
                   JOIN recipients rec ON r.recipient_id = rec.id 
                   JOIN users u ON rec.user_id = u.id 
                   JOIN blood_types bt ON r.blood_type_id = bt.id 
                   WHERE r.status = 'Open' 
                   ORDER BY r.request_date DESC 
                   LIMIT 5";
$requests_result = $conn->query($requests_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - BloodConnect</title>
    <link rel="stylesheet" href="donor_style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-hand-holding-heart"></i>
                <h1>Donor Dashboard</h1>
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
                <h2>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                <p>Thank you for being a life-saving hero. Your blood type <strong><?php echo $user['blood_type']; ?></strong> can save lives.</p>
            </div>

            <!-- Eligibility Status -->
            <div class="eligibility-card <?php echo $eligible_for_donation ? 'eligible' : 'not-eligible'; ?>">
                <div class="eligibility-icon">
                    <i class="fas <?php echo $eligible_for_donation ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                </div>
                <div class="eligibility-content">
                    <?php if ($eligible_for_donation): ?>
                        <h3>You are eligible to donate!</h3>
                        <p>You can donate blood now. Contact a blood bank to schedule your donation.</p>
                    <?php else: ?>
                        <h3>Not eligible yet</h3>
                        <p>You can donate again in <strong><?php echo $days_until_eligible; ?> days</strong> (90 days required between donations)</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-tint"></i>
                    <h3><?php echo $user['blood_type']; ?></h3>
                    <p>Blood Type</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-heart"></i>
                    <h3><?php echo $donation_count['total_donations']; ?></h3>
                    <p>Total Donations</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-alt"></i>
                    <h3><?php echo $user['last_donation_date'] ? date('M d, Y', strtotime($user['last_donation_date'])) : 'Never'; ?></h3>
                    <p>Last Donation</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-check"></i>
                    <h3><?php echo $user['age']; ?> years</h3>
                    <p>Age</p>
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
                        <strong>Gender:</strong>
                        <span><?php echo htmlspecialchars($user['gender']); ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Health Status:</strong>
                        <span><?php echo htmlspecialchars($user['health_status']); ?></span>
                    </div>
                    <div class="profile-item">
                        <strong>Member Since:</strong>
                        <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Donation History -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-history"></i> Recent Donation History</h3>
                </div>
                <?php if ($donations_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Blood Bank</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($donation = $donations_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($donation['donation_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($donation['blood_bank_name']); ?></td>
                                        <td><?php echo $donation['quantity_ml']; ?> ml</td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($donation['status']); ?>">
                                                <?php echo $donation['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No donation history yet. Make your first donation and start saving lives!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Active Blood Requests -->
            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-hand-holding-medical"></i> Active Blood Requests</h3>
                </div>
                <?php if ($requests_result->num_rows > 0): ?>
                    <div class="requests-list">
                        <?php while ($request = $requests_result->fetch_assoc()): ?>
                            <div class="request-item">
                                <div class="request-header">
                                    <span class="blood-type-badge"><?php echo $request['blood_type_needed']; ?></span>
                                    <span class="request-date"><?php echo date('M d, Y', strtotime($request['request_date'])); ?></span>
                                </div>
                                <div class="request-body">
                                    <p><strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong></p>
                                    <p><i class="fas fa-hospital"></i> <?php echo htmlspecialchars($request['hospital']); ?></p>
                                    <p><i class="fas fa-vial"></i> Quantity needed: <?php echo $request['quantity_ml']; ?> ml</p>
                                </div>
                                <div class="request-footer">
                                    <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                        <?php echo $request['status']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No active blood requests at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
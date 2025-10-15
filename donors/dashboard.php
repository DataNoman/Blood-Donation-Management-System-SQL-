<?php
session_start();

// Security check - redirect to login if not logged in
if (!isset($_SESSION['donor_logged_in']) || $_SESSION['donor_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

require_once '../config/db_connection.php';

// Get donor's user ID from session
$user_id = $_SESSION['user_id'];

// --- FETCH USER AND DONOR DATA ---
$user_query = "
    SELECT
        u.first_name, u.last_name, u.username, u.email, u.created_at,
        d.id AS donor_id,
        d.age,
        d.gender,
        d.health_status,
        bt.type AS blood_type,
        d.blood_type_id
    FROM
        users u
    LEFT JOIN
        donors d ON u.id = d.user_id
    LEFT JOIN
        blood_types bt ON d.blood_type_id = bt.id
    WHERE
        u.id = ?
";

$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Check if user data exists
if (!$user || !$user['donor_id']) {
    die("Error: Donor profile not found. Please ensure you registered as a donor. <a href='../index.php'>Go Home</a>");
}

// Extract the necessary IDs
$donor_id = $user['donor_id'];
$donor_blood_type_id = $user['blood_type_id'];

// Calculate donation statistics
$stats_query = "
    SELECT 
        MAX(response_date) AS calculated_last_response_date,
        COUNT(*) AS total_accepted_responses
    FROM responses 
    WHERE donor_id = ? AND status = 'Accepted'
";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stmt->close();

$user['calculated_last_response_date'] = $stats['calculated_last_response_date'];
$user['total_accepted_responses'] = $stats['total_accepted_responses'];

// Get response history
$response_history_query = "
    SELECT 
        res.response_date, 
        res.status,
        req.quantity_ml,
        rec.hospital
    FROM responses res
    JOIN requests req ON res.request_id = req.id
    JOIN recipients rec ON req.recipient_id = rec.id
    WHERE res.donor_id = ?
    ORDER BY res.response_date DESC
    LIMIT 5
";
$stmt = $conn->prepare($response_history_query);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$response_history_result = $stmt->get_result();
$stmt->close();

// Calculate eligibility
$eligible_for_donation = true;
$days_until_eligible = 0;
if (!empty($user['calculated_last_response_date'])) {
    $last_response_date = new DateTime($user['calculated_last_response_date']);
    $today = new DateTime();
    $diff = $today->diff($last_response_date);
    $days_passed = $diff->days;
    
    // Donation interval is 90 days
    if ($days_passed < 90) {
        $eligible_for_donation = false;
        $days_until_eligible = 90 - $days_passed;
    }
}

// Get ACCEPTED requests where donor can message (separate query)
$accepted_requests_query = "
    SELECT
        r.id,
        r.request_date,
        r.quantity_ml,
        r.status AS request_status,
        rec.hospital,
        rec.phone,
        rec.urgency_level,
        rec.user_id AS recipient_user_id,
        u.first_name,
        u.last_name,
        bt.type AS blood_type_needed,
        res.response_date,
        res.status AS response_status
    FROM
        responses res
    JOIN
        requests r ON res.request_id = r.id
    JOIN
        recipients rec ON r.recipient_id = rec.id
    JOIN
        users u ON rec.user_id = u.id
    JOIN
        blood_types bt ON r.blood_type_id = bt.id
    WHERE
        res.donor_id = ? AND res.status = 'Accepted'
    ORDER BY
        res.response_date DESC
    LIMIT 5
";
$stmt = $conn->prepare($accepted_requests_query);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$accepted_requests_result = $stmt->get_result();
$stmt->close();

// Get active blood requests that match donor's blood type
$requests_query = "
    SELECT
        r.*,
        rec.hospital,
        rec.phone,
        rec.urgency_level,
        rec.user_id AS recipient_user_id,
        u.first_name,
        u.last_name,
        bt.type AS blood_type_needed,
        res.id AS response_id,
        res.status AS response_status
    FROM
        requests r
    JOIN
        recipients rec ON r.recipient_id = rec.id
    JOIN
        users u ON rec.user_id = u.id
    JOIN
        blood_types bt ON r.blood_type_id = bt.id
    JOIN
        compatibility comp ON r.blood_type_id = comp.recipient_type_id
    LEFT JOIN
        responses res ON r.id = res.request_id AND res.donor_id = ?
    WHERE
        r.status = 'Open'
        AND comp.donor_type_id = ? 
    ORDER BY
        r.request_date DESC
    LIMIT 10;
";
$stmt = $conn->prepare($requests_query);
$stmt->bind_param("ii", $donor_id, $donor_blood_type_id);
$stmt->execute();
$requests_result = $stmt->get_result();
$stmt->close();
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
        <div class="dashboard-header">
            <div class="dashboard-title">
                <i class="fas fa-hand-holding-heart"></i>
                <h1>Donor Dashboard</h1>
            </div>
            <div class="dashboard-nav">
                <a href="../index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="welcome-card">
                <h2>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                <p>Thank you for being a life-saving hero. Your blood type <strong><?php echo htmlspecialchars($user['blood_type']); ?></strong> can save lives.</p>
            </div>

            <div class="eligibility-card <?php echo $eligible_for_donation ? 'eligible' : 'not-eligible'; ?>">
                <div class="eligibility-icon">
                    <i class="fas <?php echo $eligible_for_donation ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                </div>
                <div class="eligibility-content">
                    <?php if ($eligible_for_donation): ?>
                        <h3>You are eligible to donate!</h3>
                        <p>You can respond to a request now.</p>
                    <?php else: ?>
                        <h3>Not eligible yet</h3>
                        <p>You can donate again in <strong><?php echo $days_until_eligible; ?> days</strong> (90 days required between donations)</p>
                        <p style="margin-top: 10px; font-size: 0.9em; color: #f9f1d8ff;">
                            <i class="fas fa-info-circle"></i> You cannot respond to any requests until you become eligible again.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-tint"></i>
                    <h3><?php echo htmlspecialchars($user['blood_type']); ?></h3>
                    <p>Blood Type</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-heart"></i>
                    <h3><?php echo $user['total_accepted_responses']; ?></h3>
                    <p>Total Donations</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-alt"></i>
                    <h3><?php echo !empty($user['calculated_last_response_date']) ? date('M d, Y', strtotime($user['calculated_last_response_date'])) : 'Never'; ?></h3>
                    <p>Last Donation</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-check"></i>
                    <h3><?php echo $user['age']; ?> years</h3>
                    <p>Age</p>
                </div>
            </div>

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

            <!-- NEW SECTION: Accepted Requests (Where Donor Can Message) -->
            <?php if ($accepted_requests_result->num_rows > 0): ?>
            <div class="section-card" style="background: #e8f5e9; border-left: 4px solid #4caf50;">
                <div class="section-header">
                    <h3><i class="fas fa-comments"></i> Your Accepted Donations - Message Recipients</h3>
                </div>
                <div class="requests-list">
                    <?php while ($accepted = $accepted_requests_result->fetch_assoc()): ?>
                        <div class="request-item" style="background: white; border: 2px solid #4caf50;">
                            <div class="request-header">
                                <span class="blood-type-badge"><?php echo htmlspecialchars($accepted['blood_type_needed']); ?></span>
                                <span class="status-badge status-accepted">
                                    <i class="fas fa-check-circle"></i> ACCEPTED
                                </span>
                            </div>
                            <div class="request-body">
                                <p class="request-name">
                                    <strong><?php echo htmlspecialchars($accepted['first_name'] . ' ' . $accepted['last_name']); ?></strong>
                                </p>

                                <p class="request-hospital">
                                    <i class="fas fa-hospital"></i>
                                    <span class="label">Hospital:</span>
                                    <?php echo htmlspecialchars($accepted['hospital']); ?>
                                </p>

                                <p class="request-quantity">
                                    <i class="fas fa-vial"></i>
                                    <span class="label">Quantity:</span>
                                    <?php echo htmlspecialchars($accepted['quantity_ml']); ?> ml
                                </p>

                                <p class="request-urgency">
                                    <i class="fas fa-calendar"></i>
                                    <span class="label">Request Date:</span>
                                    <?php echo date('M d, Y', strtotime($accepted['request_date'])); ?>
                                </p>

                                <p class="request-urgency">
                                    <i class="fas fa-check"></i>
                                    <span class="label">Accepted On:</span>
                                    <?php echo date('M d, Y', strtotime($accepted['response_date'])); ?>
                                </p>

                                <?php if (!empty($accepted['phone'])): ?>
                                <p class="request-contact">
                                    <i class="fas fa-phone"></i>
                                    <span class="label">Contact:</span>
                                    <a href="tel:<?php echo htmlspecialchars($accepted['phone']); ?>" class="contact-link">
                                        <?php echo htmlspecialchars($accepted['phone']); ?>
                                    </a>
                                </p>
                                <?php endif; ?>
                            </div>

                            <div class="request-footer">
                                <a href="message.php?request_id=<?php echo $accepted['id']; ?>" class="btn btn-primary" style="background: #4caf50;">
                                    <i class="fas fa-comments"></i> Message Recipient
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-history"></i> Recent Response History</h3>
                </div>
                <?php if ($response_history_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Recipient Hospital</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($response = $response_history_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($response['response_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($response['hospital']); ?></td>
                                        <td><?php echo $response['quantity_ml']; ?> ml</td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($response['status']); ?>">
                                                <?php echo $response['status']; ?>
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
                        <p>You have not responded to any requests yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3><i class="fas fa-hand-holding-medical"></i> Compatible Blood Requests</h3>
                    <?php if (!$eligible_for_donation): ?>
                        <span style="color: #d9534f; font-size: 0.9em;">
                            <i class="fas fa-exclamation-triangle"></i> You must wait <?php echo $days_until_eligible; ?> more days to respond
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($requests_result->num_rows > 0): ?>
                    <div class="requests-list">
                        <?php while ($request = $requests_result->fetch_assoc()): ?>
                            <div class="request-item <?php echo !$eligible_for_donation ? 'not-eligible' : ''; ?>">
                                <?php if (!$eligible_for_donation): ?>
                                    <div class="eligibility-overlay">
                                        <div class="eligibility-message">
                                            <i class="fas fa-lock"></i>
                                            <h4>Not Eligible to Donate</h4>
                                            <p>You must wait <strong><?php echo $days_until_eligible; ?> more days</strong></p>
                                            <?php if (!empty($user['calculated_last_response_date'])): ?>
                                                <p style="font-size: 12px; margin-top: 10px;">Last donation: <?php echo date('M d, Y', strtotime($user['calculated_last_response_date'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="request-header">
                                    <span class="blood-type-badge"><?php echo htmlspecialchars($request['blood_type_needed']); ?></span>
                                    <span class="request-date"><?php echo date('M d, Y', strtotime($request['request_date'])); ?></span>
                                </div>
                                <div class="request-body">
                                    <p class="request-name">
                                        <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong>
                                    </p>

                                    <p class="request-hospital">
                                        <i class="fas fa-hospital"></i>
                                        <span class="label">Hospital:</span>
                                        <?php echo htmlspecialchars($request['hospital']); ?>
                                    </p>

                                    <p class="request-quantity">
                                        <i class="fas fa-vial"></i>
                                        <span class="label">Quantity needed:</span>
                                        <?php echo htmlspecialchars($request['quantity_ml']); ?> ml
                                    </p>

                                    <p class="request-urgency">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span class="label">Urgency Level:</span>
                                        <span class="urgency <?php echo strtolower($request['urgency_level']); ?>">
                                            <?php echo htmlspecialchars($request['urgency_level']); ?>
                                        </span>
                                    </p>

                                    <p class="request-contact">
                                        <i class="fas fa-phone"></i>
                                        <span class="label">Contact:</span>
                                        <?php if (!empty($request['phone'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($request['phone']); ?>" class="contact-link">
                                                <?php echo htmlspecialchars($request['phone']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Not available</span>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <div class="request-footer">
                                    <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                        <?php echo htmlspecialchars($request['status']); ?>
                                    </span>
                                    
                                    <?php if ($eligible_for_donation): ?>
                                        <?php if (empty($request['response_id'])): ?>
                                            <!-- Donor hasn't responded yet -->
                                            <a href="respond.php?request_id=<?php echo $request['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-hand-holding-heart"></i> Respond
                                            </a>
                                        <?php elseif ($request['response_status'] == 'Pending'): ?>
                                            <!-- Response is pending -->
                                            <span class="status-badge status-pending">
                                                <i class="fas fa-clock"></i> Response Pending
                                            </span>
                                        <?php elseif ($request['response_status'] == 'Rejected'): ?>
                                            <!-- Response was rejected -->
                                            <span class="status-badge status-rejected">
                                                Response Rejected
                                            </span>
                                        <?php elseif ($request['response_status'] == 'Accepted'): ?>
                                            <!-- Response accepted - they can message from the section above -->
                                            <span class="status-badge status-accepted">
                                                <i class="fas fa-check-circle"></i> Accepted - See above to message
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn btn-disabled" disabled title="You are not eligible to donate yet">
                                            <i class="fas fa-lock"></i> Not Eligible
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No matching blood requests at the moment. Check back soon!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
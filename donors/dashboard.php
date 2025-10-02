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

// --- REWRITTEN & OPTIMIZED DATA FETCHING ---
// This query now gets all stats from the 'responses' table.
// 'Last Donation' is the MAX(response_date) for 'Accepted' responses.
// 'Total Donations' is the COUNT of 'Accepted' responses.

$user_query = "
    SELECT
        u.first_name, u.last_name, u.username, u.email, u.created_at,
        d.id AS donor_id,
        d.age,
        d.gender,
        d.health_status,
        bt.type AS blood_type,
        d.blood_type_id,
        -- Calculate the most recent ACCEPTED response date
        (SELECT MAX(response_date) FROM responses WHERE donor_id = d.id AND status = 'Accepted') AS calculated_last_response_date,
        -- Count total ACCEPTED responses
        (SELECT COUNT(*) FROM responses WHERE donor_id = d.id AND status = 'Accepted') AS total_accepted_responses
    FROM
        users u
    JOIN
        donors d ON u.id = d.user_id
    JOIN
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

// Extract the necessary IDs for subsequent queries
$donor_id = $user['donor_id'] ?? 0;
$donor_blood_type_id = $user['blood_type_id'] ?? 0;

// Get response history (changed from donation history)
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

// Calculate eligibility using the ACCURATE last response date
$eligible_for_donation = true;
$days_until_eligible = 0;
if ($user['calculated_last_response_date']) {
    $last_response_date = new DateTime($user['calculated_last_response_date']);
    $today = new DateTime();
    $diff = $today->diff($last_response_date);
    $days_passed = $diff->days;
    
    if ($days_passed < 90) {
        $eligible_for_donation = false;
        $days_until_eligible = 90 - $days_passed;
    }
}

// Get active blood requests that match donor’s blood type (This query is unchanged)
$requests_query = "
    SELECT
        r.*,
        rec.hospital,
        u.first_name,
        u.last_name,
        bt.type AS blood_type_needed
    FROM
        requests r
    JOIN
        recipients rec ON r.recipient_id = rec.id
    JOIN
        users u ON rec.user_id = u.id
    JOIN
        blood_types bt ON r.blood_type_id = bt.id
    JOIN
        -- Join directly on the compatibility table
        compatibility comp ON r.blood_type_id = comp.recipient_type_id
    WHERE
        r.status = 'Open'
        AND comp.donor_type_id = ? -- Filter by the donor's ID here
    ORDER BY
        r.request_date DESC
    LIMIT 5;
";
$stmt = $conn->prepare($requests_query);
$stmt->bind_param("i", $donor_blood_type_id);
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
                <a href="../index.html" class="btn btn-secondary">
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
                <p>Thank you for being a life-saving hero. Your blood type <strong><?php echo $user['blood_type']; ?></strong> can save lives.</p>
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
                    <?php endif; ?>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-tint"></i>
                    <h3><?php echo $user['blood_type']; ?></h3>
                    <p>Blood Type</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-heart"></i>
                    <h3><?php echo $user['total_accepted_responses']; ?></h3>
                    <p>Total Donations</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-alt"></i>
                    <h3><?php echo $user['calculated_last_response_date'] ? date('M d, Y', strtotime($user['calculated_last_response_date'])) : 'Never'; ?></h3>
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
                                    <a href="respond.php?request_id=<?php echo $request['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-hand-holding-heart"></i> Respond
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No matching blood requests at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
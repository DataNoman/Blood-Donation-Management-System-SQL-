<?php
session_start();

// --- Authentication Check ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// --- Database Connection ---
require_once '../config/db_connection.php';

// --- Initialize Variables ---
$message = '';
$message_type = '';

// --- Form Submission Handling (CRUD Actions) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    // ACTION: Add a new Blood Bank
    if ($_POST['action'] == 'add_bank') {
        $name = $_POST['name'];
        $location = $_POST['location'];
        $contact = $_POST['contact'];

        $stmt = $conn->prepare("INSERT INTO blood_banks (name, location, contact) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $location, $contact);
        if ($stmt->execute()) {
            $message = "New blood bank added successfully!";
            $message_type = 'success';
        } else {
            $message = "Error adding blood bank: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }

    // ACTION: Adjust Inventory
    if ($_POST['action'] == 'adjust_inventory') {
        $bank_id = $_POST['bank_id'];
        $blood_type_id = $_POST['blood_type_id'];
        $quantity = $_POST['quantity_ml'];
        $adjustment_type = $_POST['adjustment_type'];

        if ($adjustment_type == 'subtract') {
            $quantity = -$quantity; // Make quantity negative for subtraction
        }

        $check_stmt = $conn->prepare("SELECT id FROM inventory WHERE bank_id = ? AND blood_type_id = ?");
        $check_stmt->bind_param("ii", $bank_id, $blood_type_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $update_stmt = $conn->prepare("UPDATE inventory SET quantity_ml = quantity_ml + ? WHERE bank_id = ? AND blood_type_id = ?");
            $update_stmt->bind_param("iii", $quantity, $bank_id, $blood_type_id);
            if ($update_stmt->execute()) {
                $message = "Inventory updated successfully!";
                $message_type = 'success';
            } else {
                $message = "Error updating inventory: " . $update_stmt->error;
                $message_type = 'error';
            }
            $update_stmt->close();
        } else {
            if ($quantity > 0) {
                $insert_stmt = $conn->prepare("INSERT INTO inventory (bank_id, blood_type_id, quantity_ml) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iii", $bank_id, $blood_type_id, $quantity);
                if ($insert_stmt->execute()) {
                    $message = "New inventory record created successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error creating inventory record: " . $insert_stmt->error;
                    $message_type = 'error';
                }
                $insert_stmt->close();
            } else {
                $message = "Cannot subtract from non-existent inventory.";
                $message_type = 'error';
            }
        }
        $check_stmt->close();
    }
    
    // **NEW ACTION**: Update Request and Response Status
    if ($_POST['action'] == 'update_status') {
        $request_id = $_POST['request_id'];
        $response_id = $_POST['response_id'];
        $new_status_action = $_POST['new_status'];
        
        $request_status = '';
        $response_status = '';

        // Determine the new statuses based on the selected action
        if ($new_status_action == 'complete') {
            $request_status = 'Fulfilled';
            // In your schema, response status is 'Pending','Accepted','Rejected'. Let's use 'Accepted'.
            $response_status = 'Accepted'; 
        } elseif ($new_status_action == 'cancel') {
            $request_status = 'Open'; // Request goes back to open
            // In your schema, let's use 'Rejected'.
            $response_status = 'Rejected';
        }

        if ($request_id && $response_id && $request_status && $response_status) {
            // Use a transaction to ensure both updates succeed or fail together
            $conn->begin_transaction();
            try {
                // Update responses table
                $stmt_resp = $conn->prepare("UPDATE responses SET status = ? WHERE id = ?");
                $stmt_resp->bind_param("si", $response_status, $response_id);
                $stmt_resp->execute();

                // Update requests table
                $stmt_req = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
                $stmt_req->bind_param("si", $request_status, $request_id);
                $stmt_req->execute();
                
                $conn->commit();
                $message = "Status updated successfully!";
                $message_type = 'success';
            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $message = "Error updating status: " . $exception->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = "Invalid data provided for status update.";
            $message_type = 'error';
        }
    }
}

// --- DELETE Action Handling ---
if (isset($_GET['action']) && $_GET['action'] == 'delete_bank' && isset($_GET['id'])) {
    $bank_id_to_delete = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM blood_banks WHERE id = ?");
    $stmt->bind_param("i", $bank_id_to_delete);
    if ($stmt->execute()) {
        $message = "Blood bank deleted successfully!";
        $message_type = 'success';
    } else {
        $message = "Error deleting blood bank: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}


// --- Data Fetching for Dashboard Display ---

// 1. Key Statistics
$total_donors_result = $conn->query("SELECT COUNT(*) as count FROM donors");
$total_donors = $total_donors_result ? $total_donors_result->fetch_assoc()['count'] : 0;
$active_requests = $conn->query("SELECT COUNT(*) as count FROM requests WHERE status = 'Open'")->fetch_assoc()['count'];
$pending_responses = $conn->query("SELECT COUNT(*) as count FROM responses WHERE status = 'Pending'")->fetch_assoc()['count'];
$low_inventory_result = $conn->query("SELECT bb.name, bt.type FROM inventory i JOIN blood_banks bb ON i.bank_id = bb.id JOIN blood_types bt ON i.blood_type_id = bt.id WHERE i.quantity_ml < 1000"); // Low stock threshold: 1000ml

// 2. Blood Banks List
$blood_banks_result = $conn->query("SELECT * FROM blood_banks ORDER BY name ASC");

// 3. Master Inventory List
$inventory_result = $conn->query("SELECT i.id, bb.name, bt.type, i.quantity_ml, i.last_updated FROM inventory i JOIN blood_banks bb ON i.bank_id = bb.id JOIN blood_types bt ON i.blood_type_id = bt.id ORDER BY bb.name, bt.type");

// 4. Data for Forms
$blood_types_result_for_form = $conn->query("SELECT * FROM blood_types ORDER BY type");
$blood_banks_result_for_form = $conn->query("SELECT id, name FROM blood_banks ORDER BY name ASC");


// **NEW & CORRECTED**: 5. Donor Search Logic
$donor_search_results = [];
$search_location = '';
$search_blood_type_id = '';
if (isset($_GET['search'])) {
    $search_location = $_GET['location'] ?? '';
    $search_blood_type_id = $_GET['blood_type'] ?? '';

    // CORRECTED: Selects from `donors` for location/phone and CONCATs name from `users`
    $sql = "SELECT CONCAT(u.first_name, ' ', u.last_name) AS name, u.email, d.phone, d.location, bt.type as blood_type
            FROM users u
            JOIN donors d ON u.id = d.user_id
            JOIN blood_types bt ON d.blood_type_id = bt.id
            WHERE u.role = 'donor'";
    
    $params = [];
    $types = '';

    if (!empty($search_location)) {
        $sql .= " AND d.location LIKE ?"; // CORRECTED: Searches d.location
        $types .= 's';
        $params[] = '%' . $search_location . '%';
    }
    if (!empty($search_blood_type_id)) {
        $sql .= " AND d.blood_type_id = ?";
        $types .= 'i';
        $params[] = $search_blood_type_id;
    }

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $donor_search_results[] = $row;
    }
    $stmt->close();
}

// **NEW & CORRECTED**: 6. Fetch Requests and Responses
// CORRECTED: Uses CONCAT() for names to match your DB schema
$requests_responses_result = $conn->query("
    SELECT
        req.id AS request_id,
        res.id AS response_id,
        CONCAT(recipient_user.first_name, ' ', recipient_user.last_name) AS recipient_name,
        CONCAT(donor_user.first_name, ' ', donor_user.last_name) AS donor_name,
        bt.type AS blood_type,
        req.quantity_ml,
        req.status AS request_status,
        res.status AS response_status
    FROM requests req
    LEFT JOIN responses res ON req.id = res.request_id
    JOIN recipients rec ON req.recipient_id = rec.id
    JOIN users recipient_user ON rec.user_id = recipient_user.id
    JOIN blood_types bt ON req.blood_type_id = bt.id
    LEFT JOIN donors d ON res.donor_id = d.id
    LEFT JOIN users donor_user ON d.user_id = donor_user.id
    WHERE req.status IN ('Open', 'Pending') OR res.status = 'Pending'
    ORDER BY req.request_date DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="dashboard-wrapper">
        <div class="header-bar">
            <h1>Admin Dashboard</h1>
            <form action="logout.php" method="post">
                <button type="submit" class="btn btn-danger">Logout</button>
            </form>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <h2>System Health</h2>
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_donors; ?></div>
                <div class="stat-label">Total Donors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $active_requests; ?></div>
                <div class="stat-label">Active Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_responses; ?></div>
                <div class="stat-label">Pending Responses</div>
            </div>
        </div>
        
        <?php if ($low_inventory_result->num_rows > 0): ?>
        <div class="message error">
            <strong>Low Inventory Alert!</strong> The following are running low:
            <?php while($row = $low_inventory_result->fetch_assoc()): ?>
                <p style="margin: 5px 0;">[ <?php echo htmlspecialchars($row['type']); ?>] at <?php echo htmlspecialchars($row['name']); ?></p>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <h2>Manage Requests & Responses</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Recipient</th>
                        <th>Blood Type</th>
                        <th>Donor</th>
                        <th>Request Status</th>
                        <th>Response Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($requests_responses_result->num_rows > 0): ?>
                        <?php while($row = $requests_responses_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['request_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['donor_name'] ?? 'N/A'); ?></td>
                            <td><span class="status status-<?php echo strtolower(htmlspecialchars($row['request_status'])); ?>"><?php echo htmlspecialchars($row['request_status']); ?></span></td>
                            <td><span class="status status-<?php echo strtolower(htmlspecialchars($row['response_status'] ?? 'none')); ?>"><?php echo htmlspecialchars($row['response_status'] ?? 'No Response'); ?></span></td>
                            <td>
                                <?php if ($row['response_id'] && $row['response_status'] == 'Pending'): ?>
                                    <form action="dashboard.php" method="POST" class="form-inline-actions">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                        <input type="hidden" name="response_id" value="<?php echo $row['response_id']; ?>">
                                        <select name="new_status">
                                            <option value="complete">Mark as Accepted/Fulfilled</option>
                                            <option value="cancel">Mark as Rejected</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                    </form>
                                <?php else: ?>
                                    No action required
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No active requests or pending responses.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <h2>Find Donors</h2>
        <form action="dashboard.php" method="GET">
            <input type="hidden" name="search" value="1">
            <div class="form-inline">
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" placeholder="e.g., Dhaka" value="<?php echo htmlspecialchars($search_location); ?>">
                </div>
                <div class="form-group">
                    <label for="blood_type">Blood Type</label>
                    <select id="blood_type" name="blood_type">
                        <option value="">Any</option>
                        <?php mysqli_data_seek($blood_types_result_for_form, 0); // Reset pointer ?>
                        <?php while($type = $blood_types_result_for_form->fetch_assoc()): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo ($search_blood_type_id == $type['id']) ? 'selected' : ''; ?>>
                                <?php echo $type['type']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Search Donors</button>
            </div>
        </form>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Blood Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($donor_search_results)): ?>
                        <?php foreach ($donor_search_results as $donor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($donor['name']); ?></td>
                            <td><?php echo htmlspecialchars($donor['email']); ?></td>
                            <td><?php echo htmlspecialchars($donor['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($donor['location'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($donor['blood_type']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php elseif (isset($_GET['search'])): ?>
                        <tr><td colspan="5">No donors found matching your criteria.</td></tr>
                    <?php else: ?>
                        <tr><td colspan="5">Use the form above to search for donors.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <h2>Manage Blood Banks</h2>
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="add_bank">
            <div class="form-inline">
                <div class="form-group">
                    <label for="name">Bank Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="location_bank">Location</label>
                    <input type="text" id="location_bank" name="location" required>
                </div>
                <div class="form-group">
                    <label for="contact">Contact</label>
                    <input type="text" id="contact" name="contact">
                </div>
                <button type="submit" class="btn btn-success">Add Bank</button>
            </div>
        </form>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($blood_banks_result, 0); ?>
                    <?php while($bank = $blood_banks_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $bank['id']; ?></td>
                        <td><?php echo htmlspecialchars($bank['name']); ?></td>
                        <td><?php echo htmlspecialchars($bank['location']); ?></td>
                        <td><?php echo htmlspecialchars($bank['contact']); ?></td>
                        <td>
                            <a href="dashboard.php?action=delete_bank&id=<?php echo $bank['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>


        <h2>Master Inventory</h2>
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="adjust_inventory">
            <div class="form-inline">
                <div class="form-group">
                    <label for="bank_id">Select Bank</label>
                    <select id="bank_id" name="bank_id" required>
                        <?php mysqli_data_seek($blood_banks_result_for_form, 0); // Reset pointer ?>
                        <?php while($bank = $blood_banks_result_for_form->fetch_assoc()): ?>
                            <option value="<?php echo $bank['id']; ?>"><?php echo htmlspecialchars($bank['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="blood_type_id">Blood Type</label>
                    <select id="blood_type_id" name="blood_type_id" required>
                         <?php mysqli_data_seek($blood_types_result_for_form, 0); // Reset pointer ?>
                         <?php while($type = $blood_types_result_for_form->fetch_assoc()): ?>
                             <option value="<?php echo $type['id']; ?>"><?php echo $type['type']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="quantity_ml">Quantity (ml)</label>
                    <input type="number" id="quantity_ml" name="quantity_ml" min="1" required>
                </div>
                <div class="form-group">
                    <label for="adjustment_type">Action</label>
                    <select id="adjustment_type" name="adjustment_type" required>
                        <option value="add">Add to Stock</option>
                        <option value="subtract">Subtract from Stock</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Adjust Inventory</button>
            </div>
        </form>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Bank Name</th>
                        <th>Blood Type</th>
                        <th>Quantity (ml)</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($inventory_result, 0); ?>
                    <?php while($item = $inventory_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['type']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity_ml']); ?></td>
                        <td><?php echo htmlspecialchars($item['last_updated']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php
// Close the connection
$conn->close();
?>
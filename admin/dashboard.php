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
            $quantity = -$quantity; // Make the quantity negative for subtraction
        }

        // Check if an inventory entry for this bank and blood type already exists
        $check_stmt = $conn->prepare("SELECT id FROM inventory WHERE bank_id = ? AND blood_type_id = ?");
        $check_stmt->bind_param("ii", $bank_id, $blood_type_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            // Entry exists, UPDATE it
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
            // No entry, INSERT a new one (only if adding)
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
$total_donors = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'donor'")->fetch_assoc()['count'];
$active_requests = $conn->query("SELECT COUNT(*) as count FROM requests WHERE status = 'Open'")->fetch_assoc()['count'];
$pending_donations = $conn->query("SELECT COUNT(*) as count FROM donations WHERE status = 'Pending'")->fetch_assoc()['count'];
$low_inventory_result = $conn->query("SELECT bb.name, bt.type FROM inventory i JOIN blood_banks bb ON i.bank_id = bb.id JOIN blood_types bt ON i.blood_type_id = bt.id WHERE i.quantity_ml < 1000"); // Low stock threshold: 1000ml

// 2. Blood Banks List
$blood_banks_result = $conn->query("SELECT * FROM blood_banks ORDER BY name ASC");

// 3. Master Inventory List
$inventory_result = $conn->query("SELECT i.id, bb.name, bt.type, i.quantity_ml, i.last_updated FROM inventory i JOIN blood_banks bb ON i.bank_id = bb.id JOIN blood_types bt ON i.blood_type_id = bt.id ORDER BY bb.name, bt.type");

// 4. Data for Forms
$blood_types_result_for_form = $conn->query("SELECT * FROM blood_types");
$blood_banks_result_for_form = $conn->query("SELECT id, name FROM blood_banks ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- THIS IS THE ONLY LINE THAT HAS BEEN CHANGED -->
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

        <!-- SECTION 1: SYSTEM HEALTH STATISTICS -->
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
                <div class="stat-number"><?php echo $pending_donations; ?></div>
                <div class="stat-label">Pending Donations</div>
            </div>
        </div>
        
        <?php if ($low_inventory_result->num_rows > 0): ?>
        <div class="message error">
            <strong>Low Inventory Alert!</strong> The following are running low:
            <?php while($row = $low_inventory_result->fetch_assoc()): ?>
                <p style="margin: 5px 0;">[ <?php echo htmlspecialchars($row['type']); ?>].. at <?php echo htmlspecialchars($row['name']); ?></p>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>


        <!-- SECTION 2: MANAGE BLOOD BANKS -->
        <h2>Manage Blood Banks</h2>
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="add_bank">
            <div class="form-inline">
                <div class="form-group">
                    <label for="name">Bank Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" required>
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
                    <?php while($bank = $blood_banks_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $bank['id']; ?></td>
                        <td><?php echo htmlspecialchars($bank['name']); ?></td>
                        <td><?php echo htmlspecialchars($bank['location']); ?></td>
                        <td><?php echo htmlspecialchars($bank['contact']); ?></td>
                        <td>
                            <!-- NOTE: The Edit functionality would lead to a separate page like 'edit_bank.php' 
                            <button class="btn btn-primary btn-sm" onclick="alert('Edit functionality not yet implemented.')">Edit</button>-->

                            <a href="dashboard.php?action=delete_bank&id=<?php echo $bank['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this bank? This will also delete its inventory records.')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>


        <!-- SECTION 3: MASTER INVENTORY CONTROL -->
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
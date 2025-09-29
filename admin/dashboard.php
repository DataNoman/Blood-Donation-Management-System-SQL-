```php
<?php
session_start(); // Start the session

// Include the database connection file
require_once 'db_connection.php';

// --- Authentication and Authorization Check ---
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header('Location: admin/index.php'); // Adjust this to your actual login page
    exit();
}

// Check if the logged-in user has the 'admin' role
if ($_SESSION['role'] !== 'admin') {
    // If not an admin, redirect to a different page or show an access denied message
    // For now, redirect to login as they don't have permission for this dashboard
    header('Location: admin/index.php'); // Or to a 'unauthorized.php' page
    exit();
}

// --- Fetch User Data ---
$users = [];
$sql = "SELECT id, username, email, role FROM users"; // Do NOT select 'PASSWORD' directly for display
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    mysqli_free_result($result); // Free up the result set
} else {
    // Handle database query error
    error_log("Error fetching users: " . mysqli_error($conn));
    $error_message = "Could not retrieve user data.";
}

// Close the database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BloodConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@404,700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
            color: #fff;
            margin: 0;
            padding: 20px;
        }

        .dashboard-container {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 1200px;
            margin: 30px auto;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #fff;
            font-weight: 700;
        }

        .logout-form {
            text-align: right;
            margin-bottom: 20px;
        }

        .logout-form button {
            background-color: #ff4d4d;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: 700;
        }

        .logout-form button:hover {
            background-color: #e63939;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            overflow: hidden; /* Ensures rounded corners apply to table content */
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        th {
            background-color: rgba(255, 255, 255, 0.25);
            font-weight: 700;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            text-align: center;
        }

        .message.error {
            background-color: rgba(255, 0, 0, 0.4);
            color: #fff;
        }

        /* Basic Action Buttons (placeholders for future functionality) */
        .action-buttons button {
            background-color: #007bff; /* Blue for view/edit */
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            margin-right: 5px;
            transition: background-color 0.2s ease;
        }
        .action-buttons button:hover {
            background-color: #0056b3;
        }
        .action-buttons .delete-btn {
            background-color: #dc3545; /* Red for delete */
        }
        .action-buttons .delete-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="logout-form">
            <!-- Logout form to clear the session -->
            <form action="logout.php" method="post">
                <button type="submit">Logout</button>
            </form>
        </div>

        <h1>Admin Dashboard</h1>

        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th> <!-- Placeholder for future actions -->
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td class="action-buttons">
                                    <!-- These buttons would link to edit_user.php, delete_user.php etc. -->
                                    <button onclick="alert('View user <?php echo $user['username']; ?>')">View</button>
                                    <button onclick="alert('Edit user <?php echo $user['username']; ?>')">Edit</button>
                                    <button class="delete-btn" onclick="alert('Delete user <?php echo $user['username']; ?>')">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
```

---

**You'll also need a `logout.php` file:**

Create a new file named `logout.php` in the same directory:

**`logout.php`**

```php
<?php
session_start(); // Start the session

// Unset all of the session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to the login page after logging out
header('Location: admin/index.php'); // Adjust this to your actual login page
exit();
?>
```
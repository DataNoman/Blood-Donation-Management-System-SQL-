<?php
// Start session for CSRF protection
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include database connection
include 'config/db_connection.php';

$message_sent = false;

// Rate limiting check
function check_rate_limit() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $current_time = time();
    
    if (!isset($_SESSION['last_submission'])) {
        $_SESSION['last_submission'] = [];
    }
    
    if (isset($_SESSION['last_submission'][$ip])) {
        if (($current_time - $_SESSION['last_submission'][$ip]) < 60) {
            return false;
        }
    }
    
    $_SESSION['last_submission'][$ip] = $current_time;
    return true;
}

// Handle Contact Form
if (isset($_POST['submit_contact'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log('CSRF token mismatch');
    } elseif (!check_rate_limit()) {
        error_log('Rate limit exceeded');
    } else {
        // Use prepared statement
        $stmt = $conn->prepare("INSERT INTO contact_messages(name, email, message) VALUES(?, ?, ?)");
        $name = $_POST['name'];
        $email = $_POST['email'];
        $message = $_POST['message'];
        
        $stmt->bind_param("sss", $name, $email, $message);

        if ($stmt->execute()) {
            $message_sent = true;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Regenerate token
        } else {
            error_log('Query error: ' . $stmt->error);
        }
        $stmt->close();
    }
}

// Fetch donor counts for each blood type
$sql_counts = "SELECT bt.type, COUNT(d.id) AS donor_count 
              FROM blood_types bt 
              LEFT JOIN donors d ON bt.id = d.blood_type_id 
              GROUP BY bt.type 
              ORDER BY bt.id";
$result_counts = mysqli_query($conn, $sql_counts);
$blood_counts = mysqli_fetch_all($result_counts, MYSQLI_ASSOC);

$formatted_counts = [];
foreach ($blood_counts as $count) {
    $formatted_counts[$count['type']] = $count['donor_count'];
}
mysqli_free_result($result_counts);

// Fetch the latest open emergency request
$sql_emergency = "SELECT 
                     bt.type AS blood_type,
                     r.hospital,
                     r.phone,           
                     req.request_date
                   FROM requests req
                   JOIN recipients r ON req.recipient_id = r.id
                   JOIN blood_types bt ON req.blood_type_id = bt.id
                   WHERE req.status = 'Open' and r.urgency_level = 'High'
                   ORDER BY req.request_date DESC
                   LIMIT 1";
$result_emergency = mysqli_query($conn, $sql_emergency);
$emergency_request = mysqli_fetch_assoc($result_emergency);
mysqli_free_result($result_emergency);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BloodConnect - Save Lives Together</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-heartbeat"></i>
                <span>BloodConnect</span>
            </div>
            <ul class="nav-menu">
                <li><a href="#home" class="nav-link">Home</a></li>
                <li><a href="#about" class="nav-link">About</a></li>
                <li><a href="donors/index.php" class="nav-link">Donate</a></li>
                <li><a href="receiver/index.php" class="nav-link">Request</a></li>
                <li><a href="#contact" class="nav-link">Contact</a></li>
                <li><a href="admin/index.php" class="nav-link login-btn" id="loginBtn">Login</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <section id="home" class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Every Drop Counts, Every Life Matters</h1>
                <p>Join us to create Bangladesh's largest blood donation network. Connect donors with those in need and save lives in your community.</p>
                <div class="hero-buttons">
                    <a href="donors/register.php" class="btn btn-primary">Donate Blood</a>
                    <a href="receiver/register.php" class="btn btn-secondary">Request Blood</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="blood-drop-animation">
                    <i class="fas fa-tint"></i>
                </div>
            </div>
        </div>
    </section>
        
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3>5000+</h3>
                    <p>Registered Donors</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-heartbeat"></i>
                    <h3>50+</h3>
                    <p>Lives Saved</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-hospital"></i>
                    <h3>200+</h3>
                    <p>Partner Hospitals</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>64</h3>
                    <p>Districts Covered</p>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="features">
        <div class="container">
            <h2>Why Choose BloodConnect?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-bell"></i>
                    <h3>Real-time Alerts</h3>
                    <p>Get notified immediately when someone needs your blood type.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Verified Donors</h3>
                    <p>All donors are verified for safety and authenticity.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>Mobile Friendly</h3>
                    <p>Access from anywhere, anytime on any device.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="blood-groups">
        <div class="container">
            <h2>Find Donors by Blood Group</h2>
            <div class="blood-groups-grid">
                <?php 
                $blood_groups_order = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                foreach ($blood_groups_order as $group): 
                    $count = isset($formatted_counts[$group]) ? $formatted_counts[$group] : 0;
                ?>
                <div class="blood-group-card" data-group="<?php echo htmlspecialchars($group); ?>">
                    <div class="blood-icon"><?php echo htmlspecialchars($group); ?></div>
                    <p>Available: <span class="count"><?php echo number_format($count); ?></span></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="emergency-section">
        <div class="container">
            <div class="emergency-card">
                <h2><i class="fas fa-exclamation-triangle"></i> Emergency Blood Needed</h2>
                <?php if ($emergency_request): ?>
                    <div class="emergency-details">
                        <div class="emergency-info">
                            <h3><?php echo htmlspecialchars($emergency_request['blood_type']); ?> Blood Needed Urgently</h3>
                            
                            <p><i class="fas fa-hospital"></i> Hospital: <?php echo htmlspecialchars($emergency_request['hospital']); ?></p>
                            
                            <?php if (!empty($emergency_request['phone'])): ?>
                                <p><i class="fas fa-phone-volume"></i> Contact: 
                                    <a href="tel:<?php echo htmlspecialchars($emergency_request['phone']); ?>" style="font-weight: bold; color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($emergency_request['phone']); ?>
                                    </a>
                                </p>
                            <?php else: ?>
                                <p><i class="fas fa-map-marker-alt"></i> Location: (Contact phone not provided)</p>
                            <?php endif; ?>
                            
                            <p><i class="fas fa-clock"></i> Requested on: <?php echo date('M j, Y', strtotime($emergency_request['request_date'])); ?></p>
                        </div>
                        <a href="donors/register.php" class="btn btn-emergency">Help Now</a>
                    </div>
                <?php else: ?>
                    <div class="emergency-details">
                        <div class="emergency-info">
                            <h3>No Active Emergency Requests</h3>
                            <p>Currently, there are no urgent requests for blood. Thank you to all our donors for your support!</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="contact" class="contact">
        <div class="container">
            <h2>Get in Touch</h2>
            <?php if ($message_sent): ?>
                <div style="padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; color: #3c763d; background-color: #dff0d8; border-color: #d6e9c6; text-align: center;">
                    <p>Thank you for your message! We will get back to you shortly.</p>
                </div>
            <?php endif; ?>

            <div class="contact-grid">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h3>Call Us</h3>
                            <p>+880 1700-000000</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h3>Email</h3>
                            <p>help@bloodconnect.bd</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h3>Address</h3>
                            <p>Bashundhara, Dhaka, Bangladesh</p>
                        </div>
                    </div>
                </div>
                <form class="contact-form" action="index.php#contact" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="text" name="name" placeholder="Your Name" required>
                    <input type="email" name="email" placeholder="Your Email" required>
                    <textarea name="message" placeholder="Your Message" rows="5" required></textarea>
                    <button type="submit" name="submit_contact" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-heartbeat"></i>
                        <span>BloodConnect</span>
                    </div>
                    <p>Connecting hearts, saving lives across Bangladesh.</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/noman0002"><i class="fab fa-facebook"></i></a>
                        <a href="mailto:malam2330151@bsds.uiu.ac.bd"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.linkedin.com/in/nomanalam"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.linkedin.com/in/nomanalam"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="donors/index.php">Donate</a></li>
                        <li><a href="receiver/index.php">Request</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="#contact">Help Center</a></li>
                        <li><a href="#contact">Privacy Policy</a></li>
                        <li><a href="#contact">Terms of Service</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Emergency Hotline</h3>
                    <p class="emergency-number">+880 1700-BLOOD</p>
                    <p>24/7 Emergency Blood Support</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> BloodConnect. All rights reserved. Made with ❤️ for Bangladesh</p>
            </div>
        </div>
    </footer>
    <script src="script.js"></script>
</body>
</html>
https://datanoman.github.io/about.html
<?php
mysqli_close($conn);
?>

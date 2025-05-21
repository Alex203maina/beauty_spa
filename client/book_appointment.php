<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure only clients can access this page
requireRole('client');

$userId = $_SESSION['user_id'];
$userData = getUserData($userId);

// Get available services
$conn = getDBConnection();
$services = [];
$salonists = [];

if ($conn) {
    // Get active services
    $sql = "SELECT * FROM services WHERE status = 'active' ORDER BY category, name";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }

    // Get available salonists
    $sql = "SELECT id, name FROM users WHERE role = 'salonist' ORDER BY name";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $salonists[] = $row;
    }

    closeDBConnection($conn);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $serviceId = $_POST['service_id'] ?? '';
    $salonistId = $_POST['salonist_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    if (empty($serviceId) || empty($salonistId) || empty($date) || empty($time)) {
        $_SESSION['error'] = "All fields are required.";
    } else {
        // Check if the time slot is available
        $conn = getDBConnection();
        if ($conn) {
            $sql = "SELECT COUNT(*) as count FROM appointments 
                   WHERE salonist_id = ? AND date = ? AND time = ? 
                   AND status != 'declined'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $salonistId, $date, $time);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['count'] > 0) {
                $_SESSION['error'] = "This time slot is already booked. Please choose another time.";
            } else {
                // Insert the appointment
                $sql = "INSERT INTO appointments (client_id, salonist_id, service_id, date, time) 
                       VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiiss", $userId, $salonistId, $serviceId, $date, $time);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Appointment booked successfully!";
                    header("Location: booking_history.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to book appointment.";
                }
            }
            closeDBConnection($conn);
        }
    }
}

// Generate time slots (9 AM to 5 PM, 30-minute intervals)
$timeSlots = [];
$startTime = strtotime('09:00');
$endTime = strtotime('17:00');
$interval = 30 * 60; // 30 minutes in seconds

for ($time = $startTime; $time <= $endTime; $time += $interval) {
    $timeSlots[] = date('H:i', $time);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Spa & Beauty System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <h1>Spa & Beauty System</h1>
            </div>
            <ul class="nav-links">
                <li><a href="/index.php">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="booking_history.php">Booking History</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <section class="booking-section">
                <h2>Book an Appointment</h2>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="booking-form">
                    <div class="form-group">
                        <label for="service">Select Service:</label>
                        <select id="service" name="service_id" required>
                            <option value="">Choose a service...</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>">
                                    <?php echo htmlspecialchars($service['name']); ?> 
                                    (<?php echo htmlspecialchars($service['category']); ?>) - 
                                    $<?php echo number_format($service['price'], 2); ?> 
                                    (<?php echo $service['duration']; ?> min)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="salonist">Select Salonist:</label>
                        <select id="salonist" name="salonist_id" required>
                            <option value="">Choose a salonist...</option>
                            <?php foreach ($salonists as $salonist): ?>
                                <option value="<?php echo $salonist['id']; ?>">
                                    <?php echo htmlspecialchars($salonist['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="date">Select Date:</label>
                        <input type="date" id="date" name="date" required 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="time">Select Time:</label>
                        <select id="time" name="time" required>
                            <option value="">Choose a time...</option>
                            <?php foreach ($timeSlots as $time): ?>
                                <option value="<?php echo $time; ?>">
                                    <?php echo date('g:i A', strtotime($time)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="book_appointment" class="btn btn-primary">Book Appointment</button>
                </form>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Spa & Beauty System. All rights reserved.</p>
    </footer>

    <script>
        // Add JavaScript for dynamic time slot checking
        document.getElementById('date').addEventListener('change', function() {
            const date = this.value;
            const salonistId = document.getElementById('salonist').value;
            
            if (date && salonistId) {
                // Here you could add AJAX call to check available time slots
                // for the selected date and salonist
            }
        });

        document.getElementById('salonist').addEventListener('change', function() {
            const date = document.getElementById('date').value;
            const salonistId = this.value;
            
            if (date && salonistId) {
                // Here you could add AJAX call to check available time slots
                // for the selected date and salonist
            }
        });
    </script>
</body>
</html> 
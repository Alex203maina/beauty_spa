<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure only clients can access this page
requireRole('client');

$userId = $_SESSION['user_id'];
$userData = getUserData($userId);

// Get recent bookings
$conn = getDBConnection();
$recentBookings = [];
if ($conn) {
    $sql = "SELECT a.*, s.name as service_name, u.name as salonist_name, s.price 
            FROM appointments a 
            JOIN services s ON a.service_id = s.id 
            JOIN users u ON a.salonist_id = u.id 
            WHERE a.client_id = ? 
            ORDER BY a.date DESC, a.time DESC 
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($booking = $result->fetch_assoc()) {
        $recentBookings[] = $booking;
    }
    closeDBConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Spa & Beauty System</title>
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
                <li><a href="book_appointment.php">Book Appointment</a></li>
                <li><a href="booking_history.php">Booking History</a></li>
                <li><a href="download_receipt.php">Receipts</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <section class="welcome-section">
                <h2>Welcome, <?php echo htmlspecialchars($userData['name']); ?>!</h2>
                <p>Manage your appointments and view your booking history.</p>
            </section>

            <section class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="book_appointment.php" class="btn btn-primary">Book New Appointment</a>
                    <a href="booking_history.php" class="btn btn-secondary">View All Bookings</a>
                </div>
            </section>

            <section class="recent-bookings">
                <h3>Recent Bookings</h3>
                <?php if (empty($recentBookings)): ?>
                    <p class="no-data">No recent bookings found.</p>
                <?php else: ?>
                    <div class="bookings-grid">
                        <?php foreach ($recentBookings as $booking): ?>
                            <div class="booking-card">
                                <h4><?php echo htmlspecialchars($booking['service_name']); ?></h4>
                                <p class="date"><?php echo date('F j, Y', strtotime($booking['date'])); ?></p>
                                <p class="time"><?php echo date('g:i A', strtotime($booking['time'])); ?></p>
                                <p class="salonist">With: <?php echo htmlspecialchars($booking['salonist_name']); ?></p>
                                <p class="price">$<?php echo number_format($booking['price'], 2); ?></p>
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                                <?php if ($booking['status'] === 'completed'): ?>
                                    <a href="receipts.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-secondary">View Receipt</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Spa & Beauty System. All rights reserved.</p>
    </footer>
</body>
</html> 
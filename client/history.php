<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// Get user data
$userId = $_SESSION['user_id'];
$userData = getUserData($userId);

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $bookingId = $_POST['booking_id'] ?? null;
    if ($bookingId) {
        $conn = getDBConnection();
        if ($conn) {
            // Verify the booking belongs to the user
            $sql = "SELECT id FROM appointments WHERE id = ? AND client_id = ? AND status = 'pending'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $bookingId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update booking status to declined
                $sql = "UPDATE appointments SET status = 'declined' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $bookingId);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Appointment cancelled successfully.";
                } else {
                    $_SESSION['error'] = "Failed to cancel appointment.";
                }
            } else {
                $_SESSION['error'] = "Invalid appointment or cannot be cancelled.";
            }
            closeDBConnection($conn);
        }
    }
    header("Location: history.php");
    exit();
}

// Get booking history
$conn = getDBConnection();
$bookings = [];

if ($conn) {
    $sql = "SELECT a.*, s.name as service_name, s.price, u.name as salonist_name
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            JOIN users u ON a.salonist_id = u.id
            WHERE a.client_id = ?
            ORDER BY a.date DESC, a.time DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($booking = $result->fetch_assoc()) {
        $bookings[] = $booking;
    }
    closeDBConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History - Spa & Beauty System</title>
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
                <li><a href="book_appointment.php">Book Appointment</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <section class="history-section">
                <h2>Booking History</h2>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="success-message">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($bookings)): ?>
                    <p class="no-data">No booking history found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="bookings-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Salonist</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($booking['date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($booking['time'])); ?></td>
                                        <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['salonist_name']); ?></td>
                                        <td>$<?php echo number_format($booking['price'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <?php if ($booking['status'] === 'completed'): ?>
                                                <a href="download_receipt.php?id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-primary btn-sm">Download Receipt</a>
                                            <?php endif; ?>
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <form method="POST" class="inline-form" 
                                                      onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <button type="submit" name="cancel_booking" class="btn btn-danger btn-sm">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
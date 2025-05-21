<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure only clients can access this page
requireRole('client');

$userId = $_SESSION['user_id'];

// Get all bookings
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

// Filter bookings by status if requested
$statusFilter = $_GET['status'] ?? 'all';
if ($statusFilter !== 'all') {
    $bookings = array_filter($bookings, function($booking) use ($statusFilter) {
        return $booking['status'] === $statusFilter;
    });
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
                
                <div class="filter-controls">
                    <label for="status-filter">Filter by Status:</label>
                    <select id="status-filter" onchange="window.location.href='?status=' + this.value">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Bookings</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="accepted" <?php echo $statusFilter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="declined" <?php echo $statusFilter === 'declined' ? 'selected' : ''; ?>>Declined</option>
                    </select>
                </div>

                <?php if (empty($bookings)): ?>
                    <p class="no-data">No bookings found.</p>
                <?php else: ?>
                    <div class="bookings-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Salonist</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                        <td><?php echo date('F j, Y', strtotime($booking['date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($booking['time'])); ?></td>
                                        <td><?php echo htmlspecialchars($booking['salonist_name']); ?></td>
                                        <td>$<?php echo number_format($booking['price'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($booking['status'] === 'completed'): ?>
                                                <a href="receipts.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-secondary btn-sm">View Receipt</a>
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
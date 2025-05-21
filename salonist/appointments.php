<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure user is logged in and is a salonist
if (!isLoggedIn() || !hasRole('salonist')) {
    header('Location: ../auth/login.php');
    exit();
}

// Get user data
$userId = $_SESSION['user_id'];
$userData = getUserData($userId);

// Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appointmentId = $_POST['appointment_id'] ?? null;
    $newStatus = $_POST['status'] ?? null;
    
    if ($appointmentId && $newStatus) {
        $conn = getDBConnection();
        if ($conn) {
            // Verify the appointment belongs to this salonist
            $sql = "SELECT id FROM appointments WHERE id = ? AND salonist_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $appointmentId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update appointment status
                $sql = "UPDATE appointments SET status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $newStatus, $appointmentId);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Appointment status updated successfully.";
                } else {
                    $_SESSION['error'] = "Failed to update appointment status.";
                }
            } else {
                $_SESSION['error'] = "Invalid appointment or unauthorized access.";
            }
            closeDBConnection($conn);
        }
    }
    header("Location: appointments.php");
    exit();
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$dateFilter = $_GET['date'] ?? date('Y-m-d');
$dateRange = $_GET['range'] ?? 'day';

// Calculate date range based on selected option
$startDate = $dateFilter;
$endDate = $dateFilter;

switch ($dateRange) {
    case 'week':
        // Get the start of the week (Monday)
        $startDate = date('Y-m-d', strtotime('monday this week', strtotime($dateFilter)));
        // Get the end of the week (Sunday)
        $endDate = date('Y-m-d', strtotime('sunday this week', strtotime($dateFilter)));
        break;
    case 'month':
        // Get the start of the month
        $startDate = date('Y-m-01', strtotime($dateFilter));
        // Get the end of the month
        $endDate = date('Y-m-t', strtotime($dateFilter));
        break;
}

// Get appointments
$conn = getDBConnection();
$appointments = [];

if ($conn) {
    $sql = "SELECT a.*, s.name as service_name, s.price, s.duration,
            c.name as client_name, c.email as client_email, c.phone as client_phone
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            JOIN users c ON a.client_id = c.id
            WHERE a.salonist_id = ?";
    
    $params = [$userId];
    $types = "i";
    
    if ($statusFilter !== 'all') {
        $sql .= " AND a.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }
    
    // Update date filter to use range
    $sql .= " AND a.date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
    
    $sql .= " ORDER BY a.date ASC, a.time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($appointment = $result->fetch_assoc()) {
        $appointments[] = $appointment;
    }
    closeDBConnection($conn);
}

// Get date range display text
$dateRangeText = '';
switch ($dateRange) {
    case 'day':
        $dateRangeText = date('F d, Y', strtotime($dateFilter));
        break;
    case 'week':
        $dateRangeText = date('F d', strtotime($startDate)) . ' - ' . date('F d, Y', strtotime($endDate));
        break;
    case 'month':
        $dateRangeText = date('F Y', strtotime($dateFilter));
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Spa & Beauty System</title>
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
                <li><a href="schedule.php">Schedule</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <section class="appointments-section">
                <h2>My Appointments</h2>

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

                <div class="filter-controls">
                    <form method="GET" class="filter-form">
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status" onchange="this.form.submit()">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="accepted" <?php echo $statusFilter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="declined" <?php echo $statusFilter === 'declined' ? 'selected' : ''; ?>>Declined</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="range">Date Range:</label>
                            <select id="range" name="range" onchange="this.form.submit()">
                                <option value="day" <?php echo $dateRange === 'day' ? 'selected' : ''; ?>>Day</option>
                                <option value="week" <?php echo $dateRange === 'week' ? 'selected' : ''; ?>>Week</option>
                                <option value="month" <?php echo $dateRange === 'month' ? 'selected' : ''; ?>>Month</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="date">Date:</label>
                            <input type="date" id="date" name="date" 
                                   value="<?php echo $dateFilter; ?>" 
                                   onchange="this.form.submit()">
                        </div>
                    </form>
                </div>

                <div class="date-range-display">
                    <h3>Showing appointments for: <?php echo $dateRangeText; ?></h3>
                </div>

                <?php if (empty($appointments)): ?>
                    <p class="no-data">No appointments found for the selected period.</p>
                <?php else: ?>
                    <div class="appointments-grid">
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="appointment-card">
                                <h4><?php echo htmlspecialchars($appointment['service_name']); ?></h4>
                                <p class="date">Date: <?php echo date('F d, Y', strtotime($appointment['date'])); ?></p>
                                <p class="time">Time: <?php echo date('g:i A', strtotime($appointment['time'])); ?></p>
                                <p class="duration">Duration: <?php echo $appointment['duration']; ?> minutes</p>
                                <p class="client">Client: <?php echo htmlspecialchars($appointment['client_name']); ?></p>
                                <p class="contact">Contact: <?php echo htmlspecialchars($appointment['client_email']); ?></p>
                                <?php if ($appointment['client_phone']): ?>
                                    <p class="phone">Phone: <?php echo htmlspecialchars($appointment['client_phone']); ?></p>
                                <?php endif; ?>
                                <p class="price">Price: $<?php echo number_format($appointment['price'], 2); ?></p>
                                <p class="status">
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </p>
                                
                                <?php if ($appointment['status'] === 'pending'): ?>
                                    <div class="appointment-actions">
                                        <form method="POST" class="inline-form">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="status" value="accepted">
                                            <button type="submit" name="update_status" class="btn btn-success btn-sm">Accept</button>
                                        </form>
                                        <form method="POST" class="inline-form">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="status" value="declined">
                                            <button type="submit" name="update_status" class="btn btn-danger btn-sm">Decline</button>
                                        </form>
                                    </div>
                                <?php elseif ($appointment['status'] === 'accepted'): ?>
                                    <div class="appointment-actions">
                                        <form method="POST" class="inline-form">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm">Mark as Completed</button>
                                        </form>
                                    </div>
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
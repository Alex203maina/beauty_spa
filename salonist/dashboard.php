<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure only salonists can access this page
requireRole('salonist');

$userId = $_SESSION['user_id'];
$userData = getUserData($userId);

// Get today's appointments
$conn = getDBConnection();
$todayAppointments = [];
$upcomingAppointments = [];

if ($conn) {
    // Get today's appointments
    $sql = "SELECT a.*, s.name as service_name, s.price, u.name as client_name 
            FROM appointments a 
            JOIN services s ON a.service_id = s.id 
            JOIN users u ON a.client_id = u.id 
            WHERE a.salonist_id = ? AND a.date = CURDATE() AND a.status != 'declined'
            ORDER BY a.time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($appointment = $result->fetch_assoc()) {
        $todayAppointments[] = $appointment;
    }

    // Get upcoming appointments (next 7 days)
    $sql = "SELECT a.*, s.name as service_name, s.price, u.name as client_name 
            FROM appointments a 
            JOIN services s ON a.service_id = s.id 
            JOIN users u ON a.client_id = u.id 
            WHERE a.salonist_id = ? 
            AND a.date > CURDATE() 
            AND a.date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND a.status != 'declined'
            ORDER BY a.date ASC, a.time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($appointment = $result->fetch_assoc()) {
        $upcomingAppointments[] = $appointment;
    }
    closeDBConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salonist Dashboard - Spa & Beauty System</title>
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
                <li><a href="appointments.php">All Appointments</a></li>
                <li><a href="schedule.php">Schedule</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <section class="welcome-section">
                <h2>Welcome, <?php echo htmlspecialchars($userData['name']); ?>!</h2>
                <p>Manage your appointments and view your schedule.</p>
            </section>

            <section class="today-appointments">
                <h3>Today's Appointments</h3>
                <?php if (empty($todayAppointments)): ?>
                    <p class="no-data">No appointments scheduled for today.</p>
                <?php else: ?>
                    <div class="appointments-grid">
                        <?php foreach ($todayAppointments as $appointment): ?>
                            <div class="appointment-card">
                                <h4><?php echo htmlspecialchars($appointment['service_name']); ?></h4>
                                <p class="time"><?php echo date('g:i A', strtotime($appointment['time'])); ?></p>
                                <p class="client">Client: <?php echo htmlspecialchars($appointment['client_name']); ?></p>
                                <p class="price">$<?php echo number_format($appointment['price'], 2); ?></p>
                                <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                                <div class="appointment-actions">
                                    <?php if ($appointment['status'] === 'pending'): ?>
                                        <form method="POST" action="update_appointment.php" class="inline-form">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="btn btn-success btn-sm">Accept</button>
                                        </form>
                                        <form method="POST" action="update_appointment.php" class="inline-form">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="action" value="decline">
                                            <button type="submit" class="btn btn-danger btn-sm">Decline</button>
                                        </form>
                                    <?php elseif ($appointment['status'] === 'accepted'): ?>
                                        <form method="POST" action="update_appointment.php" class="inline-form">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="btn btn-primary btn-sm">Mark as Completed</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="upcoming-appointments">
                <h3>Upcoming Appointments (Next 7 Days)</h3>
                <?php if (empty($upcomingAppointments)): ?>
                    <p class="no-data">No upcoming appointments scheduled.</p>
                <?php else: ?>
                    <div class="appointments-grid">
                        <?php foreach ($upcomingAppointments as $appointment): ?>
                            <div class="appointment-card">
                                <h4><?php echo htmlspecialchars($appointment['service_name']); ?></h4>
                                <p class="date"><?php echo date('F j, Y', strtotime($appointment['date'])); ?></p>
                                <p class="time"><?php echo date('g:i A', strtotime($appointment['time'])); ?></p>
                                <p class="client">Client: <?php echo htmlspecialchars($appointment['client_name']); ?></p>
                                <p class="price">$<?php echo number_format($appointment['price'], 2); ?></p>
                                <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
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
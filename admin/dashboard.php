<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure only admins can access this page
requireRole('admin');

$userId = $_SESSION['user_id'];
$userData = getUserData($userId);

// Get system statistics
$conn = getDBConnection();
$stats = [
    'total_clients' => 0,
    'total_salonists' => 0,
    'total_services' => 0,
    'total_appointments' => 0,
    'pending_appointments' => 0,
    'today_appointments' => 0,
    'monthly_revenue' => 0
];

if ($conn) {
    // Get total clients
    $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'client'";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $stats['total_clients'] = $row['count'];
    }

    // Get total salonists
    $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'salonist'";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $stats['total_salonists'] = $row['count'];
    }

    // Get total services
    $sql = "SELECT COUNT(*) as count FROM services";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $stats['total_services'] = $row['count'];
    }

    // Get total appointments
    $sql = "SELECT COUNT(*) as count FROM appointments";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $stats['total_appointments'] = $row['count'];
    }

    // Get pending appointments
    $sql = "SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $stats['pending_appointments'] = $row['count'];
    }

    // Get today's appointments
    $sql = "SELECT COUNT(*) as count FROM appointments WHERE date = CURDATE()";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $stats['today_appointments'] = $row['count'];
    }

    // Get monthly revenue
    $sql = "SELECT SUM(s.price) as total 
            FROM appointments a 
            JOIN services s ON a.service_id = s.id 
            WHERE a.status = 'completed' 
            AND MONTH(a.date) = MONTH(CURDATE()) 
            AND YEAR(a.date) = YEAR(CURDATE())";
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        $stats['monthly_revenue'] = $row['total'] ?? 0;
    }

    closeDBConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Spa & Beauty System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <h1>Spa & Beauty System</h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="all_appointments.php">Appointments</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <section class="welcome-section">
                <h2>Welcome, <?php echo htmlspecialchars($userData['name']); ?>!</h2>
                <p>Manage your spa and beauty system.</p>
            </section>

            <section class="stats-grid">
                <div class="stat-card">
                    <h3>Total Clients</h3>
                    <p class="stat-number"><?php echo $stats['total_clients']; ?></p>
                    <a href="users.php?role=client" class="btn btn-secondary btn-sm">View Clients</a>
                </div>

                <div class="stat-card">
                    <h3>Total Salonists</h3>
                    <p class="stat-number"><?php echo $stats['total_salonists']; ?></p>
                    <a href="users.php?role=salonist" class="btn btn-secondary btn-sm">View Salonists</a>
                </div>

                <div class="stat-card">
                    <h3>Total Services</h3>
                    <p class="stat-number"><?php echo $stats['total_services']; ?></p>
                    <a href="services.php" class="btn btn-secondary btn-sm">Manage Services</a>
                </div>

                <div class="stat-card">
                    <h3>Total Appointments</h3>
                    <p class="stat-number"><?php echo $stats['total_appointments']; ?></p>
                    <a href="appointments.php" class="btn btn-secondary btn-sm">View All</a>
                </div>

                <div class="stat-card">
                    <h3>Pending Appointments</h3>
                    <p class="stat-number"><?php echo $stats['pending_appointments']; ?></p>
                    <a href="appointments.php?status=pending" class="btn btn-secondary btn-sm">View Pending</a>
                </div>

                <div class="stat-card">
                    <h3>Today's Appointments</h3>
                    <p class="stat-number"><?php echo $stats['today_appointments']; ?></p>
                    <a href="all_appointments.php?date=<?php echo date('Y-m-d'); ?>" class="btn btn-secondary btn-sm">View Today</a>
                </div>

                <div class="stat-card">
                    <h3>Monthly Revenue</h3>
                    <p class="stat-number">$<?php echo number_format($stats['monthly_revenue'], 2); ?></p>
                    <a href="reports.php" class="btn btn-secondary btn-sm">View Reports</a>
                </div>
            </section>

            <section class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="users.php?action=add" class="btn btn-primary">Add New User</a>
                    <a href="services.php?action=add" class="btn btn-primary">Add New Service</a>
                    <a href="appointments.php" class="btn btn-primary">Manage Appointments</a>
                    <a href="reports.php" class="btn btn-primary">Generate Reports</a>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Spa & Beauty System. All rights reserved.</p>
    </footer>
</body>
</html> 
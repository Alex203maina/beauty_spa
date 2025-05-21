<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../auth/login.php');
    exit();
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$dateFilter = $_GET['date'] ?? date('Y-m-d');
$dateRange = $_GET['range'] ?? 'day';
$salonistFilter = $_GET['salonist'] ?? 'all';

// Calculate date range based on selected option
$startDate = $dateFilter;
$endDate = $dateFilter;

switch ($dateRange) {
    case 'week':
        $startDate = date('Y-m-d', strtotime('monday this week', strtotime($dateFilter)));
        $endDate = date('Y-m-d', strtotime('sunday this week', strtotime($dateFilter)));
        break;
    case 'month':
        $startDate = date('Y-m-01', strtotime($dateFilter));
        $endDate = date('Y-m-t', strtotime($dateFilter));
        break;
}

// Get all salonists for filter
$conn = getDBConnection();
$salonists = [];
if ($conn) {
    $sql = "SELECT id, name FROM users WHERE role = 'salonist' ORDER BY name";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $salonists[] = $row;
    }
}

// Get appointments
$appointments = [];
if ($conn) {
    $sql = "SELECT a.*, s.name as service_name, s.price, s.duration,
            c.name as client_name, c.email as client_email, c.phone as client_phone,
            st.name as salonist_name
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            JOIN users c ON a.client_id = c.id
            JOIN users st ON a.salonist_id = st.id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($statusFilter !== 'all') {
        $sql .= " AND a.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }
    
    if ($salonistFilter !== 'all') {
        $sql .= " AND a.salonist_id = ?";
        $params[] = $salonistFilter;
        $types .= "i";
    }
    
    $sql .= " AND a.date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
    
    $sql .= " ORDER BY a.date ASC, a.time ASC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
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

// Calculate summary statistics
$totalAppointments = count($appointments);
$totalRevenue = array_sum(array_column($appointments, 'price'));
$completedAppointments = count(array_filter($appointments, function($a) { return $a['status'] === 'completed'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Appointments - Admin Panel</title>
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
                <li><a href="services.php">Services</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <section class="appointments-section">
                <h2>All Appointments</h2>

                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>Total Appointments</h3>
                        <p class="number"><?php echo $totalAppointments; ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Total Revenue</h3>
                        <p class="number">$<?php echo number_format($totalRevenue, 2); ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Completed</h3>
                        <p class="number"><?php echo $completedAppointments; ?></p>
                    </div>
                </div>

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
                            <label for="salonist">Salonist:</label>
                            <select id="salonist" name="salonist" onchange="this.form.submit()">
                                <option value="all" <?php echo $salonistFilter === 'all' ? 'selected' : ''; ?>>All Salonists</option>
                                <?php foreach ($salonists as $salonist): ?>
                                    <option value="<?php echo $salonist['id']; ?>" 
                                            <?php echo $salonistFilter == $salonist['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($salonist['name']); ?>
                                    </option>
                                <?php endforeach; ?>
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
                    <p class="no-data">No appointments found for the selected filters.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="appointments-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Client</th>
                                    <th>Salonist</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($appointment['date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($appointment['time'])); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($appointment['client_name']); ?><br>
                                            <small><?php echo htmlspecialchars($appointment['client_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['salonist_name']); ?></td>
                                        <td>$<?php echo number_format($appointment['price'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                               class="btn btn-info btn-sm">View</a>
                                            <?php if ($appointment['status'] === 'pending'): ?>
                                                <form method="POST" action="update_appointment.php" class="inline-form">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="status" value="accepted">
                                                    <button type="submit" class="btn btn-success btn-sm">Accept</button>
                                                </form>
                                                <form method="POST" action="update_appointment.php" class="inline-form">
                                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                                    <input type="hidden" name="status" value="declined">
                                                    <button type="submit" class="btn btn-danger btn-sm">Decline</button>
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
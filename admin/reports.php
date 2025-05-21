<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure only admins can access this page
requireRole('admin');

$userId = $_SESSION['user_id'];
$userData = getUserData($userId);

// Get date range from request or default to current month
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$reportType = $_GET['type'] ?? 'revenue';

$conn = getDBConnection();
$reports = [];

if ($conn) {
    switch ($reportType) {
        case 'revenue':
            // Revenue report
            $sql = "SELECT 
                    DATE(a.date) as appointment_date,
                    COUNT(*) as total_appointments,
                    SUM(s.price) as total_revenue,
                    AVG(s.price) as average_revenue
                FROM appointments a
                JOIN services s ON a.service_id = s.id
                WHERE a.date BETWEEN ? AND ?
                AND a.status = 'completed'
                GROUP BY DATE(a.date)
                ORDER BY appointment_date";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }
            break;

        case 'services':
            // Service popularity report
            $sql = "SELECT 
                    s.name as service_name,
                    s.category,
                    COUNT(*) as total_bookings,
                    SUM(s.price) as total_revenue,
                    AVG(s.price) as average_price
                FROM appointments a
                JOIN services s ON a.service_id = s.id
                WHERE a.date BETWEEN ? AND ?
                GROUP BY s.id
                ORDER BY total_bookings DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }
            break;

        case 'salonists':
            // Salonist performance report
            $sql = "SELECT 
                    u.name as salonist_name,
                    COUNT(*) as total_appointments,
                    SUM(s.price) as total_revenue,
                    AVG(s.price) as average_revenue
                FROM appointments a
                JOIN users u ON a.salonist_id = u.id
                JOIN services s ON a.service_id = s.id
                WHERE a.date BETWEEN ? AND ?
                AND a.status = 'completed'
                GROUP BY u.id
                ORDER BY total_revenue DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }
            break;

        case 'appointments':
            // Appointment status report
            $sql = "SELECT 
                    DATE(a.date) as appointment_date,
                    a.status,
                    COUNT(*) as count
                FROM appointments a
                WHERE a.date BETWEEN ? AND ?
                GROUP BY DATE(a.date), a.status
                ORDER BY appointment_date, a.status";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }
            break;
    }

    closeDBConnection($conn);
}

// Calculate summary statistics
$summary = [
    'total_revenue' => 0,
    'total_appointments' => 0,
    'average_revenue' => 0
];

foreach ($reports as $report) {
    if (isset($report['total_revenue'])) {
        $summary['total_revenue'] += $report['total_revenue'];
    }
    if (isset($report['total_appointments'])) {
        $summary['total_appointments'] += $report['total_appointments'];
    }
}

if ($summary['total_appointments'] > 0) {
    $summary['average_revenue'] = $summary['total_revenue'] / $summary['total_appointments'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Spa & Beauty System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li><a href="users.php">Users</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <section class="reports-section">
                <h2>System Reports</h2>

                <div class="report-controls">
                    <form method="GET" class="report-form">
                        <div class="form-group">
                            <label for="type">Report Type:</label>
                            <select id="type" name="type" onchange="this.form.submit()">
                                <option value="revenue" <?php echo $reportType === 'revenue' ? 'selected' : ''; ?>>Revenue Report</option>
                                <option value="services" <?php echo $reportType === 'services' ? 'selected' : ''; ?>>Service Popularity</option>
                                <option value="salonists" <?php echo $reportType === 'salonists' ? 'selected' : ''; ?>>Salonist Performance</option>
                                <option value="appointments" <?php echo $reportType === 'appointments' ? 'selected' : ''; ?>>Appointment Status</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="start_date">Start Date:</label>
                            <input type="date" id="start_date" name="start_date" 
                                   value="<?php echo $startDate; ?>" onchange="this.form.submit()">
                        </div>

                        <div class="form-group">
                            <label for="end_date">End Date:</label>
                            <input type="date" id="end_date" name="end_date" 
                                   value="<?php echo $endDate; ?>" onchange="this.form.submit()">
                        </div>
                    </form>
                </div>

                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>Total Revenue</h3>
                        <p>$<?php echo number_format($summary['total_revenue'], 2); ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Total Appointments</h3>
                        <p><?php echo $summary['total_appointments']; ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Average Revenue</h3>
                        <p>$<?php echo number_format($summary['average_revenue'], 2); ?></p>
                    </div>
                </div>

                <div class="report-content">
                    <?php if ($reportType === 'revenue'): ?>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                        <div class="table-responsive">
                            <table class="reports-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Appointments</th>
                                        <th>Revenue</th>
                                        <th>Average</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($report['appointment_date'])); ?></td>
                                            <td><?php echo $report['total_appointments']; ?></td>
                                            <td>$<?php echo number_format($report['total_revenue'], 2); ?></td>
                                            <td>$<?php echo number_format($report['average_revenue'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php elseif ($reportType === 'services'): ?>
                        <div class="chart-container">
                            <canvas id="servicesChart"></canvas>
                        </div>
                        <div class="table-responsive">
                            <table class="reports-table">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Category</th>
                                        <th>Bookings</th>
                                        <th>Revenue</th>
                                        <th>Average Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($report['service_name']); ?></td>
                                            <td><?php echo htmlspecialchars($report['category']); ?></td>
                                            <td><?php echo $report['total_bookings']; ?></td>
                                            <td>$<?php echo number_format($report['total_revenue'], 2); ?></td>
                                            <td>$<?php echo number_format($report['average_price'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php elseif ($reportType === 'salonists'): ?>
                        <div class="chart-container">
                            <canvas id="salonistsChart"></canvas>
                        </div>
                        <div class="table-responsive">
                            <table class="reports-table">
                                <thead>
                                    <tr>
                                        <th>Salonist</th>
                                        <th>Appointments</th>
                                        <th>Revenue</th>
                                        <th>Average</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($report['salonist_name']); ?></td>
                                            <td><?php echo $report['total_appointments']; ?></td>
                                            <td>$<?php echo number_format($report['total_revenue'], 2); ?></td>
                                            <td>$<?php echo number_format($report['average_revenue'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php elseif ($reportType === 'appointments'): ?>
                        <div class="chart-container">
                            <canvas id="appointmentsChart"></canvas>
                        </div>
                        <div class="table-responsive">
                            <table class="reports-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($report['appointment_date'])); ?></td>
                                            <td><?php echo ucfirst($report['status']); ?></td>
                                            <td><?php echo $report['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Spa & Beauty System. All rights reserved.</p>
    </footer>

    <script>
        // Initialize charts based on report type
        <?php if ($reportType === 'revenue'): ?>
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($r) { 
                    return date('M d', strtotime($r['appointment_date'])); 
                }, $reports)); ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?php echo json_encode(array_map(function($r) { 
                        return $r['total_revenue']; 
                    }, $reports)); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php elseif ($reportType === 'services'): ?>
        new Chart(document.getElementById('servicesChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($r) { 
                    return $r['service_name']; 
                }, $reports)); ?>,
                datasets: [{
                    label: 'Total Bookings',
                    data: <?php echo json_encode(array_map(function($r) { 
                        return $r['total_bookings']; 
                    }, $reports)); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php elseif ($reportType === 'salonists'): ?>
        new Chart(document.getElementById('salonistsChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($r) { 
                    return $r['salonist_name']; 
                }, $reports)); ?>,
                datasets: [{
                    label: 'Total Revenue',
                    data: <?php echo json_encode(array_map(function($r) { 
                        return $r['total_revenue']; 
                    }, $reports)); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php elseif ($reportType === 'appointments'): ?>
        new Chart(document.getElementById('appointmentsChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_map(function($r) { 
                    return ucfirst($r['status']); 
                }, $reports)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_map(function($r) { 
                        return $r['count']; 
                    }, $reports)); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
        <?php endif; ?>
    </script>
</body>
</html> 
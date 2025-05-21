<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure only salonists can access this page
requireRole('salonist');

$userId = $_SESSION['user_id'];
$userData = getUserData($userId);

// Get the selected month and year from query parameters, default to current month
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Validate month and year
if ($month < 1 || $month > 12) {
    $month = (int)date('m');
}
if ($year < 2000 || $year > 2100) {
    $year = (int)date('Y');
}

// Get appointments for the selected month
$conn = getDBConnection();
$appointments = [];

if ($conn) {
    $sql = "SELECT a.*, s.name as service_name, s.price, u.name as client_name 
            FROM appointments a 
            JOIN services s ON a.service_id = s.id 
            JOIN users u ON a.client_id = u.id 
            WHERE a.salonist_id = ? 
            AND MONTH(a.date) = ? 
            AND YEAR(a.date) = ?
            AND a.status != 'declined'
            ORDER BY a.date ASC, a.time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $userId, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($appointment = $result->fetch_assoc()) {
        $appointments[$appointment['date']][] = $appointment;
    }
    closeDBConnection($conn);
}

// Generate calendar data
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$numberDays = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('w', $firstDayOfMonth);
$monthName = date('F', $firstDayOfMonth);

// Previous and next month links
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - Spa & Beauty System</title>
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
                <li><a href="appointments.php">All Appointments</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <section class="schedule-section">
                <h2>Schedule for <?php echo $monthName . ' ' . $year; ?></h2>
                
                <div class="calendar-navigation">
                    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-secondary">Previous Month</a>
                    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-secondary">Next Month</a>
                </div>

                <div class="calendar">
                    <div class="calendar-header">
                        <div>Sunday</div>
                        <div>Monday</div>
                        <div>Tuesday</div>
                        <div>Wednesday</div>
                        <div>Thursday</div>
                        <div>Friday</div>
                        <div>Saturday</div>
                    </div>
                    <div class="calendar-body">
                        <?php
                        // Add empty cells for days before the first day of the month
                        for ($i = 0; $i < $firstDayOfWeek; $i++) {
                            echo '<div class="calendar-day empty"></div>';
                        }

                        // Add cells for each day of the month
                        for ($day = 1; $day <= $numberDays; $day++) {
                            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $hasAppointments = isset($appointments[$date]);
                            $isToday = $date === date('Y-m-d');
                            
                            echo '<div class="calendar-day' . ($isToday ? ' today' : '') . '">';
                            echo '<div class="day-number">' . $day . '</div>';
                            
                            if ($hasAppointments) {
                                echo '<div class="appointments-list">';
                                foreach ($appointments[$date] as $appointment) {
                                    echo '<div class="appointment-item status-' . $appointment['status'] . '">';
                                    echo '<span class="time">' . date('g:i A', strtotime($appointment['time'])) . '</span>';
                                    echo '<span class="service">' . htmlspecialchars($appointment['service_name']) . '</span>';
                                    echo '</div>';
                                }
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Spa & Beauty System. All rights reserved.</p>
    </footer>
</body>
</html> 
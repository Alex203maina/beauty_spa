<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure user is logged in and is an admin
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../auth/login.php');
    exit();
}

// Get appointment ID
$appointmentId = $_GET['id'] ?? null;

if (!$appointmentId) {
    $_SESSION['error'] = "Invalid appointment ID.";
    header("Location: all_appointments.php");
    exit();
}

// Get appointment details
$conn = getDBConnection();
$appointment = null;

if ($conn) {
    $sql = "SELECT a.*, s.name as service_name, s.price, s.duration, s.description as service_description,
            c.name as client_name, c.email as client_email, c.phone as client_phone,
            st.name as salonist_name, st.email as salonist_email, st.phone as salonist_phone
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            JOIN users c ON a.client_id = c.id
            JOIN users st ON a.salonist_id = st.id
            WHERE a.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    closeDBConnection($conn);
}

if (!$appointment) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: all_appointments.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointment - Admin Panel</title>
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
                <li><a href="all_appointments.php">Appointments</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <section class="appointment-details">
                <div class="section-header">
                    <h2>Appointment Details</h2>
                    <a href="all_appointments.php" class="btn btn-secondary">Back to Appointments</a>
                </div>

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

                <div class="appointment-info">
                    <div class="info-section">
                        <h3>Service Information</h3>
                        <p><strong>Service:</strong> <?php echo htmlspecialchars($appointment['service_name']); ?></p>
                        <p><strong>Price:</strong> $<?php echo number_format($appointment['price'], 2); ?></p>
                        <p><strong>Duration:</strong> <?php echo $appointment['duration']; ?> minutes</p>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($appointment['service_description']); ?></p>
                    </div>

                    <div class="info-section">
                        <h3>Appointment Details</h3>
                        <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($appointment['date'])); ?></p>
                        <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment['time'])); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                <?php echo ucfirst($appointment['status']); ?>
                            </span>
                        </p>
                        <p><strong>Created At:</strong> <?php echo date('F d, Y g:i A', strtotime($appointment['created_at'])); ?></p>
                    </div>

                    <div class="info-section">
                        <h3>Client Information</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($appointment['client_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($appointment['client_email']); ?></p>
                        <?php if ($appointment['client_phone']): ?>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($appointment['client_phone']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="info-section">
                        <h3>Salonist Information</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($appointment['salonist_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($appointment['salonist_email']); ?></p>
                        <?php if ($appointment['salonist_phone']): ?>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($appointment['salonist_phone']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($appointment['status'] === 'pending'): ?>
                    <div class="appointment-actions">
                        <form method="POST" action="update_appointment.php" class="inline-form">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                            <input type="hidden" name="status" value="accepted">
                            <button type="submit" class="btn btn-success">Accept Appointment</button>
                        </form>
                        <form method="POST" action="update_appointment.php" class="inline-form">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                            <input type="hidden" name="status" value="declined">
                            <button type="submit" class="btn btn-danger">Decline Appointment</button>
                        </form>
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
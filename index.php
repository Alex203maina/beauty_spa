<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spa & Beauty System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <h1>Spa & Beauty System</h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin/dashboard.php">Admin Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] === 'client'): ?>
                        <li><a href="client/dashboard.php">Client Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] === 'salonist'): ?>
                        <li><a href="salonist/dashboard.php">Salonist Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="auth/login.php">Login</a></li>
                    <li><a href="auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h2>Welcome to Our Spa & Beauty System</h2>
            <p>Book your favorite services with ease and enjoy a relaxing experience.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="cta-buttons">
                    <a href="auth/register.php" class="btn btn-primary">Get Started</a>
                    <a href="auth/login.php" class="btn btn-secondary">Login</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="features">
            <h3>Our Services</h3>
            <div class="service-grid">
                <?php
                $conn = getDBConnection();
                if ($conn) {
                    $sql = "SELECT * FROM services LIMIT 6";
                    $result = $conn->query($sql);
                    
                    if ($result && $result->num_rows > 0) {
                        while ($service = $result->fetch_assoc()) {
                            echo '<div class="service-card">';
                            echo '<h4>' . htmlspecialchars($service['name']) . '</h4>';
                            echo '<p>' . htmlspecialchars($service['description']) . '</p>';
                            echo '<p class="price">$' . number_format($service['price'], 2) . '</p>';
                            if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client') {
                                echo '<a href="client/book_appointment.php?service_id=' . $service['id'] . '" class="btn btn-primary">Book Now</a>';
                            }
                            echo '</div>';
                        }
                    }
                    closeDBConnection($conn);
                }
                ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Spa & Beauty System. All rights reserved.</p>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html> 
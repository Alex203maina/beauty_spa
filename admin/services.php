<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure only admins can access this page
requireRole('admin');

$userId = $_SESSION['user_id'];
$userData = getUserData($userId);

// Get filter parameters
$categoryFilter = $_GET['category'] ?? 'all';
$priceFilter = $_GET['price'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$action = $_GET['action'] ?? 'list';

// Handle service deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service'])) {
    $deleteId = $_POST['service_id'] ?? null;
    if ($deleteId) {
        $conn = getDBConnection();
        if ($conn) {
            $sql = "DELETE FROM services WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $deleteId);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Service deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete service.";
            }
            closeDBConnection($conn);
        }
    }
    header("Location: services.php");
    exit();
}

// Handle service addition/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service'])) {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = $_POST['price'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $serviceId = $_POST['service_id'] ?? null;

    if (empty($name) || empty($category) || empty($price) || empty($duration)) {
        $_SESSION['error'] = "All fields are required.";
    } else {
        $conn = getDBConnection();
        if ($conn) {
            if ($serviceId) {
                // Update existing service
                $sql = "UPDATE services SET name = ?, description = ?, category = ?, price = ?, duration = ?, status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssdisi", $name, $description, $category, $price, $duration, $status, $serviceId);
            } else {
                // Add new service
                $sql = "INSERT INTO services (name, description, category, price, duration, status) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssdis", $name, $description, $category, $price, $duration, $status);
            }

            if ($stmt->execute()) {
                $_SESSION['success'] = $serviceId ? "Service updated successfully." : "Service added successfully.";
            } else {
                $_SESSION['error'] = "Failed to save service.";
            }
            closeDBConnection($conn);
        }
        header("Location: services.php");
        exit();
    }
}

// Get service data for editing
$editService = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $conn = getDBConnection();
    if ($conn) {
        $sql = "SELECT * FROM services WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $editService = $row;
        }
        closeDBConnection($conn);
    }
}

// Get services list with filters
$conn = getDBConnection();
$services = [];

if ($conn) {
    $sql = "SELECT * FROM services WHERE 1=1";
    $params = [];
    $types = "";

    if ($categoryFilter !== 'all') {
        $sql .= " AND category = ?";
        $params[] = $categoryFilter;
        $types .= "s";
    }

    if ($statusFilter !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }

    if ($priceFilter !== 'all') {
        switch ($priceFilter) {
            case 'low':
                $sql .= " AND price <= 50";
                break;
            case 'medium':
                $sql .= " AND price > 50 AND price <= 100";
                break;
            case 'high':
                $sql .= " AND price > 100";
                break;
        }
    }

    $sql .= " ORDER BY category, name";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($service = $result->fetch_assoc()) {
        $services[] = $service;
    }
    closeDBConnection($conn);
}

// Get unique categories for filter
$categories = [];
$conn = getDBConnection();
if ($conn) {
    $sql = "SELECT DISTINCT category FROM services ORDER BY category";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    closeDBConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management - Spa & Beauty System</title>
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
                <li><a href="users.php">Users</a></li>
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <section class="services-section">
                <div class="section-header">
                    <h2>Service Management</h2>
                    <div class="filter-controls">
                        <div class="filter-group">
                            <label for="category-filter">Category:</label>
                            <select id="category-filter" onchange="applyFilters()">
                                <option value="all">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>" 
                                            <?php echo $categoryFilter === $category ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="price-filter">Price Range:</label>
                            <select id="price-filter" onchange="applyFilters()">
                                <option value="all" <?php echo $priceFilter === 'all' ? 'selected' : ''; ?>>All Prices</option>
                                <option value="low" <?php echo $priceFilter === 'low' ? 'selected' : ''; ?>>$0 - $50</option>
                                <option value="medium" <?php echo $priceFilter === 'medium' ? 'selected' : ''; ?>>$51 - $100</option>
                                <option value="high" <?php echo $priceFilter === 'high' ? 'selected' : ''; ?>>$100+</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="status-filter">Status:</label>
                            <select id="status-filter" onchange="applyFilters()">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
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

                <?php if ($action === 'add' || $action === 'edit'): ?>
                    <div class="form-section">
                        <h3><?php echo $action === 'add' ? 'Add New Service' : 'Edit Service'; ?></h3>
                        <form method="POST" class="service-form">
                            <?php if ($editService): ?>
                                <input type="hidden" name="service_id" value="<?php echo $editService['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="name">Service Name:</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo $editService ? htmlspecialchars($editService['name']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="description">Description:</label>
                                <textarea id="description" name="description" rows="3"><?php echo $editService ? htmlspecialchars($editService['description']) : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="category">Category:</label>
                                <input type="text" id="category" name="category" required list="categories"
                                       value="<?php echo $editService ? htmlspecialchars($editService['category']) : ''; ?>">
                                <datalist id="categories">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="form-group">
                                <label for="price">Price ($):</label>
                                <input type="number" id="price" name="price" step="0.01" min="0" required 
                                       value="<?php echo $editService ? htmlspecialchars($editService['price']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="duration">Duration (minutes):</label>
                                <input type="number" id="duration" name="duration" min="15" step="15" required 
                                       value="<?php echo $editService ? htmlspecialchars($editService['duration']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="status">Status:</label>
                                <select id="status" name="status" required>
                                    <option value="active" <?php echo $editService && $editService['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $editService && $editService['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="save_service" class="btn btn-primary">Save Service</button>
                                <a href="services.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="services-list">
                        <div class="list-header">
                            <a href="?action=add" class="btn btn-primary">Add New Service</a>
                        </div>

                        <?php if (empty($services)): ?>
                            <p class="no-data">No services found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="services-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $service): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                                <td><?php echo htmlspecialchars($service['category']); ?></td>
                                                <td>$<?php echo number_format($service['price'], 2); ?></td>
                                                <td><?php echo $service['duration']; ?> min</td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $service['status']; ?>">
                                                        <?php echo ucfirst($service['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="actions">
                                                    <a href="?action=edit&id=<?php echo $service['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                                    <form method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                                        <button type="submit" name="delete_service" class="btn btn-danger btn-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Spa & Beauty System. All rights reserved.</p>
    </footer>

    <script>
        function applyFilters() {
            const category = document.getElementById('category-filter').value;
            const price = document.getElementById('price-filter').value;
            const status = document.getElementById('status-filter').value;
            
            window.location.href = `services.php?category=${category}&price=${price}&status=${status}`;
        }
    </script>
</body>
</html> 
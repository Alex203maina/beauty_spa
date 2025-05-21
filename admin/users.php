<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure only admins can access this page
requireRole('admin');

$userId = $_SESSION['user_id'];
$userData = getUserData($userId);

// Get role filter from query parameter
$roleFilter = $_GET['role'] ?? 'all';
$action = $_GET['action'] ?? 'list';

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $deleteId = $_POST['user_id'] ?? null;
    if ($deleteId && $deleteId != $userId) { // Prevent self-deletion
        $conn = getDBConnection();
        if ($conn) {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $deleteId);
            if ($stmt->execute()) {
                $_SESSION['success'] = "User deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete user.";
            }
            closeDBConnection($conn);
        }
    }
    header("Location: users.php");
    exit();
}

// Handle user addition/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $userId = $_POST['user_id'] ?? null;

    if (empty($name) || empty($email) || empty($role) || (empty($password) && !$userId)) {
        $_SESSION['error'] = "All fields are required.";
    } else {
        $conn = getDBConnection();
        if ($conn) {
            if ($userId) {
                // Update existing user
                if (!empty($password)) {
                    $sql = "UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->bind_param("ssssi", $name, $email, $role, $hashedPassword, $userId);
                } else {
                    $sql = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssi", $name, $email, $role, $userId);
                }
            } else {
                // Add new user
                $sql = "INSERT INTO users (name, email, role, password) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt->bind_param("ssss", $name, $email, $role, $hashedPassword);
            }

            if ($stmt->execute()) {
                $_SESSION['success'] = $userId ? "User updated successfully." : "User added successfully.";
            } else {
                $_SESSION['error'] = "Failed to save user.";
            }
            closeDBConnection($conn);
        }
        header("Location: users.php");
        exit();
    }
}

// Get user data for editing
$editUser = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $conn = getDBConnection();
    if ($conn) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $editUser = $row;
        }
        closeDBConnection($conn);
    }
}

// Get users list
$conn = getDBConnection();
$users = [];

if ($conn) {
    $sql = "SELECT * FROM users WHERE 1=1";
    $params = [];
    $types = "";

    if ($roleFilter !== 'all') {
        $sql .= " AND role = ?";
        $params[] = $roleFilter;
        $types .= "s";
    }

    $sql .= " ORDER BY role, name";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($user = $result->fetch_assoc()) {
        $users[] = $user;
    }
    closeDBConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Spa & Beauty System</title>
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
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <section class="users-section">
                <div class="section-header">
                    <h2>User Management</h2>
                    <div class="filter-controls">
                        <label for="role-filter">Filter by Role:</label>
                        <select id="role-filter" onchange="window.location.href='?role=' + this.value">
                            <option value="all" <?php echo $roleFilter === 'all' ? 'selected' : ''; ?>>All Users</option>
                            <option value="client" <?php echo $roleFilter === 'client' ? 'selected' : ''; ?>>Clients</option>
                            <option value="salonist" <?php echo $roleFilter === 'salonist' ? 'selected' : ''; ?>>Salonists</option>
                            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                        </select>
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
                        <h3><?php echo $action === 'add' ? 'Add New User' : 'Edit User'; ?></h3>
                        <form method="POST" class="user-form">
                            <?php if ($editUser): ?>
                                <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="name">Name:</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo $editUser ? htmlspecialchars($editUser['name']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" required 
                                       value="<?php echo $editUser ? htmlspecialchars($editUser['email']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="role">Role:</label>
                                <select id="role" name="role" required>
                                    <option value="client" <?php echo $editUser && $editUser['role'] === 'client' ? 'selected' : ''; ?>>Client</option>
                                    <option value="salonist" <?php echo $editUser && $editUser['role'] === 'salonist' ? 'selected' : ''; ?>>Salonist</option>
                                    <option value="admin" <?php echo $editUser && $editUser['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="password">Password<?php echo $editUser ? ' (leave blank to keep current)' : ''; ?>:</label>
                                <input type="password" id="password" name="password" <?php echo $editUser ? '' : 'required'; ?>>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="save_user" class="btn btn-primary">Save User</button>
                                <a href="users.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="users-list">
                        <div class="list-header">
                            <a href="?action=add" class="btn btn-primary">Add New User</a>
                        </div>

                        <?php if (empty($users)): ?>
                            <p class="no-data">No users found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="users-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo ucfirst($user['role']); ?></td>
                                                <td class="actions">
                                                    <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                                    <?php if ($user['id'] != $userId): ?>
                                                        <form method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Delete</button>
                                                        </form>
                                                    <?php endif; ?>
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
</body>
</html> 
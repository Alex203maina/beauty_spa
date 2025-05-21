<?php
require_once __DIR__ . '/../config/database.php';

function registerUser($name, $email, $password, $role, $phone = '') {
    $conn = getDBConnection();
    if (!$conn) return false;

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        closeDBConnection($conn);
        return false; // Email already exists
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $hashedPassword, $role, $phone);
    $success = $stmt->execute();
    
    closeDBConnection($conn);
    return $success;
}

function loginUser($email, $password) {
    $conn = getDBConnection();
    if (!$conn) return false;

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Start session and store user data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            closeDBConnection($conn);
            return true;
        }
    }
    
    closeDBConnection($conn);
    return false;
}

function logoutUser() {
    session_start();
    session_destroy();
    return true;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (getUserRole() !== $role) {
        header('Location: /index.php');
        exit();
    }
}

function getUserData($userId) {
    $conn = getDBConnection();
    if (!$conn) return null;

    $stmt = $conn->prepare("SELECT id, name, email, role, phone, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $userData = $result->fetch_assoc();
    closeDBConnection($conn);
    return $userData;
}

function updateUserProfile($userId, $name, $phone) {
    $conn = getDBConnection();
    if (!$conn) return false;

    $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $phone, $userId);
    $success = $stmt->execute();
    
    closeDBConnection($conn);
    return $success;
}

function changePassword($userId, $currentPassword, $newPassword) {
    $conn = getDBConnection();
    if (!$conn) return false;

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!password_verify($currentPassword, $user['password'])) {
        closeDBConnection($conn);
        return false;
    }

    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    $success = $stmt->execute();
    
    closeDBConnection($conn);
    return $success;
}

function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userId = $_SESSION['user_id'];
    $conn = getDBConnection();
    
    if ($conn) {
        $sql = "SELECT role FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        closeDBConnection($conn);
        
        return $userData && $userData['role'] === $role;
    }
    
    return false;
}
?> 
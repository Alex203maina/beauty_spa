<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../functions/receipt_functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// Get appointment ID from request
$appointmentId = $_GET['id'] ?? null;

if (!$appointmentId) {
    $_SESSION['error'] = "Invalid appointment ID.";
    header('Location: history.php');
    exit();
}

// Verify that the appointment belongs to the logged-in user
$conn = getDBConnection();
if ($conn) {
    $sql = "SELECT client_id FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();

    if (!$appointment || $appointment['client_id'] != $_SESSION['user_id']) {
        $_SESSION['error'] = "You are not authorized to download this receipt.";
        header('Location: history.php');
        exit();
    }
    closeDBConnection($conn);
}

// Generate and download receipt
if (!downloadReceipt($appointmentId)) {
    $_SESSION['error'] = "Failed to generate receipt.";
    header('Location: history.php');
    exit();
}
?> 
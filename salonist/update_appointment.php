<?php
session_start();
require_once '../functions/auth_functions.php';
require_once '../config/database.php';

// Ensure only salonists can access this page
requireRole('salonist');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentId = $_POST['appointment_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $userId = $_SESSION['user_id'];

    if (!$appointmentId || !$action) {
        $_SESSION['error'] = "Invalid request parameters.";
        header("Location: dashboard.php");
        exit();
    }

    $conn = getDBConnection();
    if ($conn) {
        // Verify the appointment belongs to this salonist
        $sql = "SELECT * FROM appointments WHERE id = ? AND salonist_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $appointmentId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['error'] = "Appointment not found or unauthorized.";
            header("Location: dashboard.php");
            exit();
        }

        $appointment = $result->fetch_assoc();
        $newStatus = '';

        // Update status based on action
        switch ($action) {
            case 'accept':
                if ($appointment['status'] === 'pending') {
                    $newStatus = 'accepted';
                }
                break;
            case 'decline':
                if ($appointment['status'] === 'pending') {
                    $newStatus = 'declined';
                }
                break;
            case 'complete':
                if ($appointment['status'] === 'accepted') {
                    $newStatus = 'completed';
                }
                break;
            default:
                $_SESSION['error'] = "Invalid action.";
                header("Location: dashboard.php");
                exit();
        }

        if ($newStatus) {
            // Update appointment status
            $sql = "UPDATE appointments SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $newStatus, $appointmentId);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Appointment status updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to update appointment status.";
            }
        } else {
            $_SESSION['error'] = "Invalid status transition.";
        }

        closeDBConnection($conn);
    } else {
        $_SESSION['error'] = "Database connection failed.";
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: dashboard.php");
exit(); 
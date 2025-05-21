<?php
require_once __DIR__ . '/../config/database.php';

function generateReceipt($appointmentId) {
    $conn = getDBConnection();
    if (!$conn) {
        return false;
    }

    // Get appointment details
    $sql = "SELECT a.*, s.name as service_name, s.price, s.duration,
            c.name as client_name, c.email as client_email,
            st.name as salonist_name
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

    if (!$appointment) {
        closeDBConnection($conn);
        return false;
    }

    // Generate receipt HTML
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Receipt #' . $appointmentId . '</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .receipt { max-width: 800px; margin: 0 auto; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .details { margin-bottom: 20px; }
            .details table { width: 100%; border-collapse: collapse; }
            .details th, .details td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
            .total { text-align: right; font-weight: bold; margin-top: 20px; }
            .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="receipt">
            <div class="header">
                <h1>Spa & Beauty System</h1>
                <h2>Receipt</h2>
            </div>
            <div class="details">
                <table>
                    <tr>
                        <th>Receipt No:</th>
                        <td>#' . $appointmentId . '</td>
                        <th>Date:</th>
                        <td>' . date('F d, Y', strtotime($appointment['date'])) . '</td>
                    </tr>
                    <tr>
                        <th>Client:</th>
                        <td>' . htmlspecialchars($appointment['client_name']) . '</td>
                        <th>Email:</th>
                        <td>' . htmlspecialchars($appointment['client_email']) . '</td>
                    </tr>
                    <tr>
                        <th>Service:</th>
                        <td>' . htmlspecialchars($appointment['service_name']) . '</td>
                        <th>Duration:</th>
                        <td>' . $appointment['duration'] . ' minutes</td>
                    </tr>
                    <tr>
                        <th>Salonist:</th>
                        <td>' . htmlspecialchars($appointment['salonist_name']) . '</td>
                        <th>Time:</th>
                        <td>' . date('g:i A', strtotime($appointment['time'])) . '</td>
                    </tr>
                </table>
            </div>
            <div class="total">
                <p>Total Amount: $' . number_format($appointment['price'], 2) . '</p>
            </div>
            <div class="footer">
                <p>Thank you for choosing Spa & Beauty System!</p>
                <p>This is a computer-generated receipt and does not require a signature.</p>
            </div>
        </div>
    </body>
    </html>';

    // Save receipt to database
    $sql = "INSERT INTO receipts (appointment_id, client_id, salonist_id, service_id, total_amount) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisd", 
        $appointmentId, 
        $appointment['client_id'], 
        $appointment['salonist_id'], 
        $appointment['service_id'], 
        $appointment['price']
    );
    $stmt->execute();

    closeDBConnection($conn);
    return $html;
}

function downloadReceipt($appointmentId) {
    $html = generateReceipt($appointmentId);
    if (!$html) {
        return false;
    }

    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="receipt_' . $appointmentId . '.pdf"');

    // Convert HTML to PDF using TCPDF
    require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Spa & Beauty System');
    $pdf->SetAuthor('Spa & Beauty System');
    $pdf->SetTitle('Receipt #' . $appointmentId);

    $pdf->AddPage();
    $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->Output('receipt_' . $appointmentId . '.pdf', 'D');
    return true;
}
?> 
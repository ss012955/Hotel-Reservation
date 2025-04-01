<?php
session_start();
require_once "config.php";

// Check if we have a reservation ID
if (!isset($_GET['id'])) {
    header("Location: HotelReservation.php");
    exit();
}

$reservation_id = $_GET['id'];

// Fetch the reservation details
try {
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        header("Location: HotelReservation.php");
        exit();
    }
} catch(PDOException $e) {
    error_log("Error fetching reservation: " . $e->getMessage());
    header("Location: HotelReservation.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reservation Success - HorsePlaying Hotel</title>
    <link rel="icon" type="image/x-icon" href="horse.jpg">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .page-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        .success-container {
            width: 100%;
            max-width: 800px;
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            background-color: #2ecc71;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }
        .success-message {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .reservation-number {
            font-size: 20px;
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        .email-sent {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #2c3e50;
        }
        .email-sent svg {
            color: #3498db;
        }
        .reservation-details {
            text-align: left;
            margin: 30px 0;
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .return-home {
            display: inline-block;
            background-color: #2ecc71;
            color: white;
            padding: 15px 40px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 30px;
            transition: background-color 0.3s ease;
        }
        .return-home:hover {
            background-color: #27ae60;
        }
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #ef9a9a;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="page-wrapper">
        <div class="success-container">
            <div class="success-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                </svg>
            </div>
            <div class="success-message">Reservation Confirmed!</div>
            <div class="reservation-number">Reservation #<?php echo htmlspecialchars($reservation_id); ?></div>
            
            <div class="email-sent">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
                A confirmation email has been sent to <?php echo htmlspecialchars($reservation['email']); ?>
            </div>
            
            <div class="reservation-details">
                <h3>Reservation Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Guest Name:</span>
                    <span><?php echo htmlspecialchars($reservation['customer_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contact Number:</span>
                    <span><?php echo htmlspecialchars($reservation['contact_number']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Room Type:</span>
                    <span><?php echo htmlspecialchars($reservation['room_type']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Room Capacity:</span>
                    <span><?php echo htmlspecialchars($reservation['room_capacity']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-in Date:</span>
                    <span><?php echo date('F j, Y', strtotime($reservation['from_date'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-out Date:</span>
                    <span><?php echo date('F j, Y', strtotime($reservation['to_date'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Cost:</span>
                    <span>$<?php echo number_format($reservation['total_cost'], 2); ?></span>
                </div>
            </div>
            
            <a href="index.php" class="return-home">Return to Home</a>
        </div>
    </div>
</body>
</html> 
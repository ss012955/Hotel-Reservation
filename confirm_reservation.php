<?php
session_start();
require_once "config.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if there's a temporary reservation
if (!isset($_SESSION['temp_reservation'])) {
    header("Location: HotelReservation.php");
    exit();
}

$reservation = $_SESSION['temp_reservation'];
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm'])) {
    try {
        // Prepare an insert statement
        $sql = "INSERT INTO reservations (customer_name, contact_number, email, room_type, room_capacity, payment_type, from_date, to_date, total_cost, status) 
                VALUES (:customer_name, :contact_number, :email, :room_type, :room_capacity, :payment_type, :from_date, :to_date, :total_cost, 'Confirmed')";
        
        if ($stmt = $pdo->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":customer_name", $reservation['customer_name'], PDO::PARAM_STR);
            $stmt->bindParam(":contact_number", $reservation['contact_number'], PDO::PARAM_STR);
            $stmt->bindParam(":email", $reservation['email'], PDO::PARAM_STR);
            $stmt->bindParam(":room_type", $reservation['room_type'], PDO::PARAM_STR);
            $stmt->bindParam(":room_capacity", $reservation['room_capacity'], PDO::PARAM_STR);
            $stmt->bindParam(":payment_type", $reservation['payment_type'], PDO::PARAM_STR);
            $stmt->bindParam(":from_date", $reservation['from_date'], PDO::PARAM_STR);
            $stmt->bindParam(":to_date", $reservation['to_date'], PDO::PARAM_STR);
            $stmt->bindParam(":total_cost", $reservation['total_cost'], PDO::PARAM_STR);
            
            // Execute the prepared statement
            if ($stmt->execute()) {
                $reservation_id = $pdo->lastInsertId();
                
                // Send confirmation email with invoice
                require_once 'send_invoice.php';
                if (!function_exists('sendInvoiceEmail')) {
                    throw new Exception("Failed to load invoice email function");
                }
                
                if (sendInvoiceEmail($reservation, $reservation_id)) {
                    // Clear the temporary reservation
                    unset($_SESSION['temp_reservation']);
                    
                    // Redirect to success page
                    header("Location: reservation_success.php?id=" . $reservation_id);
                    exit();
                } else {
                    throw new Exception("Failed to send confirmation email");
                }
            } else {
                throw new Exception("Failed to save reservation");
            }
        }
    } catch(Exception $e) {
        error_log("Error processing reservation: " . $e->getMessage());
        $error_message = "There was an error processing your reservation: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Confirm Reservation - HorsePlaying Hotel</title>
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
        .confirmation-container {
            width: 100%;
            max-width: 800px;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .confirmation-container h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .reservation-details {
            margin-bottom: 30px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .cost-breakdown {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .cost-breakdown h3 {
            margin-top: 0;
            color: #2c3e50;
            text-align: center;
        }
        .total-cost {
            font-size: 24px;
            color: #2ecc71;
            text-align: right;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            gap: 20px;
        }
        .confirm-btn {
            background-color: #2ecc71;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            flex: 1;
            transition: background-color 0.3s ease;
        }
        .back-btn {
            background-color: #95a5a6;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            flex: 1;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        .confirm-btn:hover {
            background-color: #27ae60;
        }
        .back-btn:hover {
            background-color: #7f8c8d;
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
        <div class="confirmation-container">
            <h2>Confirm Your Reservation</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="reservation-details">
                <div class="detail-row">
                    <span class="detail-label">Customer Name:</span>
                    <span><?php echo htmlspecialchars($reservation['customer_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contact Number:</span>
                    <span><?php echo htmlspecialchars($reservation['contact_number']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span><?php echo htmlspecialchars($reservation['email']); ?></span>
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
                    <span class="detail-label">Payment Method:</span>
                    <span><?php echo htmlspecialchars($reservation['payment_type']); ?></span>
                </div>
            </div>
            
            <div class="cost-breakdown">
                <h3>Cost Breakdown</h3>
                <div class="detail-row">
                    <span>Base Rate (<?php echo $reservation['days']; ?> nights)</span>
                    <span>$<?php echo number_format($reservation['base_rate'], 2); ?></span>
                </div>
                <?php if ($reservation['payment_type'] == 'Check'): ?>
                    <div class="detail-row">
                        <span>Check Payment Fee (5%)</span>
                        <span>$<?php echo number_format($reservation['base_rate'] * 0.05, 2); ?></span>
                    </div>
                <?php elseif ($reservation['payment_type'] == 'Credit Card'): ?>
                    <div class="detail-row">
                        <span>Credit Card Fee (10%)</span>
                        <span>$<?php echo number_format($reservation['base_rate'] * 0.10, 2); ?></span>
                    </div>
                <?php elseif ($reservation['payment_type'] == 'Cash' && $reservation['days'] >= 3): ?>
                    <div class="detail-row">
                        <span>Cash Discount (<?php echo $reservation['days'] >= 6 ? '15%' : '10%'; ?>)</span>
                        <span>-$<?php echo number_format($reservation['base_rate'] * ($reservation['days'] >= 6 ? 0.15 : 0.10), 2); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="total-cost">
                    <strong>Total Cost: $<?php echo number_format($reservation['total_cost'], 2); ?></strong>
                </div>
            </div>
            
            <div class="buttons">
                <a href="HotelReservation.php" class="back-btn">Back</a>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display: inline;">
                    <input type="hidden" name="confirm" value="1">
                    <button type="submit" class="confirm-btn">Confirm Reservation</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 
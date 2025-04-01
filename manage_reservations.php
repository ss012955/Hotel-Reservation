<?php
// Initialize the session
session_start();

// Set session timeout to 5 minutes (300 seconds)
$session_timeout = 300; // 5 minutes

// Check if the last activity time is set
if (isset($_SESSION['last_activity'])) {
    // Calculate time difference
    $inactive_time = time() - $_SESSION['last_activity'];
    
    // If inactive for more than the timeout period, destroy the session
    if ($inactive_time >= $session_timeout) {
        session_unset();
        session_destroy();
        header("location: admin_login.php");
        exit;
    }
}

// Update last activity time stamp
$_SESSION['last_activity'] = time();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: admin_login.php");
    exit;
}

// Include config file
require_once "config.php";
require_once "send_invoice.php";

// Define variables and initialize with empty values
$customer_name = $contact_number = $email = $room_type = $room_capacity = $payment_type = $from_date = $to_date = "";
$customer_name_err = $contact_number_err = $email_err = $room_type_err = $room_capacity_err = $payment_type_err = $from_date_err = $to_date_err = "";

// Processing form data when form is submitted for adding a new reservation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "add") {
    
    // Validate customer name
    if (empty(trim($_POST["customer_name"]))) {
        $customer_name_err = "Please enter customer name.";
    } else {
        $customer_name = trim($_POST["customer_name"]);
    }
    
    // Validate contact number
    if (empty(trim($_POST["contact_number"]))) {
        $contact_number_err = "Please enter contact number.";
    } else {
        $contact_number = trim($_POST["contact_number"]);
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter email.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Validate room type
    if (empty($_POST["room_type"])) {
        $room_type_err = "Please select room type.";
    } else {
        $room_type = $_POST["room_type"];
    }
    
    // Validate room capacity
    if (empty($_POST["room_capacity"])) {
        $room_capacity_err = "Please select room capacity.";
    } else {
        $room_capacity = $_POST["room_capacity"];
    }
    
    // Validate payment type
    if (empty($_POST["payment_type"])) {
        $payment_type_err = "Please select payment type.";
    } else {
        $payment_type = $_POST["payment_type"];
    }
    
    // Validate from date
    if (empty($_POST["from_date"])) {
        $from_date_err = "Please select from date.";
    } else {
        $from_date = $_POST["from_date"];
    }
    
    // Validate to date
    if (empty($_POST["to_date"])) {
        $to_date_err = "Please select to date.";
    } else {
        $to_date = $_POST["to_date"];
    }
    
    // Check input errors before inserting in database
    if (empty($customer_name_err) && empty($contact_number_err) && empty($email_err) && empty($room_type_err) && 
        empty($room_capacity_err) && empty($payment_type_err) && empty($from_date_err) && empty($to_date_err)) {
        
        // Calculate the number of days and total cost
        $from_date_timestamp = strtotime($from_date);
        $to_date_timestamp = strtotime($to_date);
        $days = ($to_date_timestamp - $from_date_timestamp) / (60 * 60 * 24);
        
        $rates = [
            'Single' => ['Regular' => 100, 'De Luxe' => 300, 'Suite' => 500],
            'Double' => ['Regular' => 200, 'De Luxe' => 500, 'Suite' => 800],
            'Family' => ['Regular' => 500, 'De Luxe' => 750, 'Suite' => 1000]
        ];
        
        $base_rate = $rates[$room_capacity][$room_type] * $days;
        $total_cost = $base_rate;
        
        if ($payment_type == 'Check') {
            $total_cost *= 1.05;
        } elseif ($payment_type == 'Credit Card') {
            $total_cost *= 1.10;
        } elseif ($payment_type == 'Cash') {
            if ($days >= 6) {
                $total_cost *= 0.85;
            } elseif ($days >= 3) {
                $total_cost *= 0.90;
            }
        }
        
        // Prepare an insert statement
        $sql = "INSERT INTO reservations (customer_name, contact_number, email, room_type, room_capacity, payment_type, from_date, to_date, total_cost, is_paid) 
                VALUES (:customer_name, :contact_number, :email, :room_type, :room_capacity, :payment_type, :from_date, :to_date, :total_cost, :is_paid)";
        
        if ($stmt = $pdo->prepare($sql)) {
            // Bind variables
            $stmt->bindParam(":customer_name", $param_customer_name, PDO::PARAM_STR);
            $stmt->bindParam(":contact_number", $param_contact_number, PDO::PARAM_STR);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $stmt->bindParam(":room_type", $param_room_type, PDO::PARAM_STR);
            $stmt->bindParam(":room_capacity", $param_room_capacity, PDO::PARAM_STR);
            $stmt->bindParam(":payment_type", $param_payment_type, PDO::PARAM_STR);
            $stmt->bindParam(":from_date", $param_from_date, PDO::PARAM_STR);
            $stmt->bindParam(":to_date", $param_to_date, PDO::PARAM_STR);
            $stmt->bindParam(":total_cost", $param_total_cost, PDO::PARAM_STR);
            $stmt->bindParam(":is_paid", $param_is_paid, PDO::PARAM_BOOL);
            
            // Set parameters
            $param_customer_name = $customer_name;
            $param_contact_number = $contact_number;
            $param_email = $_POST['email'];
            $param_room_type = $room_type;
            $param_room_capacity = $room_capacity;
            $param_payment_type = $payment_type;
            $param_from_date = $from_date;
            $param_to_date = $to_date;
            $param_total_cost = $total_cost;
            $param_is_paid = isset($_POST['is_paid']) ? 1 : 0;
            
            // Execute the prepared statement
            if ($stmt->execute()) {
                $reservation_id = $pdo->lastInsertId();
                
                // Get the reservation details for the email
                $sql = "SELECT * FROM reservations WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(":id", $reservation_id, PDO::PARAM_INT);
                $stmt->execute();
                $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Calculate days and base rate for the email
                $from_date_timestamp = strtotime($reservation['from_date']);
                $to_date_timestamp = strtotime($reservation['to_date']);
                $days = ($to_date_timestamp - $from_date_timestamp) / (60 * 60 * 24);
                $reservation['days'] = $days;
                
                $rates = [
                    'Single' => ['Regular' => 100, 'De Luxe' => 300, 'Suite' => 500],
                    'Double' => ['Regular' => 200, 'De Luxe' => 500, 'Suite' => 800],
                    'Family' => ['Regular' => 500, 'De Luxe' => 750, 'Suite' => 1000]
                ];
                
                $base_rate = $rates[$reservation['room_capacity']][$reservation['room_type']] * $days;
                $reservation['base_rate'] = $base_rate;
                
                // Send confirmation email
                try {
                    sendInvoiceEmail($reservation, $reservation_id);
                } catch (Exception $e) {
                    // Log the error but don't prevent the reservation from being created
                    error_log("Failed to send confirmation email for reservation #" . $reservation_id . ": " . $e->getMessage());
                }
                
                // If room is selected, assign it
                if (!empty($_POST['room_id'])) {
                    $room_id = $_POST['room_id'];
                    
                    // Create a unique guest identifier by combining name and reservation ID
                    $unique_guest_id = $customer_name . "_" . $reservation_id;
                    
                    // Update the room with the unique guest identifier and set status to occupied
                    $sql = "UPDATE rooms SET status = 'Occupied', guest_name = :guest_name WHERE id = :room_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':guest_name', $unique_guest_id, PDO::PARAM_STR);
                    $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
                    $stmt->execute();
                }
                
                // Redirect to reservation list page
                header("location: manage_reservations.php");
                exit();
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            unset($stmt);
        }
    }
}

// Process delete reservation
if (isset($_GET["delete"]) && !empty($_GET["delete"])) {
    // Prepare a delete statement
    $sql = "DELETE FROM reservations WHERE id = :id";
    
    if ($stmt = $pdo->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
        
        // Set parameters
        $param_id = trim($_GET["delete"]);
        
        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Redirect to reservation list page
            header("location: manage_reservations.php");
            exit();
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        // Close statement
        unset($stmt);
    }
}

// Process update reservation status
if (isset($_GET["complete"]) && !empty($_GET["complete"])) {
    // Get the reservation ID
    $reservation_id = trim($_GET["complete"]);
    
    // First, get the customer name from the reservation
    $sql = "SELECT customer_name FROM reservations WHERE id = :reservation_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':reservation_id', $reservation_id, PDO::PARAM_INT);
    $stmt->execute();
    $customer_name = $stmt->fetch(PDO::FETCH_ASSOC)['customer_name'];
    
    // Find and clear any rooms assigned to this reservation using the unique identifier
    $sql = "UPDATE rooms SET status = 'Available', guest_name = NULL WHERE guest_name LIKE :guest_name";
    $stmt = $pdo->prepare($sql);
    $search_pattern = "%" . "_" . $reservation_id;
    $stmt->bindParam(':guest_name', $search_pattern, PDO::PARAM_STR);
    $stmt->execute();
    
    // Now update the reservation status
    $sql = "UPDATE reservations SET status = 'Completed' WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":id", $reservation_id, PDO::PARAM_INT);
    
    // Attempt to execute the prepared statement
    if ($stmt->execute()) {
        // Redirect to reservation list page
        header("location: manage_reservations.php");
        exit();
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    
    // Close statement
    unset($stmt);
}

if (isset($_GET["cancel"]) && !empty($_GET["cancel"])) {
    // Get the reservation ID
    $reservation_id = trim($_GET["cancel"]);
    
    // Find and clear any rooms assigned to this reservation using the unique identifier
    $sql = "UPDATE rooms SET status = 'Available', guest_name = NULL WHERE guest_name LIKE :guest_name";
    $stmt = $pdo->prepare($sql);
    $search_pattern = "%" . "_" . $reservation_id;
    $stmt->bindParam(':guest_name', $search_pattern, PDO::PARAM_STR);
    $stmt->execute();
    
    // Prepare an update statement
    $sql = "UPDATE reservations SET status = 'Cancelled' WHERE id = :id";
    
    if ($stmt = $pdo->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
        
        // Set parameters
        $param_id = $reservation_id;
        
        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Redirect to reservation list page
            header("location: manage_reservations.php");
            exit();
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        // Close statement
        unset($stmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle room assignment
    if (isset($_POST['action']) && $_POST['action'] == 'assign_room' && isset($_POST['reservation_id'])) {
        $reservation_id = $_POST['reservation_id'];
        $new_room_id = isset($_POST['room_id']) ? $_POST['room_id'] : '';

        // If room_id is not empty, update the room's guest name and status
        if (!empty($new_room_id)) {
            // Check if the room is under maintenance
            $sql = "SELECT status FROM rooms WHERE id = :room_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':room_id', $new_room_id, PDO::PARAM_INT);
            $stmt->execute();
            $room_status = $stmt->fetch(PDO::FETCH_ASSOC)['status'];
            
            // Only proceed if the room is not under maintenance
            if ($room_status != 'Maintenance') {
                // First, get the customer name from the reservation
                $sql = "SELECT customer_name FROM reservations WHERE id = :reservation_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':reservation_id', $reservation_id, PDO::PARAM_INT);
                $stmt->execute();
                $customer_info = $stmt->fetch(PDO::FETCH_ASSOC);
                $customer_name = $customer_info['customer_name'];
                
                // Create a unique guest identifier by combining name and reservation ID
                $unique_guest_id = $customer_name . "_" . $reservation_id;

                // Find the current room assigned to this reservation (using unique guest ID)
                $sql = "SELECT id FROM rooms WHERE guest_name LIKE :guest_name";
                $stmt = $pdo->prepare($sql);
                $search_pattern = "%" . "_" . $reservation_id;
                $stmt->bindParam(':guest_name', $search_pattern, PDO::PARAM_STR);
                $stmt->execute();
                $current_room = $stmt->fetch(PDO::FETCH_ASSOC);

                // If there's a current room and it's different from the new room, clear it
                if ($current_room && $current_room['id'] != $new_room_id) {
                    $sql = "UPDATE rooms SET status = 'Available', guest_name = NULL WHERE id = :room_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':room_id', $current_room['id'], PDO::PARAM_INT);
                    $stmt->execute();
                }

                // Update the new room with the unique guest ID and set status to occupied
                $sql = "UPDATE rooms SET status = 'Occupied', guest_name = :guest_name WHERE id = :room_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':guest_name', $unique_guest_id, PDO::PARAM_STR);
                $stmt->bindParam(':room_id', $new_room_id, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    } 
    // Handle payment status update
    elseif (isset($_POST['action']) && $_POST['action'] == 'update_payment' && isset($_POST['reservation_id'])) {
        $reservation_id = $_POST['reservation_id'];
        $is_paid = isset($_POST['is_paid']) ? 1 : 0;

        // Update payment status in reservations table
        $sql = "UPDATE reservations SET is_paid = :is_paid WHERE id = :reservation_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':is_paid', $is_paid, PDO::PARAM_BOOL);
        $stmt->bindParam(':reservation_id', $reservation_id, PDO::PARAM_INT);
        $stmt->execute();
    }
}

// Fetch all rooms and reservations AFTER any updates to ensure fresh data
$sql = "SELECT * FROM rooms ORDER BY room_number ASC";
$stmt = $pdo->query($sql);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get sort parameters
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Validate sort column to prevent SQL injection
$allowed_columns = ['id', 'customer_name', 'contact_number', 'email', 'room_type', 'room_capacity', 'payment_type', 'from_date', 'total_cost', 'status', 'is_paid', 'created_at'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'created_at';
}

// Validate sort order
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Fetch all reservations with fresh data
$sql = "SELECT r.* FROM reservations r ORDER BY $sort_column $sort_order";
$stmt = $pdo->query($sql);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add sorting styles
echo '<style>
    .sort-link {
        color: #333;
        text-decoration: none;
    }
    .sort-link:hover {
        color: #3498db;
    }
    .sort-indicator::after {
        content: "↕";
        margin-left: 5px;
        font-size: 12px;
    }
    .sort-asc::after {
        content: "↑";
    }
    .sort-desc::after {
        content: "↓";
    }
</style>';
?>

<!DOCTYPE html>
<html>
<head>
    <title>HorsePlaying Hotel </title>
    <link rel="icon" type="image/x-icon" href="horse.jpg">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            padding-top: 20px;
        }
        .sidebar-header {
            padding: 0 20px 20px 20px;
            border-bottom: 1px solid #34495e;
            text-align: center;
        }
        .sidebar-menu {
            padding: 20px 0;
        }
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .sidebar-menu a:hover {
            background-color: #34495e;
        }
        .sidebar-menu a.active {
            background-color: #3498db;
        }
        .content {
            flex: 1;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-role {
            display: inline-block;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            margin-left: 10px;
            font-size: 14px;
        }
        .user-role-admin {
            background-color: #e74c3c;
        }
        .user-role-staff {
            background-color: #3498db;
        }
        .logout-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
        }
        .data-table tr:hover {
            background-color: #f9f9f9;
        }
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        .status-confirmed {
            background-color: #2ecc71;
            color: white;
        }
        .status-cancelled {
            background-color: #e74c3c;
            color: white;
        }
        .status-completed {
            background-color: #3498db;
            color: white;
        }
        .action-btn {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            color: white;
            text-decoration: none;
            margin-right: 5px;
        }
        .view-btn {
            background-color: #3498db;
        }
        .edit-btn {
            background-color: #f39c12;
        }
        .delete-btn {
            background-color: #e74c3c;
        }
        .complete-btn {
            background-color: #2ecc71;
        }
        .cancel-btn {
            background-color: #95a5a6;
        }
        .add-btn {
            display: inline-block;
            background-color: #2ecc71;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .submit-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>HorsePlaying Hotel</h3>
                <img src="horse.jpg" alt="Hotel Logo" style="width: 80px; height: 80px; margin-top: 10px; border-radius: 50%;">
            </div>
            <div class="sidebar-menu">
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="manage_reservations.php" class="active">Reservations</a>
                <a href="manage_rooms.php">Rooms</a>
                <a href="manage_users.php">Users</a>
                <a href="logout.php?redirect=index.php">View Website</a>
            </div>
        </div>
        <div class="content">
            <div class="header">
                <div class="user-info">
                    <h2>Manage Reservations</h2>
                    <span class="user-role user-role-<?php echo strtolower($_SESSION["role"]); ?>"><?php echo htmlspecialchars($_SESSION["role"]); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
            
            <button id="addReservationBtn" class="add-btn">Add New Reservation</button>
            
            <?php if (count($reservations) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><a href="?sort=id&order=<?php echo $sort_column === 'id' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'id' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">ID</a></th>
                            <th><a href="?sort=customer_name&order=<?php echo $sort_column === 'customer_name' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'customer_name' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Customer</a></th>
                            <th><a href="?sort=email&order=<?php echo $sort_column === 'email' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'email' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Email</a></th>
                            <th><a href="?sort=contact_number&order=<?php echo $sort_column === 'contact_number' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'contact_number' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Contact</a></th>
                            <th><a href="?sort=room_type&order=<?php echo $sort_column === 'room_type' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'room_type' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Room Type</a></th>
                            <th><a href="?sort=room_capacity&order=<?php echo $sort_column === 'room_capacity' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'room_capacity' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Room Capacity</a></th>
                            <th><a href="?sort=payment_type&order=<?php echo $sort_column === 'payment_type' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'payment_type' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Payment</a></th>
                            <th><a href="?sort=from_date&order=<?php echo $sort_column === 'from_date' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'from_date' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Dates</a></th>
                            <th><a href="?sort=total_cost&order=<?php echo $sort_column === 'total_cost' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'total_cost' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Total</a></th>
                            <th>Room</th>
                            <th><a href="?sort=is_paid&order=<?php echo $sort_column === 'is_paid' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'is_paid' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Paid</a></th>
                            <th><a href="?sort=status&order=<?php echo $sort_column === 'status' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'status' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Status</a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?php echo $reservation['id']; ?></td>
                                <td><?php echo htmlspecialchars($reservation['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['email']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['contact_number']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['room_type']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['room_capacity']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['payment_type']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['from_date'] . ' to ' . $reservation['to_date']); ?></td>
                                <td>$<?php echo number_format($reservation['total_cost'], 2); ?></td>
                                <td>
                                    <?php 
                                    // Find the currently assigned room
                                    $current_room = null;
                                    foreach ($rooms as $room) {
                                        if ($room['guest_name'] && strpos($room['guest_name'], $reservation['customer_name'] . '_' . $reservation['id']) !== false) {
                                            $current_room = $room;
                                            break;
                                        }
                                    }
                                    ?>
                                    <form method="post" action="" style="display: inline-block; margin-bottom: 0; width: 100%;">
                                        <input type="hidden" name="action" value="assign_room">
                                        <?php if ($current_room): ?>
                                            <div style="margin-bottom: 8px; font-weight: bold; color: #3498db;">
                                                Currently in Room: <?php echo htmlspecialchars($current_room['room_number']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <select name="room_id" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ddd; background-color: <?php echo ($reservation['status'] == 'Completed' || $reservation['status'] == 'Cancelled') ? '#f5f5f5' : 'white'; ?>;" 
                                            <?php echo (!$reservation['is_paid'] || $reservation['status'] == 'Completed' || $reservation['status'] == 'Cancelled') ? 'disabled' : ''; ?>>
                                                <option value="">Select Room</option>
                                                <?php 
                                                // Display rooms grouped by current and available
                                                if ($current_room) {
                                                    echo '<optgroup label="Currently Assigned">';
                                                    echo '<option value="' . $current_room['id'] . '" selected>';
                                                    echo htmlspecialchars($current_room['room_number'] . ' (Current)');
                                                    echo '</option>';
                                                    echo '</optgroup>';
                                                }
                                                
                                                // Get all available rooms that match the capacity and type
                                                $available_rooms = array_filter($rooms, function($room) use ($reservation, $current_room) {
                                                    // Map room type names to type_ids
                                                    $type_id_map = [
                                                        'Regular' => 1,
                                                        'De Luxe' => 2,
                                                        'Suite' => 3
                                                    ];
                                                    
                                                    $reservation_type_id = $type_id_map[$reservation['room_type']];
                                                    
                                                    return $room['capacity'] == $reservation['room_capacity'] && 
                                                           $room['type_id'] == $reservation_type_id &&
                                                           (($room['status'] == 'Available' || 
                                                            ($current_room && $room['id'] == $current_room['id'])) &&
                                                            $room['status'] != 'Maintenance');
                                                });
                                                
                                                if (!empty($available_rooms)) {
                                                    echo '<optgroup label="Available Rooms">';
                                                    foreach ($available_rooms as $room):
                                                        if (!$current_room || $room['id'] != $current_room['id']):
                                                ?>
                                                            <option value="<?php echo $room['id']; ?>">
                                                                <?php echo htmlspecialchars($room['room_number']); ?>
                                                            </option>
                                                <?php 
                                                        endif;
                                                    endforeach;
                                                    echo '</optgroup>';
                                                }
                                                ?>
                                            </select>
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                            <button type="submit" style="background-color: <?php echo (!$reservation['is_paid'] || $reservation['status'] == 'Completed' || $reservation['status'] == 'Cancelled') ? '#b5b5b5' : '#3498db'; ?>; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: <?php echo (!$reservation['is_paid'] || $reservation['status'] == 'Completed' || $reservation['status'] == 'Cancelled') ? 'not-allowed' : 'pointer'; ?>;" 
                                            <?php echo (!$reservation['is_paid'] || $reservation['status'] == 'Completed' || $reservation['status'] == 'Cancelled') ? 'disabled' : ''; ?>>
                                                <?php echo $current_room ? 'Change Room' : 'Assign Room'; ?>
                                            </button>
                                        </div>
                                        <?php if (!$reservation['is_paid'] && $reservation['status'] == 'Confirmed'): ?>
                                            <div style="margin-top: 8px; color: #e74c3c; font-size: 13px; display: flex; align-items: center;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                                                Payment required before room assignment
                                            </div>
                                        <?php elseif ($reservation['status'] == 'Completed' || $reservation['status'] == 'Cancelled'): ?>
                                            <div style="margin-top: 8px; color: #95a5a6; font-size: 13px; display: flex; align-items: center;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px;"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                                                Reservation <?php echo strtolower($reservation['status']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td>
                                    <form method="post" action="" style="display: inline-block; margin-bottom: 0; width: 100%;">
                                        <input type="hidden" name="action" value="update_payment">
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <div style="display: flex; align-items: center; background-color: <?php echo ($reservation['status'] == 'Completed' || $reservation['status'] == 'Cancelled') ? '#f5f5f5' : 'white'; ?>; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                                                <input type="checkbox" name="is_paid" id="payment_<?php echo $reservation['id']; ?>" value="1" <?php echo $reservation['is_paid'] ? 'checked' : ''; ?> 
                                                <?php echo ($reservation['status'] == 'Completed' || $reservation['status'] == 'Cancelled') ? 'disabled' : ''; ?> 
                                                style="margin-right: 10px; transform: scale(1.2);"> 
                                                <label for="payment_<?php echo $reservation['id']; ?>" style="font-weight: 500;">Mark as Paid</label>
                                            </div>
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                            <button type="submit" style="background-color: <?php echo ($reservation['status'] == 'Completed' || $reservation['status'] == 'Cancelled') ? '#b5b5b5' : '#2ecc71'; ?>; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: <?php echo ($reservation['status'] == 'Completed' || $reservation['status'] == 'Cancelled') ? 'not-allowed' : 'pointer'; ?>;"
                                            <?php echo ($reservation['status'] == 'Completed' || $reservation['status'] == 'Cancelled') ? 'disabled' : ''; ?>>
                                                Update Payment Status
                                            </button>
                                        </div>
                                        <?php if ($reservation['status'] == 'Completed' || $reservation['status'] == 'Cancelled'): ?>
                                            <div style="margin-top: 8px; color: #95a5a6; font-size: 13px; display: flex; align-items: center;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 5px;"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                                                Reservation <?php echo strtolower($reservation['status']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td>
                                    <span class="status status-<?php echo strtolower($reservation['status']); ?>">
                                        <?php echo $reservation['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        <?php if ($reservation['status'] == 'Confirmed'): ?>
                                            <div style="display: flex; gap: 5px; margin-bottom: 5px;">
                                                <a href="manage_reservations.php?complete=<?php echo $reservation['id']; ?>" class="action-btn complete-btn" style="flex: 1; text-align: center;" onclick="return confirm('Mark this reservation as completed?')">Complete</a>
                                                <a href="manage_reservations.php?cancel=<?php echo $reservation['id']; ?>" class="action-btn cancel-btn" style="flex: 1; text-align: center;" onclick="return confirm('Cancel this reservation?')">Cancel</a>
                                            </div>
                                        <?php endif; ?>
                                        <a href="manage_reservations.php?delete=<?php echo $reservation['id']; ?>" class="action-btn delete-btn" style="text-align: center;" onclick="return confirm('Delete this reservation permanently?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No reservations found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Reservation Modal -->
    <div id="addReservationModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Reservation</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" name="customer_name" class="form-control <?php echo (!empty($customer_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $customer_name; ?>">
                    <span class="error"><?php echo $customer_name_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" class="form-control <?php echo (!empty($contact_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $contact_number; ?>">
                    <span class="error"><?php echo $contact_number_err; ?></span>
                </div>
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="from_date" class="form-control <?php echo (!empty($from_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $from_date; ?>">
                    <span class="error"><?php echo $from_date_err; ?></span>
                </div>
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="to_date" class="form-control <?php echo (!empty($to_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $to_date; ?>">
                    <span class="error"><?php echo $to_date_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Room Type</label>
                    <select name="room_type" class="form-control <?php echo (!empty($room_type_err)) ? 'is-invalid' : ''; ?>">
                        <option value="" <?php echo empty($room_type) ? 'selected' : ''; ?>>Select Room Type</option>
                        <option value="Regular" <?php echo ($room_type == 'Regular') ? 'selected' : ''; ?>>Regular</option>
                        <option value="De Luxe" <?php echo ($room_type == 'De Luxe') ? 'selected' : ''; ?>>De Luxe</option>
                        <option value="Suite" <?php echo ($room_type == 'Suite') ? 'selected' : ''; ?>>Suite</option>
                    </select>
                    <span class="error"><?php echo $room_type_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Room Capacity</label>
                    <select name="room_capacity" class="form-control <?php echo (!empty($room_capacity_err)) ? 'is-invalid' : ''; ?>">
                        <option value="" <?php echo empty($room_capacity) ? 'selected' : ''; ?>>Select Room Capacity</option>
                        <option value="Single" <?php echo ($room_capacity == 'Single') ? 'selected' : ''; ?>>Single</option>
                        <option value="Double" <?php echo ($room_capacity == 'Double') ? 'selected' : ''; ?>>Double</option>
                        <option value="Family" <?php echo ($room_capacity == 'Family') ? 'selected' : ''; ?>>Family</option>
                    </select>
                    <span class="error"><?php echo $room_capacity_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Payment Type</label>
                    <select name="payment_type" class="form-control <?php echo (!empty($payment_type_err)) ? 'is-invalid' : ''; ?>">
                        <option value="" <?php echo empty($payment_type) ? 'selected' : ''; ?>>Select Payment Type</option>
                        <option value="Cash" <?php echo ($payment_type == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                        <option value="Check" <?php echo ($payment_type == 'Check') ? 'selected' : ''; ?>>Check</option>
                        <option value="Credit Card" <?php echo ($payment_type == 'Credit Card') ? 'selected' : ''; ?>>Credit Card</option>
                    </select>
                    <span class="error"><?php echo $payment_type_err; ?></span>
                  
                <div class="form-group">
                    <input type="submit" class="submit-btn" value="Add Reservation">
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById("addReservationModal");
        const btn = document.getElementById("addReservationBtn");
        const span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html> 
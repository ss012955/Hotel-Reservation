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

// Define variables and initialize with empty values
$room_number = $capacity = $type_id = $rate_regular = $rate_deluxe = $rate_suite = $status = "";
$room_number_err = $capacity_err = $type_id_err = $rate_regular_err = $rate_deluxe_err = $rate_suite_err = $status_err = "";

// Fetch room types for dropdown
$sql = "SELECT * FROM room_types ORDER BY name";
$stmt = $pdo->query($sql);
$room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processing form data when form is submitted for adding a new room
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"]) && $_POST["action"] == "add") {
    
    // Validate room number
    if (empty(trim($_POST["room_number"]))) {
        $room_number_err = "Please enter room number.";
    } else {
        // Check if room number already exists
        $sql = "SELECT id FROM rooms WHERE room_number = :room_number";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":room_number", $param_room_number, PDO::PARAM_STR);
        $param_room_number = trim($_POST["room_number"]);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $room_number_err = "This room number is already taken.";
        } else {
            $room_number = trim($_POST["room_number"]);
        }
    }
    
    // Validate capacity
    if (empty($_POST["capacity"])) {
        $capacity_err = "Please select room capacity.";
    } else {
        $capacity = $_POST["capacity"];
    }
    
    // Validate type
    if (empty($_POST["type_id"])) {
        $type_id_err = "Please select room type.";
    } else {
        $type_id = $_POST["type_id"];
    }
    
    // Validate rates
    if (empty(trim($_POST["rate_regular"]))) {
        $rate_regular_err = "Please enter regular rate.";
    } elseif (!is_numeric(trim($_POST["rate_regular"])) || floatval(trim($_POST["rate_regular"])) <= 0) {
        $rate_regular_err = "Please enter a valid rate.";
    } else {
        $rate_regular = trim($_POST["rate_regular"]);
    }
    
    if (empty(trim($_POST["rate_deluxe"]))) {
        $rate_deluxe_err = "Please enter deluxe rate.";
    } elseif (!is_numeric(trim($_POST["rate_deluxe"])) || floatval(trim($_POST["rate_deluxe"])) <= 0) {
        $rate_deluxe_err = "Please enter a valid rate.";
    } else {
        $rate_deluxe = trim($_POST["rate_deluxe"]);
    }
    
    if (empty(trim($_POST["rate_suite"]))) {
        $rate_suite_err = "Please enter suite rate.";
    } elseif (!is_numeric(trim($_POST["rate_suite"])) || floatval(trim($_POST["rate_suite"])) <= 0) {
        $rate_suite_err = "Please enter a valid rate.";
    } else {
        $rate_suite = trim($_POST["rate_suite"]);
    }
    
    // Validate status
    if (empty($_POST["status"])) {
        $status_err = "Please select room status.";
    } else {
        $status = $_POST["status"];
    }
    
    // Check input errors before inserting in database
    if (empty($room_number_err) && empty($capacity_err) && empty($type_id_err) && 
        empty($rate_regular_err) && empty($rate_deluxe_err) && empty($rate_suite_err) && empty($status_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO rooms (room_number, capacity, type_id, rate_regular, rate_deluxe, rate_suite, status) 
                VALUES (:room_number, :capacity, :type_id, :rate_regular, :rate_deluxe, :rate_suite, :status)";
        
        if ($stmt = $pdo->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":room_number", $param_room_number, PDO::PARAM_STR);
            $stmt->bindParam(":capacity", $param_capacity, PDO::PARAM_STR);
            $stmt->bindParam(":type_id", $param_type_id, PDO::PARAM_INT);
            $stmt->bindParam(":rate_regular", $param_rate_regular, PDO::PARAM_STR);
            $stmt->bindParam(":rate_deluxe", $param_rate_deluxe, PDO::PARAM_STR);
            $stmt->bindParam(":rate_suite", $param_rate_suite, PDO::PARAM_STR);
            $stmt->bindParam(":status", $param_status, PDO::PARAM_STR);
            
            // Set parameters
            $param_room_number = $room_number;
            $param_capacity = $capacity;
            $param_type_id = $type_id;
            $param_rate_regular = $rate_regular;
            $param_rate_deluxe = $rate_deluxe;
            $param_rate_suite = $rate_suite;
            $param_status = $status;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Redirect to room list page
                header("location: manage_rooms.php");
                exit();
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            unset($stmt);
        }
    }
}

// Process delete room
if (isset($_GET["delete"]) && !empty($_GET["delete"])) {
    // Prepare a delete statement
    $sql = "DELETE FROM rooms WHERE id = :id";
    
    if ($stmt = $pdo->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
        
        // Set parameters
        $param_id = trim($_GET["delete"]);
        
        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Redirect to room list page
            header("location: manage_rooms.php");
            exit();
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        // Close statement
        unset($stmt);
    }
}

// Process update room status
if (isset($_GET["status"]) && !empty($_GET["status"]) && isset($_GET["id"]) && !empty($_GET["id"])) {
    // Get status and room ID
    $status = trim($_GET["status"]);
    $room_id = trim($_GET["id"]);
    
    // If status is Available, clear the guest_name field
    if ($status == 'Available') {
        $sql = "UPDATE rooms SET status = :status, guest_name = NULL WHERE id = :id";
    } else {
        $sql = "UPDATE rooms SET status = :status WHERE id = :id";
    }
    
    if ($stmt = $pdo->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":status", $param_status, PDO::PARAM_STR);
        $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);
        
        // Set parameters
        $param_status = $status;
        $param_id = $room_id;
        
        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Redirect to room list page
            header("location: manage_rooms.php");
            exit();
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        
        // Close statement
        unset($stmt);
    }
}

// Fetch all rooms with type information
$sql = "SELECT r.*, t.name as type_name 
        FROM rooms r 
        JOIN room_types t ON r.type_id = t.id 
        ORDER BY r.room_number";
$stmt = $pdo->query($sql);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            color: white;
        }
        .status-available {
            background-color: #2ecc71;
        }
        .status-occupied {
            background-color: #e74c3c;
        }
        .status-maintenance {
            background-color: #f39c12;
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
        .status-btn {
            background-color: #3498db;
            margin: 5px;
        }
        .delete-btn {
            background-color: #e74c3c;
            margin: 5px;
        }
        .add-btn {
            display: inline-block;
            background-color: #2ecc71;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-bottom: 20px;
            cursor: pointer;
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
        }
        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
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
                <a href="manage_reservations.php">Reservations</a>
                <a href="manage_rooms.php" class="active">Rooms</a>
                <a href="manage_users.php">Users</a>
                <a href="logout.php?redirect=index.php">View Website</a>
            </div>
        </div>
        <div class="content">
            <div class="header">
                <div class="user-info">
                    <h2>Manage Rooms</h2>
                    <span class="user-role user-role-<?php echo strtolower($_SESSION["role"]); ?>"><?php echo htmlspecialchars($_SESSION["role"]); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
            
            <button id="addRoomBtn" class="add-btn">Add New Room</button>
            
            <?php if (count($rooms) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Room #</th>
                            <th>Capacity</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Guest</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                <td><?php echo htmlspecialchars($room['capacity']); ?></td>
                                <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower($room['status']); ?>">
                                        <?php echo $room['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($room['guest_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="action-btn status-btn">Change Status</a>
                                        <div class="dropdown-content">
                                            <a href="manage_rooms.php?id=<?php echo $room['id']; ?>&status=Available">Available</a>
                                            <a href="manage_rooms.php?id=<?php echo $room['id']; ?>&status=Occupied">Occupied</a>
                                            <a href="manage_rooms.php?id=<?php echo $room['id']; ?>&status=Maintenance">Maintenance</a>
                                        </div>
                                    </div>
                                    <a href="manage_rooms.php?delete=<?php echo $room['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Delete this room permanently?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No rooms found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Room Modal -->
    <div id="addRoomModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Room</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Room Number</label>
                    <input type="text" name="room_number" class="form-control <?php echo (!empty($room_number_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $room_number; ?>">
                    <span class="error"><?php echo $room_number_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Room Capacity</label>
                    <select name="capacity" class="form-control <?php echo (!empty($capacity_err)) ? 'is-invalid' : ''; ?>">
                        <option value="" <?php echo empty($capacity) ? 'selected' : ''; ?>>Select Capacity</option>
                        <option value="Single" <?php echo ($capacity == 'Single') ? 'selected' : ''; ?>>Single</option>
                        <option value="Double" <?php echo ($capacity == 'Double') ? 'selected' : ''; ?>>Double</option>
                        <option value="Family" <?php echo ($capacity == 'Family') ? 'selected' : ''; ?>>Family</option>
                    </select>
                    <span class="error"><?php echo $capacity_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Room Type</label>
                    <select name="type_id" class="form-control <?php echo (!empty($type_id_err)) ? 'is-invalid' : ''; ?>">
                        <option value="" <?php echo empty($type_id) ? 'selected' : ''; ?>>Select Type</option>
                        <?php foreach ($room_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo ($type_id == $type['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="error"><?php echo $type_id_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Regular Rate ($)</label>
                    <input type="text" name="rate_regular" class="form-control <?php echo (!empty($rate_regular_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $rate_regular; ?>">
                    <span class="error"><?php echo $rate_regular_err; ?></span>
                </div>
                <div class="form-group">
                    <label>De Luxe Rate ($)</label>
                    <input type="text" name="rate_deluxe" class="form-control <?php echo (!empty($rate_deluxe_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $rate_deluxe; ?>">
                    <span class="error"><?php echo $rate_deluxe_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Suite Rate ($)</label>
                    <input type="text" name="rate_suite" class="form-control <?php echo (!empty($rate_suite_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $rate_suite; ?>">
                    <span class="error"><?php echo $rate_suite_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control <?php echo (!empty($status_err)) ? 'is-invalid' : ''; ?>">
                        <option value="" <?php echo empty($status) ? 'selected' : ''; ?>>Select Status</option>
                        <option value="Available" <?php echo ($status == 'Available') ? 'selected' : ''; ?>>Available</option>
                        <option value="Occupied" <?php echo ($status == 'Occupied') ? 'selected' : ''; ?>>Occupied</option>
                        <option value="Maintenance" <?php echo ($status == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                    <span class="error"><?php echo $status_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="submit-btn" value="Add Room">
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById("addRoomModal");
        const btn = document.getElementById("addRoomBtn");
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

        // Dropdown functionality for status change
        const dropdowns = document.querySelectorAll(".dropdown");
        dropdowns.forEach(dropdown => {
            const btn = dropdown.querySelector(".status-btn");
            const content = dropdown.querySelector(".dropdown-content");
            
            // Add custom styling
            content.style.display = "none";
            content.style.position = "absolute";
            content.style.backgroundColor = "#f9f9f9";
            content.style.minWidth = "120px";
            content.style.boxShadow = "0px 8px 16px 0px rgba(0,0,0,0.2)";
            content.style.zIndex = "1";
            content.style.borderRadius = "4px";
            
            // Style the links
            const links = content.querySelectorAll("a");
            links.forEach(link => {
                link.style.color = "black";
                link.style.padding = "12px 16px";
                link.style.textDecoration = "none";
                link.style.display = "block";
                link.style.textAlign = "left";
                
                // Hover effect
                link.addEventListener("mouseover", function() {
                    this.style.backgroundColor = "#f1f1f1";
                });
                link.addEventListener("mouseout", function() {
                    this.style.backgroundColor = "transparent";
                });
            });
            
            // Toggle dropdown
            btn.addEventListener("click", function() {
                const isDisplayed = content.style.display === "block";
                content.style.display = isDisplayed ? "none" : "block";
            });
            
            // Close dropdown when clicking outside
            window.addEventListener("click", function(event) {
                if (!event.target.matches('.status-btn')) {
                    content.style.display = "none";
                }
            });
        });
    </script>
</body>
</html> 
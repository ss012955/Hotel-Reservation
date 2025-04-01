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

// Get reservation statistics
$stats = [];

// Total reservations
$sql = "SELECT COUNT(*) as total FROM reservations";
$stmt = $pdo->query($sql);
$stats['total_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Active reservations
$sql = "SELECT COUNT(*) as active FROM reservations WHERE status = 'Confirmed'";
$stmt = $pdo->query($sql);
$stats['active_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];

// Completed reservations
$sql = "SELECT COUNT(*) as completed FROM reservations WHERE status = 'Completed'";
$stmt = $pdo->query($sql);
$stats['completed_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['completed'];

// Cancelled reservations
$sql = "SELECT COUNT(*) as cancelled FROM reservations WHERE status = 'Cancelled'";
$stmt = $pdo->query($sql);
$stats['cancelled_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['cancelled'];

// Total revenue
$sql = "SELECT SUM(total_cost) as revenue FROM reservations WHERE status != 'Cancelled' AND is_paid = TRUE";
$stmt = $pdo->query($sql);
$revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
$stats['total_revenue'] = $revenue ? $revenue : 0;

// Room availability
$sql = "SELECT 
            SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'Occupied' THEN 1 ELSE 0 END) as occupied,
            SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) as maintenance,
            COUNT(*) as total
        FROM rooms";
$stmt = $pdo->query($sql);
$room_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['rooms'] = $room_stats;

// Get recent reservations
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Validate sort column to prevent SQL injection
$allowed_columns = ['id', 'customer_name', 'email', 'room_type', 'from_date', 'total_cost', 'status', 'is_paid', 'created_at'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'created_at';
}

// Validate sort order
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

$sql = "SELECT * FROM reservations ORDER BY $sort_column $sort_order LIMIT 5";
$stmt = $pdo->query($sql);
$recent_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .card-title {
            margin-top: 0;
            color: #333;
            font-size: 18px;
            font-weight: bold;
        }
        .card-value {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
            margin: 10px 0;
        }
        .card-footer {
            font-size: 14px;
            color: #777;
        }
        .recent-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .recent-table th, .recent-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .recent-table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
        }
        .recent-table tr:hover {
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
                <a href="admin_dashboard.php" class="active">Dashboard</a>
                <a href="manage_reservations.php">Reservations</a>
                <a href="manage_rooms.php">Rooms</a>
                <a href="manage_users.php">Users</a>
                <a href="logout.php?redirect=index.php">View Website</a>
            </div>
        </div>
        <div class="content">
            <div class="header">
                <div class="user-info">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></h2>
                    <span class="user-role user-role-<?php echo strtolower($_SESSION["role"]); ?>"><?php echo htmlspecialchars($_SESSION["role"]); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
            
            <h3>Dashboard Overview</h3>
            <div class="dashboard-cards">
                <div class="card">
                    <h4 class="card-title">Total Reservations</h4>
                    <div class="card-value"><?php echo $stats['total_reservations']; ?></div>
                    <div class="card-footer">All time reservations</div>
                </div>
                <div class="card">
                    <h4 class="card-title">Active Reservations</h4>
                    <div class="card-value"><?php echo $stats['active_reservations']; ?></div>
                    <div class="card-footer">Currently confirmed</div>
                </div>
                <div class="card">
                    <h4 class="card-title">Total Revenue</h4>
                    <div class="card-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <div class="card-footer">From all reservations</div>
                </div>
                <div class="card">
                    <h4 class="card-title">Room Availability</h4>
                    <div class="card-value"><?php echo $stats['rooms']['available']; ?> / <?php echo $stats['rooms']['total']; ?></div>
                    <div class="card-footer">Rooms available for booking</div>
                </div>
            </div>
            
            <h3>Recent Reservations</h3>
            <?php if (!empty($recent_reservations)): ?>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th><a href="?sort=id&order=<?php echo $sort_column === 'id' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'id' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">ID</a></th>
                            <th><a href="?sort=customer_name&order=<?php echo $sort_column === 'customer_name' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'customer_name' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Customer</a></th>
                            <th><a href="?sort=email&order=<?php echo $sort_column === 'email' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'email' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Email</a></th>
                            <th><a href="?sort=room_type&order=<?php echo $sort_column === 'room_type' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'room_type' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Room</a></th>
                            <th><a href="?sort=from_date&order=<?php echo $sort_column === 'from_date' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'from_date' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Dates</a></th>
                            <th><a href="?sort=total_cost&order=<?php echo $sort_column === 'total_cost' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'total_cost' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Total</a></th>
                            <th><a href="?sort=status&order=<?php echo $sort_column === 'status' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'status' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Status</a></th>
                            <th><a href="?sort=is_paid&order=<?php echo $sort_column === 'is_paid' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link sort-indicator <?php echo $sort_column === 'is_paid' ? ($sort_order === 'ASC' ? 'sort-asc' : 'sort-desc') : ''; ?>">Payment</a></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_reservations as $reservation): ?>
                            <tr>
                                <td><?php echo $reservation['id']; ?></td>
                                <td><?php echo htmlspecialchars($reservation['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['email']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['room_capacity'] . ' ' . $reservation['room_type']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['from_date'] . ' to ' . $reservation['to_date']); ?></td>
                                <td>$<?php echo number_format($reservation['total_cost'], 2); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower($reservation['status']); ?>">
                                        <?php echo $reservation['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status <?php echo $reservation['is_paid'] ? 'status-confirmed' : 'status-cancelled'; ?>">
                                        <?php echo $reservation['is_paid'] ? 'Paid' : 'Unpaid'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No recent reservations found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 
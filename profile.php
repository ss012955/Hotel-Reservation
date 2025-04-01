<?php
    $company_description = "HorsePlaying Hotel offers world-class accommodations and exceptional service in top destinations around the globe. Our mission is to provide guests with an unforgettable experience, blending comfort, elegance, and hospitality.";
    $company_mission = "To create the most comfortable and luxurious stay for travelers, ensuring exceptional service and premium hospitality.";
    $company_vision = "To be the leading luxury hotel brand recognized globally for excellence in service and hospitality.";
?>

<!DOCTYPE html>
<html>
<head>
    <title>HorsePlaying Hotel</title>
    <link rel="icon" type="image/x-icon" href="horse.jpg">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 20px auto;
        }
        h1, h2 {
            color: #333;
        }
        p, ul {
            font-size: 18px;
            color: #555;
            line-height: 1.6;
        }
        .section {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
<?php
    include 'header.php'; 
?>
    <div class="container">
        <div class="section">
            <h2>About Us</h2>
            <p><?php echo $company_description; ?></p>
        </div>
        
        <div class="section">
            <h2>Our Mission</h2>
            <p><?php echo $company_mission; ?></p>
        </div>
        
        <div class="section">
            <h2>Our Vision</h2>
            <p><?php echo $company_vision; ?></p>
        </div>
        
    </div>
</body>
</html>
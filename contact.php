<?php
    $contact_email = "contact@horseplaying.com";
    $contact_phone = "+1 234 567 890";
    $contact_address = "123 Bahay Namin Maliit Lamang";
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
            max-width: 600px;
            margin: 20px auto;
        }
        h1, h2 {
            color: #333;
        }
        p {
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
        <h1>Contact Us</h1>
        
        <div class="section">
            <h2>Email</h2>
            <p><?php echo $contact_email; ?></p>
        </div>
        
        <div class="section">
            <h2>Phone</h2>
            <p><?php echo $contact_phone; ?></p>
        </div>
        
        <div class="section">
            <h2>Address</h2>
            <p><?php echo $contact_address; ?></p>
        </div>
    </div>
</body>
</html>

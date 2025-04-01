<?php
session_start();
// Include database configuration
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_name = $_POST['customer_name'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $room_type = $_POST['room_type'];
    $room_capacity = $_POST['room_capacity'];
    $payment_type = $_POST['payment_type'];
    
    // Handle the date range from the single date picker
    $date_range = $_POST['date_range'];
    $dates = explode(" to ", $date_range);
    $from_date_str = trim($dates[0]);
    $to_date_str = isset($dates[1]) ? trim($dates[1]) : $from_date_str;
    
    // Debug the incoming dates
    error_log("From date string: " . $from_date_str);
    error_log("To date string: " . $to_date_str);
    
    // Convert dates to proper format for database
    $from_date = date('Y-m-d', strtotime($from_date_str));
    $to_date = date('Y-m-d', strtotime($to_date_str));
    
    // Debug the converted dates
    error_log("Converted from date: " . $from_date);
    error_log("Converted to date: " . $to_date);
    
    // Calculate days between dates
    $from_timestamp = strtotime($from_date);
    $to_timestamp = strtotime($to_date);
    $days = ceil(($to_timestamp - $from_timestamp) / (60 * 60 * 24));
    
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
    
    // Store reservation data in session for confirmation
    $_SESSION['temp_reservation'] = [
        'customer_name' => $customer_name,
        'contact_number' => $contact_number,
        'email' => $email,
        'room_type' => $room_type,
        'room_capacity' => $room_capacity,
        'payment_type' => $payment_type,
        'from_date' => $from_date,
        'to_date' => $to_date,
        'total_cost' => $total_cost,
        'days' => $days,
        'base_rate' => $base_rate
    ];
    
    // Redirect to confirmation page
    header("Location: confirm_reservation.php");
    exit();
} else {
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
            box-sizing: border-box;
        }
        .main-container {
            display: flex;
            flex-direction: row;
            justify-content: center;
            max-width: 1200px;
            margin: 0 auto;
            gap: 30px;
        }
        .form-container, .calendar-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            flex: 1;
            min-width: 350px;
        }
        h2 {
            color: #333;
            text-align: center;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
            text-align: center;
        }
        input[type="text"], input[type="email"], .flatpickr-input {
            width: 91%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            margin-left: auto;
            margin-right: auto;
            display: block;
        }
        select{
            width: 95%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            margin-left: auto;
            margin-right: auto;
            display: block;
        }
        input[type="submit"] {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        } 
        input[type="reset"]{
            background-color:rgba(68, 71, 72, 0.54);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .form-actions {
            text-align: center;
        }
        /* Redesigned styles for the calendar */
        .flatpickr-calendar.inline {
            width: 100%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 0 auto 20px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 16px;
            background: #fff;
            max-width: 360px;
        }
        
        .flatpickr-months {
            padding: 0 0 0 0;
            background: #fff;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: left;
        }
        
     
        .flatpickr-current-month {
            display: flex !important;
            align-items: left !important;
            justify-content: left !important;
            gap: 20px !important;         
            padding: 0 0 !important;
        }

       
        .flatpickr-current-month .cur-month {
            font-size: 1.4em !important;   
            font-weight: bold !important;
        }

      
        .flatpickr-current-month .numInputWrapper {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;        
            position: end !important;  
        }

     
        .flatpickr-current-month input.cur-year {
            padding:0px !important;
            font-size: 1.3em !important;   
            color: #333 !important;
            width: 100% !important;       
            text-align: left !important;
            border: none !important;
            background: transparent !important;
        }


        
        .flatpickr-months .flatpickr-prev-month,
        .flatpickr-months .flatpickr-next-month {
            padding: 20px !important;
            fill: #666 !important;
            cursor: pointer !important;
            transition: fill 0.3s ease !important;
        }

        .flatpickr-months.numInputWrapper {
            position: end !important;   
            height: 30px !important;     
        }
        
        .flatpickr-months .flatpickr-prev-month:hover,
        .flatpickr-months .flatpickr-next-month:hover {
            fill: #333;
        }
        
        .flatpickr-weekdays {
            background: #fff;
            padding: 8px 0;
            display: flex;
            justify-content: space-around;
        }
        
        span.flatpickr-weekday {
            color: #666;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .flatpickr-day {
            margin: 2px;
            height: 42px;
            line-height: 42px;
            border-radius: 8px !important;
            font-weight: 500;
            font-size: 0.95em;
            color: #333;
            border: 1px solid transparent;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        .flatpickr-day.today {
            border-color: #3498db !important;
            background: #f8f9fa !important;
        }
        
        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange {
            background: #3498db !important;
            border-color: #3498db !important;
            color: white !important;
            font-weight: 600;
        }
        
       
        .flatpickr-day:hover {
            background: #f0f8ff !important;
            border-color: #3498db !important;
        }
        
        .flatpickr-day.prevMonthDay,
        .flatpickr-day.nextMonthDay {
            color: #999;
        }
        
        .date-range-container {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .date-range-info {
            background-color: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 15px;
            color: #444;
            text-align: center;
            width: 90%;
            font-weight: 500;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
        }
        
        .flatpickr-input {
            font-size: 16px;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 100%;
            box-sizing: border-box;
            color: #333;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .flatpickr-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.2);
        }
     
        
    </style>
    <!-- Flatpickr CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="main-container">
        <div class="form-container">
            <h2>Reservation Form</h2>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="reservationForm">
                <label for="customer_name">Customer Name:</label>
                <input type="text" name="customer_name" id="customer_name" required>
                
                <label for="contact_number">Contact Number:</label>
                <input type="text" name="contact_number" id="contact_number" required>
                
                <label for="email">Email Address:</label>
                <input type="email" name="email" id="email" required>
                
                <label for="room_type">Room Type:</label>
                <select name="room_type" id="room_type" required>
                    <option value="" disabled selected>Select Room Type</option>
                    <option value="Regular">Regular</option>
                    <option value="De Luxe">De Luxe</option>
                    <option value="Suite">Suite</option>
                </select>
                
                <label for="room_capacity">Room Capacity:</label>
                <select name="room_capacity" id="room_capacity" required>
                    <option value="" disabled selected>Select Room Capacity</option>
                    <option value="Single">Single</option>
                    <option value="Double">Double</option>
                    <option value="Family">Family</option>
                </select>
                
                <label for="payment_type">Payment Type:</label>
                <select name="payment_type" id="payment_type" required>
                    <option value="" disabled selected>Select Payment Type</option>
                    <option value="Cash">Cash</option>
                    <option value="Check">Check</option>
                    <option value="Credit Card">Credit Card</option>
                </select>
                
                <!-- Hidden input for date range -->
                <input type="hidden" id="date_range_hidden" name="date_range" required>
                
                <div class="form-actions">
                    <input type="submit" value="Submit Reservation">
                    <input type="reset" value="Clear Entry">
                </div>
            </form>
        </div>
        <div class="calendar-container">
            <h2>Select Your Dates</h2>
            <div class="date-range-container">
                <input type="text" id="date_range" placeholder="Select dates..." required>
                <div id="date-range-info" class="date-range-info">Please select your check-in and check-out dates</div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('reservationForm');
        
        // Initialize Flatpickr date range picker
        const dateRangePicker = flatpickr("#date_range", {
            mode: "range",
            minDate: "today",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            showMonths: 1,
            static: true,
            inline: true,
            disableMobile: true,
            onChange: function(selectedDates, dateStr, instance) {
                // Update the hidden input with the selected dates
                document.getElementById('date_range_hidden').value = dateStr;
                
                // Update the date range info display
                const infoElement = document.getElementById('date-range-info');
                
                // Process based on number of dates selected
                if (selectedDates.length === 0) {
                    infoElement.textContent = 'Please select your check-in and check-out dates';
                } else if (selectedDates.length === 1) {
                    infoElement.textContent = 'Select another date to complete your reservation';
                } else if (selectedDates.length >= 2) {
                    let checkIn = new Date(Math.min(selectedDates[0], selectedDates[1]));
                    let checkOut = new Date(Math.max(selectedDates[0], selectedDates[1]));
                    
                    if (selectedDates[0] > selectedDates[1]) {
                        instance.setDate([checkIn, checkOut]);
                    }
                    
                    const nights = calculateNights(checkIn, checkOut);
                    let roomType = document.querySelector('select[name="room_type"]').value || 'Regular';
                    let roomCapacity = document.querySelector('select[name="room_capacity"]').value || 'Single';
                    let paymentType = document.querySelector('select[name="payment_type"]').value || 'Cash';
                    
                    const totalCost = calculateTotalCost(nights, roomType, roomCapacity, paymentType);
                    
                    infoElement.textContent = formatDate(checkIn) + ' to ' + formatDate(checkOut) + 
                                             ' • ' + nights + ' night' + (nights > 1 ? 's' : '') + 
                                             ' • $' + totalCost;
                }
            }
        });

        // Form submission validation
        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission
            
            // Check if dates are selected
            const dateRangeValue = document.getElementById('date_range_hidden').value;
            if (!dateRangeValue) {
                alert('Please select your check-in and check-out dates before submitting.');
                return;
            }
            
            // Check if all required fields are filled
            const requiredFields = form.querySelectorAll('[required]');
            let allFieldsFilled = true;
            
            requiredFields.forEach(field => {
                if (!field.value) {
                    allFieldsFilled = false;
                    field.style.borderColor = '#e74c3c';
                } else {
                    field.style.borderColor = '#ccc';
                }
            });
            
            if (!allFieldsFilled) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // If all validations pass, submit the form
            form.submit();
        });

        // Reset form handler
        document.querySelector('input[type="reset"]').addEventListener('click', function() {
            dateRangePicker.clear();
            document.getElementById('date-range-info').textContent = 'Please select your check-in and check-out dates';
            document.getElementById('date_range_hidden').value = '';
            
            // Reset border colors
            const fields = form.querySelectorAll('input, select');
            fields.forEach(field => {
                field.style.borderColor = '#ccc';
            });
        });
        
        // Helper functions
        function formatDate(date) {
            const options = { weekday: 'short', month: 'short', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }
        
        function calculateNights(checkIn, checkOut) {
            const timeDiff = checkOut.getTime() - checkIn.getTime();
            return Math.ceil(timeDiff / (1000 * 3600 * 24));
        }

        function calculateTotalCost(nights, roomType, roomCapacity, paymentType) {
            const rates = {
                'Single': {'Regular': 100, 'De Luxe': 300, 'Suite': 500},
                'Double': {'Regular': 200, 'De Luxe': 500, 'Suite': 800},
                'Family': {'Regular': 500, 'De Luxe': 750, 'Suite': 1000}
            };

            let baseRate = 100 * nights;
            
            if (rates[roomCapacity] && rates[roomCapacity][roomType]) {
                baseRate = rates[roomCapacity][roomType] * nights;
            }

            if (paymentType === 'Check') {
                baseRate *= 1.05;
            } else if (paymentType === 'Credit Card') {
                baseRate *= 1.10;
            } else if (paymentType === 'Cash') {
                if (nights >= 6) {
                    baseRate *= 0.85;
                } else if (nights >= 3) {
                    baseRate *= 0.90;
                }
            }

            return baseRate.toFixed(2);
        }
    });
    </script>
</body>
</html>
<?php
}
?>
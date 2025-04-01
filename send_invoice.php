<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendInvoiceEmail($reservation, $reservation_id) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'xxxsean.russel@gmail.com'; // Your email
        $mail->Password = 'uccc ihpo hetj fjnf';    // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Enable debug output for troubleshooting
        $mail->SMTPDebug = 2;    // Enable verbose debug output
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer debug: $str");
        };

        // Recipients
        $mail->setFrom('your-email@gmail.com', 'HorsePlaying Hotel');
        $mail->addAddress($reservation['email'], $reservation['customer_name']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your HorsePlaying Hotel Reservation Invoice #' . $reservation_id;

        // Generate invoice HTML with improved design
        $invoice_html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #2c3e50;
                    color: white;
                    padding: 30px;
                    text-align: center;
                    border-radius: 8px 8px 0 0;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                }
                .header h2 {
                    margin: 10px 0 0;
                    font-size: 18px;
                    font-weight: normal;
                }
                .content {
                    background-color: #ffffff;
                    padding: 30px;
                    border: 1px solid #e0e0e0;
                    border-radius: 0 0 8px 8px;
                }
                .reservation-details {
                    margin-bottom: 30px;
                }
                .detail-row {
                    padding: 12px 0;
                    border-bottom: 1px solid #eee;
                }
                .detail-row:last-child {
                    border-bottom: none;
                }
                .cost-breakdown {
                    background-color: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                }
                .cost-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 8px 0;
                }
                .total-cost {
                    font-size: 24px;
                    color: #2ecc71;
                    text-align: right;
                    padding: 20px 0;
                    border-top: 2px solid #eee;
                    margin-top: 20px;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 2px solid #eee;
                    color: #666;
                }
                .contact-info {
                    background-color: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    margin-top: 20px;
                    text-align: center;
                }
                .thank-you {
                    font-size: 18px;
                    color: #2c3e50;
                    text-align: center;
                    margin: 30px 0;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <h1>HorsePlaying Hotel</h1>
                    <h2>Reservation Confirmation #' . $reservation_id . '</h2>
                </div>
                
                <div class="content">
                    <div class="thank-you">
                        Thank you for choosing HorsePlaying Hotel!
                    </div>
                    
                    <div class="reservation-details">
                        <div class="detail-row">
                            <strong>Guest Name:</strong> ' . htmlspecialchars($reservation['customer_name']) . '
                        </div>
                        <div class="detail-row">
                            <strong>Contact Number:</strong> ' . htmlspecialchars($reservation['contact_number']) . '
                        </div>
                        <div class="detail-row">
                            <strong>Email:</strong> ' . htmlspecialchars($reservation['email']) . '
                        </div>
                        <div class="detail-row">
                            <strong>Room Type:</strong> ' . htmlspecialchars($reservation['room_type']) . '
                        </div>
                        <div class="detail-row">
                            <strong>Room Capacity:</strong> ' . htmlspecialchars($reservation['room_capacity']) . '
                        </div>
                        <div class="detail-row">
                            <strong>Check-in Date:</strong> ' . date('F j, Y', strtotime($reservation['from_date'])) . '
                        </div>
                        <div class="detail-row">
                            <strong>Check-out Date:</strong> ' . date('F j, Y', strtotime($reservation['to_date'])) . '
                        </div>
                        <div class="detail-row">
                            <strong>Payment Method:</strong> ' . htmlspecialchars($reservation['payment_type']) . '
                        </div>
                    </div>
                    
                    <div class="cost-breakdown">
                        <h3>Cost Breakdown</h3>
                        <div class="cost-row">
                            <span>Base Rate (' . $reservation['days'] . ' nights)</span>
                            <span>$' . number_format($reservation['base_rate'], 2) . '</span>
                        </div>';

        // Add payment type fees or discounts
        if ($reservation['payment_type'] == 'Check') {
            $invoice_html .= '
                        <div class="cost-row">
                            <span>Check Payment Fee (5%)</span>
                            <span>$' . number_format($reservation['base_rate'] * 0.05, 2) . '</span>
                        </div>';
        } elseif ($reservation['payment_type'] == 'Credit Card') {
            $invoice_html .= '
                        <div class="cost-row">
                            <span>Credit Card Fee (10%)</span>
                            <span>$' . number_format($reservation['base_rate'] * 0.10, 2) . '</span>
                        </div>';
        } elseif ($reservation['payment_type'] == 'Cash' && $reservation['days'] >= 3) {
            $discount_percent = $reservation['days'] >= 6 ? '15%' : '10%';
            $discount_amount = $reservation['base_rate'] * ($reservation['days'] >= 6 ? 0.15 : 0.10);
            $invoice_html .= '
                        <div class="cost-row">
                            <span>Cash Discount (' . $discount_percent . ')</span>
                            <span>-$' . number_format($discount_amount, 2) . '</span>
                        </div>';
        }

        $invoice_html .= '
                        <div class="total-cost">
                            <strong>Total Cost: $' . number_format($reservation['total_cost'], 2) . '</strong>
                        </div>
                    </div>
                    
                    <div class="contact-info">
                        <h3>Need Assistance?</h3>
                        <p>Contact our 24/7 guest services:</p>
                        <p>+1 234 567 890<br>
                        contact@horseplaying.com</p>
                    </div>
                    
                    <div class="footer">
                        <p>We look forward to welcoming you to HorsePlaying Hotel!</p>
                        <p>Please keep this email for your records.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>';

        $mail->Body = $invoice_html;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $invoice_html));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error sending invoice email: " . $e->getMessage());
        return false;
    }
}
?> 
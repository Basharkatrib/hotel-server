<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 15px;
        }
        .booking-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #e0e0e0;
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
        .detail-value {
            color: #333;
            text-align: right;
        }
        .total-row {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #667eea;
        }
        .total-row .detail-value {
            font-size: 1.4em;
            font-weight: bold;
            color: #667eea;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: scale(1.05);
        }
        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box strong {
            display: block;
            margin-bottom: 8px;
            color: #1976D2;
        }
        .info-box ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .footer {
            text-align: center;
            padding: 30px;
            color: #777;
            font-size: 14px;
            background-color: #f9f9f9;
            border-top: 1px solid #e0e0e0;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer .small {
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🎉 Booking Confirmed!</h1>
            <p>Your reservation has been successfully confirmed</p>
        </div>

        <div class="content">
            <p class="greeting">Hello <strong>{{ $userName }}</strong>,</p>
            <p>We're excited to confirm your booking! Here are your reservation details:</p>

            <div class="booking-details">
                <div class="detail-row">
                    <span class="detail-label">📋 Booking Number:</span>
                    <span class="detail-value"><strong>{{ $bookingNumber }}</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">🏨 Hotel:</span>
                    <span class="detail-value">{{ $hotelName }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">🛏️ Room:</span>
                    <span class="detail-value">{{ $roomName }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">📅 Check-in:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($checkIn)->format('F d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">📅 Check-out:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($checkOut)->format('F d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">💳 Payment Method:</span>
                    <span class="detail-value">Card ending in {{ $cardLast4 }}</span>
                </div>
                <div class="detail-row total-row">
                    <span class="detail-label">💰 Total Amount:</span>
                    <span class="detail-value">${{ number_format($totalAmount, 2) }}</span>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/my-bookings" class="button">
                    View My Bookings
                </a>
            </div>

            <div class="info-box">
                <strong>What's Next?</strong>
                <ul>
                    <li>Save this email for your records</li>
                    <li>Present your booking number at check-in</li>
                    <li>Arrive after 2:00 PM on your check-in date</li>
                    <li>Contact us if you need any assistance</li>
                </ul>
            </div>

            <p style="margin-top: 30px;">
                If you have any questions or need to make changes to your reservation, 
                please don't hesitate to contact our support team.
            </p>
        </div>

        <div class="footer">
            <p><strong>Thank you for choosing us!</strong></p>
            <p>We look forward to welcoming you.</p>
            <p class="small">
                This is an automated email. Please do not reply to this message.
            </p>
        </div>
    </div>
</body>
</html>

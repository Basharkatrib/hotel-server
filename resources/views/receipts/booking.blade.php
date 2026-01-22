<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Booking Receipt #{{ $booking->id }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 5px;
        }
        .receipt-title {
            font-size: 18px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .details-section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            background: #f1f5f9;
            padding: 8px 12px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .grid {
            display: block;
            width: 100%;
        }
        .col {
            display: inline-block;
            vertical-align: top;
            width: 48%;
        }
        .label {
            font-weight: bold;
            color: #64748b;
            font-size: 12px;
            display: block;
            margin-bottom: 2px;
        }
        .value {
            font-size: 14px;
            margin-bottom: 12px;
            display: block;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th {
            text-align: left;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px;
            font-size: 12px;
            color: #64748b;
        }
        .table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        .total-row td {
            font-weight: bold;
            font-size: 16px;
            color: #3b82f6;
            border-top: 2px solid #e2e8f0;
            padding-top: 15px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef9c3; color: #854d0e; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">Basharkatrib Hotel</div>
        <div class="receipt-title">Payment Receipt</div>
    </div>

    <div class="details-section">
        <div class="grid">
            <div class="col">
                <span class="label">BOOKING ID</span>
                <span class="value">#{{ $booking->id }}</span>
                
                <span class="label">DATE ISSUED</span>
                <span class="value">{{ now()->format('F d, Y') }}</span>

                <span class="label">STATUS</span>
                <span class="value">
                    <span class="badge {{ $booking->status === 'confirmed' ? 'badge-success' : 'badge-pending' }}">
                        {{ strtoupper($booking->status) }}
                    </span>
                </span>
            </div>
            <div class="col" style="text-align: right;">
                <span class="label">BILLED TO</span>
                <span class="value" style="font-weight: bold;">{{ $booking->guest_name }}</span>
                <span class="value">{{ $booking->guest_email }}</span>
                <span class="value">{{ $booking->guest_phone }}</span>
            </div>
        </div>
    </div>

    <div class="details-section">
        <div class="section-title">STAY DETAILS</div>
        <div class="grid">
            <div class="col">
                <span class="label">HOTEL</span>
                <span class="value">{{ $booking->hotel->name }}</span>
                
                <span class="label">LOCATION</span>
                <span class="value">{{ $booking->hotel->city }}, {{ $booking->hotel->country }}</span>
            </div>
            <div class="col">
                <span class="label">ROOM TYPE</span>
                <span class="value">{{ $booking->room->name }}</span>
                
                <span class="label">DATES</span>
                <span class="value">
                    {{ \Carbon\Carbon::parse($booking->check_in_date)->format('M d') }} - {{ \Carbon\Carbon::parse($booking->check_out_date)->format('M d, Y') }}
                    ({{ $booking->total_nights }} nights)
                </span>
            </div>
        </div>
    </div>

    <div class="details-section">
        <div class="section-title">PAYMENT SUMMARY</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Accommodation ({{ $booking->total_nights }} nights × ${{ number_format($booking->price_per_night, 2) }})</td>
                    <td style="text-align: right;">${{ number_format($booking->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>Service Fee (2.8%)</td>
                    <td style="text-align: right;">${{ number_format($booking->service_fee, 2) }}</td>
                </tr>
                <tr>
                    <td>Taxes (1.64%)</td>
                    <td style="text-align: right;">${{ number_format($booking->taxes, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL PAID</td>
                    <td style="text-align: right;">${{ number_format($booking->total_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Thank you for choosing Basharkatrib Hotel. We wish you a pleasant stay!</p>
        <p>If you have any questions, please contact our support at support@basharkatrib.com</p>
    </div>
</body>
</html>
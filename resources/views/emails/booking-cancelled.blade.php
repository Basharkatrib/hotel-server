<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; direction: ltr; text-align: left;">
    <h2>Hello {{ $booking->guest_name }},</h2>

    <p>Your booking <strong>#{{ $booking->id }}</strong> has been successfully cancelled.</p>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">Check-in Date</td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $booking->check_in_date->format('Y-m-d') }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">Check-out Date</td>
            <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $booking->check_out_date->format('Y-m-d') }}</td>
        </tr>
    </table>

    @if($refundStatus['refund_processed'])
        <p style="color: #16a34a;">
            ✅ A refund of <strong>${{ number_format($refundStatus['refund_amount'], 2) }}</strong> has been processed to your original payment method and should appear within 5-10 business days.
        </p>
    @elseif(!$refundStatus['success'])
        <p style="color: #dc2626;">
            ⚠️ There was an issue processing your refund. Our team will handle it manually and reach out to you shortly.
        </p>
    @else
        <p>{{ $refundStatus['message'] }}</p>
    @endif

    <p>Thank you for using Vayka.</p>
</body>
</html>
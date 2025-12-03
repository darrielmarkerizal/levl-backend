<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Assessment Registration Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2c3e50;">Assessment Registration Confirmation</h2>
        
        <p>Dear {{ $user->name }},</p>
        
        <p>Your registration for the assessment has been confirmed.</p>
        
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2c3e50;">Assessment Details</h3>
            <p><strong>Assessment:</strong> {{ $exercise->title }}</p>
            @if($registration->scheduled_at)
                <p><strong>Scheduled Date:</strong> {{ $registration->scheduled_at->format('F j, Y g:i A') }}</p>
            @endif
            @if($exercise->time_limit_minutes)
                <p><strong>Duration:</strong> {{ $exercise->time_limit_minutes }} minutes</p>
            @endif
            @if($registration->payment_amount)
                <p><strong>Payment Amount:</strong> ${{ number_format($registration->payment_amount, 2) }}</p>
                <p><strong>Payment Status:</strong> {{ ucfirst($registration->payment_status) }}</p>
            @endif
            <p><strong>Registration Status:</strong> {{ ucfirst($registration->status) }}</p>
        </div>
        
        @if($exercise->description)
            <div style="margin: 20px 0;">
                <h3 style="color: #2c3e50;">Assessment Description</h3>
                <p>{{ $exercise->description }}</p>
            </div>
        @endif
        
        <p>Please make sure to arrive on time and bring any required materials.</p>
        
        <p>If you have any questions, please contact us.</p>
        
        <p>Best regards,<br>
        {{ config('app.name') }}</p>
    </div>
</body>
</html>

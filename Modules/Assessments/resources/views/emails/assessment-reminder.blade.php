<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .info-box {
            background-color: #fff;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin: 20px 0;
        }
        .info-row {
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #777;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⏰ Assessment Reminder</h1>
    </div>
    
    <div class="content">
        <p>Dear {{ $user->name }},</p>
        
        <p>This is a friendly reminder that your assessment is scheduled for <strong>tomorrow</strong>.</p>
        
        <div class="info-box">
            <div class="info-row">
                <span class="label">Assessment:</span> {{ $exercise->title }}
            </div>
            <div class="info-row">
                <span class="label">Scheduled Date & Time:</span> {{ $registration->scheduled_at->format('l, F j, Y \a\t g:i A') }}
            </div>
            <div class="info-row">
                <span class="label">Status:</span> {{ ucfirst($registration->status) }}
            </div>
            @if($exercise->duration)
            <div class="info-row">
                <span class="label">Duration:</span> {{ $exercise->duration }} minutes
            </div>
            @endif
        </div>
        
        <p><strong>Important Reminders:</strong></p>
        <ul>
            <li>Please arrive 15 minutes before the scheduled time</li>
            <li>Bring a valid ID for verification</li>
            <li>Ensure you have all necessary materials</li>
            <li>Review the assessment guidelines beforehand</li>
        </ul>
        
        @if($registration->notes)
        <div class="info-box">
            <div class="info-row">
                <span class="label">Additional Notes:</span><br>
                {{ $registration->notes }}
            </div>
        </div>
        @endif
        
        <p>If you need to cancel or reschedule, please do so at least 24 hours before the scheduled time.</p>
        
        <p>Good luck with your assessment!</p>
        
        <p>Best regards,<br>
        <strong>LMS Sertifikasi Team</strong></p>
    </div>
    
    <div class="footer">
        <p>This is an automated reminder. Please do not reply to this email.</p>
        <p>&copy; {{ date('Y') }} LMS Sertifikasi. All rights reserved.</p>
    </div>
</body>
</html>

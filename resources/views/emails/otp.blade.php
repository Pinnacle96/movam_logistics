<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .header { background: #10b981; padding: 40px 20px; text-align: center; color: white; }
        .content { padding: 40px; text-align: center; }
        .otp { font-size: 48px; font-weight: bold; letter-spacing: 10px; color: #10b981; margin: 20px 0; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; background: #fafafa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Movam</h1>
            <p>Verification Code</p>
        </div>
        <div class="content">
            <h2>Verify Your Email</h2>
            <p>Use the code below to complete your registration on Movam.</p>
            <div class="otp">{{ $token }}</div>
            <p>This code expires in 15 minutes.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Movam Logistics. All rights reserved.
        </div>
    </div>
</body>
</html>

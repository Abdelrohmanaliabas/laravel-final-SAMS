<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Welcome to SAMS</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4;">
        <div style="background-color: white; padding: 30px; border-radius: 5px;">
            <h2 style="color: #4CAF50; margin-bottom: 20px;">Welcome to SAMS!</h2>

            <p>Hello {{ $user->name }},</p>

            <p>Your account has been successfully created. Below are your login credentials:</p>

            <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0;">
                <p style="margin: 5px 0;"><strong>Email:</strong> {{ $user->email }}</p>
                <p style="margin: 5px 0;"><strong>Password:</strong> {{ $password }}</p>
            </div>

            <p>
                <a href="{{ $loginUrl }}"
                    style="display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 3px;">
                    Login to Your Account
                </a>
            </p>

            <p style="color: #666; font-size: 14px; margin-top: 20px;">
                <strong>Important:</strong> Please change your password after your first login for security purposes.
            </p>

            <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

            <p style="color: #999; font-size: 12px;">
                This is an automated message from SAMS. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>

</html>
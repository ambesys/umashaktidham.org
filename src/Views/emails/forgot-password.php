Subject: Uma Shakti Dham Password Reset Request
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Password Reset</title>
</head>
<body>
  <h2>Password Reset Request</h2>
  <p>Hello <?php echo htmlspecialchars($user['name'] ?? $user['u_name'] ?? 'Friend'); ?>,</p>
  <p>We received a request to reset your password. Click the link below to set a new password. This link expires in 1 hour.</p>
  <p><a href="<?php echo htmlspecialchars($reset_url ?? '#'); ?>">Reset Your Password</a></p>
  <p>If you did not request a password reset, please ignore this email.</p>

  <p>Regards,<br>Uma Shakti Dham Team</p>
</body>
</html>

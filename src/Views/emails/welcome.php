Subject: Welcome to Uma Shakti Dham!
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Welcome</title>
</head>
<body>
  <h2>Welcome, <?php echo htmlspecialchars($user['name'] ?? $user['u_name'] ?? 'Friend'); ?>!</h2>
  <p>Thank you for joining Uma Shakti Dham. We're delighted to have you in our community.</p>

  <p>
    <strong>What you can do next:</strong>
    <ul>
      <li>Explore member resources</li>
      <li>Join upcoming events</li>
      <li>Connect with other families</li>
    </ul>
  </p>

  <p>Visit <a href="<?php echo (defined('BASE_URL') ? BASE_URL : 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000')); ?>">our site</a> to get started.</p>

  <p>Warm regards,<br>Uma Shakti Dham Team</p>
</body>
</html>

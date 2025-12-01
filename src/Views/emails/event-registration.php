Subject: Event Registration Confirmation - <?php echo htmlspecialchars($event['title'] ?? $event['name'] ?? 'Event'); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Event Registration</title>
</head>
<body>
  <h2>Registration Confirmed</h2>
  <p>Hi <?php echo htmlspecialchars($user['name'] ?? $user['u_name'] ?? 'Friend'); ?>,</p>
  <p>Thank you for registering for <strong><?php echo htmlspecialchars($event['title'] ?? $event['name'] ?? 'our event'); ?></strong>.</p>
  <p>
    <strong>Date & Time:</strong> <?php echo htmlspecialchars($event['datetime'] ?? $event['date'] ?? 'TBD'); ?><br>
    <strong>Location:</strong> <?php echo htmlspecialchars($event['location'] ?? 'TBD'); ?>
  </p>
  <p>We look forward to seeing you there.</p>
  <p>Regards,<br>Uma Shakti Dham Team</p>
</body>
</html>

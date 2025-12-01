<?php
// Compatibility redirect: some legacy links and controllers redirect to /dashboard.php
// Redirect to the router-managed user dashboard route.
header('Location: /user/dashboard', true, 302);
exit;

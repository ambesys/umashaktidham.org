<?php $pageTitle = 'Login - Uma Shakti Dham'; ?>
<?php include __DIR__ . '/../layouts/header.php'; ?>

<main class="content">
    <div class="login-container">
        <h2>Login to Your Account</h2>
        <form action="/auth/login.php" method="POST" novalidate>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="/auth/register.php">Register here</a></p>
    </div>
</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

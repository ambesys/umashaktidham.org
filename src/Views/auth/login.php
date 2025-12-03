<?php
// Login page content only - using Layout class
?>

<main class="container">
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="fas fa-praying-hands"></i> Welcome Back</h1>
            <p>Sign in to access your community account</p>
        </div>

        <div class="auth-form-container">
            <!-- Error Messages -->
            <?php if (isset($_GET['error']) || isset($error)): ?>
                <div class="error-message" id="errorMessage">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php
                    if (isset($error)) {
                        echo htmlspecialchars($error);
                    } elseif (isset($_GET['error'])) {
                        $error_param = $_GET['error'];
                        switch ($error_param) {
                            case 'oauth_config':
                                echo 'OAuth configuration error. Please try again later or contact support.';
                                break;
                            case 'oauth_access_denied':
                                echo 'Access denied. You cancelled the login or denied permissions.';
                                break;
                            case 'oauth_invalid_request':
                                echo 'Invalid OAuth request. Please try again.';
                                break;
                            case 'oauth_callback':
                                echo 'Authentication failed. Please try again or contact support if the problem persists.';
                                break;
                            case 'oauth_no_code':
                                echo 'Authentication code missing. Please try again.';
                                break;
                            case 'oauth_redirect_mismatch':
                                echo 'Authentication redirect error. The redirect URI may not be properly configured in Google Cloud Console.';
                                break;
                            case 'oauth_invalid_client':
                                echo 'Authentication client error. The OAuth client credentials may be incorrect.';
                                break;
                            default:
                                if (strpos($error_param, 'oauth_') === 0) {
                                    echo 'Social login error. Please try again or use email/password login.';
                                } else {
                                    echo htmlspecialchars($error_param);
                                }
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Success Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message" id="successMessage">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Social Login Options -->
            <div class="social-login-section">
                <h3>Quick Sign In</h3>
                <button class="social-btn google-btn" onclick="signInWithGoogle()">
                    <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google" class="social-icon">
                    Sign in with Google
                </button>
                <!-- <button class="social-btn facebook-btn" onclick="signInWithFacebook()">
                    <i class="fab fa-facebook"></i> Sign in with Facebook
                </button> -->
            </div>

            <div class="divider">
                <span>OR</span>
            </div>

            <!-- Login Form -->
            <form action="/login" method="POST" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email address">
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                    <div class="password-options">
                        <label class="checkbox-label">
                            <input type="checkbox" id="show_password" onchange="togglePassword()">
                            <span class="checkmark"></span>
                            Show password
                        </label>
                        <a href="/forgot-password" class="forgot-link">Forgot password?</a>
                    </div>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="remember" name="remember">
                        <span class="checkmark"></span>
                        <i class="fas fa-save"></i> Keep me signed in
                    </label>
                </div>

                <button type="submit" name="submit" class="btn auth-btn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="auth-footer">
                <p>New to our community? <a href="/register" class="auth-link"><i class="fas fa-star"></i> Create an Account</a></p>
                <div class="help-links">
                    <a href="/contact"><i class="fas fa-question-circle"></i> Need Help?</a>
                    <span>•</span>
                    <a href="/about"><i class="fas fa-home"></i> About Our Community</a>
                    <span>•</span>
                    <a href="/events"><i class="fas fa-calendar-alt"></i> Upcoming Events</a>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function signInWithGoogle() {
    // Redirect to Google OAuth
    window.location.href = '/auth/google';
}

function signInWithFacebook() {
    // Redirect to Facebook OAuth
    window.location.href = '/auth/facebook';
}

function togglePassword() {
    const passwordField = document.getElementById('password');
    const showPasswordCheckbox = document.getElementById('show_password');
    
    if (showPasswordCheckbox.checked) {
        passwordField.type = 'text';
    } else {
        passwordField.type = 'password';
    }
}

// Form validation
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    if (!email || !password) {
        e.preventDefault();
        alert('Please fill in all required fields');
        return;
    }
    
    // Add loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
    submitBtn.disabled = true;
});
</script>

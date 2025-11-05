<?php
// Register page content only - using Layout class
?>

<main class="container">
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="fas fa-praying-hands"></i> Join Our Community</h1>
            <p>Become a member of Uma Shakti Dham and connect with our community family</p>
        </div>

        <div class="auth-form-container">
            <!-- Social Login Options -->
            <div class="social-login-section">
                <h3>Quick Registration</h3>
                <button class="social-btn google-btn" onclick="signInWithGoogle()">
                    <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google" class="social-icon">
                    Continue with Google
                </button>
                <button class="social-btn facebook-btn" onclick="signInWithFacebook()">
                    <i class="fab fa-facebook"></i> Continue with Facebook
                </button>
            </div>

            <div class="divider">
                <span>OR</span>
            </div>

            <!-- Manual Registration Form -->
            <form action="/register" method="POST" class="auth-form" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name"><i class="fas fa-user"></i> First Name *</label>
                        <input type="text" id="first_name" name="first_name" required placeholder="Enter your first name">
                    </div>
                    <div class="form-group">
                        <label for="last_name"><i class="fas fa-user"></i> Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required placeholder="Enter your last name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address *</label>
                    <input type="email" id="email" name="email" required placeholder="your.email@example.com">
                </div>

                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+1 (555) 123-4567">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password *</label>
                        <input type="password" id="password" name="password" required placeholder="Create a strong password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                    </div>
                </div>

                <div class="form-group">
                    <label for="birth_date"><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                    <input type="date" id="birth_date" name="birth_date">
                </div>

                <div class="form-group">
                    <label for="city"><i class="fas fa-map-marker-alt"></i> City</label>
                    <input type="text" id="city" name="city" placeholder="Your city">
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="newsletter" name="newsletter" checked>
                        <span class="checkmark"></span>
                        <i class="fas fa-newspaper"></i> Receive community newsletters and event updates
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="terms" name="terms" required>
                        <span class="checkmark"></span>
                        <i class="fas fa-check-circle"></i> I agree to the <a href="/terms" target="_blank">Terms & Conditions</a> and <a href="/privacy" target="_blank">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" class="btn auth-btn">
                    <i class="fas fa-star"></i> Create My Account
                </button>
            </form>

            <div class="auth-footer">
                <p>Already a member? <a href="/login" class="auth-link"><i class="fas fa-sign-in-alt"></i> Sign In Here</a></p>
                <p class="help-text">Need help? <a href="/contact">Contact our community center</a></p>
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

// Password strength validation
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strength = document.getElementById('password-strength') || createStrengthMeter();
    
    let score = 0;
    if (password.length >= 8) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    const levels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['#ff4444', '#ff8800', '#ffbb00', '#88cc00', '#00cc44'];
    
    strength.textContent = `Password Strength: ${levels[score] || 'Very Weak'}`;
    strength.style.color = colors[score] || '#ff4444';
});

function createStrengthMeter() {
    const meter = document.createElement('div');
    meter.id = 'password-strength';
    meter.style.fontSize = '0.8em';
    meter.style.marginTop = '5px';
    document.getElementById('password').parentNode.appendChild(meter);
    return meter;
}

// Confirm password validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.style.borderColor = '#ff4444';
        this.setCustomValidity('Passwords do not match');
    } else {
        this.style.borderColor = '';
        this.setCustomValidity('');
    }
});
</script>
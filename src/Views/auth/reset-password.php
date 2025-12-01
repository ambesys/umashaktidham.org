<?php
// Reset password page content only - using Layout class
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0"><i class="fas fa-key"></i> Reset Your Password</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['token']) && !empty($_GET['token'])): ?>
                        <!-- Password Reset Form -->
                        <form id="resetPasswordForm" method="POST" action="/reset-password">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required
                                       minlength="8" placeholder="Enter your new password">
                                <div class="form-text">Password must be at least 8 characters long.</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                                       minlength="8" placeholder="Confirm your new password">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Reset Password
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Token Required Message -->
                        <div class="text-center">
                            <div class="mb-4">
                                <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                                <h5>Check Your Email</h5>
                                <p class="text-muted">
                                    A password reset link has been sent to your email address.
                                    Please click the link in the email to reset your password.
                                </p>
                            </div>

                            <div class="alert alert-info">
                                <strong>Didn't receive the email?</strong><br>
                                Check your spam folder, or <a href="/forgot-password">request a new reset link</a>.
                            </div>

                            <a href="/login" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Back to Login
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetPasswordForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                alert('Passwords do not match. Please try again.');
                return;
            }

            if (password.length < 8) {
                alert('Password must be at least 8 characters long.');
                return;
            }

            // Submit the form
            this.submit();
        });
    }
});
</script>
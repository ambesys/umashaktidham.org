<?php
// Forgot password request form
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0"><i class="fas fa-envelope"></i> Forgot Password</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error) || isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($notice) || isset($notice)): ?>
                        <div class="alert alert-info">
                            <?php echo htmlspecialchars($notice); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/forgot-password">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="you@example.com">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Reset Link
                            </button>
                        </div>
                    </form>

                    <div class="mt-3 text-center">
                        <a href="/login" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// End forgot-password view
?>

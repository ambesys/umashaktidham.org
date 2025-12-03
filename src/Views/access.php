<?php
// Access gate view
// Available variables: $error (from App logic), $pageTitle
?>
<div class="access-screen min-vh-70 d-flex align-items-center justify-content-center p-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-body p-4 text-center">
                        <h2 class="card-title mb-3 text-primary">Access Required</h2>
                        <p class="card-text text-muted mb-4">Please enter the access code to continue. This code will grant access for 2 hours.</p>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger mb-3">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="/access">
                            <input type="hidden" name="next" value="<?php echo htmlspecialchars($_GET['next'] ?? '/'); ?>" />
                            <div class="mb-3">
                                <input name="access_password" type="password" class="form-control form-control-lg" placeholder="Access code" required />
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold">Enter</button>
                            </div>
                            <p class="text-muted small mt-3 mb-0">If you don't have a code, contact the site administrator.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

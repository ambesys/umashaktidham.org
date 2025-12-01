<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h3>Change Password</h3>

            <?php if (!empty($error)) : ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)) : ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="/user/change-password">
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="d-flex justify-content-between">
                    <a href="/user/profile" class="btn btn-outline-secondary">Cancel</a>
                    <button class="btn btn-primary" type="submit">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

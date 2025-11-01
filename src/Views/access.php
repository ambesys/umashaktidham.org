<?php
// Access gate view
// Available variables: $error (from App logic), $pageTitle
?>
<div class="access-screen" style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:40px;">
    <div style="max-width:420px;width:100%;background:#fff;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,0.12);padding:28px;text-align:center;">
        <h2 style="margin-top:0;color:#eb3337;font-family:var(--font-heading);">Access Required</h2>
        <p style="color:#444;margin-bottom:18px;">Please enter the access code to continue. This code will grant access for 2 hours.</p>

        <?php if (!empty($error)): ?>
            <div style="color:#fff;background:#e53935;padding:10px;border-radius:6px;margin-bottom:12px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="/access">
            <input type="hidden" name="next" value="<?php echo htmlspecialchars($_GET['next'] ?? '/'); ?>" />
            <div style="margin-bottom:12px;">
                <input name="access_code" type="password" placeholder="Access code" required style="width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:16px;" />
            </div>
            <div>
                <button type="submit" style="background:var(--primary-color);color:#fff;border:none;padding:12px 18px;border-radius:6px;font-weight:700;">Enter</button>
            </div>
            <p style="margin-top:14px;color:#777;font-size:13px;">If you don't have a code, contact the site administrator.</p>
        </form>
    </div>
</div>

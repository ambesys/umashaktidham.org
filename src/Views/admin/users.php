<style>
    .admin-container {
        padding: 2rem;
    }
    
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    .admin-header h1 {
        margin: 0;
        font-size: 2rem;
        font-weight: 600;
    }
    
    .users-grid {
        display: grid;
        gap: 1rem;
    }
    
    .user-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 1rem;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .user-card-header {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr auto;
        gap: 1rem;
        align-items: center;
        padding-bottom: 1rem;
        border-bottom: 2px solid #007bff;
        margin-bottom: 1rem;
    }
    
    .user-card-header h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .user-info {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
    }
    
    .info-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        margin-bottom: 0.3rem;
    }
    
    .info-value {
        font-size: 0.95rem;
        color: #333;
    }
    
    .family-section {
        background: #f8f9fa;
        padding: 0.8rem;
        border-radius: 4px;
        margin-top: 1rem;
    }
    
    .family-section h4 {
        margin: 0 0 0.8rem 0;
        font-size: 0.95rem;
        font-weight: 600;
    }
    
    .family-list {
        display: grid;
        gap: 0.5rem;
    }
    
    .family-item {
        display: grid;
        grid-template-columns: 100px 150px 80px 120px 120px auto;
        gap: 0.8rem;
        padding: 0.6rem;
        background: #fff;
        border-radius: 4px;
        border-left: 3px solid #28a745;
        font-size: 0.85rem;
        align-items: center;
    }
    
    .actions-btn {
        display: flex;
        gap: 0.4rem;
    }
    
    .badge {
        display: inline-block;
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-primary {
        background: #007bff;
        color: white;
    }
    
    .badge-secondary {
        background: #6c757d;
        color: white;
    }
    
    .badge-success {
        background: #28a745;
        color: white;
    }
</style>

<div class="admin-container">
    <div class="admin-header">
        <h1>Manage Users</h1>
        <a href="/admin/create-user" class="btn btn-success">
            <i class="fas fa-user-plus"></i> Add New User
        </a>
    </div>

    <!-- Users List -->
    <div class="users-grid">
        <?php if (!empty($users) && is_array($users)): ?>
            <?php foreach ($users as $user): ?>
                <div class="user-card">
                    <!-- User Header Info -->
                    <div class="user-card-header">
                        <div>
                            <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')); ?></h3>
                            <small style="color: #666;">ID: <?php echo $user['id']; ?></small>
                        </div>
                        <div>
                            <strong>Email:</strong><br>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                        <div>
                            <strong>Role:</strong><br>
                            <span class="badge badge-primary"><?php echo htmlspecialchars($user['role_name'] ?? 'User'); ?></span>
                        </div>
                        <div>
                            <strong>Joined:</strong><br>
                            <?php echo date('M d, Y', strtotime($user['created_at'] ?? 'now')); ?>
                        </div>
                        <div class="actions-btn">
                            <a href="/admin/edit-user/<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="/admin/delete-user/<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>

                    <!-- User Details -->
                    <div class="user-info">
                        <div class="info-item">
                            <span class="info-label">Birth Year</span>
                            <span class="info-value"><?php echo $user['birth_year'] ?? '-'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Gender</span>
                            <span class="info-value" style="text-transform: capitalize;"><?php echo $user['gender'] ?? '-'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Village</span>
                            <span class="info-value"><?php echo $user['village'] ?? '-'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Mosal</span>
                            <span class="info-value"><?php echo $user['mosal'] ?? '-'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Occupation</span>
                            <span class="info-value"><?php echo $user['occupation'] ?? '-'; ?></span>
                        </div>
                    </div>

                    <!-- Family Members Section -->
                    <?php 
                    // Fetch family members for this user
                    if (isset($GLOBALS['pdo'])) {
                        $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM family_members WHERE user_id = ? ORDER BY relationship, first_name");
                        $stmt->execute([$user['id']]);
                        $familyMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        $familyMembers = [];
                    }
                    ?>
                    
                    <?php if (!empty($familyMembers)): ?>
                        <div class="family-section">
                            <h4><i class="fas fa-users"></i> Family Members (<?php echo count($familyMembers); ?>)</h4>
                            <div class="family-list">
                                <?php foreach ($familyMembers as $member): ?>
                                    <div class="family-item">
                                        <div style="font-weight: 600; color: #007bff;">
                                            <?php echo htmlspecialchars($member['relationship']); ?>
                                        </div>
                                        <div><?php echo htmlspecialchars($member['first_name'] . ' ' . ($member['last_name'] ?? '')); ?></div>
                                        <div>
                                            <?php 
                                            if ($member['birth_year']) {
                                                echo (date('Y') - $member['birth_year']) . ' yrs';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </div>
                                        <div><?php echo htmlspecialchars($member['village'] ?? '-'); ?></div>
                                        <div><?php echo htmlspecialchars($member['mosal'] ?? '-'); ?></div>
                                        <div style="font-size: 0.8rem; color: #666;">
                                            <?php echo htmlspecialchars($member['phone_e164'] ?? '-'); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="family-section">
                            <h4 style="color: #999;"><i class="fas fa-users"></i> No family members added yet</h4>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>No users found.</strong> <a href="/admin/create-user">Add a new user</a>
            </div>
        <?php endif; ?>
    </div>
</div>
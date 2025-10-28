<?php
session_start();
require_once '../../config/config.php';
require_once '../../src/Controllers/AdminController.php';

$adminController = new AdminController();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /uma-shakti-dham/public/index.php');
    exit();
}

$moderators = $adminController->getModerators();

include '../layouts/header.php';
?>

<div class="container">
    <h1>Manage Moderators</h1>
    <a href="add_moderator.php" class="btn btn-primary">Add Moderator</a>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($moderators as $moderator): ?>
                <tr>
                    <td><?php echo htmlspecialchars($moderator['id']); ?></td>
                    <td><?php echo htmlspecialchars($moderator['name']); ?></td>
                    <td><?php echo htmlspecialchars($moderator['email']); ?></td>
                    <td>
                        <a href="edit_moderator.php?id=<?php echo htmlspecialchars($moderator['id']); ?>" class="btn btn-warning">Edit</a>
                        <a href="delete_moderator.php?id=<?php echo htmlspecialchars($moderator['id']); ?>" class="btn btn-danger">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../layouts/footer.php'; ?>
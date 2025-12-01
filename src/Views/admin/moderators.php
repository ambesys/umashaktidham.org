<div class="page-heading">
    <div class="container">
        <h1>Manage Moderators</h1>
        <p>Add, edit, and remove moderators who help manage community content.</p>
    </div>
</div>

<div class="container">
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
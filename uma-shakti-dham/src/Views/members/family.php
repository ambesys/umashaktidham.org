<?php
session_start();
require_once '../../config/config.php';
require_once '../../src/Models/Family.php';

$familyModel = new Family();

if (!isset($_SESSION['user_id'])) {
    header('Location: /uma-shakti-dham/public/auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$families = $familyModel->getFamiliesByUserId($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_family_member'])) {
        $name = $_POST['name'];
        $relation = $_POST['relation'];
        $age = $_POST['age'];
        $familyModel->addFamilyMember($userId, $name, $relation, $age);
        header('Location: family.php');
        exit();
    }
}

include '../layouts/header.php';
?>

<div class="container">
    <h2>Manage Family Details</h2>
    <form method="POST" action="family.php">
        <input type="text" name="name" placeholder="Family Member Name" required>
        <input type="text" name="relation" placeholder="Relation" required>
        <input type="number" name="age" placeholder="Age" required>
        <button type="submit" name="add_family_member">Add Family Member</button>
    </form>

    <h3>Your Family Members</h3>
    <ul>
        <?php foreach ($families as $family): ?>
            <li><?php echo htmlspecialchars($family['name']); ?> - <?php echo htmlspecialchars($family['relation']); ?> (<?php echo htmlspecialchars($family['age']); ?> years old)</li>
        <?php endforeach; ?>
    </ul>
</div>

<?php include '../layouts/footer.php'; ?>
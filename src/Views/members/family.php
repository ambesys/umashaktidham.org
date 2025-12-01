<?php
// This view expects a `$families` array to be provided by the controller.
// If not provided, gracefully fall back to an empty array so rendering continues.
// The layout (header/footer) is handled by the Layout / render_view helper.

$families = $families ?? [];
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
            <?php
                // Be defensive: some family items may be missing keys or have null values.
                $name = isset($family['name']) ? $family['name'] : '';
                $relation = isset($family['relation']) ? $family['relation'] : '';
                $age = isset($family['age']) ? $family['age'] : '';
                // Cast to string to avoid passing null to htmlspecialchars (deprecated in PHP 8.1+)
                $nameEsc = htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8');
                $relationEsc = htmlspecialchars((string)$relation, ENT_QUOTES, 'UTF-8');
                $ageEsc = htmlspecialchars((string)$age, ENT_QUOTES, 'UTF-8');
            ?>
            <li><?php echo $nameEsc; ?> - <?php echo $relationEsc; ?> (<?php echo $ageEsc; ?> years old)</li>
        <?php endforeach; ?>
    </ul>
    </div>

<?php
// Note: footer is included by the layout when using render_view()
?>
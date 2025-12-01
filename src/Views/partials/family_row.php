<?php
// Expects $member to be an associative array for a family member
// Renders a table row and an expandable edit form container
?>
<tr>
    <td><strong><?= htmlspecialchars($member['relationship'] ?? '') ?></strong></td>
    <td><?= htmlspecialchars($member['first_name'] ?? '') ?><?= !empty($member['last_name']) ? ' ' . htmlspecialchars($member['last_name']) : '' ?></td>
    <td>
        <?php
        $birthYear = $member['birth_year'] ?? null;
        if ($birthYear) {
            $age = date('Y') - $birthYear;
            echo $age;
        } else {
            echo '-';
        }
        ?>
    </td>
    <td><?= htmlspecialchars($member['village'] ?? '-') ?></td>
    <td><?= htmlspecialchars($member['mosal'] ?? '-') ?></td>
    <td>
        <button class="btn btn-edit btn-sm" type="button" data-action="edit-family"
            data-member-id="<?= htmlspecialchars($member['id']) ?>"
            data-first-name="<?= htmlspecialchars($member['first_name'] ?? '') ?>"
            data-last-name="<?= htmlspecialchars($member['last_name'] ?? '') ?>"
            data-birth-year="<?= htmlspecialchars($member['birth_year'] ?? '') ?>"
            data-gender="<?= htmlspecialchars($member['gender'] ?? '') ?>"
            data-phone="<?= htmlspecialchars($member['phone_e164'] ?? '') ?>"
            data-email="<?= htmlspecialchars($member['email'] ?? '') ?>"
            data-occupation="<?= htmlspecialchars($member['occupation'] ?? '') ?>"
            data-business-info="<?= htmlspecialchars($member['business_info'] ?? '') ?>"
            data-village="<?= htmlspecialchars($member['village'] ?? '') ?>"
            data-mosal="<?= htmlspecialchars($member['mosal'] ?? '') ?>"
            data-relationship="<?= htmlspecialchars($member['relationship'] ?? '') ?>"
            data-relationship-other="<?= htmlspecialchars($member['relationship_other'] ?? '') ?>"
            aria-label="Edit <?= htmlspecialchars($member['first_name'] . ' ' . ($member['last_name'] ?? '')) ?>">
            <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-delete btn-sm" type="button"
            data-member-id="<?= htmlspecialchars($member['id']) ?>"
            data-member-name="<?= htmlspecialchars($member['first_name'] . ' ' . ($member['last_name'] ?? '')) ?>"
            aria-label="Delete <?= htmlspecialchars($member['first_name'] . ' ' . ($member['last_name'] ?? '')) ?>">
            <i class="fas fa-trash"></i>
        </button>
    </td>
</tr>

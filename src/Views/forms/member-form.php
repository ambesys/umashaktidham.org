<?php
/**
 * Reusable Member Form
 * ---------------------------------------------------------
 * Used for:
 *  - Editing main user profile
 *  - Adding or editing a family member
 *
 * Variables expected:
 *  - $member: array of member data
 *  - $isMainUser: bool
 *  - $memberId: int|null
 *  - $userId: int|null
 */

$member = $member ?? [];
$isMainUser = $isMainUser ?? false;
$memberId = $memberId ?? null;
$userId = $userId ?? null;
$isEditMode = !empty($memberId);

/* Safe escaping helper */
function esc($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* Preload + sanitize values */
$defaults = [
    'relationship' => '',
    'first_name' => '',
    'last_name' => '',
    'gender' => '',
    'birth_year' => '',
    'email' => '',
    'phone_e164' => '',
    'village' => '',
    'mosal' => '',
    'occupation' => '',
    'business_info' => '',
    'street_address' => '',
    'address_line_2' => '',
    'zip_code' => '',
    'city' => '',
    'state' => '',
];
$data = array_merge($defaults, $member);

/* ENUM options */
$relationshipOptions = [
    'spouse' => 'Spouse',
    'son' => 'Son',
    'daughter' => 'Daughter',
    'mother' => 'Mother',
    'father' => 'Father',
    'sibling' => 'Sibling',
    'brother' => 'Brother',
    'sister' => 'Sister',
    'father-in-law' => 'Father-in-law',
    'mother-in-law' => 'Mother-in-law',
    'other' => 'Other',
];

$genderOptions = [
    'male' => 'Male',
    'female' => 'Female',
    'other' => 'Other',
    'prefer_not_say' => 'Prefer not to say',
];
?>

<form id="memberForm" class="member-form">

    <!-- Hidden fields -->
    <input type="hidden" name="member_id" value="<?= esc($isEditMode ? $memberId : ($member['id'] ?? '')) ?>">
    <input type="hidden" name="user_id" value="<?= esc($userId) ?>">
    <input type="hidden" name="is_main_user" value="<?= $isMainUser ? '1' : '0' ?>">

    <!-- ==================== BASIC INFORMATION ==================== -->
    <fieldset class="mb-1 border p-1 rounded">
        <legend class="fs-6 mb-1 px-1" style="font-size: 0.9rem !important;"><i class="fas fa-user me-1"></i> Basic Information</legend>

        <div class="row g-1">
            <div class="col-md-2">
                <label for="relationship" class="form-label small mb-0">Relationship</label>
                <?php if ($isMainUser): ?>
                    <input type="text" class="form-control form-control-sm" disabled name="relationship-display" value="Self">
                    <input type="hidden" disabled name="relationship" value="self">
                <?php elseif (!$isMainUser): ?>
                    <select class="form-select form-select-sm" id="relationship" name="relationship" <?= $isMainUser ? 'disabled' : '' ?>>
                        <option value="">Select...</option>
                        <?php foreach ($relationshipOptions as $key => $label): ?>
                            <option value="<?= esc($key) ?>" <?= $data['relationship'] === $key ? 'selected' : '' ?>>
                                <?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

            </div>

            <div class="col-md-3">
                <label for="first_name" class="form-label small mb-0">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm" id="first_name" name="first_name"
                    value="<?= esc($data['first_name']) ?>" placeholder="John" required>
            </div>

            <div class="col-md-3">
                <label for="last_name" class="form-label small mb-0">Last Name</label>
                <input type="text" class="form-control form-control-sm" id="last_name" name="last_name"
                    value="<?= esc($data['last_name']) ?>" placeholder="Doe">
            </div>

            <div class="col-md-2">
                <label for="gender" class="form-label small mb-0">Gender</label>
                <select class="form-select form-select-sm" id="gender" name="gender">
                    <option value="">Select...</option>
                    <?php foreach ($genderOptions as $key => $label): ?>
                        <option value="<?= esc($key) ?>" <?= $data['gender'] === $key ? 'selected' : '' ?>><?= esc($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="birth_year" class="form-label small mb-0">Birth Year</label>
                <input type="number" class="form-control form-control-sm" id="birth_year" name="birth_year"
                    value="<?= esc($data['birth_year']) ?>" min="1900" max="<?= date('Y') ?>" placeholder="1990">
            </div>
        </div>


        <div class="row g-1 mt-1">
            <div class="col-md-3">
                <label for="email" class="form-label small mb-0">Email</label>
                <input type="email" class="form-control form-control-sm" id="email" name="email" value="<?= esc($data['email']) ?>"
                    placeholder="email@example.com">
            </div>

            <div class="col-md-3">
                <label for="phone_e164" class="form-label small mb-0 d-flex">Phone</label>
                <input type="tel" class="form-control form-control-sm" id="phone_e164" name="phone_e164"
                    value="<?= esc($data['phone_e164']) ?>">
            </div>

            <div class="col-md-3">
                <label for="village" class="form-label small mb-0">Native (Village)</label>
                <input type="text" class="form-control form-control-sm" id="village" name="village" value="<?= esc($data['village']) ?>"
                    placeholder="Village name">
            </div>

            <div class="col-md-3">
                <label for="mosal" class="form-label small mb-0">Mosal</label>
                <input type="text" class="form-control form-control-sm" id="mosal" name="mosal" value="<?= esc($data['mosal']) ?>"
                    placeholder="Community">
            </div>
        </div>
    </fieldset>

    <!-- ==================== OCCUPATION ==================== -->
    <fieldset class="mb-1 border p-1 rounded">
        <legend class="fs-6 mb-1 px-1" style="font-size: 0.9rem !important;"><i class="fas fa-briefcase me-1"></i> Occupation</legend>

        <div class="row g-1">
            <div class="col-md-4">
                <label for="occupation" class="form-label small mb-0">Occupation</label>
                <input type="text" class="form-control form-control-sm" id="occupation" name="occupation"
                    value="<?= esc($data['occupation']) ?>" placeholder="IT, Business, Private Job etc.">
            </div>

            <div class="col-md-8">
                <label for="business_info" class="form-label small mb-0">Business Info (if business owner)</label>
                <textarea class="form-control form-control-sm" id="business_info" name="business_info" rows="2"
                    placeholder="Business details, mention different types of business you own."><?= esc($data['business_info']) ?></textarea>
            </div>
        </div>
    </fieldset>

    <!-- ==================== ADDRESS (MAIN USER ONLY) ==================== -->
    <?php if ($isMainUser): ?>
        <fieldset class="mb-1 border p-1 rounded">
            <legend class="fs-6 mb-1 px-1" style="font-size: 0.9rem !important;"><i class="fas fa-map-marker-alt me-1"></i> Address</legend>

            <div class="row g-1">
                <div class="col-md-6">
                    <label for="street_address" class="form-label small mb-0">Address Line 1</label>
                    <input type="text" class="form-control form-control-sm" id="street_address" name="street_address"
                        value="<?= esc($data['street_address']) ?>" placeholder="Street address">
                </div>

                <div class="col-md-6">
                    <label for="address_line_2" class="form-label small mb-0">Address Line 2</label>
                    <input type="text" class="form-control form-control-sm" id="address_line_2" name="address_line_2"
                        value="<?= esc($data['address_line_2']) ?>" placeholder="Apt, suite, etc.">
                </div>

                <div class="col-md-3">
                    <label for="zip_code" class="form-label small mb-0">Zip Code</label>
                    <input type="text" class="form-control form-control-sm" id="zip_code" name="zip_code"
                        value="<?= esc($data['zip_code']) ?>" placeholder="12345" maxlength="10">
                </div>

                <div class="col-md-5">
                    <label for="city" class="form-label small mb-0">City</label>
                    <input type="text" class="form-control form-control-sm" id="city" name="city" value="<?= esc($data['city']) ?>"
                        placeholder="City">
                </div>

                <div class="col-md-4">
                    <label for="state" class="form-label small mb-0">State</label>
                    <input type="text" class="form-control form-control-sm" id="state" name="state" value="<?= esc($data['state']) ?>"
                        placeholder="State">
                </div>
            </div>
        </fieldset>
    <?php endif; ?>
</form>
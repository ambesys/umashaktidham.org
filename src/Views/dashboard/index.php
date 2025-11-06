<style>
    .card {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        position: relative;
        margin-bottom: 1rem;
    }

    .card-header {
        font-size: 1.25rem;
        font-weight: bold;
        text-transform: uppercase;
        padding: 0.75rem;
    }

    .card-body {
        padding: 1rem;
    }

    .container {
        padding: 1rem;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
    }

    .is-invalid {
        border-color: #dc3545;
    }
</style>

<script>
    /**
     * Validate birth year field
     * Must be 4-digit number between current year - 120 and current year
     */
    function validateBirthYear(input) {
        const value = input.value.trim();
        const currentYear = new Date().getFullYear();
        const minYear = currentYear - 120;
        const maxYear = currentYear;

        // Clear previous validation state
        input.classList.remove('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = '';
        }

        // If empty, it's valid (optional field)
        if (value === '') {
            return true;
        }

        // Check if 4-digit number
        if (!/^\d{4}$/.test(value)) {
            input.classList.add('is-invalid');
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = 'Birth year must be a 4-digit number';
            }
            return false;
        }

        const year = parseInt(value, 10);

        // Check range
        if (year < minYear || year > maxYear) {
            input.classList.add('is-invalid');
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = `Birth year must be between ${minYear} and ${maxYear}`;
            }
            return false;
        }

        return true;
    }

    /**
     * Attach validation to all birth year inputs
     */
    function attachBirthYearValidation() {
        const birthYearInputs = document.querySelectorAll('input[name="birth_year"]');
        birthYearInputs.forEach(input => {
            input.addEventListener('blur', function () {
                validateBirthYear(this);
            });
            input.addEventListener('change', function () {
                validateBirthYear(this);
            });
        });
    }
</script>

<script>
    function toggleInlineForm(formId) {
        const form = document.getElementById(formId);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    function toggleAddForm() {
        const addForm = document.getElementById('addForm');
        const addButton = document.getElementById('addFamilyButton');
        if (addForm.style.display === 'none') {
            addForm.style.display = 'block';
            addButton.style.display = 'none';
        } else {
            addForm.style.display = 'none';
            addButton.style.display = 'block';
        }
    }

    async function handleAddFormSubmit(event) {
        event.preventDefault();
        event.stopPropagation();

        console.log('Add family form submission intercepted');

        const form = event.target;
        const successMessage = document.getElementById('addSuccessMessage');
        const errorMessage = document.getElementById('addErrorMessage');

        // Validate birth year if present
        const birthYearInput = form.querySelector('input[name="birth_year"]');
        if (birthYearInput && birthYearInput.value) {
            if (!validateBirthYear(birthYearInput)) {
                if (errorMessage) {
                    errorMessage.style.display = 'block';
                    successMessage.style.display = 'none';
                }
                return false;
            }
        }

        // Collect form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Set user ID from current user
        const userIdInput = document.querySelector('input[name="id"]');
        const userId = userIdInput ? userIdInput.value : null;
        data.user_id = parseInt(userId);

        // Convert phone to E.164 format if provided
        if (data.phone && data.phone.trim()) {
            data.phone_e164 = data.phone;
            delete data.phone;
        } else {
            delete data.phone;
        }

        // Remove business_info if empty
        if (!data.business_info || !data.business_info.trim()) {
            delete data.business_info;
        }

        console.log('Sending family member data:', data);

        try {
            const response = await fetch('/add-family-member', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            console.log('HTTP Response:', response.status, response.statusText);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            console.log('Server response:', result);

            if (result.success) {
                console.log('Family member added successfully!');
                if (successMessage) {
                    successMessage.style.display = 'block';
                    errorMessage.style.display = 'none';
                }

                // Reset form and reload after 2 seconds
                setTimeout(() => {
                    form.reset();
                    toggleAddForm();
                    location.reload();
                }, 2000);
            } else {
                console.error('Add failed:', result.error);
                if (errorMessage) {
                    errorMessage.textContent = 'Error: ' + (result.error || 'Failed to add family member');
                    errorMessage.style.display = 'block';
                    successMessage.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Exception occurred:', error);
            if (errorMessage) {
                errorMessage.textContent = 'Error: ' + error.message;
                errorMessage.style.display = 'block';
                successMessage.style.display = 'none';
            }
        }

        return false;
    }

    function toggleEditForm(formId) {
        const form = document.getElementById(formId);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    function populateForm(templateId, data) {
        let template = document.getElementById(templateId).innerHTML;
        for (const key in data) {
            const regex = new RegExp(`{{${key}}}`, 'g');
            template = template.replace(regex, data[key] || '');
        }
        return template;
    }

    function toggleInlineForm(formId, templateId, data) {
        const form = document.getElementById(formId);
        if (form.style.display === 'none') {
            form.innerHTML = populateForm(templateId, data);
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
        }
    }

    async function handleSelfFormSubmit(event) {
        event.preventDefault(); // CRITICAL: Prevent default form submission
        event.stopPropagation(); // Stop event bubbling

        console.log('Form submission intercepted');

        const form = event.target;

        // Validate birth year if present
        const birthYearInput = form.querySelector('input[name="birth_year"]');
        if (birthYearInput && birthYearInput.value) {
            if (!validateBirthYear(birthYearInput)) {
                showErrorBanner('Please fix the birth year before submitting');
                return false;
            }
        }

        // Get user ID from hidden input
        const userIdInput = form.querySelector('input[name="id"]');
        const userId = userIdInput ? userIdInput.value : null;

        if (!userId || !Number.isInteger(parseInt(userId))) {
            console.error('User ID not found or invalid:', userId);
            showErrorBanner('Error: User ID not found in form');
            return false;
        }

        // Collect form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Set user ID
        data.id = parseInt(userId);

        // Remove fields that don't belong in users table
        delete data.relation; // Don't send relation to users table

        // Convert phone to E.164 format if provided
        if (data.phone && data.phone.trim()) {
            data.phone_e164 = data.phone;
            delete data.phone;
        } else {
            delete data.phone;
        }

        // Remove business_info if empty
        if (!data.business_info || !data.business_info.trim()) {
            delete data.business_info;
        }

        console.log('Sending JSON data:', data);

        try {
            const response = await fetch('/update-user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            console.log('HTTP Response:', response.status, response.statusText);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            console.log('Server response:', result);

            if (result.success) {
                console.log('Update successful!');
                showSuccessBanner('Profile updated successfully!');

                // Reload dashboard after 2 seconds
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                console.error('Update failed:', result.error);
                showErrorBanner(result.error || 'Failed to update profile. Please try again.');
            }
        } catch (error) {
            console.error('Exception occurred:', error);
            showErrorBanner('Error: ' + error.message);
        }

        return false; // Double-ensure no form submission
    }

    async function handleEditFamilyFormSubmit(event) {
        event.preventDefault();
        event.stopPropagation();

        console.log('Edit family form submission intercepted');

        const form = event.target;
        const container = form.closest('[id^="editFamilyForm"]');
        const containerId = container ? container.id : null;

        if (!containerId) {
            console.error('Could not find form container');
            return false;
        }

        // Validate birth year if present
        const birthYearInput = form.querySelector('input[name="birth_year"]');
        if (birthYearInput && birthYearInput.value) {
            if (!validateBirthYear(birthYearInput)) {
                showFamilyErrorBanner(containerId, 'Please fix the birth year before submitting');
                return false;
            }
        }

        // Get family member ID from hidden input
        const familyIdInput = form.querySelector('input[name="family_id"]');
        const familyId = familyIdInput ? familyIdInput.value : null;

        if (!familyId || !Number.isInteger(parseInt(familyId))) {
            console.error('Family ID not found or invalid:', familyId);
            showFamilyErrorBanner(containerId, 'Error: Family member ID not found');
            return false;
        }

        // Collect form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Set family ID
        data.id = parseInt(familyId);

        // Convert phone to E.164 format if provided
        if (data.phone && data.phone.trim()) {
            data.phone_e164 = data.phone;
            delete data.phone;
        } else {
            delete data.phone;
        }

        // Remove business_info if empty
        if (!data.business_info || !data.business_info.trim()) {
            delete data.business_info;
        }

        console.log('Sending family member update data:', data);

        try {
            const response = await fetch('/update-family-member', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            console.log('HTTP Response:', response.status, response.statusText);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            console.log('Server response:', result);

            if (result.success) {
                console.log('Family member updated successfully!');
                showFamilySuccessBanner(containerId, 'Family member updated successfully!');

                // Reload dashboard after 2 seconds
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                console.error('Update failed:', result.error);
                showFamilyErrorBanner(containerId, result.error || 'Failed to update family member. Please try again.');
            }
        } catch (error) {
            console.error('Exception occurred:', error);
            showFamilyErrorBanner(containerId, 'Error: ' + error.message);
        }

        return false;
    }

    async function handleDeleteFamilyMember(familyId, memberName) {
        if (!confirm(`Are you sure you want to delete ${memberName}? This action cannot be undone.`)) {
            return false;
        }

        console.log('Deleting family member:', familyId);

        try {
            const response = await fetch('/delete-family-member', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: familyId }),
            });

            console.log('HTTP Response:', response.status, response.statusText);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            console.log('Server response:', result);

            if (result.success) {
                console.log('Family member deleted successfully!');
                showSuccessBanner('Family member removed successfully!');

                // Reload dashboard after 1.5 seconds
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                console.error('Delete failed:', result.error);
                showErrorBanner(result.error || 'Failed to delete family member. Please try again.');
            }
        } catch (error) {
            console.error('Exception occurred:', error);
            showErrorBanner('Error: ' + error.message);
        }

        return false;
    }

    function showFamilySuccessBanner(containerId, message) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Remove any existing messages
        const existingSuccess = container.querySelector('.alert-success');
        const existingError = container.querySelector('.alert-danger');
        if (existingSuccess) existingSuccess.remove();
        if (existingError) existingError.remove();

        // Create success message
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success';
        successDiv.style.marginTop = '8px';
        successDiv.style.fontSize = '0.9rem';
        successDiv.textContent = message;
        container.appendChild(successDiv);
    }

    function showFamilyErrorBanner(containerId, message) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Remove any existing messages
        const existingSuccess = container.querySelector('.alert-success');
        const existingError = container.querySelector('.alert-danger');
        if (existingSuccess) existingSuccess.remove();
        if (existingError) existingError.remove();

        // Create error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger';
        errorDiv.style.marginTop = '8px';
        errorDiv.style.fontSize = '0.9rem';
        errorDiv.textContent = message;
        container.appendChild(errorDiv);
    }

    function showSuccessBanner(message) {
        const banner = document.getElementById('selfSuccessBanner');
        const errorBanner = document.getElementById('selfErrorBanner');

        if (banner) {
            banner.innerText = 'Success! ' + message;
            banner.style.display = 'block';
        }

        if (errorBanner) {
            errorBanner.style.display = 'none';
        }
    }

    function showErrorBanner(message) {
        const banner = document.getElementById('selfErrorBanner');
        const successBanner = document.getElementById('selfSuccessBanner');
        const errorMsg = document.getElementById('selfErrorMessage');

        if (banner) {
            if (errorMsg) {
                errorMsg.innerText = message;
            }
            banner.style.display = 'block';
        }

        if (successBanner) {
            successBanner.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Note: Self form is kept as hardcoded HTML, not populated from template
        // The form handler (handleSelfFormSubmit) intercepts the submission
        // and sends data as JSON to the /update-user endpoint

        // Attach birth year validation to form fields
        attachBirthYearValidation();
    });
</script>

<div class="page-heading">
    <div class="container">
        <h1 class="">Welcome, <?= htmlspecialchars($dashboardData['user']['name'] ?? 'User') ?>!</h1>
    </div>
</div>

<div class="container">
    <?php
    // Check if user is new (created within last 7 days)
    if (isset($dashboardData['user']['created_at'])) {
        $createdAt = strtotime($dashboardData['user']['created_at']);
        $sevenDaysAgo = strtotime('-7 days');
        if ($createdAt >= $sevenDaysAgo) {
            $daysAgo = ceil((time() - $createdAt) / 86400);
            $daysLeft = 7 - $daysAgo;
    ?>
    <!-- Welcome Banner for New Users -->
    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert" style="border-left: 4px solid #17a2b8; background-color: #d1ecf1;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <i class="fas fa-star" style="font-size: 1.5rem; color: #17a2b8;"></i>
            <div>
                <strong style="font-size: 1.1rem;">Welcome to Umashakti Dham! 🎉</strong>
                <p style="margin: 4px 0 0 0; font-size: 0.95rem;">
                    Thank you for joining our community! Get started by completing your profile and adding family members. This welcome banner will disappear in <?= $daysLeft ?> day<?= $daysLeft !== 1 ? 's' : '' ?>.
                </p>
            </div>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="margin-top: -25px;">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php
        }
    }
    ?>

    <div class="row">
        <!-- Family Section -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Your Family</h5>
                    <button id="addFamilyButton" class="btn btn-success btn-sm" onclick="toggleAddForm()">
                        <i class="fas fa-user-plus"></i> Add Member
                    </button>
                </div>
                <div class="card-body">
                    <!-- Success/Error Banners for self update -->
                    <div id="selfSuccessBanner" class="alert alert-success alert-dismissible fade show mb-3"
                        style="display: none;" role="alert">
                        <strong>Success!</strong> Profile updated successfully!
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div id="selfErrorBanner" class="alert alert-danger alert-dismissible fade show mb-3"
                        style="display: none;" role="alert">
                        <strong>Error!</strong> <span id="selfErrorMessage">Failed to update profile.</span>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <!-- Family List as Grid Cards -->

                    <!-- Family List Header -->
                    <div
                        style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; padding: 12px; border-bottom: 2px solid #ddd; font-weight: 600; font-size: 0.9rem; background-color: #f5f5f5;">
                        <div>Relation</div>
                        <div>Name</div>
                        <div>Age</div>
                        <div>Village</div>
                        <div>Mosal</div>
                        <div>Actions</div>
                    </div>

                    <!-- Family List as Grid Cards -->
                    <div id="familyList" style="display: flex; flex-direction: column; gap: 0;">
                        <!-- Display user as 'Self' -->
                        <div
                            style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; padding: 12px; border-bottom: 1px solid #ddd; align-items: center;">
                            <div style="font-size: 0.9rem;">
                                <strong><?= htmlspecialchars($dashboardData['user']['relationship'] ?? 'Self') ?></strong>
                            </div>
                            <div style="font-size: 0.9rem;">
                                <?= htmlspecialchars($dashboardData['user']['name'] ?? 'User') ?></div>
                            <div style="font-size: 0.9rem;">
                                <?php
                                $birthYear = $dashboardData['user']['birth_year'] ?? null;
                                if ($birthYear) {
                                    $age = date('Y') - $birthYear;
                                    echo $age;
                                } else {
                                    echo '-';
                                }
                                ?>
                            </div>
                            <div style="font-size: 0.9rem;">
                                <?= htmlspecialchars($dashboardData['user']['village'] ?? '-') ?></div>
                            <div style="font-size: 0.9rem;">
                                <?= htmlspecialchars($dashboardData['user']['mosal'] ?? '-') ?></div>
                            <div style="display: flex; gap: 4px;">
                                <button class="btn btn-sm btn-primary" onclick="toggleEditForm('editSelfForm')"
                                    style="padding: 4px 8px;">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Self User Form (Expandable) -->
                        <div id="editSelfForm"
                            style="display: none; padding: 12px; background: #f8f9fa; border-bottom: 1px solid #ddd;">
                            <div id="editSelfFormContent" style="width: 100%;">
                                <form onsubmit="handleSelfFormSubmit(event)"
                                    style="background: #f8f9fa; padding: 12px; border-radius: 4px; width: 100%;">
                                    <!-- Hidden user ID field -->
                                    <input type="hidden" id="userId" name="id"
                                        value="<?= htmlspecialchars($dashboardData['user']['id'] ?? '') ?>">
                                    <input type="hidden" name="relation" value="Self">

                                    <!-- Row 1: First Name, Last Name, Birth Year, Gender -->
                                    <div
                                        style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px;">
                                        <div style="grid-column: span 1;">
                                            <label for="selfFirstName"
                                                style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">First
                                                Name</label>
                                            <input type="text" id="selfFirstName" name="first_name"
                                                class="form-control form-control-sm"
                                                value="<?= htmlspecialchars($dashboardData['user']['first_name'] ?? '') ?>"
                                                required style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                        </div>
                                        <div style="grid-column: span 1;">
                                            <label for="selfLastName"
                                                style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Last
                                                Name</label>
                                            <input type="text" id="selfLastName" name="last_name"
                                                class="form-control form-control-sm"
                                                value="<?= htmlspecialchars($dashboardData['user']['last_name'] ?? '') ?>"
                                                style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                        </div>
                                        <div style="grid-column: span 1;">
                                            <label for="selfBirthYear"
                                                style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Birth
                                                Year</label>
                                            <input type="text" id="selfBirthYear" name="birth_year"
                                                class="form-control form-control-sm"
                                                value="<?= htmlspecialchars($dashboardData['user']['birth_year'] ?? '') ?>"
                                                placeholder="YYYY" pattern="\d{4}" inputmode="numeric" maxlength="4"
                                                style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                            <small class="invalid-feedback"></small>
                                        </div>
                                        <div style="grid-column: span 1;">
                                            <label for="selfGender"
                                                style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Gender</label>
                                            <select id="selfGender" name="gender" class="form-control form-control-sm"
                                                style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                                <option value="male" <?= ($dashboardData['user']['gender'] ?? null) === 'male' ? 'selected' : '' ?>>Male</option>
                                                <option value="female" <?= ($dashboardData['user']['gender'] ?? null) === 'female' ? 'selected' : '' ?>>Female</option>
                                                <option value="other" <?= ($dashboardData['user']['gender'] ?? null) === 'other' ? 'selected' : '' ?>>Other</option>
                                                <option value="prefer_not_say" <?= ($dashboardData['user']['gender'] ?? null) === 'prefer_not_say' ? 'selected' : '' ?>>Prefer not</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Row 2: Phone, Email, Village, Mosal -->
                                    <div
                                        style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px;">
                                        <div style="grid-column: span 1;">
                                            <label for="selfPhone"
                                                style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Phone</label>
                                            <input type="text" id="selfPhone" name="phone"
                                                class="form-control form-control-sm"
                                                value="<?= htmlspecialchars($dashboardData['user']['phone_e164'] ?? '') ?>"
                                                style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                        </div>
                                        <div style="grid-column: span 1;">
                                            <label for="selfEmail"
                                                style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Email</label>
                                            <input type="email" id="selfEmail" name="email"
                                                class="form-control form-control-sm"
                                                value="<?= htmlspecialchars($dashboardData['user']['email'] ?? '') ?>"
                                                readonly
                                                style="font-size: 0.9rem; padding: 6px 8px; width: 100%; background-color: #e9ecef;">
                                        </div>
                                        <div style="grid-column: span 1;">
                                            <label for="selfVillage"
                                                style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Village
                                                (Vatan)</label>
                                            <input type="text" id="selfVillage" name="village"
                                                class="form-control form-control-sm"
                                                value="<?= htmlspecialchars($dashboardData['user']['village'] ?? '') ?>"
                                                style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                        </div>
                                        <div style="grid-column: span 1;">
                                            <label for="selfMosal"
                                                style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Mosal</label>
                                            <input type="text" id="selfMosal" name="mosal"
                                                class="form-control form-control-sm"
                                                value="<?= htmlspecialchars($dashboardData['user']['mosal'] ?? '') ?>"
                                                style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                        </div>
                                    </div>

                                    <!-- Row 3: Occupation, Relation, Business Info -->
                                    <div
                                        style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 12px;">
                                       
                                        <div style="grid-column: span 1;">
                                            <label for="selfRelationship"
                                                style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Relation</label>
                                            <select id="selfRelationship" name="relationship"
                                                class="form-control form-control-sm"
                                                style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                                <option value="self" selected>Self</option>
                                            </select>
                                        </div>
                                         <div style="grid-column: span 1;">
                                            <label for="selfOccupation"
                                                style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Occupation</label>
                                            <input type="text" id="selfOccupation" name="occupation"
                                                class="form-control form-control-sm"
                                                value="<?= htmlspecialchars($dashboardData['user']['occupation'] ?? '') ?>"
                                                style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                        </div>
                                        <div style="grid-column: span 1;">
                                            <label for="selfBusinessInfo"
                                                style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Business
                                                Info</label>
                                            <textarea id="selfBusinessInfo" name="business_info"
                                                class="form-control form-control-sm"
                                                style="font-size: 0.9rem; padding: 6px 8px; min-height: 32px; width: 100%; resize: vertical;"><?= htmlspecialchars($dashboardData['user']['business_info'] ?? '') ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div style="display: flex; gap: 6px; justify-content: flex-end; margin-top: 8px;">
                                        <button type="submit" class="btn btn-success btn-sm"
                                            style="padding: 4px 12px; font-size: 0.85rem;">
                                            <i class="fas fa-save"></i> Save
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            onclick="toggleEditForm('editSelfForm')"
                                            style="padding: 4px 12px; font-size: 0.85rem;">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Family Members -->
                        <?php if (!empty($dashboardData['family']) && is_array($dashboardData['family'])): ?>
                            <?php foreach ($dashboardData['family'] as $index => $member): ?>
                                <!-- Family Member Row -->
                                <div
                                    style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; padding: 12px; border-bottom: 1px solid #ddd; align-items: center;">
                                    <div style="font-size: 0.9rem;">
                                        <strong><?= htmlspecialchars($member['relationship'] ?? '') ?></strong></div>
                                    <div style="font-size: 0.9rem;">
                                        <?= htmlspecialchars($member['first_name'] ?? '') ?>        <?= !empty($member['last_name']) ? ' ' . htmlspecialchars($member['last_name']) : '' ?>
                                    </div>
                                    <div style="font-size: 0.9rem;">
                                        <?php
                                        $birthYear = $member['birth_year'] ?? null;
                                        if ($birthYear) {
                                            $age = date('Y') - $birthYear;
                                            echo $age;
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                    <div style="font-size: 0.9rem;"><?= htmlspecialchars($member['village'] ?? '-') ?></div>
                                    <div style="font-size: 0.9rem;"><?= htmlspecialchars($member['mosal'] ?? '-') ?></div>
                                    <div style="display: flex; gap: 4px;">
                                        <button class="btn btn-sm btn-primary" type="button" onclick="toggleInlineForm('editFamilyForm<?= $member['id'] ?>', 'familyFormTemplate', {
                                            familyId: '<?= htmlspecialchars($member['id'] ?? '') ?>',
                                            actionUrl: '/update-family-member/<?= $member['id'] ?? '' ?>',
                                            firstName: '<?= htmlspecialchars($member['first_name']) ?>',
                                            lastName: '<?= htmlspecialchars($member['last_name']) ?>',
                                            birthYear: '<?= htmlspecialchars($member['birth_year']) ?>',
                                            genderMale: '<?= $member['gender'] === 'male' ? 'selected' : '' ?>',
                                            genderFemale: '<?= $member['gender'] === 'female' ? 'selected' : '' ?>',
                                            genderOther: '<?= $member['gender'] === 'other' ? 'selected' : '' ?>',
                                            genderPreferNotSay: '<?= $member['gender'] === 'prefer_not_say' ? 'selected' : '' ?>',
                                            phone: '<?= htmlspecialchars($member['phone_e164'] ?? '') ?>',
                                            email: '<?= htmlspecialchars($member['email'] ?? '') ?>',
                                            emailReadonly: '',
                                            occupation: '<?= htmlspecialchars($member['occupation'] ?? '') ?>',
                                            businessInfo: '<?= htmlspecialchars($member['business_info'] ?? '') ?>',
                                            village: '<?= htmlspecialchars($member['village'] ?? '') ?>',
                                            mosal: '<?= htmlspecialchars($member['mosal'] ?? '') ?>',
                                            relationshipSelf: '<?= $member['relationship'] === 'self' ? 'selected' : '' ?>',
                                            relationshipSpouse: '<?= $member['relationship'] === 'spouse' ? 'selected' : '' ?>',
                                            relationshipChild: '<?= $member['relationship'] === 'child' ? 'selected' : '' ?>',
                                            relationshipFather: '<?= $member['relationship'] === 'father' ? 'selected' : '' ?>',
                                            relationshipMother: '<?= $member['relationship'] === 'mother' ? 'selected' : '' ?>',
                                            relationshipSibling: '<?= $member['relationship'] === 'sibling' ? 'selected' : '' ?>',
                                            relationshipBrother: '<?= $member['relationship'] === 'brother' ? 'selected' : '' ?>',
                                            relationshipSister: '<?= $member['relationship'] === 'sister' ? 'selected' : '' ?>',
                                            relationshipFatherInLaw: '<?= $member['relationship'] === 'father-in-law' ? 'selected' : '' ?>',
                                            relationshipMotherInLaw: '<?= $member['relationship'] === 'mother-in-law' ? 'selected' : '' ?>',
                                            relationshipOther: '<?= $member['relationship'] === 'other' ? 'selected' : '' ?>',
                                            formId: 'editFamilyForm<?= $member['id'] ?>'
                                        })" style="padding: 4px 8px;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" type="button" onclick="handleDeleteFamilyMember('<?= htmlspecialchars($member['id']) ?>', '<?= htmlspecialchars($member['first_name'] . ' ' . ($member['last_name'] ?? '')) ?>')" style="padding: 4px 8px;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Family Member Edit Form (Expandable) -->
                                <div id="editFamilyForm<?= $member['id'] ?>"
                                    style="display: none; padding: 12px; background: #f8f9fa; border-bottom: 1px solid #ddd;">
                                    <div id="editFamilyForm<?= $member['id'] ?>Content"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Add Family Member Form -->
                    <div id="addForm"
                        style="display: none; margin-top: 8px; background: #f8f9fa; padding: 12px; border-radius: 4px; width: 100%;">
                        <form onsubmit="handleAddFormSubmit(event)" style="width: 100%;">
                            <!-- Row 1: First Name, Last Name, Birth Year, Gender -->
                            <div
                                style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px;">
                                <div style="grid-column: span 1;">
                                    <label for="addFirstName"
                                        style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">First
                                        Name*</label>
                                    <input type="text" id="addFirstName" name="first_name"
                                        class="form-control form-control-sm" required
                                        style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                </div>
                                <div style="grid-column: span 1;">
                                    <label for="addLastName"
                                        style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Last
                                        Name</label>
                                    <input type="text" id="addLastName" name="last_name"
                                        class="form-control form-control-sm"
                                        style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                </div>
                                <div style="grid-column: span 1;">
                                    <label for="addBirthYear"
                                        style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Birth
                                        Year</label>
                                    <input type="text" id="addBirthYear" name="birth_year"
                                        class="form-control form-control-sm" placeholder="YYYY" pattern="\d{4}"
                                        inputmode="numeric" maxlength="4"
                                        style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                    <small class="invalid-feedback"></small>
                                </div>
                                <div style="grid-column: span 1;">
                                    <label for="addGender"
                                        style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Gender</label>
                                    <select id="addGender" name="gender" class="form-control form-control-sm"
                                        style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                        <option value="prefer_not_say">Prefer not</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Row 2: Phone, Email, Village, Mosal -->
                            <div
                                style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px;">
                                <div style="grid-column: span 1;">
                                    <label for="addPhone"
                                        style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Phone</label>
                                    <input type="text" id="addPhone" name="phone" class="form-control form-control-sm"
                                        style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                </div>
                                <div style="grid-column: span 1;">
                                    <label for="addEmail"
                                        style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Email</label>
                                    <input type="email" id="addEmail" name="email" class="form-control form-control-sm"
                                        style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                </div>
                                <div style="grid-column: span 1;">
                                    <label for="addVillage"
                                        style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Village
                                        (Vatan)</label>
                                    <input type="text" id="addVillage" name="village"
                                        class="form-control form-control-sm"
                                        style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                </div>
                                <div style="grid-column: span 1;">
                                    <label for="addMosal"
                                        style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Mosal</label>
                                    <input type="text" id="addMosal" name="mosal" class="form-control form-control-sm"
                                        style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                </div>
                            </div>

                            <!-- Row 3: Occupation, Relationship, Business Info -->
                            <div
                                style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 12px;">

                                <div style="grid-column: span 1;">
                                    <label for="addRelationship"
                                        style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Relationship*</label>
                                    <select id="addRelationship" name="relationship"
                                        class="form-control form-control-sm" required
                                        style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                        <option value="">Select...</option>
                                        <option value="spouse">Spouse</option>
                                        <option value="child">Child</option>
                                        <option value="mother">Mother</option>
                                        <option value="father">Father</option>
                                        <option value="sibling">Sibling</option>
                                        <option value="brother">Brother</option>
                                        <option value="sister">Sister</option>
                                        <option value="father-in-law">Father-in-law</option>
                                        <option value="mother-in-law">Mother-in-law</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div style="grid-column: span 1;">
                                    <label for="addOccupation"
                                        style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Occupation</label>
                                    <input type="text" id="addOccupation" name="occupation"
                                        class="form-control form-control-sm"
                                        style="font-size: 0.9rem; padding: 6px 8px; width: 100%;">
                                </div>
                                <div style="grid-column: span 1;">
                                    <label for="addBusinessInfo"
                                        style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Business
                                        Info</label>
                                    <textarea id="addBusinessInfo" name="business_info"
                                        class="form-control form-control-sm"
                                        style="font-size: 0.9rem; padding: 6px 8px; min-height: 32px; width: 100%; resize: vertical;"></textarea>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div style="display: flex; gap: 6px; justify-content: flex-end; margin-top: 8px;">
                                <button type="submit" class="btn btn-success btn-sm"
                                    style="padding: 4px 12px; font-size: 0.85rem;">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAddForm()"
                                    style="padding: 4px 12px; font-size: 0.85rem;">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                        <div id="addSuccessMessage" class="alert alert-success mt-2"
                            style="display: none; font-size: 0.9rem; margin-top: 8px !important;">Family member added
                            successfully!</div>
                        <div id="addErrorMessage" class="alert alert-danger mt-2"
                            style="display: none; font-size: 0.9rem; margin-top: 8px !important;">Failed to add family
                            member. Please try again.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events and Tickets Section -->
        <div class="col-md-4">
            <!-- Events Section -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5>Upcoming Events</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php if (!empty($dashboardData['events']) && is_array($dashboardData['events'])): ?>
                            <?php foreach ($dashboardData['events'] as $event): ?>
                                <li class="list-group-item">
                                    <strong><?= htmlspecialchars($event['name']) ?></strong><br>
                                    <?= htmlspecialchars($event['date']) ?>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item">No upcoming events.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Tickets Section -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5>Your Tickets</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php if (!empty($dashboardData['tickets']) && is_array($dashboardData['tickets'])): ?>
                            <?php foreach ($dashboardData['tickets'] as $ticket): ?>
                                <li class="list-group-item">
                                    <strong><?= htmlspecialchars($ticket['event_name']) ?></strong><br>
                                    <button class="btn btn-sm btn-primary">View</button>
                                    <button class="btn btn-sm btn-secondary">Check-in QR</button>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item">No tickets available.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reusable Form Template -->
<script type="text/template" id="familyFormTemplate">
    <form onsubmit="handleEditFamilyFormSubmit(event)" action="{{actionUrl}}" method="POST" style="background: #f8f9fa; padding: 12px; border-radius: 4px; width: 100%;">
        <input type="hidden" name="family_id" value="{{familyId}}">
        
        <!-- Row 1: Names, Birth Year, Gender, Relationship, Village -->
        <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; margin-bottom: 8px;">
            <div style="grid-column: span 1;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">First Name</label>
                <input type="text" name="first_name" class="form-control form-control-sm" value="{{firstName}}" required style="font-size: 0.9rem; padding: 4px 6px; width: 100%;">
            </div>
            <div style="grid-column: span 1;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Last Name</label>
                <input type="text" name="last_name" class="form-control form-control-sm" value="{{lastName}}" style="font-size: 0.9rem; padding: 4px 6px; width: 100%;">
            </div>
            <div style="grid-column: span 1;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Birth Yr</label>
                <input type="text" name="birth_year" class="form-control form-control-sm" value="{{birthYear}}" placeholder="YYYY" pattern="\d{4}" inputmode="numeric" maxlength="4" style="font-size: 0.9rem; padding: 4px 6px; width: 100%;">
                <small class="invalid-feedback"></small>
            </div>
            <div style="grid-column: span 1;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Gender</label>
                <select name="gender" class="form-control form-control-sm" style="font-size: 0.9rem; padding: 4px 6px; width: 100%;">
                    <option value="male" {{genderMale}}>Male</option>
                    <option value="female" {{genderFemale}}>Female</option>
                    <option value="other" {{genderOther}}>Other</option>
                    <option value="prefer_not_say" {{genderPreferNotSay}}>Prefer not</option>
                </select>
            </div>
            <div style="grid-column: span 1;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Relationship*</label>
                <select name="relationship" class="form-control form-control-sm" required style="font-size: 0.9rem; padding: 4px 6px; width: 100%;">
                    <option value="">Select...</option>
                    <option value="self" {{relationshipSelf}}>Self</option>
                    <option value="spouse" {{relationshipSpouse}}>Spouse</option>
                    <option value="child" {{relationshipChild}}>Child</option>
                    <option value="father" {{relationshipFather}}>Father</option>
                    <option value="mother" {{relationshipMother}}>Mother</option>
                    <option value="sibling" {{relationshipSibling}}>Sibling</option>
                    <option value="brother" {{relationshipBrother}}>Brother</option>
                    <option value="sister" {{relationshipSister}}>Sister</option>
                    <option value="father-in-law" {{relationshipFatherInLaw}}>Father-in-law</option>
                    <option value="mother-in-law" {{relationshipMotherInLaw}}>Mother-in-law</option>
                    <option value="other" {{relationshipOther}}>Other</option>
                </select>
            </div>
            <div style="grid-column: span 1;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Village</label>
                <input type="text" name="village" class="form-control form-control-sm" value="{{village}}" style="font-size: 0.9rem; padding: 4px 6px; width: 100%;">
            </div>
        </div>

        <!-- Row 2: Phone, Email, Occupation, Mosal, Business Info -->
        <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; margin-bottom: 8px;">
            <div style="grid-column: span 1;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Phone</label>
                <input type="text" name="phone" class="form-control form-control-sm" value="{{phone}}" style="font-size: 0.9rem; padding: 4px 6px; width: 100%;">
            </div>
            <div style="grid-column: span 1;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Email</label>
                <input type="email" name="email" class="form-control form-control-sm" value="{{email}}" style="font-size: 0.9rem; padding: 4px 6px; width: 100%;">
            </div>
            <div style="grid-column: span 1;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Occupation</label>
                <input type="text" name="occupation" class="form-control form-control-sm" value="{{occupation}}" style="font-size: 0.9rem; padding: 4px 6px; width: 100%;">
            </div>
            <div style="grid-column: span 1;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Mosal</label>
                <input type="text" name="mosal" class="form-control form-control-sm" value="{{mosal}}" style="font-size: 0.9rem; padding: 4px 6px; width: 100%;">
            </div>
            <div style="grid-column: span 2;">
                <label style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 4px;">Business Info</label>
                <textarea name="business_info" class="form-control form-control-sm" style="font-size: 0.9rem; padding: 4px 6px; min-height: 32px; width: 100%; resize: vertical;">{{businessInfo}}</textarea>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div style="display: flex; gap: 6px; justify-content: flex-end; margin-top: 8px;">
            <button type="submit" class="btn btn-success btn-sm" style="padding: 4px 12px; font-size: 0.85rem;">
                <i class="fas fa-save"></i> Save
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleInlineForm('{{formId}}')" style="padding: 4px 12px; font-size: 0.85rem;">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </form>
</script>
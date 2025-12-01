/**
 * Dashboard page JavaScript (extracted from src/Views/dashboard/index.php)
 * - Handles birth year validation
 * - Toggles forms and handles add/edit/delete actions via fetch()
 * - Shows success/error banners
 */

// Validate birth year input
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

// Form toggle helpers
function toggleInlineFormSimple(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    // Toggle Bootstrap d-none class instead of inline styles
    const isHidden = form.classList.contains('d-none') || form.style.display === 'none';
    if (isHidden) {
        form.classList.remove('d-none');
        form.style.display = '';
    } else {
        form.classList.add('d-none');
        form.style.display = 'none';
    }
}

function toggleAddForm() {
    const addForm = document.getElementById('addForm');
    const addButton = document.getElementById('addFamilyButton');
    // Toggle Bootstrap d-none class instead of inline styles
    // This works with both inline styles and Bootstrap classes
    const isHidden = addForm.classList.contains('d-none') || addForm.style.display === 'none';
    if (isHidden) {
        addForm.classList.remove('d-none');
        addForm.style.display = '';
        addButton.classList.add('d-none');
        addButton.style.display = 'none';
    } else {
        addForm.classList.add('d-none');
        addForm.style.display = 'none';
        addButton.classList.remove('d-none');
        addButton.style.display = '';
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
                errorMessage.classList.remove('d-none');
                errorMessage.style.display = 'block';
                if (successMessage) {
                    successMessage.classList.add('d-none');
                    successMessage.style.display = 'none';
                }
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
                successMessage.classList.remove('d-none');
                successMessage.style.display = 'block';
                if (errorMessage) {
                    errorMessage.classList.add('d-none');
                    errorMessage.style.display = 'none';
                }
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
                errorMessage.classList.remove('d-none');
                errorMessage.style.display = 'block';
                if (successMessage) {
                    successMessage.classList.add('d-none');
                    successMessage.style.display = 'none';
                }
            }
        }
    } catch (error) {
        console.error('Exception occurred:', error);
        if (errorMessage) {
            errorMessage.textContent = 'Error: ' + error.message;
            errorMessage.classList.remove('d-none');
            errorMessage.style.display = 'block';
            if (successMessage) {
                successMessage.classList.add('d-none');
                successMessage.style.display = 'none';
            }
        }
    }

    return false;
}

function toggleEditForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    // Toggle Bootstrap d-none class instead of inline styles
    const isHidden = form.classList.contains('d-none') || form.style.display === 'none';
    if (isHidden) {
        form.classList.remove('d-none');
        form.style.display = '';
    } else {
        form.classList.add('d-none');
        form.style.display = 'none';
    }
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
    if (!form) return;
    // Toggle Bootstrap d-none class instead of inline styles
    const isHidden = form.classList.contains('d-none') || form.style.display === 'none';
    if (isHidden) {
        form.innerHTML = populateForm(templateId, data);
        form.classList.remove('d-none');
        form.style.display = '';
    } else {
        form.classList.add('d-none');
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
            console.log('Family member updated successfully!', result);
            // If the server reports rows_affected === 0, show friendly message and do not reload
            if (result.rows_affected !== undefined && parseInt(result.rows_affected) === 0) {
                showFamilyErrorBanner(containerId, 'No changes were made — nothing to update.');
                return false;
            }
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

// Banner helpers
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
        banner.classList.remove('d-none');
        banner.style.display = 'block';
    }

    if (errorBanner) {
        errorBanner.classList.add('d-none');
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
        banner.classList.remove('d-none');
        banner.style.display = 'block';
    }

    if (successBanner) {
        successBanner.classList.add('d-none');
        successBanner.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Attach birth year validation to form fields
    attachBirthYearValidation();

    // NOTE: Add form submit handler is already attached via onsubmit="handleAddFormSubmit(event)" in HTML
    // Do NOT add another event listener here as it would cause duplicate submissions
    
    // Delegate delete button clicks for family rows
    // NOTE: Edit button clicks are now handled by modal-forms.js (uses data-action="edit-family")
    document.addEventListener('click', function (e) {
        const delBtn = e.target.closest && e.target.closest('.btn-delete');
        if (delBtn) {
            e.preventDefault();
            handleDeleteButton(delBtn);
            return;
        }
    });
});

function handleEditButton(btn) {
    const id = btn.dataset.memberId;
    if (!id) return;

    const data = {
        familyId: id,
        actionUrl: '/user/update-family-member/' + id,
        firstName: btn.dataset.firstName || '',
        lastName: btn.dataset.lastName || '',
        birthYear: btn.dataset.birthYear || '',
        phone: btn.dataset.phone || '',
        email: btn.dataset.email || '',
        occupation: btn.dataset.occupation || '',
        businessInfo: btn.dataset.businessInfo || '',
        village: btn.dataset.village || '',
        mosal: btn.dataset.mosal || '',
        formId: 'editFamilyForm' + id,
        // gender / relationship radios/select prefill flags used by template
        genderMale: (btn.dataset.gender === 'male') ? 'selected' : '',
        genderFemale: (btn.dataset.gender === 'female') ? 'selected' : '',
        genderOther: (btn.dataset.gender === 'other') ? 'selected' : '',
        genderPreferNotSay: (btn.dataset.gender === 'prefer_not_say') ? 'selected' : '',
        relationshipSelf: (btn.dataset.relationship === 'self') ? 'selected' : '',
        relationshipSpouse: (btn.dataset.relationship === 'spouse') ? 'selected' : '',
        relationshipChild: (btn.dataset.relationship === 'child') ? 'selected' : '',
        relationshipFather: (btn.dataset.relationship === 'father') ? 'selected' : '',
        relationshipMother: (btn.dataset.relationship === 'mother') ? 'selected' : '',
        relationshipSibling: (btn.dataset.relationship === 'sibling') ? 'selected' : '',
        relationshipBrother: (btn.dataset.relationship === 'brother') ? 'selected' : '',
        relationshipSister: (btn.dataset.relationship === 'sister') ? 'selected' : '',
        relationshipFatherInLaw: (btn.dataset.relationship === 'father-in-law') ? 'selected' : '',
        relationshipMotherInLaw: (btn.dataset.relationship === 'mother-in-law') ? 'selected' : '',
        relationshipOther: (btn.dataset.relationship === 'other') ? 'selected' : ''
    };

    toggleInlineForm('editFamilyForm' + id, 'familyFormTemplate', data);
}

function handleDeleteButton(btn) {
    const id = btn.dataset.memberId;
    const name = btn.dataset.memberName || 'this member';
    if (!id) return;

    handleDeleteFamilyMember(id, name);
}

// End of dashboard.js

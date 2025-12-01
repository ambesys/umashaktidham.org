/**
 * Modal Form Handler - AJAX-based Approach
 * 
 * Pattern from your working project:
 * - Modal div is NOT in DOM initially (page fully clickable)
 * - On button click: fetch form HTML from backend
 * - Backend returns: form HTML pre-populated with user data
 * - Create modal dynamically, insert form, show it
 * 
 * This ensures nothing blocks clicks when page loads
 */

class ModalFormHandler {
    constructor() {
        console.log('[ModalFormHandler] Initializing...');
        this.modal = null;
        this.modalElement = null;
        this.setupEventListeners();
        console.log('[ModalFormHandler] Initialized successfully');
    }

    /**
     * Setup event listeners for all form action buttons
     */
    setupEventListeners() {
        console.log('[ModalFormHandler] Setting up event listeners...');
        // User profile edit button
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="edit-profile"]')) {
                console.log('[ModalFormHandler] Edit profile button clicked');
                e.preventDefault();
                this.openUserProfileForm();
            }
        });

        // Family member edit button
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="edit-family"]')) {
                console.log('[ModalFormHandler] Edit family button clicked');
                e.preventDefault();
                const button = e.target.closest('[data-action="edit-family"]');
                const memberId = button.getAttribute('data-member-id');
                this.openFamilyMemberForm(memberId, 'edit');
            }
        });

        // Add family member button
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="add-family"]')) {
                console.log('[ModalFormHandler] Add family button clicked');
                e.preventDefault();
                this.openFamilyMemberForm(null, 'add');
            }
        });
        console.log('[ModalFormHandler] Event listeners set up successfully');
    }

    /**
     * Create modal dynamically (only when needed)
     * This keeps the page clean - no modal in DOM initially
     */
    createModal() {
        if (this.modalElement && document.body.contains(this.modalElement)) {
            console.log('Modal already exists in DOM');
            return; // Already created and in DOM
        }

        console.log('Creating new modal');

        const modalHTML = `
            <div class="modal fade" id="formModal" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-xl">
                <div class="modal-content">
                  <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="formModalLabel">Form Title</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div id="formModalBody">
                      <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden">Loading...</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="formModalSaveBtn">Save Changes</button>
                  </div>
                </div>
              </div>
            </div>
        `;

        // Create and append to body
        const wrapper = document.createElement('div');
        wrapper.innerHTML = modalHTML;
        this.modalElement = wrapper.firstElementChild;
        document.body.appendChild(this.modalElement);

        // Initialize Bootstrap modal
        this.modal = new bootstrap.Modal(this.modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });

        // Add event listener for when modal is hidden
        this.modalElement.addEventListener('hidden.bs.modal', () => {
            console.log('Modal hidden, cleaning up');
            if (this.modalElement && document.body.contains(this.modalElement)) {
                this.modalElement.remove();
                this.modalElement = null;
                this.modal = null;
            }
        });

        // Setup save button listener
        document.getElementById('formModalSaveBtn').addEventListener('click', () => {
            this.submitForm();
        });
    }

    /**
     * Open user profile form - fetch from backend
     */
    openUserProfileForm() {
        // Create modal if needed
        this.createModal();

        // Show loading state
        document.getElementById('formModalLabel').textContent = 'Loading Profile...';
        document.getElementById('formModalBody').innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        this.modal.show();

        // Fetch form from backend (unified endpoint for main user)
        console.log('Fetching user form from /get-member-form?type=user');
        fetch('/get-member-form?type=user')
            .then(response => {
                console.log('Response status:', response.status, response.statusText);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.text();
            })
            .then(html => {
                console.log('Form HTML received, length:', html.length);
                // Insert form
                document.getElementById('formModalBody').innerHTML = html;
                document.getElementById('formModalLabel').textContent = 'Edit Profile';
                
                // Initialize phone input
                this.initPhoneInput();
                
                // Initialize zipcode autofill
                this.initZipcodeAutofill();
                
                // Reattach form listener
                const form = document.getElementById('memberForm');
                if (form) {
                    form.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.submitForm();
                    });
                }
            })
            .catch(error => {
                console.error('Error loading form:', error);
                document.getElementById('formModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        Failed to load form: ${error.message}
                    </div>
                `;
            });
    }

    /**
     * Open family member form - fetch from backend
     */
    openFamilyMemberForm(memberId, action = 'add') {
        // Create modal if needed
        this.createModal();

        // Show loading state
        document.getElementById('formModalLabel').textContent = 'Loading...';
        document.getElementById('formModalBody').innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        this.modal.show();

        // Build URL with type and ID (unified endpoint)
        let url = `/get-member-form?type=member`;
        if (memberId && action === 'edit') {
            url += `&id=${memberId}`;
        }

        console.log('Fetching member form from:', url);

        // Fetch form from backend
        fetch(url)
            .then(response => {
                console.log('Response status:', response.status, response.statusText);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.text();
            })
            .then(html => {
                console.log('Form HTML received, length:', html.length);
                // Insert form
                document.getElementById('formModalBody').innerHTML = html;
                document.getElementById('formModalLabel').textContent = 
                    action === 'add' ? 'Add Family Member' : 'Edit Family Member';
                
                // Initialize phone input
                this.initPhoneInput();
                
                // Initialize zipcode autofill
                this.initZipcodeAutofill();
                
                // Reattach form listener
                const form = document.getElementById('memberForm');
                if (form) {
                    form.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.submitForm();
                    });
                }
            })
            .catch(error => {
                console.error('Error loading form:', error);
                document.getElementById('formModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        Failed to load form: ${error.message}
                    </div>
                `;
            });
    }

    /**
     * Initialize phone input with intl-tel-input library
     * Auto-formats to E.164 format with country code
     */
    initPhoneInput() {
        const phoneInput = document.getElementById('phone_e164');
        if (!phoneInput) return;

        // Initialize intl-tel-input
        const iti = window.intlTelInput(phoneInput, {
            initialCountry: 'us',
            preferredCountries: ['us', 'in'],
            separateDialCode: false,
            nationalMode: false,
            formatOnDisplay: true,
            autoPlaceholder: 'aggressive',
            customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
                return selectedCountryPlaceholder;
            },
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js'
        });

        // Store the iti instance on the input element so we can access it later
        phoneInput.itiInstance = iti;

        // Validate on blur
        phoneInput.addEventListener('blur', () => {
            if (phoneInput.value.trim()) {
                if (!iti.isValidNumber()) {
                    phoneInput.classList.add('is-invalid');
                } else {
                    phoneInput.classList.remove('is-invalid');
                }
            }
        });

        // Clear validation on input
        phoneInput.addEventListener('input', () => {
            phoneInput.classList.remove('is-invalid');
        });
    }

    /**
     * Initialize zipcode lookup for address autofill
     */
    initZipcodeAutofill() {
        const zipcodeInput = document.getElementById('zip_code');
        if (!zipcodeInput) return;

        zipcodeInput.addEventListener('blur', (e) => {
            const zipcode = e.target.value.trim();
            if (zipcode.length === 5 && /^\d{5}$/.test(zipcode)) {
                this.lookupZipcode(zipcode);
            }
        });

        // Also allow Enter key
        zipcodeInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const zipcode = e.target.value.trim();
                if (zipcode.length === 5 && /^\d{5}$/.test(zipcode)) {
                    this.lookupZipcode(zipcode);
                }
            }
        });
    }

    /**
     * Lookup zipcode and autofill city and state
     */
    lookupZipcode(zipcode) {
        // Use a simple fetch to free API: zippopotam.us
        fetch(`https://api.zippopotam.us/us/${zipcode}`)
            .then(response => {
                if (!response.ok) {
                    console.warn('Zipcode not found');
                    return null;
                }
                return response.json();
            })
            .then(data => {
                if (data && data.places && data.places.length > 0) {
                    const place = data.places[0];
                    const cityInput = document.getElementById('city');
                    const stateInput = document.getElementById('state');
                    
                    if (cityInput) cityInput.value = place['place name'] || '';
                    if (stateInput) stateInput.value = place['state abbreviation'] || place.state || '';
                    
                    // Trigger change event in case other listeners need it
                    if (cityInput) cityInput.dispatchEvent(new Event('change'));
                    if (stateInput) stateInput.dispatchEvent(new Event('change'));
                }
            })
            .catch(error => {
                console.error('Error looking up zipcode:', error);
                // Fail silently - user can still fill manually
            });
    }

    /**
     * Validate form fields
     */
    validateForm() {
        const form = document.getElementById('memberForm');
        if (!form) return false;

        let isValid = true;
        const errors = [];

        // Clear previous errors
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
            const errorMsg = el.parentElement.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        });

        // Validate first_name (required)
        const firstName = form.querySelector('#first_name');
        if (!firstName) {
            console.error('First name field not found in form');
            isValid = false;
        } else if (!firstName.value.trim()) {
            isValid = false;
            firstName.classList.add('is-invalid');
            this.addErrorMessage(firstName, 'First name is required');
            errors.push('First name is required');
        }

        // Validate birth_year if provided (must be a valid number)
        const birthYear = form.querySelector('#birth_year');
        if (birthYear && birthYear.value) {
            const year = parseInt(birthYear.value);
            if (isNaN(year) || year < 1900 || year > new Date().getFullYear()) {
                isValid = false;
                birthYear.classList.add('is-invalid');
                this.addErrorMessage(birthYear, 'Enter a valid birth year (1900 to current year)');
                errors.push('Invalid birth year');
            }
        }

        // Validate email if provided (must be valid format)
        const email = form.querySelector('#email');
        if (email && email.value && !this.isValidEmail(email.value)) {
            isValid = false;
            email.classList.add('is-invalid');
            this.addErrorMessage(email, 'Enter a valid email address');
            errors.push('Invalid email format');
        }

        // Show error banner if validation fails
        if (!isValid) {
            this.showErrorBanner('Please fix the errors below');
        }

        return isValid;
    }

    /**
     * Add error message to a field
     */
    addErrorMessage(field, message) {
        // Safety check - if field is null, don't try to add message
        if (!field) return;
        
        // Remove any existing error message for this field
        const existingError = field.parentElement.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }

        // Create and insert error message
        const errorMsg = document.createElement('small');
        errorMsg.className = 'error-message show';
        errorMsg.textContent = message;
        field.parentElement.appendChild(errorMsg);
    }

    /**
     * Check if email is valid
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Submit form via AJAX
     */
    submitForm() {
        // Get the form (unified name across all forms)
        let form = document.getElementById('memberForm');

        if (!form) {
            console.error('No form found');
            return;
        }

        // Validate form before submitting
        if (!this.validateForm()) {
            return;
        }

        const formData = new FormData(form);
        
        // Convert phone to E.164 format if intl-tel-input is initialized
        const phoneInput = document.getElementById('phone_e164');
        if (phoneInput && phoneInput.itiInstance) {
            const iti = phoneInput.itiInstance;
            if (phoneInput.value.trim()) {
                if (iti.isValidNumber()) {
                    const e164Number = iti.getNumber(); // Gets full E.164 format
                    formData.set('phone_e164', e164Number);
                    console.log('Phone converted to E.164:', e164Number);
                } else {
                    this.showErrorBanner('Please enter a valid phone number');
                    saveBtn.textContent = originalText;
                    saveBtn.disabled = false;
                    return;
                }
            }
        }
        
        // DEBUG: Log all form data being sent
        console.log('FORM DATA BEFORE SUBMIT:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: "${value}"`);
        }
            // Extra logging for member_id
            console.log('[MODAL-FORMS] member_id value:', formData.get('member_id'));
        
        // Clean up empty numeric fields to prevent database errors
        // If birth_year is empty, remove it so database receives NULL instead of empty string
        if (!formData.get('birth_year')) {
            formData.delete('birth_year');
        }
        
        // DEBUG: Log member_id specifically
        console.log('member_id being sent:', formData.get('member_id'));
        
        // Show loading state
        const saveBtn = document.getElementById('formModalSaveBtn');
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Saving...';
        saveBtn.disabled = true;

        fetch('/save-member', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('SaveMember response object:', data);
                // If server says update happened but rows_affected === 0, show a friendly message (no-op update)
                if (data.rows_affected !== undefined && parseInt(data.rows_affected) === 0) {
                    this.showErrorBanner('No changes made — nothing to update.');
                    // Re-enable save button and stop; do not reload page
                    saveBtn.textContent = originalText;
                    saveBtn.disabled = false;
                    return; // don't proceed with hide/reload
                }
                // Show success banner in modal before closing
                this.showSuccessBanner('Changes saved successfully!');
                
                // Store success message in sessionStorage so it shows after reload
                sessionStorage.setItem('dashboardSuccessMessage', 'Member information updated successfully!');
                
                // Close the modal after brief delay
                setTimeout(() => {
                    if (this.modal) {
                        this.modal.hide();
                    }
                }, 1500);
                
                // Reload page to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1600);
            } else {
                // Show error banner
                this.showErrorBanner('Error: ' + (data.message || 'Unknown error'));
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showErrorBanner('Failed to save: ' + error.message);
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
        });
    }

    /**
     * Show success banner inside the modal form
     */
    showSuccessBanner(message) {
        const form = document.getElementById('memberForm');
        if (!form) return;

        // Remove any existing banners
        const existingBanner = form.querySelector('.alert-banner');
        if (existingBanner) existingBanner.remove();

        // Create success banner
        const banner = document.createElement('div');
        banner.className = 'alert-banner alert-success';
        banner.innerHTML = `
            <i class="fas fa-check-circle"></i> ${message}
        `;
        
        // Insert at top of form
        form.insertBefore(banner, form.firstChild);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (banner.parentNode) banner.remove();
        }, 3000);
    }

    /**
     * Show error banner inside the modal form
     */
    showErrorBanner(message) {
        const form = document.getElementById('memberForm');
        if (!form) return;

        // Remove any existing banners
        const existingBanner = form.querySelector('.alert-banner');
        if (existingBanner) existingBanner.remove();

        // Create error banner
        const banner = document.createElement('div');
        banner.className = 'alert-banner alert-danger';
        banner.innerHTML = `
            <i class="fas fa-exclamation-circle"></i> ${message}
        `;
        
        // Insert at top of form
        form.insertBefore(banner, form.firstChild);
        
        // Auto-remove after 4 seconds
        setTimeout(() => {
            if (banner.parentNode) banner.remove();
        }, 4000);
    }

    /**
     * Show success banner on page (for family members table)
     */
    showPageSuccessBanner(message) {
        // Remove any existing page banners
        const existingBanner = document.querySelector('.page-alert-banner');
        if (existingBanner) existingBanner.remove();

        // Create page-level success banner
        const banner = document.createElement('div');
        banner.className = 'page-alert-banner alert-success';
        banner.innerHTML = `
            <div class="container">
                <i class="fas fa-check-circle"></i> ${message}
            </div>
        `;
        
        // Insert at top of body (after navbar)
        document.body.insertBefore(banner, document.body.firstChild);
        
        // Auto-remove after 4 seconds
        setTimeout(() => {
            if (banner.parentNode) banner.remove();
        }, 4000);
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
    console.log('[ModalFormHandler] DOMContentLoaded event fired');
    const handler = new ModalFormHandler();
    
    // Check if there's a success message from previous save operation
    const successMessage = sessionStorage.getItem('dashboardSuccessMessage');
    if (successMessage) {
        console.log('[ModalFormHandler] Found success message:', successMessage);
        // Show the success banner on the page
        handler.showPageSuccessBanner(successMessage);
        // Clear the flag so it doesn't show again on subsequent page loads
        sessionStorage.removeItem('dashboardSuccessMessage');
    }
    
    console.log('[ModalFormHandler] Initialization complete');
});


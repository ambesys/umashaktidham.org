/**
 * Admin Users Management JavaScript
 * Handles user and family member CRUD operations via modal forms
 */

class AdminUserManager {
    constructor() {
        this.modal = null;
        this.modalElement = null;
        this.init();
    }

    init() {
        console.log('[AdminUserManager] Initializing...');
        
        // Wait for Bootstrap and modal HTML to be ready
        if (typeof bootstrap === 'undefined') {
            console.error('[AdminUserManager] Bootstrap not loaded');
            return;
        }

        // Initialize modal
        const modalEl = document.getElementById('formModal');
        if (modalEl) {
            this.modalElement = modalEl;
            this.modal = new bootstrap.Modal(modalEl);
            console.log('[AdminUserManager] Modal initialized');
        }

        // Bind save button
        const saveBtn = document.getElementById('formModalSaveBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                this.submitForm();
            });
        }

        // Check for success message from session storage
        const successMessage = sessionStorage.getItem('adminSuccessMessage');
        if (successMessage) {
            this.showPageSuccessBanner(successMessage);
            sessionStorage.removeItem('adminSuccessMessage');
        }
    }

    /**
     * Open form to add new user
     */
    openAddUserForm() {
        console.log('[AdminUserManager] Opening add user form');
        
        document.getElementById('formModalLabel').textContent = 'Add New User';
        document.getElementById('formModalBody').innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        this.modal.show();

        fetch('/admin/get-admin-member-form?type=user')
            .then(response => response.text())
            .then(html => {
                document.getElementById('formModalBody').innerHTML = html;
                document.getElementById('formModalLabel').textContent = 'Add New User';
                
                // Initialize phone input if available
                this.initPhoneInput();
            })
            .catch(error => {
                console.error('Error loading form:', error);
                document.getElementById('formModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Error loading form. Please try again.
                    </div>
                `;
            });
    }

    /**
     * Open form to edit existing user
     */
    openEditUserForm(userId) {
        console.log('[AdminUserManager] Opening edit user form for user:', userId);
        
        document.getElementById('formModalLabel').textContent = 'Edit User';
        document.getElementById('formModalBody').innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        this.modal.show();

        fetch(`/admin/get-admin-member-form?type=user&user_id=${userId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('formModalBody').innerHTML = html;
                document.getElementById('formModalLabel').textContent = 'Edit User';
                
                // Initialize phone input if available
                this.initPhoneInput();
            })
            .catch(error => {
                console.error('Error loading form:', error);
                document.getElementById('formModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Error loading form. Please try again.
                    </div>
                `;
            });
    }

    /**
     * Open form to add family member
     */
    openAddFamilyMemberForm(userId) {
        console.log('[AdminUserManager] Opening add family member form for user:', userId);
        
        document.getElementById('formModalLabel').textContent = 'Add Family Member';
        document.getElementById('formModalBody').innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        this.modal.show();

        fetch(`/admin/get-admin-member-form?type=member&user_id=${userId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('formModalBody').innerHTML = html;
                document.getElementById('formModalLabel').textContent = 'Add Family Member';
                
                // Initialize phone input if available
                this.initPhoneInput();
            })
            .catch(error => {
                console.error('Error loading form:', error);
                document.getElementById('formModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Error loading form. Please try again.
                    </div>
                `;
            });
    }

    /**
     * Open form to edit family member
     */
    openEditFamilyMemberForm(memberId, userId) {
        console.log('[AdminUserManager] Opening edit family member form for member:', memberId);
        
        document.getElementById('formModalLabel').textContent = 'Edit Family Member';
        document.getElementById('formModalBody').innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        this.modal.show();

        fetch(`/admin/get-admin-member-form?type=member&id=${memberId}&user_id=${userId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('formModalBody').innerHTML = html;
                document.getElementById('formModalLabel').textContent = 'Edit Family Member';
                
                // Initialize phone input if available
                this.initPhoneInput();
            })
            .catch(error => {
                console.error('Error loading form:', error);
                document.getElementById('formModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Error loading form. Please try again.
                    </div>
                `;
            });
    }

    /**
     * Initialize phone input with intl-tel-input
     */
    initPhoneInput() {
        const phoneInput = document.getElementById('phone_e164');
        if (!phoneInput || !window.intlTelInput) return;

        const iti = window.intlTelInput(phoneInput, {
            initialCountry: 'us',
            preferredCountries: ['us', 'in'],
            separateDialCode: false,
            nationalMode: false,
            formatOnDisplay: true,
            autoPlaceholder: 'aggressive',
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/utils.js'
        });

        phoneInput.itiInstance = iti;

        phoneInput.addEventListener('blur', () => {
            if (phoneInput.value.trim() && !iti.isValidNumber()) {
                phoneInput.classList.add('is-invalid');
            } else {
                phoneInput.classList.remove('is-invalid');
            }
        });

        phoneInput.addEventListener('input', () => {
            phoneInput.classList.remove('is-invalid');
        });
    }

    /**
     * Submit form
     */
    submitForm() {
        const form = document.getElementById('memberForm');
        if (!form) {
            console.error('No form found');
            return;
        }

        // Validate
        const firstName = form.querySelector('#first_name');
        if (!firstName || !firstName.value.trim()) {
            this.showErrorBanner('First name is required');
            if (firstName) firstName.focus();
            return;
        }

        const formData = new FormData(form);

        // Convert phone to E.164 format if intl-tel-input is initialized
        const phoneInput = document.getElementById('phone_e164');
        if (phoneInput && phoneInput.itiInstance) {
            const iti = phoneInput.itiInstance;
            if (phoneInput.value.trim()) {
                if (iti.isValidNumber()) {
                    const e164Number = iti.getNumber();
                    formData.set('phone_e164', e164Number);
                } else {
                    this.showErrorBanner('Please enter a valid phone number');
                    return;
                }
            }
        }

        const saveBtn = document.getElementById('formModalSaveBtn');
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Saving...';
        saveBtn.disabled = true;

        fetch('/admin/save-admin-member', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showSuccessBanner('Changes saved successfully!');
                sessionStorage.setItem('adminSuccessMessage', data.message || 'Changes saved successfully!');
                
                setTimeout(() => {
                    if (this.modal) {
                        this.modal.hide();
                    }
                }, 1500);
                
                setTimeout(() => {
                    window.location.reload();
                }, 1600);
            } else {
                this.showErrorBanner('Error: ' + (data.message || 'Unknown error'));
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showErrorBanner('Error saving. Please try again.');
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
        });
    }

    /**
     * Show success banner in modal
     */
    showSuccessBanner(message) {
        const modalBody = document.getElementById('formModalBody');
        const existing = modalBody.querySelector('.alert-banner');
        if (existing) existing.remove();

        const banner = document.createElement('div');
        banner.className = 'alert-banner alert-success';
        banner.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        modalBody.insertBefore(banner, modalBody.firstChild);
    }

    /**
     * Show error banner in modal
     */
    showErrorBanner(message) {
        const modalBody = document.getElementById('formModalBody');
        const existing = modalBody.querySelector('.alert-banner');
        if (existing) existing.remove();

        const banner = document.createElement('div');
        banner.className = 'alert-banner alert-danger';
        banner.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        modalBody.insertBefore(banner, modalBody.firstChild);
    }

    /**
     * Show page-level success banner
     */
    showPageSuccessBanner(message) {
        const banner = document.createElement('div');
        banner.className = 'page-alert-banner';
        banner.innerHTML = `<i class="fas fa-check-circle me-2"></i>${message}`;
        document.body.appendChild(banner);

        setTimeout(() => {
            banner.style.opacity = '0';
            setTimeout(() => banner.remove(), 300);
        }, 3000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.adminUserManager = new AdminUserManager();
});

// Global functions for onclick handlers
function openAddUserForm() {
    window.adminUserManager.openAddUserForm();
}

function openEditUserForm(userId) {
    window.adminUserManager.openEditUserForm(userId);
}

function openAddFamilyMemberForm(userId) {
    window.adminUserManager.openAddFamilyMemberForm(userId);
}

function openEditFamilyMemberForm(memberId, userId) {
    window.adminUserManager.openEditFamilyMemberForm(memberId, userId);
}

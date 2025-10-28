// This file contains JavaScript functionality specific to the user dashboard.

document.addEventListener('DOMContentLoaded', function() {
    const userDetailsForm = document.getElementById('user-details-form');
    const familyDetailsForm = document.getElementById('family-details-form');
    
    if (userDetailsForm) {
        userDetailsForm.addEventListener('submit', function(event) {
            event.preventDefault();
            // Add AJAX call to update user details
            const formData = new FormData(userDetailsForm);
            fetch('/update-user-details', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User details updated successfully!');
                } else {
                    alert('Error updating user details: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }

    if (familyDetailsForm) {
        familyDetailsForm.addEventListener('submit', function(event) {
            event.preventDefault();
            // Add AJAX call to update family details
            const formData = new FormData(familyDetailsForm);
            fetch('/update-family-details', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Family details updated successfully!');
                } else {
                    alert('Error updating family details: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }
});
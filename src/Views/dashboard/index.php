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
</style>

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

    function handleAddFormSubmit(event) {
        event.preventDefault();
        const successMessage = document.getElementById('addSuccessMessage');
        const errorMessage = document.getElementById('addErrorMessage');

        // Simulate form submission logic
        const isSuccess = Math.random() > 0.5; // Replace with actual success condition

        if (isSuccess) {
            successMessage.style.display = 'block';
            errorMessage.style.display = 'none';
        } else {
            successMessage.style.display = 'none';
            errorMessage.style.display = 'block';
        }

        setTimeout(() => {
            successMessage.style.display = 'none';
            errorMessage.style.display = 'none';
            toggleAddForm();
        }, 3000); // Hide messages and reset form after 3 seconds
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
        event.preventDefault(); // Prevent form from reloading the page

        console.log('Form submission started');

        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        console.log('Form data:', data);

        fetch('/update-user', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        })
            .then(response => {
                console.log('Response received:', response);
                if (!response.ok) {
                    throw new Error('Failed to update profile');
                }
                return response.json();
            })
            .then(result => {
                console.log('Success result:', result);
                // Show success banner
                document.getElementById('selfSuccessBanner').style.display = 'block';
                document.getElementById('selfErrorBanner').style.display = 'none';

                // Update the table entry for "Self"
                const selfRow = document.querySelector('tr td:contains("Self")').parentElement;
                selfRow.children[0].textContent = data.first_name;
            })
            .catch(error => {
                console.error('Error occurred:', error);
                // Show error banner
                document.getElementById('selfSuccessBanner').style.display = 'none';
                document.getElementById('selfErrorBanner').style.display = 'block';
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const selfData = {
            actionUrl: '/update-user',
            firstName: "<?= htmlspecialchars($dashboardData['user']['name'] ?? 'User') ?>",
            lastName: '',
            birthYear: '',
            genderMale: '',
            genderFemale: '',
            genderOther: '',
            genderPreferNotSay: '',
            phone: "<?= htmlspecialchars($dashboardData['user']['phone_e164'] ?? '') ?>",
            email: "<?= htmlspecialchars($dashboardData['user']['email'] ?? '') ?>",
            emailReadonly: 'readonly',
            occupation: "<?= htmlspecialchars($dashboardData['user']['occupation'] ?? '') ?>",
            businessInfo: "<?= htmlspecialchars($dashboardData['user']['business_info'] ?? '') ?>",
            relation: 'Self',
            relationReadonly: 'readonly',
            formId: 'editSelfFormContent'
        };
        const selfFormContent = document.getElementById('editSelfFormContent');
        if (selfFormContent) {
            selfFormContent.innerHTML = populateForm('familyFormTemplate', selfData);
        }
    });
</script>

<div class="page-heading">
    <div class="container">
        <h1 class="">Welcome, <?= htmlspecialchars($dashboardData['user']['name'] ?? 'User') ?>!</h1>
    </div>
</div>

<div class="container">
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
                    <table class="table table-hover table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>Name</th>
                                <th>Relation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Display user as 'Self' -->
                            <tr>
                                <td><?= htmlspecialchars($dashboardData['user']['name'] ?? 'User') ?></td>
                                <td>Self</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="toggleEditForm('editSelfForm')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <!-- Self User Form -->
                            <tr id="editSelfForm" style="display: none;">
                                <td colspan="3">
                                    <div id="editSelfFormContent">
                                        <form onsubmit="handleSelfFormSubmit(event)">
                                            <!-- Form fields dynamically populated -->
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="selfFirstName">First Name</label>
                                                    <input type="text" id="selfFirstName" name="first_name" class="form-control form-control-sm" value="<?= htmlspecialchars($dashboardData['user']['name'] ?? 'User') ?>" required>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="selfEmail">Email</label>
                                                    <input type="email" id="selfEmail" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($dashboardData['user']['email'] ?? '') ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="selfPhone">Phone</label>
                                                    <input type="text" id="selfPhone" name="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($dashboardData['user']['phone_e164'] ?? '') ?>">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="selfOccupation">Occupation</label>
                                                    <input type="text" id="selfOccupation" name="occupation" class="form-control form-control-sm" value="<?= htmlspecialchars($dashboardData['user']['occupation'] ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="selfBusinessInfo">Business Info</label>
                                                <textarea id="selfBusinessInfo" name="business_info" class="form-control form-control-sm"><?= htmlspecialchars($dashboardData['user']['business_info'] ?? '') ?></textarea>
                                            </div>
                                            <input type="hidden" name="relation" value="Self">
                                            <div class="d-flex justify-content-between">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-save"></i> Save
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEditForm('editSelfForm')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </div>
                                        </form>
                                        <div id="selfSuccessBanner" class="alert alert-success mt-3" style="display: none;">Profile updated successfully!</div>
                                        <div id="selfErrorBanner" class="alert alert-danger mt-3" style="display: none;">Failed to update profile. Please try again.</div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Display other family members -->
                            <?php if (!empty($dashboardData['family']) && is_array($dashboardData['family'])): ?>
                                <?php foreach ($dashboardData['family'] as $index => $member): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($member['first_name']) ?></td>
                                        <td><?= htmlspecialchars($member['relationship']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="toggleInlineForm('editFamilyForm<?= $index ?>', 'familyFormTemplate', {
                                                actionUrl: '/update-family-member/<?= $index ?>',
                                                firstName: '<?= htmlspecialchars($member['first_name']) ?>',
                                                lastName: '<?= htmlspecialchars($member['last_name']) ?>',
                                                birthYear: '<?= htmlspecialchars($member['birth_year']) ?>',
                                                genderMale: '<?= $member['gender'] === 'male' ? 'selected' : '' ?>',
                                                genderFemale: '<?= $member['gender'] === 'female' ? 'selected' : '' ?>',
                                                genderOther: '<?= $member['gender'] === 'other' ? 'selected' : '' ?>',
                                                genderPreferNotSay: '<?= $member['gender'] === 'prefer_not_say' ? 'selected' : '' ?>',
                                                phone: '<?= htmlspecialchars($member['phone_e164']) ?>',
                                                email: '<?= htmlspecialchars($member['email']) ?>',
                                                emailReadonly: '',
                                                occupation: '<?= htmlspecialchars($member['occupation']) ?>',
                                                businessInfo: '<?= htmlspecialchars($member['business_info']) ?>',
                                                formId: 'editFamilyForm<?= $index ?>'
                                            })">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Family Member Forms -->
                                    <tr id="editFamilyForm<?= $index ?>" style="display: none;">
                                        <td colspan="3">
                                            <div id="editFamilyForm<?= $index ?>Content"></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Add Family Member Form -->
                    <div id="addForm" style="display: none; margin-top: 10px;">
                        <form onsubmit="handleAddFormSubmit(event)">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="addFirstName">First Name</label>
                                    <input type="text" id="addFirstName" class="form-control form-control-sm" placeholder="Enter first name" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="addLastName">Last Name</label>
                                    <input type="text" id="addLastName" class="form-control form-control-sm" placeholder="Enter last name">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="addBirthYear">Birth Year</label>
                                    <input type="number" id="addBirthYear" class="form-control form-control-sm" placeholder="Enter birth year">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="addGender">Gender</label>
                                    <select id="addGender" class="form-control form-control-sm">
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                        <option value="prefer_not_say">Prefer not to say</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="addPhone">Phone</label>
                                    <input type="text" id="addPhone" class="form-control form-control-sm" placeholder="Enter phone number">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="addEmail">Email</label>
                                    <input type="email" id="addEmail" class="form-control form-control-sm" placeholder="Enter email">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="addOccupation">Occupation</label>
                                    <input type="text" id="addOccupation" class="form-control form-control-sm" placeholder="Enter occupation">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="addBusinessInfo">Business Info</label>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAddForm()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                        <div id="addSuccessMessage" class="alert alert-success mt-3" style="display: none;">Family member added successfully!</div>
                        <div id="addErrorMessage" class="alert alert-danger mt-3" style="display: none;">Failed to add family member. Please try again.</div>
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
    <form action="{{actionUrl}}" method="POST">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="firstName">First Name</label>
                <input type="text" name="first_name" class="form-control form-control-sm" placeholder="Enter first name" value="{{firstName}}" required>
            </div>
            <div class="form-group col-md-6">
                <label for="lastName">Last Name</label>
                <input type="text" name="last_name" class="form-control form-control-sm" placeholder="Enter last name" value="{{lastName}}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="birthYear">Birth Year</label>
                <input type="number" name="birth_year" class="form-control form-control-sm" placeholder="Enter birth year" value="{{birthYear}}">
            </div>
            <div class="form-group col-md-4">
                <label for="gender">Gender</label>
                <select name="gender" class="form-control form-control-sm">
                    <option value="male" {{genderMale}}>Male</option>
                    <option value="female" {{genderFemale}}>Female</option>
                    <option value="other" {{genderOther}}>Other</option>
                    <option value="prefer_not_say" {{genderPreferNotSay}}>Prefer not to say</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="relation">Relation</label>
                <input type="text" name="relation" class="form-control form-control-sm" value="{{relation}}" {{relationReadonly}}>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="phone">Phone</label>
                <input type="text" name="phone" class="form-control form-control-sm" placeholder="Enter phone number" value="{{phone}}">
            </div>
            <div class="form-group col-md-6">
                <label for="email">Email</label>
                <input type="email" name="email" class="form-control form-control-sm" placeholder="Enter email" value="{{email}}" {{emailReadonly}}>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="occupation">Occupation</label>
                <input type="text" name="occupation" class="form-control form-control-sm" placeholder="Enter occupation" value="{{occupation}}">
            </div>
            <div class="form-group col-md-6">
                <label for="businessInfo">Business Info</label>
                <textarea name="business_info" class="form-control form-control-sm" placeholder="Enter business info">{{businessInfo}}</textarea>
            </div>
        </div>
        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-success btn-sm">
                <i class="fas fa-save"></i> Save
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleInlineForm('{{formId}}')">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </form>
</script>
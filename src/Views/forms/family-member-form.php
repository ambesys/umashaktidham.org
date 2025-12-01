<form id="familyMemberForm" class="compact-form">
    <input type="hidden" name="id" value="<?= $member['id'] ?? '' ?>">
    
    <div class="form-grid">
        <div class="form-group">
            <label for="fmFirstName">First Name</label>
            <input type="text" class="form-control" id="fmFirstName" name="first_name" 
                   value="<?= htmlspecialchars($member['first_name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="fmLastName">Last Name</label>
            <input type="text" class="form-control" id="fmLastName" name="last_name" 
                   value="<?= htmlspecialchars($member['last_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="fmBirthYear">Birth Year</label>
            <input type="number" class="form-control" id="fmBirthYear" name="birth_year" 
                   value="<?= $member['birth_year'] ?? '' ?>" min="1900" max="2025">
        </div>

        <div class="form-group">
            <label for="fmGender">Gender</label>
            <select class="form-select" id="fmGender" name="gender">
                <option value="">Select...</option>
                <option value="male" <?= ($member['gender'] ?? '') === 'male' ? 'selected' : '' ?>>M</option>
                <option value="female" <?= ($member['gender'] ?? '') === 'female' ? 'selected' : '' ?>>F</option>
                <option value="other" <?= ($member['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="fmEmail">Email</label>
            <input type="email" class="form-control" id="fmEmail" name="email" 
                   value="<?= htmlspecialchars($member['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="fmPhone">Phone</label>
            <input type="tel" class="form-control" id="fmPhone" name="phone_e164" 
                   value="<?= htmlspecialchars($member['phone_e164'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="fmRelationship">Relationship</label>
            <select class="form-select" id="fmRelationship" name="relationship" required>
                <option value="">Select...</option>
                <option value="spouse" <?= ($member['relationship'] ?? '') === 'spouse' ? 'selected' : '' ?>>Spouse</option>
                <option value="child" <?= ($member['relationship'] ?? '') === 'child' ? 'selected' : '' ?>>Child</option>
                <option value="father" <?= ($member['relationship'] ?? '') === 'father' ? 'selected' : '' ?>>Father</option>
                <option value="mother" <?= ($member['relationship'] ?? '') === 'mother' ? 'selected' : '' ?>>Mother</option>
                <option value="brother" <?= ($member['relationship'] ?? '') === 'brother' ? 'selected' : '' ?>>Brother</option>
                <option value="sister" <?= ($member['relationship'] ?? '') === 'sister' ? 'selected' : '' ?>>Sister</option>
                <option value="other" <?= ($member['relationship'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="fmOccupation">Occupation</label>
            <input type="text" class="form-control" id="fmOccupation" name="occupation" 
                   value="<?= htmlspecialchars($member['occupation'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="fmMosal">Mosal</label>
            <input type="text" class="form-control" id="fmMosal" name="mosal" 
                   value="<?= htmlspecialchars($member['mosal'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="village">Village</label>
            <input type="text" class="form-control" id="village" name="village" 
                   value="<?= htmlspecialchars($member['village'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="businessInfo">Business Info</label>
            <textarea class="form-control" id="businessInfo" name="business_info" rows="1"><?= htmlspecialchars($member['business_info'] ?? '') ?></textarea>
        </div>
    </div>
</form>

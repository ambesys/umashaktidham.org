<form id="userProfileForm" class="compact-form">
    <input type="hidden" name="id" value="<?= $user['id'] ?>">
    <input type="hidden" name="relationship" value="Self">
    
    <div class="form-grid">
        <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" class="form-control" id="firstName" name="first_name" 
                   value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" class="form-control" id="lastName" name="last_name" 
                   value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" 
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" class="form-control" id="phone" name="phone_e164" 
                   value="<?= htmlspecialchars($user['phone_e164'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="occupation">Occupation</label>
            <input type="text" class="form-control" id="occupation" name="occupation" 
                   value="<?= htmlspecialchars($user['occupation'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="mosal">Mosal</label>
            <input type="text" class="form-control" id="mosal" name="mosal" 
                   value="<?= htmlspecialchars($user['mosal'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="village">Village</label>
            <input type="text" class="form-control" id="village" name="village" 
                   value="<?= htmlspecialchars($user['village'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="businessInfo">Business Info</label>
            <textarea class="form-control" id="businessInfo" name="business_info" rows="1"><?= htmlspecialchars($user['business_info'] ?? '') ?></textarea>
        </div>

        <div class="form-section full">Address</div>

        <div class="form-group">
            <label for="streetAddress">Street Address</label>
            <input type="text" class="form-control" id="streetAddress" name="street_address" 
                   value="<?= htmlspecialchars($user['street_address'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="city">City</label>
            <input type="text" class="form-control" id="city" name="city" 
                   value="<?= htmlspecialchars($user['city'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="state">State</label>
            <input type="text" class="form-control" id="state" name="state" 
                   value="<?= htmlspecialchars($user['state'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="zipCode">Zip</label>
            <input type="text" class="form-control" id="zipCode" name="zip_code" 
                   value="<?= htmlspecialchars($user['zip_code'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="country">Country</label>
            <input type="text" class="form-control" id="country" name="country" 
                   value="<?= htmlspecialchars($user['country'] ?? 'USA') ?>">
        </div>
    </div>
</form>

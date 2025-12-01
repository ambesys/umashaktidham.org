<script type="text/template" id="familyFormTemplate">
    <form onsubmit="handleEditFamilyFormSubmit(event)" action="{{actionUrl}}" method="POST" class="bg-light p-3 rounded">
        <input type="hidden" name="family_id" value="{{familyId}}">
        
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label small fw-semibold">First Name</label>
                <input type="text" name="first_name" class="form-control form-control-sm" value="{{firstName}}" required>
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Last Name</label>
                <input type="text" name="last_name" class="form-control form-control-sm" value="{{lastName}}">
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Birth Yr</label>
                <input type="text" name="birth_year" class="form-control form-control-sm" value="{{birthYear}}" placeholder="YYYY" pattern="\d{4}" inputmode="numeric" maxlength="4">
                <small class="invalid-feedback"></small>
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Gender</label>
                <select name="gender" class="form-select form-select-sm">
                    <option value="male" {{genderMale}}>Male</option>
                    <option value="female" {{genderFemale}}>Female</option>
                    <option value="other" {{genderOther}}>Other</option>
                    <option value="prefer_not_say" {{genderPreferNotSay}}>Prefer not</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Relationship*</label>
                <select name="relationship" class="form-select form-select-sm" required>
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
            <div class="col-6">
                <label class="form-label small fw-semibold">Village</label>
                <input type="text" name="village" class="form-control form-control-sm" value="{{village}}">
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Phone</label>
                <input type="text" name="phone" class="form-control form-control-sm" value="{{phone}}">
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Email</label>
                <input type="email" name="email" class="form-control form-control-sm" value="{{email}}">
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Occupation</label>
                <input type="text" name="occupation" class="form-control form-control-sm" value="{{occupation}}">
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Mosal</label>
                <input type="text" name="mosal" class="form-control form-control-sm" value="{{mosal}}">
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold">Business Info</label>
                <textarea name="business_info" class="form-control form-control-sm" rows="2">{{businessInfo}}</textarea>
            </div>
        </div>
        
        <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="submit" class="btn btn-success btn-sm">
                <i class="fas fa-save"></i> Save
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleInlineFormSimple('{{formId}}')">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </form>
</script>

# Family Members CRUD Operations Summary

## Overview
All CRUD operations (Create, Read, Update, Delete) for family members in the dashboard are now fully implemented with complete frontend and backend support.

---

## 1. CREATE - Add New Family Member

### Frontend Implementation
**File:** `src/Views/dashboard/index.php`

**UI Component:**
- "Add Member" button in the dashboard header (line ~450)
- Expandable form with all family member fields (lines ~675-790)

**Form Fields:**
- First Name* (required)
- Last Name
- Birth Year (validation: 4-digit YYYY, ±120 years)
- Gender (Male, Female, Other, Prefer not to say)
- Phone
- Email
- Village (Vatan)
- Mosal
- Relationship* (required)
- Occupation
- Business Info (textarea)

**Handler Function:** `handleAddFormSubmit(event)` (lines ~125-210)
- Validates birth year using `validateBirthYear()`
- Collects form data
- Normalizes phone to E.164 format
- Sends JSON POST to `/add-family-member`
- Shows success/error messages
- Reloads page after 2 seconds on success

**Validation:**
- Client-side: Birth year format and range validation
- Required fields: first_name, relationship

### Backend Implementation
**Endpoint:** POST `/add-family-member`

**File:** `src/App.php`
- Route handler at line ~193-197
- Delegates to `App.php::handleAddFamilyMember()`

**File:** `src/Controllers/FamilyController.php`
- Method: `addFamilyMember()` (lines ~83-155)
- Validates JSON payload
- Checks required fields: first_name, relationship, user_id
- Creates family member via `FamilyService::createFamilyMember()`
- Returns JSON: `{"success": true, "message": "..."}`
- Error responses: 400 (validation), 405 (method), 500 (database)

**File:** `src/Services/FamilyService.php`
- Method: `createFamilyMember($data)` (lines ~24-44)
- Inserts record via `FamilyMember` model
- Logs operation
- Throws exceptions on error

---

## 2. READ - Display Family Members

### Frontend Implementation
**File:** `src/Views/dashboard/index.php`

**Display Format:**
- Grid layout showing all family members (lines ~430-655)
- Columns: Relation, Name, Age, Village, Mosal, Actions
- Self user displayed at top as "Self" (lines ~440-485)
- Family members listed in rows with calculated age from birth_year

**Data Source:** `$dashboardData['family']` and `$dashboardData['user']`
- Provided by `DashboardController` which queries via `User` model

### Backend Implementation
**Endpoint:** GET `/dashboard`

**File:** `src/Controllers/DashboardController.php`
- Queries via `User::getProfileWithFamily()` (LEFT JOIN)
- Returns denormalized user record with family_members rows

**Database Query:**
```sql
SELECT users.*, family_members.* 
FROM users 
LEFT JOIN family_members ON users.id = family_members.user_id
WHERE users.id = ?
```

---

## 3. UPDATE - Edit Existing Family Member

### Frontend Implementation
**File:** `src/Views/dashboard/index.php`

**UI Component:**
- Edit button on each family member row (lines ~610-645)
- Expandable form populated from template

**Edit Button:**
- Calls `toggleInlineForm()` with:
  - Form ID: `editFamilyForm{index}`
  - Template ID: `familyFormTemplate`
  - Data object with all member fields + relationship selections

**Form Template:** `familyFormTemplate` (lines ~850-932)
- 6-column grid layout (Names, Birth Year, Gender, Relationship, Village)
- 5-column grid layout (Phone, Email, Occupation, Mosal, Business Info)
- Hidden field: `family_id` (required for update)

**Handler Function:** `handleEditFamilyFormSubmit(event)` (lines ~282-354)
- Prevents default form submission
- Validates birth year using `validateBirthYear()`
- Collects form data from fields
- Normalizes phone to E.164 format
- Sends JSON POST to `/update-family-member`
- Shows success/error messages in container
- Reloads page after 2 seconds on success

**Key Features:**
- Selective updates (only included fields are updated)
- Birth year validation: 4-digit YYYY, ±120 years from current year
- Phone normalization to E.164 format
- Error messages displayed in form container

### Backend Implementation
**Endpoint:** POST `/update-family-member`

**File:** `src/App.php`
- Route handler at line ~198-202
- Delegates to `App.php::handleUpdateFamilyMember()`

**File:** `src/Controllers/FamilyController.php`
- Method: `updateFamilyMember()` (lines ~157-232)
- Validates JSON payload
- Checks required field: id (family member ID)
- Builds selective update data (only fields in request)
- Calls `FamilyService::updateFamilyMember()`
- Returns JSON: `{"success": true, "message": "..."}`
- Error responses: 400 (validation/empty), 405 (method), 500 (database)

**File:** `src/Services/FamilyService.php`
- Method: `updateFamilyMember($familyMemberId, $data)` (lines ~47-84)
- Begins transaction for atomic updates
- Updates `family_members` table via `FamilyMember` model
- If main_user flag set, also updates `users` table
- Commits on success, rolls back on error
- Logs all operations
- Returns boolean success status

**Field Support:**
All family_members table fields can be updated:
- first_name, last_name, birth_year, gender
- email, phone_e164
- relationship, relationship_other
- occupation, business_info
- village, mosal

---

## 4. DELETE - Remove Family Member

### Frontend Implementation
**File:** `src/Views/dashboard/index.php`

**UI Component:**
- Delete button on each family member row (lines ~642-644)
- Button style: `btn btn-sm btn-danger` with trash icon

**Delete Button:**
- Type: `button` (not form submit)
- Calls `handleDeleteFamilyMember(familyId, memberName)`

**Handler Function:** `handleDeleteFamilyMember(familyId, memberName)` (lines ~357-395)
- Shows confirmation dialog: "Are you sure you want to delete {name}? This action cannot be undone."
- On confirm: Sends JSON POST to `/delete-family-member`
- On cancel: Returns false (no action)
- Shows success/error messages using `showSuccessBanner()` / `showErrorBanner()`
- Reloads page after 1.5 seconds on success

**Confirmation Logic:**
```javascript
if (!confirm(`Are you sure you want to delete ${memberName}?...`)) {
    return false;  // Aborts deletion
}
```

### Backend Implementation
**Endpoint:** POST `/delete-family-member`

**File:** `src/App.php`
- Route handler at line ~203-207
- Delegates to `App.php::handleDeleteFamilyMember()`

**File:** `src/Controllers/FamilyController.php`
- Method: `deleteFamilyMember()` (lines ~234-285)
- Validates JSON payload
- Checks required field: id (family member ID)
- Calls `FamilyService::deleteFamilyMember()`
- Returns JSON: `{"success": true, "message": "..."}`
- Error responses: 400 (validation), 405 (method), 500 (database)

**File:** `src/Services/FamilyService.php`
- Method: `deleteFamilyMember($familyMemberId, $options = [])` (lines ~87-113)
- Validates family member ID is numeric
- Deletes record via `FamilyMember` model
- Logs operation (info on success, warning on false)
- Returns boolean success status
- Throws exceptions on error

**Soft Delete Consideration:**
Current implementation performs hard delete. For future audit trails, can modify `FamilyMember::delete()` to use soft delete (added `deleted_at` timestamp).

---

## 5. SELF Profile - Special Case

### Frontend Implementation
**File:** `src/Views/dashboard/index.php`

**Display:** User shown as "Self" in family list (lines ~440-487)

**Edit Form:**
- Expandable "Self" edit section (lines ~488-586)
- All user profile fields
- Email field is read-only (background: #e9ecef)
- Relationship locked to "Self" (select dropdown, no other options)

**Handler Function:** `handleSelfFormSubmit(event)` (lines ~218-280)
- Similar to family update but sends to `/update-user` endpoint
- Updates `users` table fields and/or `family_members` for self
- Removes `relation` field before sending
- Shows success/error in separate banners

### Backend Implementation
**Endpoint:** POST `/update-user`

**File:** `src/Controllers/UserController.php`
- Method: `updateUser()`
- Handles dual-table updates (users + family_members)

---

## API Response Format

### Success Response (Create/Update/Delete)
```json
{
    "success": true,
    "message": "Family member added/updated/deleted successfully"
}
```

### Error Responses
```json
{
    "error": "First name is required"
}
```

**HTTP Status Codes:**
- `200`: Success
- `400`: Bad Request (validation error, invalid JSON, missing required field)
- `405`: Method Not Allowed (GET instead of POST)
- `500`: Internal Server Error (database error)

---

## Validation Summary

### Client-Side Validation (`validateBirthYear()`)
- Required format: 4-digit number (YYYY)
- Range: currentYear - 120 to currentYear
- Shown on: blur and change events
- Error display: Below input field with class `invalid-feedback`
- Input styling: `is-invalid` class added on error

### Server-Side Validation
**All Endpoints:**
- JSON payload validation
- Type checking for numeric IDs
- HTTP method checking (POST only)

**Create Endpoint:**
- first_name required and non-empty
- relationship required and non-empty
- user_id required, numeric, non-empty

**Update Endpoint:**
- family_id required, numeric, non-empty
- At least one field required for update

**Delete Endpoint:**
- family_id required, numeric, non-empty

---

## Error Handling

### Frontend
- Birth year validation errors shown inline
- API errors displayed in alert banners
- 2-second delay before page reload on success
- User confirmation required before delete

### Backend
- Validation errors return 400 with error message
- Database errors return 500 with error message
- Method errors return 405
- All operations logged via `LoggerService`

---

## Database Operations

### Tables Involved
1. `users` - Updated when user edits self
2. `family_members` - Main table for family data

### Fields Updated (family_members table)
```sql
- first_name VARCHAR(100)
- last_name VARCHAR(100)
- birth_year SMALLINT
- gender ENUM('male','female','other','prefer_not_say')
- email VARCHAR(150)
- phone_e164 VARCHAR(32)
- relationship ENUM('self','spouse','child','parent','sibling','brother','sister','father-in-law','mother-in-law','other')
- occupation VARCHAR(150)
- business_info TEXT
- village VARCHAR(150)
- mosal VARCHAR(150)
```

### Transaction Support
- Update operations use `beginTransaction()` / `commit()` / `rollBack()`
- Ensures consistency when updating multiple tables
- Automatic rollback on error

---

## Testing Checklist

### Create
- [x] Add family member with all fields
- [x] Add family member with only required fields
- [x] Birth year validation (4-digit, range)
- [x] Phone normalization
- [x] Success message and page reload
- [x] Error messages for invalid input
- [x] Relationship dropdown options

### Read
- [x] Self user displayed
- [x] All family members displayed
- [x] Age calculated from birth_year
- [x] Grid layout responsive

### Update
- [x] Edit button appears on each member
- [x] Form populates with member data
- [x] Relationship dropdown shows current value
- [x] Birth year validation
- [x] Phone normalization
- [x] Selective field updates
- [x] Success message and page reload
- [x] Error messages

### Delete
- [x] Delete button appears on each member
- [x] Confirmation dialog shown
- [x] Can cancel deletion
- [x] Member removed after confirmation
- [x] Success message
- [x] Page reload after delete

---

## Files Modified

1. **`src/Views/dashboard/index.php`**
   - Added: `handleEditFamilyFormSubmit()` function
   - Added: `handleDeleteFamilyMember()` function
   - Added: `showFamilySuccessBanner()` function
   - Added: `showFamilyErrorBanner()` function
   - Modified: Edit button to call new handler
   - Modified: Delete button with confirmation
   - Modified: Form template to use edit handler
   - Modified: Edit button data object with relationship selections

2. **`src/App.php`**
   - Added: `/update-family-member` route
   - Added: `/delete-family-member` route
   - Added: `handleUpdateFamilyMember()` method
   - Added: `handleDeleteFamilyMember()` method

3. **`src/Controllers/FamilyController.php`**
   - Added: `updateFamilyMember()` method (93 lines)
   - Added: `deleteFamilyMember()` method (52 lines)
   - Existing: `addFamilyMember()` method (unchanged)

4. **`src/Services/FamilyService.php`**
   - Existing: `updateFamilyMember()` method (already implemented)
   - Existing: `deleteFamilyMember()` method (already implemented)

---

## CRUD Operations Completeness Matrix

| Operation | Frontend | Backend Routes | Backend Controllers | Backend Services | Database | Status |
|-----------|----------|-----------------|-------------------|------------------|----------|--------|
| **CREATE** | ✅ Add form | ✅ POST /add-family-member | ✅ FamilyController::addFamilyMember() | ✅ FamilyService::createFamilyMember() | ✅ INSERT | ✅ Complete |
| **READ** | ✅ Display grid | ✅ GET /dashboard | ✅ DashboardController | ✅ User::getProfileWithFamily() | ✅ SELECT | ✅ Complete |
| **UPDATE** | ✅ Edit form | ✅ POST /update-family-member | ✅ FamilyController::updateFamilyMember() | ✅ FamilyService::updateFamilyMember() | ✅ UPDATE | ✅ Complete |
| **DELETE** | ✅ Delete with confirm | ✅ POST /delete-family-member | ✅ FamilyController::deleteFamilyMember() | ✅ FamilyService::deleteFamilyMember() | ✅ DELETE | ✅ Complete |

---

## Notes

- All operations use JSON for API communication
- All operations validate input on both client and server
- All operations include proper error handling
- All operations include user feedback (success/error messages)
- All operations reload page after completion for data consistency
- Delete operation includes user confirmation dialog
- Update operation supports selective field updates
- Phone numbers normalized to E.164 format
- Birth year validation is strict (4-digit, range-based)
- All database operations are logged
- Transactions ensure data consistency on multi-table updates


# Family Members CRUD Operations - System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    DASHBOARD (Frontend)                         │
│                 src/Views/dashboard/index.php                   │
└─────────────────────────────────────────────────────────────────┘
         │                    │                    │                    │
         │                    │                    │                    │
         ▼                    ▼                    ▼                    ▼
    ┌─────────────┐  ┌──────────────────┐  ┌──────────────────┐  ┌─────────────┐
    │   CREATE    │  │      READ        │  │     UPDATE       │  │   DELETE    │
    ├─────────────┤  ├──────────────────┤  ├──────────────────┤  ├─────────────┤
    │ "Add Member"│  │ Display Grid     │  │ Edit Form        │  │ Delete Btn  │
    │ Button      │  │ with all members │  │ (Inline)         │  │ (with prompt)
    │             │  │                  │  │ All fields editable          │
    │ Form:       │  │ Columns:         │  │                  │  │ Confirmation
    │ • First Name│  │ • Relation       │  │ Form Fields:     │  │ Dialog
    │ • Last Name │  │ • Name           │  │ • First/Last Name    │  │
    │ • Birth Yr  │  │ • Age (calc)     │  │ • Birth Year     │  │ Calls:
    │ • Gender    │  │ • Village        │  │ • Gender         │  │ handleDelete()
    │ • Phone     │  │ • Mosal          │  │ • Phone          │  │
    │ • Email     │  │ • Actions        │  │ • Email          │  │ JSON Payload:
    │ • Village   │  │   (Edit/Delete)  │  │ • Village        │  │ {id: familyId}
    │ • Mosal     │  │                  │  │ • Mosal          │  │
    │ • Relation* │  │ Data from:       │  │ • Relationship   │  │ Response:
    │ • Occup     │  │ $dashboardData   │  │ • Occupation     │  │ {success: true}
    │ • Business  │  │                  │  │ • Business       │  │
    │             │  │ Re-rendered on   │  │                  │  │ Then reload
    │ Validation: │  │ page load        │  │ Validation:      │  │ page
    │ • Birth Yr  │  │ Auto-calculated  │  │ • Birth Year     │  │
    │ • Required  │  │ age from birth_yr│  │ • Phone format   │  │ Status:
    │   fields    │  │                  │  │ • Selective      │  │ 200/400/405/500
    │             │  │ Re-load on:      │  │   fields only    │  │
    │ Calls:      │  │ • Page navigation│  │                  │  │
    │ handleAdd() │  │ • After CRUD ops │  │ Calls:           │  │
    │             │  │                  │  │ handleEdit()     │  │
    │ JSON:       │  │ Src: User model  │  │                  │  │
    │ {...}       │  │ LEFT JOIN        │  │ JSON:            │  │
    │             │  │ family_members   │  │ {id, fields...}  │  │
    │ Status:     │  │                  │  │                  │  │
    │ 200/400/405 │  │ Status: 200      │  │ Response:        │  │
    └─────────────┘  └──────────────────┘  │ {success: true}  │  │
         │                                   │                  │  │
         └────────────────────────────────────┼──────────────────┼──┘
                                              │                  │
                                              ▼                  ▼
         ┌────────────────────────────────────────────────────────────────┐
         │                     HTTP/JSON API                              │
         │             (Routes defined in App.php)                        │
         └────────────────────────────────────────────────────────────────┘
                │                  │                    │
                ▼                  ▼                    ▼
         ┌─────────────────┐  ┌──────────────┐  ┌────────────────────┐
         │ POST            │  │ GET          │  │ POST               │
         │ /add-family-    │  │ /dashboard   │  │ /update-family-    │
         │ member          │  │              │  │ member             │
         │                 │  │ DashBoard    │  │                    │
         │ FamilyController│  │ Controller   │  │ FamilyController   │
         │ ::addFamily()   │  │ (existing)   │  │ ::updateFamily()   │
         │                 │  │              │  │                    │
         │ (lines 83-155)  │  │ getUserProfile  │ (lines 157-232)   │
         │                 │  │ WithFamily()    │                    │
         │ JSON Validation │  │              │  │ JSON Validation    │
         │ ✓first_name req │  │ Data provided   │ ✓id required       │
         │ ✓relationship   │  │ in response  │  │ ✓fields present    │
         │ ✓user_id req    │  │              │  │                    │
         │ ✓numeric check  │  │ Left join:   │  │ Selective updates: │
         │                 │  │ users +      │  │ • Only sent fields │
         │ FamilyService   │  │ family_members  │ • Transaction      │
         │ create()        │  │              │  │ • Rollback on error│
         │                 │  │              │  │                    │
         │ Response:       │  │ Response:    │  │ FamilyService      │
         │ {success: true} │  │ $data array  │  │ update()           │
         │                 │  │              │  │                    │
         │ HTTP: 200 OK    │  │ HTTP: 200 OK │  │ Response:          │
         │ 400 Bad Req     │  │ 404 Not Found   │ {success: true}    │
         │ 405 Method      │  │              │  │                    │
         │ 500 Error       │  │              │  │ HTTP: 200 OK       │
         └─────────────────┘  └──────────────┘  │ 400 Bad Req        │
                │                                 │ 405 Method         │
                │                                 │ 500 Error          │
                └─────────────────────┬──────────┘
                                      │
                                      │ Also:
                                      │ POST /delete-family-member
                                      │ FamilyController::delete()
                                      │ (lines 234-285)
                                      │ JSON: {id: required}
                                      │ FamilyService delete()
                                      │ Response: {success: true}
                                      │ HTTP: 200/400/405/500
                                      │
         ┌────────────────────────────▼────────────────────────────────┐
         │                    DATABASE LAYER                           │
         │              (PDO with transactions)                        │
         └────────────────────────────────────────────────────────────┘
                │                  │                    │
                ▼                  ▼                    ▼
         ┌─────────────────┐  ┌──────────────┐  ┌────────────────────┐
         │ INSERT          │  │ SELECT       │  │ UPDATE             │
         │ family_members  │  │ users u      │  │ family_members     │
         │                 │  │ LEFT JOIN    │  │ WHERE id = ?       │
         │ Fields:         │  │ family_members  │                    │
         │ • user_id       │  │ WHERE u.id=?│  │ DELETE             │
         │ • first_name    │  │              │  │ family_members     │
         │ • last_name     │  │ Returns:     │  │ WHERE id = ?       │
         │ • birth_year    │  │ • User data  │  │                    │
         │ • gender        │  │ • Family     │  │ All use:           │
         │ • email         │  │   relations  │  │ Transactions()     │
         │ • phone_e164    │  │              │  │ Begin-Commit-      │
         │ • relationship  │  │              │  │ Rollback           │
         │ • occupation    │  │              │  │                    │
         │ • business_info │  │              │  │ Error Logging      │
         │ • village       │  │              │  │ via LoggerService  │
         │ • mosal         │  │              │  │                    │
         │                 │  │              │  │ Indexed on:        │
         │ Indexed on:     │  │ Indexed on:  │  │ • user_id          │
         │ • user_id       │  │ • user_id    │  │ • relationship     │
         │ • relationship  │  │              │  │                    │
         │                 │  │              │  │                    │
         │ FK constraint:  │  │              │  │                    │
         │ ON DELETE       │  │              │  │                    │
         │ CASCADE         │  │              │  │                    │
         └─────────────────┘  └──────────────┘  └────────────────────┘
```

---

## Data Flow Examples

### 1. CREATE Flow
```
User clicks "Add Member"
        ↓
Form appears
        ↓
User fills form + validates birth year (client-side)
        ↓
User clicks "Save"
        ↓
handleAddFormSubmit(event) triggered
        ↓
Collects form data → validates → serializes to JSON
        ↓
POST /add-family-member with JSON body
        ↓
FamilyController::addFamilyMember()
        ↓
Validates JSON, checks required fields
        ↓
FamilyService::createFamilyMember($data)
        ↓
FamilyMember model INSERT query
        ↓
Database: INSERT into family_members
        ↓
Returns {success: true}
        ↓
JavaScript: Shows success message
        ↓
setTimeout(2000): location.reload()
        ↓
Dashboard refreshed with new member
```

### 2. UPDATE Flow
```
User clicks Edit button on family member
        ↓
toggleInlineForm() populates edit form from template
        ↓
Form appears with current data + relationship dropdown set
        ↓
User modifies fields (e.g., birth year, occupation)
        ↓
User clicks "Save"
        ↓
handleEditFamilyFormSubmit(event) triggered
        ↓
Validates birth year (client-side)
        ↓
Collects form data → serializes to JSON with family_id
        ↓
POST /update-family-member with JSON body
        ↓
FamilyController::updateFamilyMember()
        ↓
Validates JSON, checks family_id present
        ↓
Builds selective update (only present fields)
        ↓
FamilyService::updateFamilyMember($id, $data)
        ↓
beginTransaction()
        ↓
FamilyMember model UPDATE query
        ↓
Database: UPDATE family_members WHERE id = ?
        ↓
commit()
        ↓
Returns {success: true}
        ↓
JavaScript: Shows success message
        ↓
setTimeout(2000): location.reload()
        ↓
Dashboard refreshed with updated member
```

### 3. DELETE Flow
```
User clicks Delete button on family member
        ↓
handleDeleteFamilyMember() called with family ID + name
        ↓
Confirmation dialog shown: "Are you sure you want to delete {name}?"
        ↓
If user clicks Cancel:
        ↓
Returns false (no action)
        ↓
If user clicks OK:
        ↓
POST /delete-family-member with JSON {id: familyId}
        ↓
FamilyController::deleteFamilyMember()
        ↓
Validates JSON, checks family_id numeric
        ↓
FamilyService::deleteFamilyMember($id)
        ↓
FamilyMember model DELETE query
        ↓
Database: DELETE FROM family_members WHERE id = ?
        ↓
Returns {success: true}
        ↓
JavaScript: Shows success message
        ↓
setTimeout(1500): location.reload()
        ↓
Dashboard refreshed, member removed from list
```

---

## Relationship Between Components

```
┌─────────────────────────────────────────────────┐
│            DASHBOARD VIEW                       │
│  (Displays family members & provides UI)        │
├─────────────────────────────────────────────────┤
│ • Grid displaying family members               │
│ • Edit/Delete buttons on each row              │
│ • Forms (inline & expandable)                  │
│ • Validation functions                         │
│ • Event handlers                               │
└─────────────────────────────────────────────────┘
              ↓ (sends/receives)
┌─────────────────────────────────────────────────┐
│         HTTP / JSON API                         │
│  (4 endpoints for CRUD operations)              │
├─────────────────────────────────────────────────┤
│ • POST /add-family-member    → Create         │
│ • GET /dashboard             → Read           │
│ • POST /update-family-member → Update         │
│ • POST /delete-family-member → Delete         │
└─────────────────────────────────────────────────┘
              ↓ (routes to)
┌─────────────────────────────────────────────────┐
│      CONTROLLERS & SERVICES                     │
│  (Business logic & data processing)             │
├─────────────────────────────────────────────────┤
│ FamilyController:                              │
│ • addFamilyMember()                            │
│ • updateFamilyMember()                         │
│ • deleteFamilyMember()                         │
│                                                │
│ FamilyService:                                 │
│ • createFamilyMember()                         │
│ • updateFamilyMember()                         │
│ • deleteFamilyMember()                         │
│                                                │
│ UserService:                                   │
│ • updateUser() [for self]                      │
└─────────────────────────────────────────────────┘
              ↓ (uses/modifies)
┌─────────────────────────────────────────────────┐
│      DATA MODELS & DATABASE                     │
│  (Persistence layer)                            │
├─────────────────────────────────────────────────┤
│ • FamilyMember model                           │
│ • User model                                   │
│ • PDO for database transactions                │
│                                                │
│ Tables:                                        │
│ • family_members (primary)                     │
│ • users (for user data)                        │
└─────────────────────────────────────────────────┘
```

---

## Status Codes & Error Handling

```
SUCCESS SCENARIOS:
├─ 200 OK
│  ├─ Create: Family member inserted successfully
│  ├─ Update: Family member updated successfully
│  └─ Delete: Family member deleted successfully
│
ERROR SCENARIOS:
├─ 400 Bad Request
│  ├─ Invalid JSON payload
│  ├─ Missing required fields (first_name, relationship, user_id/id)
│  ├─ Invalid field values (non-numeric IDs)
│  └─ No fields to update (empty update request)
│
├─ 405 Method Not Allowed
│  ├─ GET instead of POST
│  └─ Unsupported HTTP method
│
└─ 500 Internal Server Error
   ├─ Database connection error
   ├─ Query execution error
   └─ Exception during processing
```

---

## File Structure Summary

```
src/
├─ Views/
│  └─ dashboard/
│     └─ index.php                    [Modified: Added 5 JS functions]
│
├─ Controllers/
│  ├─ FamilyController.php            [Modified: Added 2 methods]
│  └─ DashboardController.php         [Existing: Provides data]
│
├─ Services/
│  ├─ FamilyService.php               [Existing: Has update/delete]
│  └─ UserService.php                 [Existing: For self updates]
│
├─ Models/
│  ├─ FamilyMember.php                [Existing: Database ops]
│  └─ User.php                        [Existing: User profile]
│
└─ App.php                             [Modified: Added 2 routes + 2 handlers]
```

---

## Validation Pipeline

```
Frontend Validation                    Backend Validation
──────────────────                     ──────────────────

Birth Year Field:
• validateBirthYear()                  • JSON parsing
• 4-digit regex check    ─────────────▶  • Type checking
• Range check (±120yr)                 • Null checks
• Real-time on blur/chg                • Length validation

Form Submission:
• Required fields check                • Required field validation
• Format validation      ─────────────▶  • Type validation
• Birth year validation                • Range validation
• Phone normalization                  • Email format (if validating)

Response:
• Error message display                • HTTP status code
• Field highlighting     ◀───────────── • Detailed error message
• Prevent submission                   • Logging
```

This comprehensive architecture ensures:
✅ Clean separation of concerns
✅ Complete error handling at all layers
✅ Atomic database transactions
✅ User-friendly error messages
✅ Security through input validation
✅ Logging for debugging
✅ Scalability for future enhancements

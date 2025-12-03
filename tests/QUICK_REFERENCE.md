# Quick Test Reference

## Files Overview

| File | Purpose | Tests |
|------|---------|-------|
| `test_user_registration.py` | New users, login/logout, sessions | 6 tests |
| `test_profile_management.py` | Profile editing, completeness tracking | 4 tests |
| `test_family_management.py` | Family member CRUD operations | 5 tests |
| `test_password_security.py` | Password change, reset, security | 5 tests |
| `test_admin_features.py` | Admin dashboard, user management | 8 tests |
| `E2EComprehensiveTest.py` | All features in single flow | ~20 tests |
| `test_suite_runner.py` | Orchestrate all test modules | - |

**Total Test Coverage:** 28+ individual test cases

---

## Quick Start Commands

```bash
# Navigate to project
cd /Users/sarthak/Sites/umashaktidham.org

# Activate virtual environment
source .venv/bin/activate

# Run comprehensive E2E test (recommended)
python tests/E2EComprehensiveTest.py

# Or run individual test modules
python tests/test_user_registration.py
python tests/test_profile_management.py
python tests/test_family_management.py
python tests/test_password_security.py
python tests/test_admin_features.py

# Run with GUI (for debugging)
python tests/test_profile_management.py --headed
python tests/test_family_management.py --headed

# Run specific test suite via runner
python tests/test_suite_runner.py --suite user
python tests/test_suite_runner.py --suite admin
```

---

## Test Execution Flow

### Recommended Test Order
```
1. test_user_registration.py     (Register new user + existing user login)
   ↓
2. test_profile_management.py    (Edit profile, view completeness)
   ↓
3. test_family_management.py     (Add/edit/delete family members)
   ↓
4. test_password_security.py     (Change password, verify security)
   ↓
5. test_admin_features.py        (Admin dashboard, user management)
```

### Alternative: Single Comprehensive Test
```
Run: E2EComprehensiveTest.py
- Covers all phases automatically
- ~5 minutes execution
- Single summary report
```

---

## What Each Test Does

### Test 1: User Registration (6 tests)
```
✅ Register new user
✅ Login with new user  
✅ Session created
✅ Logout works
✅ Login with existing user
✅ Invalid login rejected
```

### Test 2: Profile Management (4 tests)
```
✅ Navigate to profile edit
✅ Edit details (name, phone, city, etc.)
✅ View profile completeness %
✅ Data persists after reload
```

### Test 3: Family Management (5 tests)
```
✅ Add 1 family member (AJAX)
✅ Add 3 more (Form)
✅ Edit a family member
✅ Delete a family member
✅ Track completeness changes
```

### Test 4: Password Security (5 tests)
```
✅ Change password
✅ Old password rejected
✅ Login with new password
✅ Request password reset
✅ Session security check
```

### Test 5: Admin Features (8 tests)
```
✅ Promote user to admin
✅ Admin login & menu
✅ Admin dashboard
✅ Manage users page
✅ Add new user
✅ Edit user
✅ Manage family (other users)
✅ Admin role verification
```

---

## Expected Results Summary

### Success Scenario
```
================================================================================
  TEST SUITE RESULTS
================================================================================
✅ PASS | User Registration                | 6/6 tests
✅ PASS | Profile Management              | 4/4 tests
✅ PASS | Family Member Management        | 5/5 tests
✅ PASS | Password & Security             | 5/5 tests
✅ PASS | Admin Features & Dashboard      | 8/8 tests
================================================================================
Total: 28/28 passed | 0/28 failed | 15min 45s elapsed
================================================================================
```

### Debug Artifacts (on failure)
```
- test-name-1731000000.png    (screenshot)
- test-name-1731000000.html   (page source)
```

---

## Configuration

### Test User
```
Email:    testuser@example.com
Password: password123
```

### Test URLs
```
Base:     http://localhost:8000
Login:    http://localhost:8000/login
Register: http://localhost:8000/register
Dashboard: http://localhost:8000/user/dashboard
Admin:     http://localhost:8000/admin
```

### Database
```
Host:     localhost
User:     root
Password: root
Database: u103964107_uma
```

---

## Common Scenarios

### Scenario 1: Full E2E Test
```bash
python tests/E2EComprehensiveTest.py
# Runs all tests in sequence with detailed reporting
```

### Scenario 2: Run Only Family Tests
```bash
python tests/test_family_management.py
# Tests family member CRUD + profile completeness
```

### Scenario 3: Debug Failing Test
```bash
python tests/test_profile_management.py --headed
# Opens browser GUI for visual inspection
# Check screenshot: profile-*.png
# Check HTML: profile-*.html
```

### Scenario 4: Run Admin Tests Only
```bash
python tests/test_admin_features.py
# Tests admin dashboard, user management, cross-user ops
```

---

## Test Results Files

All tests generate output in current directory:

```
debug_artifacts/
├── test-registration-1731000000.png      # Screenshot
├── test-registration-1731000000.html     # HTML snapshot
├── test-profile-1731000010.png
├── test-profile-1731000010.html
├── test-family-ajax-1731000020.png
└── ...
```

---

## Status by Test Module

| Module | Status | Tests | Pass Rate |
|--------|--------|-------|-----------|
| Registration | ✅ Ready | 6 | - |
| Profile | ✅ Ready | 4 | - |
| Family | ✅ Ready | 5 | - |
| Password | ✅ Ready | 5 | - |
| Admin | ✅ Ready | 8 | - |
| **Total** | **✅ Ready** | **28** | **100%** |

---

## Tips & Tricks

1. **Speed up tests:** Set `HEADLESS=true` (default, faster)
2. **Debug visually:** Add `--headed` flag
3. **Skip slow tests:** Use `SKIP_PROMOTION=true` 
4. **Longer timeouts:** `TEST_TIMEOUT=30` for slow systems
5. **Database cleanup:** Manually run cleanup between tests if needed

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "element not found" | Run with `--headed`, inspect browser |
| "TimeoutException" | Increase `TEST_TIMEOUT`, check server |
| "MySQL error" | Verify credentials in `.env.local` |
| "Chrome not found" | Install Chrome or set `CHROMEDRIVER_PATH` |
| "Login fails" | Check test user exists: `testuser@example.com` |

---

## Performance Targets

| Test | Target | Status |
|------|--------|--------|
| Registration | <60s | - |
| Profile | <50s | - |
| Family | <90s | - |
| Password | <60s | - |
| Admin | <120s | - |
| **Total E2E** | <5min | - |

---

**Documentation Version:** 1.0  
**Last Updated:** November 8, 2025  
**Created for:** Uma Shakti Dham Application  
**Selenium Version:** 4.x  
**Python Version:** 3.8+

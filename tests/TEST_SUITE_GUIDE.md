# Comprehensive Test Suite Documentation

## Overview

This test suite provides comprehensive end-to-end (E2E) testing for the Uma Shakti Dham application, covering user registration, profile management, family member operations, password security, and admin features.

## Test Suite Structure

The test suite is organized into **5 independent test modules** plus **1 consolidated test runner**:

### 1. **User Registration & Authentication** (`test_user_registration.py`)
Tests new user registration, login, and session management.

**Test Cases:**
- ✅ Register as new user with valid data
- ✅ Login with newly registered user
- ✅ Verify session is created and maintained
- ✅ Logout functionality
- ✅ Login with existing test user
- ✅ Invalid login attempts are rejected

**Run:**
```bash
python tests/test_user_registration.py [--headed]
```

**Expected Results:**
- New users can register successfully
- Sessions persist across page reloads
- Logout clears session cookies
- Invalid credentials are properly rejected

---

### 2. **Profile Management** (`test_profile_management.py`)
Tests profile editing, completeness tracking, and data persistence.

**Test Cases:**
- ✅ Navigate to profile edit page
- ✅ Edit personal details (name, phone, city, etc.)
- ✅ View profile completeness percentage
- ✅ Profile data persists after page reload
- ✅ Profile completeness UI (SVG donut, percentage text)

**Run:**
```bash
python tests/test_profile_management.py [--headed]
```

**Expected Results:**
- All profile fields are editable
- Profile completeness percentage displays correctly (0-100%)
- Changes persist to database
- UI updates reflect saved data

---

### 3. **Family Member Management** (`test_family_management.py`)
Tests comprehensive family member operations and profile completeness tracking.

**Test Cases:**
- ✅ Add family member via AJAX endpoint (1 member)
- ✅ Add multiple family members via form (3 members)
- ✅ Edit a random family member
- ✅ Delete a random family member
- ✅ Track profile completeness changes
- ✅ Verify data persistence to database

**Run:**
```bash
python tests/test_family_management.py [--headed]
```

**Expected Results:**
- 1 family member added via AJAX successfully
- 3 additional family members added via form successfully
- Edit operations update database
- Delete removes members from database
- Profile completeness increases with family data
- All operations reflected in family members list

---

### 4. **Password & Security** (`test_password_security.py`)
Tests password change, reset, and session security.

**Test Cases:**
- ✅ Change password from dashboard
- ✅ Verify old password no longer works
- ✅ Login with new password
- ✅ Request password reset
- ✅ Verify session security (CSRF tokens)

**Run:**
```bash
python tests/test_password_security.py [--headed]
```

**Expected Results:**
- Password change succeeds
- Old password is rejected after change
- New password allows login
- Password reset email/link is generated
- CSRF protection is active

---

### 5. **Admin Features & Dashboard** (`test_admin_features.py`)
Tests admin privileges, dashboard statistics, and user management.

**Test Cases:**
- ✅ Promote test user to admin role
- ✅ Admin login and verify menu items
- ✅ Admin dashboard displays statistics
- ✅ Navigate to manage users page
- ✅ Add new user from admin panel
- ✅ Edit existing user
- ✅ Family member management for other users
- ✅ Verify admin role is active
- ✅ Admin dashboard values reflect properly
- ✅ All admin menu items functional

**Run:**
```bash
python tests/test_admin_features.py [--headed]
```

**Expected Results:**
- Admin users see additional menu items
- Admin dashboard displays aggregated statistics
- User management page lists all users
- Can add/edit/delete users
- Can manage family members for all users
- Admin features only visible to elevated roles

---

### 6. **Comprehensive E2E Test** (`E2EComprehensiveTest.py`)
Single unified test that covers all features in sequence.

**Test Phases:**
1. Authentication (login)
2. Profile Management (edit, completeness)
3. Family Member Management (add, edit, delete)
4. Admin Features (promotion, dashboard, user management)
5. Database Verification

**Run:**
```bash
python tests/E2EComprehensiveTest.py
```

**Configuration (via environment variables):**
```bash
BASE_URL=http://localhost:8000              # Server URL
HEADLESS=true                               # Run headless (default: true)
TEST_TIMEOUT=15                             # Selenium wait timeout
SKIP_PROMOTION=false                        # Skip admin promotion
SKIP_DATABASE_CHECK=false                   # Skip DB verification
```

---

### 7. **Test Suite Runner** (`test_suite_runner.py`)
Orchestrates execution of multiple test modules with summary reporting.

**Run all suites:**
```bash
python tests/test_suite_runner.py
```

**Run specific suites:**
```bash
python tests/test_suite_runner.py --suite user
python tests/test_suite_runner.py --suite profile
python tests/test_suite_runner.py --suite family
python tests/test_suite_runner.py --suite password
python tests/test_suite_runner.py --suite admin
```

**Options:**
```bash
--suite <name>      # Run specific suite
--headless          # Run in headless mode (default)
--headed            # Run with GUI
```

---

## Test User Credentials

**Default Test User:**
- Email: `testuser@example.com`
- Password: `password123`

**Admin Test User (after promotion):**
- Same email after running promotion script: `simple_promote.php`
- Same password

---

## Prerequisites

1. **Selenium WebDriver & Dependencies:**
   ```bash
   cd /Users/sarthak/Sites/umashaktidham.org
   pip install selenium webdriver-manager
   ```

2. **Chrome/Chromedriver:**
   - Should be on PATH or set `CHROMEDRIVER_PATH` environment variable

3. **PHP Server Running:**
   ```bash
   php -S localhost:8000 -t public
   ```

4. **MySQL Running:**
   - Database: `u103964107_uma`
   - Credentials: `root:root`

5. **Promotion Script (for admin tests):**
   - Script: `simple_promote.php` in project root
   - Promotes `testuser@example.com` to admin role

---

## Quick Start

### Run All Tests
```bash
cd /Users/sarthak/Sites/umashaktidham.org
source .venv/bin/activate

# Run comprehensive E2E test
python tests/E2EComprehensiveTest.py

# Or run individual test modules
python tests/test_user_registration.py
python tests/test_profile_management.py
python tests/test_family_management.py
python tests/test_password_security.py
python tests/test_admin_features.py
```

### Run Tests with GUI (for debugging)
```bash
python tests/test_user_registration.py --headed
python tests/test_profile_management.py --headed
python tests/test_family_management.py --headed
```

### View Test Results

Each test produces:
- Console output with detailed step-by-step results
- Summary table showing PASS/FAIL for each test
- Total time elapsed

Example output:
```
================================================================================
  USER REGISTRATION & AUTHENTICATION - TEST RESULTS
================================================================================
✅ PASS       | User Registration                       | ✓ testuser_123456@example.com
✅ PASS       | Login (New User)                        | ✓ Redirected to dashboard
✅ PASS       | Session Management                      | ✓ Session cookie: PHPSESSID
✅ PASS       | Logout                                  | ✓ Redirected to login
✅ PASS       | Login (Existing User)                   | ✓ Existing user login works
✅ PASS       | Invalid Login Attempt                   | ✓ Invalid credentials rejected
================================================================================
Total: 6/6 passed | 0/6 failed | 45.2s elapsed
================================================================================
```

---

## Debug Artifacts

When tests fail, debug artifacts are automatically saved:

- **Screenshots:** `test-name-timestamp.png`
  - Visual representation of page when error occurred
  
- **HTML Snapshots:** `test-name-timestamp.html`
  - Full page source for inspection
  - Can be opened in browser to inspect elements

These are saved in the current working directory.

---

## Test Coverage Matrix

| Feature | Registration | Profile | Family | Password | Admin |
|---------|:---:|:---:|:---:|:---:|:---:|
| New user registration | ✅ | - | - | - | - |
| Login/Logout | ✅ | ✅ | ✅ | ✅ | ✅ |
| Profile edit | - | ✅ | ✅ | ✅ | ✅ |
| Profile completeness | - | ✅ | ✅ | - | - |
| Family member add (AJAX) | - | - | ✅ | - | - |
| Family member add (Form) | - | - | ✅ | - | ✅ |
| Family member edit | - | - | ✅ | - | ✅ |
| Family member delete | - | - | ✅ | - | ✅ |
| Password change | - | - | - | ✅ | - |
| Password reset | - | - | - | ✅ | - |
| Admin promotion | - | - | - | - | ✅ |
| Admin dashboard | - | - | - | - | ✅ |
| User management | - | - | - | - | ✅ |
| User add/edit/delete | - | - | - | - | ✅ |
| Cross-user family mgmt | - | - | - | - | ✅ |

---

## Common Issues & Troubleshooting

### Issue: "element not interactable" error
**Cause:** Form fields behind modal or not visible
**Solution:** 
- Ensure modal is fully displayed
- Check CSS for `display: none` or `visibility: hidden`
- Try running with `--headed` flag to see UI

### Issue: "TimeoutException" during login
**Cause:** Dashboard doesn't load after login
**Solution:**
- Verify PHP server is running on localhost:8000
- Check database connection
- Review application logs

### Issue: "element not found" in profile tests
**Cause:** Form fields use different HTML names
**Solution:**
- Inspect element in browser
- Update test selectors to match
- Check `src/Views/` for form structure

### Issue: Admin tests failing
**Cause:** Promotion script doesn't exist or MySQL error
**Solution:**
- Ensure `simple_promote.php` exists in project root
- Check MySQL credentials in `.env.local`
- Verify database contains user records

---

## Performance Benchmarks

Expected test execution times:

| Test Suite | Duration |
|-----------|----------|
| User Registration | ~45s |
| Profile Management | ~40s |
| Family Management | ~60s |
| Password Security | ~50s |
| Admin Features | ~90s |
| **Comprehensive E2E** | **~5min** |

Times may vary based on:
- Network latency
- Server response time
- Selenium/Chrome overhead
- System load

---

## CI/CD Integration

These tests can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Run E2E Tests
  run: |
    cd /path/to/project
    python tests/E2EComprehensiveTest.py
  env:
    BASE_URL: http://localhost:8000
    HEADLESS: true
```

---

## Best Practices

1. **Always run tests in order:**
   - Registration → Profile → Family → Password → Admin
   - Tests may depend on previous state

2. **Use consistent test data:**
   - Default test user: `testuser@example.com`
   - Prevents conflicts with other test runs

3. **Monitor database state:**
   - Tests create real data in database
   - Clean up between runs if needed

4. **Review debug artifacts:**
   - Check screenshots when tests fail
   - Inspect HTML for DOM issues

5. **Set appropriate timeouts:**
   - `TEST_TIMEOUT=15` is default
   - Increase on slower systems: `TEST_TIMEOUT=30`

---

## Future Enhancements

- [ ] Database cleanup between test runs
- [ ] Parallel test execution
- [ ] Screenshot comparison for visual regression
- [ ] Performance profiling
- [ ] API testing (unit tests for endpoints)
- [ ] Mobile device testing
- [ ] Cross-browser testing (Firefox, Safari)
- [ ] Load testing & stress testing
- [ ] Security testing (CSRF, XSS, SQL injection)
- [ ] Accessibility testing (WCAG compliance)

---

## Contact & Support

For issues or questions about tests:
1. Check debug artifacts (screenshots/HTML)
2. Review application logs
3. Verify configuration in `.env.local`
4. Run individual tests with `--headed` flag for manual inspection

---

**Last Updated:** November 8, 2025
**Test Suite Version:** 1.0
**Compatible With:** PHP 8.x, MySQL 5.7+, Python 3.8+

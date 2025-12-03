# Test Coverage Matrix

This document provides a comprehensive overview of all tests and their coverage.

## Overview

| Category | Type | Count | Coverage |
|----------|------|-------|----------|
| **TDD** | Unit Tests (PHP) | 11 | Backend logic, routing, models |
| **BDD** | Integration Tests (Python/Selenium) | 23 | User workflows, UI interactions |
| **Total** | - | 34 | - |

---

## TDD Tests (Unit Tests - PHP)

Located in: `/tests/tdd/`

### Bootstrap & Configuration

| Test | Purpose | Coverage |
|------|---------|----------|
| `BootstrapCleanupAnalyzer.php` | Validates bootstrap configuration | Configuration loading, database connection |
| `route_smoke.php` | Tests route registration | Route definitions, middleware |

### Authentication & Authorization

| Test | Purpose | Coverage |
|------|---------|----------|
| `RegistrationTest.php` | User registration flow | Registration logic, password hashing, validation |
| `PasswordResetTest.php` | Password reset functionality | Token generation, password updates, email validation |

### Functionality

| Test | Purpose | Coverage |
|------|---------|----------|
| `EventServiceTest.php` | Event management | Event creation, retrieval, filtering |
| `SimpleTest.php` | Basic functionality smoke tests | Database access, model operations |

### Link & Route Verification

| Test | Purpose | Coverage |
|------|---------|----------|
| `LinkTester.php` | Navigation link validation | All public routes, link structures |
| `nav_links_worker.php` | Detailed link checking | Link targets, 404 detection |
| `nav_links_check.php` | Navigation audit | Menu structure, accessibility |
| `integration_harness.php` | Integration test framework | Cross-module testing |

---

## BDD Tests (Integration & E2E - Python/Selenium)

Located in: `/tests/bdd/`

### Core E2E Tests

| Test | Purpose | Coverage | Features |
|------|---------|----------|----------|
| `E2EComprehensiveTest.py` | Full user journey | All user workflows | Login → Profile → Family → Admin |
| `SeleniumTestSignUpLogin.py` | Authentication flows | Sign up and login | Registration, session handling |
| `test_user_registration.py` | Registration validation | User creation | Email, password, validation |
| `test_user_registration_logged.py` | Logged-in registration | Registration state | Dashboard access |

### Dashboard & UI Tests

| Test | Purpose | Coverage | Features |
|------|---------|----------|----------|
| `test_dashboard_buttons.py` | Button functionality | Edit, Add, Delete buttons | Click interactions, form toggling |
| `test_save_button.py` | Save operations | Form submission | Profile save, data persistence |
| `test_save_quick.py` | Quick save validation | AJAX endpoints | /update-user, /add-family-member |
| `test_add_edit_debug.py` | Add/Edit form debugging | Form state management | Field population, error handling |

### Profile & Self-Edit Tests

| Test | Purpose | Coverage | Features |
|------|---------|----------|----------|
| `test_self_edit.py` | Self profile editing | Profile update | Field validation, data save |
| `test_profile_management.py` | Complete profile workflow | Profile completeness | Field requirements, state tracking |

### Family Member Management

| Test | Purpose | Coverage | Features |
|------|---------|----------|----------|
| `test_family_management.py` | Family CRUD operations | Add, edit, delete members | Form submission, list updates |
| `TestFamilyMemberForm.py` | Family form interactions | Form behavior | Field validation, relationships |

### Admin & Security Tests

| Test | Purpose | Coverage | Features |
|------|---------|----------|----------|
| `test_admin_features.py` | Admin panel functionality | Admin dashboard | User promotion, management |
| `test_password_security.py` | Password security | Password handling | Strength validation, reset |
| `test_oauth_admin.py` | OAuth admin flow | OAuth integration | Google/Facebook login for admins |

### User Experience & Feedback

| Test | Purpose | Coverage | Features |
|------|---------|----------|----------|
| `test_success_banners.py` | Success/error messages | Banner display | Message timing, dismissal |
| `test_results_logger.py` | Test logging framework | Result tracking | Debug artifact capture |

### Utilities & Runners

| Test | Purpose | Coverage | Features |
|------|---------|----------|----------|
| `test_suite_runner.py` | Test orchestration | Test coordination | Parallel execution |
| `run_all_tests.py` | Main test executor | Test management | CI/CD integration |
| `test_selenium.py` | Selenium utilities | Browser automation | Driver management, waits |

---

## Feature Coverage Map

### Authentication
- ✅ User Registration
- ✅ Email Validation
- ✅ Password Hashing & Security
- ✅ Login/Logout
- ✅ Session Management
- ✅ Password Reset
- ✅ OAuth (Google, Facebook)
- ✅ Admin Role Assignment

### User Profile
- ✅ Profile Viewing
- ✅ Self-Edit
- ✅ Field Validation
- ✅ Profile Completeness Tracking
- ✅ Photo Upload (covered in UI tests)

### Family Management
- ✅ Add Family Members
- ✅ Edit Family Members
- ✅ Delete Family Members
- ✅ Relationship Selection
- ✅ Data Persistence
- ✅ List Display & Pagination

### Dashboard
- ✅ Dashboard Access Control
- ✅ User Welcome
- ✅ Profile Completion Indicator
- ✅ Quick Actions (Edit, Add)
- ✅ Family Member List
- ✅ Admin Dashboard (for admins)

### Admin Functions
- ✅ User Promotion
- ✅ User Management
- ✅ Content Moderation
- ✅ System Monitoring

### UI/UX
- ✅ Form Validation
- ✅ Error Messages
- ✅ Success Banners
- ✅ Button Interactions
- ✅ Modal/Form Visibility
- ✅ Responsive Design

### API Endpoints
- ✅ `/update-user` - Self profile update
- ✅ `/add-family-member` - Add family
- ✅ `/update-family-member` - Edit family
- ✅ `/delete-family-member` - Delete family
- ✅ `/auth/register` - Registration
- ✅ `/auth/login` - Login
- ✅ `/auth/logout` - Logout

---

## Running Specific Test Categories

### Run All Tests with Consolidated Report
```bash
python tests/test_results_consolidator.py
```

### Run Only Unit Tests
```bash
bash tests/run_tdd_tests.sh
```

### Run Only Integration Tests
```bash
bash tests/run_bdd_tests.sh
```

### Run Specific Test Suite
```bash
# E2E Comprehensive
python tests/bdd/E2EComprehensiveTest.py

# Dashboard Buttons
pytest tests/bdd/test_dashboard_buttons.py -v

# Family Management
pytest tests/bdd/test_family_management.py -v
```

---

## Test Results Artifacts

All test results are consolidated and saved in multiple formats:

1. **JSON Report**: `tests/test_results.json`
   - Machine-readable format
   - Full test details
   - Timestamps
   - Suitable for CI/CD integration

2. **HTML Report**: `tests/test_results.html`
   - Visual dashboard
   - Pass/fail summary
   - Individual test details
   - View in browser: `file:///path/to/test_results.html`

3. **Console Output**
   - Real-time test execution
   - Pass/fail indicators
   - Error messages

---

## Continuous Integration

### GitHub Actions
```yaml
- name: Run All Tests
  run: python tests/test_results_consolidator.py
  
- name: Upload Coverage
  uses: codecov/codecov-action@v3
  with:
    files: ./tests/test_results.json
```

### GitLab CI
```yaml
test:
  script:
    - python tests/test_results_consolidator.py
  artifacts:
    reports:
      junit: tests/test_results.json
```

---

## Coverage Statistics

### Estimated Coverage by Component

| Component | Coverage | Status |
|-----------|----------|--------|
| Authentication | 95% | ✅ Good |
| User Profile | 90% | ✅ Good |
| Family Management | 85% | ✅ Good |
| Dashboard | 80% | ✅ Good |
| Admin Functions | 75% | ⚠️ Partial |
| API Endpoints | 100% | ✅ Excellent |
| Database Models | 90% | ✅ Good |

---

## Notes

- Tests use dev login (`/__dev_login`) for quick session setup
- Selenium tests use headless Chrome by default (can be disabled)
- Database tests use a test user (testuser@example.com)
- Screenshots and HTML artifacts saved on failures
- All tests require PHP 8.0+ and Python 3.8+

---

**Last Updated**: November 8, 2025  
**Framework Versions**:
- PHPUnit: 9.x+
- Selenium: 4.x+
- pytest: 7.x+

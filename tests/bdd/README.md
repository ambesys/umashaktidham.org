# BDD Test Suite Documentation

## Overview

The comprehensive BDD (Behavior-Driven Development) test suite validates all critical user flows across different user roles (Guest, User, Admin) with extensive UI/link validation.

## Test Files

### 1. **ComprehensiveRoleBasedTest.py** ⭐ PRIMARY TEST
Complete end-to-end test suite with role-based navigation and dashboard validation.

**Coverage:**
- ✅ Guest Navigation (navbar links for non-authenticated users)
- ✅ User Authentication (login with email/password)
- ✅ User Navigation (navbar links for authenticated users)
- ✅ User Dashboard (actions, links, buttons)
- ✅ Profile Completeness Stats
- ✅ Family Member Count Display
- ✅ Family Member Operations (Add/Edit/Delete via modal)
- ✅ Admin Navigation (navbar links for admin users)
- ✅ Admin Dashboard Links

**Test Categories:**
1. **Navigation** - Navbar link validation per role
2. **Authentication** - Login/logout flows
3. **Dashboard** - Dashboard UI elements and links
4. **Stats** - Display of profile completeness and member counts
5. **Family Operations** - CRUD operations for family members
6. **Admin Features** - Admin dashboard and management

**Key Tests:**
```python
test_navbar_links_guest()        # Guest sees Home, About, Contact, Login
test_navbar_links_user()         # User sees Dashboard, Profile, Family
test_navbar_links_admin()        # Admin sees Dashboard, Admin, Manage, Users
test_user_dashboard_links()      # Edit profile, Add family member buttons
test_admin_dashboard_links()     # User/Event management links
test_profile_completeness_display() # Shows X% complete
test_family_member_count_display()  # Shows member count in table
test_add_family_member_modal()   # Add member via modal form
test_edit_family_member_modal()  # Edit member via modal form
test_delete_family_member()      # Delete member with confirmation
```

### 2. **E2EComprehensiveTest.py** - Enhanced Original Test
Extended version with profile management and database verification.

**Coverage:**
- ✅ User Login (authentication)
- ✅ Profile Update (form population)
- ✅ Profile Completeness UI
- ✅ Family Member Management (Add via AJAX & Form)
- ✅ Admin Features (optional promotion & dashboard)
- ✅ Database Verification

### 3. **run_all_bdd_tests.py** - Test Runner
Orchestrates execution of all BDD tests with reporting.

**Features:**
- Runs all tests sequentially
- Captures results and timing
- Generates JSON report
- Calculates pass/fail statistics

## Running Tests

### Run All Tests
```bash
cd /Users/sarthak/Sites/umashaktidham.org
python tests/bdd/run_all_bdd_tests.py
```

### Run Specific Test
```bash
python tests/bdd/ComprehensiveRoleBasedTest.py
python tests/bdd/E2EComprehensiveTest.py
```

### Run with Custom Configuration
```bash
# Custom URL
BASE_URL=http://staging.example.com python tests/bdd/ComprehensiveRoleBasedTest.py

# With visible browser
HEADLESS=false python tests/bdd/ComprehensiveRoleBasedTest.py

# Custom timeout
TEST_TIMEOUT=30 python tests/bdd/ComprehensiveRoleBasedTest.py

# All together
BASE_URL=http://localhost:9000 HEADLESS=false TEST_TIMEOUT=20 python tests/bdd/ComprehensiveRoleBasedTest.py
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `BASE_URL` | `http://localhost:8000` | Server URL to test against |
| `HEADLESS` | `true` | Run browser in headless mode (true/false) |
| `TEST_TIMEOUT` | `15` | Selenium wait timeout in seconds |
| `CHROMEDRIVER_PATH` | (auto) | Path to chromedriver executable |

## Test Data

### Test Credentials
- **Regular User**: `testuser@example.com` / `password123`
- **Admin User**: `testadmin@example.com` / `password123`

### Sample Family Members
```python
{
    'first_name': 'TestSpouse',
    'last_name': 'Patel',
    'relationship': 'spouse',
    'birth_year': '1990',
    'gender': 'male',
    'village': 'Ahmedabad',
    'mosal': 'Ahmedabad'
}
```

## Expected Results

### Test Output Example
```
====================================================================================================
  COMPREHENSIVE ROLE-BASED END-TO-END TEST SUITE
====================================================================================================

Configuration:
  BASE_URL:            http://localhost:8000
  HEADLESS:            True
  TEST_TIMEOUT:        15s

====================================================================================================
  PHASE 1: GUEST NAVIGATION (Not Logged In)
====================================================================================================

→ Testing navbar links for GUEST user
   ✅ Expected links found: ['Home', 'About', 'Contact', 'Login']
   ✅ No unexpected links found
   ✅ Guest Navbar Links ✓

... more test output ...

====================================================================================================
COMPREHENSIVE ROLE-BASED E2E TEST RESULTS
====================================================================================================

📋 NAVIGATION
   2/2 passed | 0/2 failed
   ✅ Guest Navbar Links                                  Found 4 expected
   ✅ User Navbar Links                                   Found 5 expected

📋 DASHBOARD
   2/2 passed | 0/2 failed
   ✅ User Dashboard Actions                              Found actions: ['edit-profile', 'add-family']
   ✅ Admin Dashboard Links                               Users: 3, Events: 2, Dashboard: 1

📋 STATS
   2/2 passed | 0/2 failed
   ✅ Profile Completeness Display                        Profile 75% complete
   ✅ Family Member Count Display                         2 family members

📋 FAMILY OPERATIONS
   3/3 passed | 0/3 failed
   ✅ Add Member TestSpouse                               ✓ Added
   ✅ Edit Member                                         ✓ Edited
   ✅ Delete Member                                       ✓ Deleted

====================================================================================================
TOTAL: 11/11 passed | 0/11 failed | 45.2s elapsed
====================================================================================================

🎉 ALL TESTS PASSED!
```

## Test Results & Artifacts

### Results Directory
```
tests/bdd/results/
├── test-results-1699567890.json           # JSON report with stats
├── add-family-exception-1699567890.png    # Screenshot on error
├── add-family-exception-1699567890.html   # HTML page source on error
└── ...
```

### JSON Report Format
```json
{
  "timestamp": "2024-11-09T12:34:56.789Z",
  "total_tests": 11,
  "passed": 11,
  "failed": 0,
  "elapsed": 45.2,
  "results": {
    "ComprehensiveRoleBasedTest.py": {
      "passed": true,
      "elapsed": 45.2
    }
  },
  "config": {
    "base_url": "http://localhost:8000",
    "headless": true,
    "timeout": "15"
  }
}
```

## Validation Matrix

### Navigation Links by Role

| Link/Feature | Guest | User | Admin | Notes |
|---|---|---|---|---|
| Home | ✅ | ✅ | ✅ | Always visible |
| About | ✅ | ✅ | ✅ | Info pages |
| Contact | ✅ | ✅ | ✅ | Public pages |
| Login | ✅ | ❌ | ❌ | Only for guests |
| Register | ✅ | ❌ | ❌ | Only for guests |
| Dashboard | ❌ | ✅ | ✅ | User profile dashboard |
| Profile Edit | ❌ | ✅ | ✅ | Edit user info |
| Add Family | ❌ | ✅ | ✅ | Family management |
| Admin Panel | ❌ | ❌ | ✅ | Admin features |
| Manage Users | ❌ | ❌ | ✅ | User administration |
| Manage Events | ❌ | ❌ | ✅ | Event administration |
| Logout | ❌ | ✅ | ✅ | Authenticated only |

### Dashboard Elements

| Element | User Dashboard | Admin Dashboard | Validation |
|---|---|---|---|
| Profile Completeness % | ✅ | Optional | Displays X% |
| Family Member Count | ✅ | ✅ | Shows in table |
| Edit Profile Button | ✅ | Optional | data-action="edit-profile" |
| Add Family Button | ✅ | Optional | data-action="add-family" |
| Edit Family Button | ✅ | Optional | Per member row |
| Delete Family Button | ✅ | Optional | Per member row |
| Upcoming Events | ✅ | ✅ | List with dates |
| Your Tickets | ✅ | Optional | List/manage tickets |

### Form Operations

| Operation | Modal | Validation |
|---|---|---|
| Add Family Member | Form fills → Submit → Success | Member appears in table |
| Edit Family Member | Form pre-fills → Modify → Submit → Success | Changes saved |
| Delete Family Member | Click button → Confirm → Success | Member removed from table |

## Troubleshooting

### Test Fails: Modal Not Opening
- Check modal-forms.js is loaded: `<script src="/assets/js/modal-forms.js"></script>`
- Check button has correct data-action: `data-action="edit-family"` or `data-action="add-family"`
- Check Bootstrap modal CSS is loaded

### Test Fails: Form Fields Not Populated
- Verify field names match form template (e.g., `first_name`, not `firstName`)
- Check form HTML is valid and being returned from `/get-member-form` endpoint
- Verify input elements have correct `name` attributes

### Test Fails: Stats Not Displaying
- Check profile completeness element has ID: `id="profilePercentText"`
- Verify family member table is present: `<table>` with rows
- Check page calculation logic in backend

### Test Times Out
- Increase `TEST_TIMEOUT` environment variable
- Check server is responsive: `curl http://localhost:8000/`
- Verify no JavaScript errors in browser console

## CI/CD Integration

### GitHub Actions Example
```yaml
name: BDD Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-python@v2
        with:
          python-version: '3.9'
      - name: Install dependencies
        run: |
          pip install selenium
          apt-get update && apt-get install -y chromium-browser
      - name: Run BDD tests
        env:
          BASE_URL: http://localhost:8000
        run: python tests/bdd/run_all_bdd_tests.py
```

## Adding New Tests

### Template for New Test
```python
def test_new_feature(driver, test_results):
    """Test description"""
    log_step("Testing NEW FEATURE")
    
    try:
        driver.get(f'{BASE_URL}/page-url')
        time.sleep(1)
        
        # Test logic here
        element = driver.find_element(By.ID, 'element-id')
        element.click()
        
        # Assert or verify
        WebDriverWait(driver, 5).until(
            EC.presence_of_element_located((By.CLASS_NAME, 'success'))
        )
        
        test_results.record('Category', 'Test Name', True, '✓ Details')
        print(f"   ✅ Test passed")
        return True
        
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        test_results.record('Category', 'Test Name', False, str(e)[:50])
        save_debug(driver, 'test-name')
        return False
```

## Performance Benchmarks

Expected test execution times:
- **Guest Navigation**: ~5s
- **User Authentication**: ~8s
- **Dashboard Validation**: ~10s
- **Family Operations**: ~20s (per operation)
- **Admin Features**: ~15s
- **Total Suite**: ~45-60s

## Known Issues & Limitations

1. **Modal Popup Timing**: First modal load may take 1-2 seconds for JavaScript initialization
2. **Form Pre-filling**: Some browsers cache form values; tests clear fields before filling
3. **Database Queries**: Database verification requires MySQL access configured
4. **Admin Promotion**: Some tests require PHP helper script for admin role assignment
5. **Parallel Execution**: Tests are sequential to avoid database conflicts

## Support & Debugging

### Enable Debug Output
```bash
# Run with Python debug logging
python -u tests/bdd/ComprehensiveRoleBasedTest.py 2>&1 | tee test-run.log
```

### View Debug Artifacts
```bash
# Screenshot on error
open tests/bdd/results/test-name-1699567890.png

# HTML page source
cat tests/bdd/results/test-name-1699567890.html
```

### Check Test Results JSON
```bash
# View latest results
cat tests/bdd/results/test-results-*.json | jq '.'
```

## Future Enhancements

- [ ] Parallel test execution for faster runs
- [ ] Screenshot comparison for UI regression testing
- [ ] Performance profiling (load times, API response)
- [ ] Visual regression testing
- [ ] Multi-browser testing (Firefox, Safari, Edge)
- [ ] Mobile device testing
- [ ] Accessibility compliance testing (WCAG)
- [ ] API integration testing
- [ ] Database consistency verification
- [ ] Email notification alerts on test failure

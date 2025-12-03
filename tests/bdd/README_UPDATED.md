# BDD (Behavior-Driven Development) Test Suite - COMPREHENSIVE GUIDE

Updated: November 9, 2024

## Overview

This comprehensive BDD test suite validates all critical user flows, navigation, dashboards, and statistics across different user roles with extensive UI/link validation and role-based testing.

### What's New in This Update

✨ **New Features:**
- ✅ **Navbar Link Validation** - Validates navbar links for each user role (Guest, User, Admin)
- ✅ **Dashboard Stats Validation** - Verifies profile completeness % and family member counts are displayed
- ✅ **Link Working Tests** - Ensures all dashboard links are clickable and functional
- ✅ **Shared Validator Utils** - Reusable validators reduce code duplication
- ✅ **Enhanced Test Runner** - Supports both pytest and direct execution modes
- ✅ **Improved Reporting** - Better structured test results and HTML output

## Test Files Structure

### Primary Test Files

#### 1. **E2EComprehensiveTest.py** ⭐ ENHANCED
The main end-to-end test with complete coverage including new navbar and stats validation.

**Coverage:**
- ✅ User Login (authentication)
- ✅ **Navbar Links Validation by Role** (NEW)
- ✅ **Dashboard Stats Display** (NEW)
- ✅ **Dashboard Links Validation** (NEW)
- ✅ Profile Update (form population)
- ✅ Profile Completeness UI
- ✅ Family Member Management (Add via AJAX & Form)
- ✅ Admin Features (optional promotion & dashboard)
- ✅ **Admin Navbar Links Validation** (NEW)
- ✅ Database Verification

**Test Phases:**
1. **PHASE 1: AUTHENTICATION** - Login test
2. **PHASE 2: NAVBAR LINKS & NAVIGATION** - Test navbar for user role
3. **PHASE 3: PROFILE MANAGEMENT** - Profile updates and completeness
4. **PHASE 4: FAMILY MEMBER MANAGEMENT** - Add/manage family members
5. **PHASE 5: ADMIN FEATURES** - Admin promotion and dashboard
6. **PHASE 6: DATABASE VERIFICATION** - Data persistence checks

#### 2. **ComprehensiveRoleBasedTest.py**
Role-based navigation testing with extensive coverage for Guest, User, and Admin roles.

**Coverage:**
- ✅ Guest Navigation
- ✅ User Authentication
- ✅ User Navigation & Dashboard
- ✅ Admin Navigation & Dashboard
- ✅ Profile Completeness Stats
- ✅ Family Member Operations (Modal-based)
- ✅ Admin User/Event Management

#### 3. **run_all_bdd_tests.py** ⭐ ENHANCED
Test suite orchestrator with pytest support and improved reporting.

**Features:**
- ✅ Direct test execution (unittest-style)
- ✅ Pytest integration (`--pytest` flag)
- ✅ HTML report generation (`--html` flag)
- ✅ Custom configuration via CLI arguments
- ✅ Comprehensive result logging

### Utility Modules

#### `utils/navbar_links_validator.py`
Validates navbar links by user role with fuzzy matching.

```python
from utils.navbar_links_validator import NavbarLinksValidator

validator = NavbarLinksValidator(driver)
result = validator.validate_for_role('user')
# Returns: {'passed': bool, 'found_links': [...], 'missing_links': [...], ...}
```

**Features:**
- Role-based link validation (guest, user, admin)
- Partial text matching (handles 'Dashboard', 'My Dashboard', etc.)
- Link clickability verification
- Comprehensive reporting

#### `utils/dashboard_stats_validator.py`
Validates dashboard statistics and links.

```python
from utils.dashboard_stats_validator import DashboardStatsValidator

validator = DashboardStatsValidator(driver)
stats = validator.get_all_dashboard_stats()
# Returns: {
#   'profile_completeness': {...},
#   'family_member_count': {...},
#   'stats_accuracy': {...}
# }
```

**Features:**
- Profile completeness % display
- Family member count display
- Dashboard link validation
- Link working/clickable checks
- Comprehensive error handling

#### `utils/__init__.py`
Common utilities and helpers for BDD tests.

**Includes:**
- BDDConfig - Configuration management
- BDDReporter - Test result reporting
- BDDLogger - Formatted logging

#### `conftest.py`
Pytest configuration and fixtures.

**Features:**
- Pytest configuration and markers
- Test fixtures (config, results_dir, timestamps)
- Custom test discovery
- Pytest hooks (headers, session, report generation)

## Running Tests

### Basic Usage

**Run all tests (direct execution):**
```bash
cd /Users/sarthak/Sites/umashaktidham.org
python tests/bdd/run_all_bdd_tests.py
```

**Run all tests with pytest:**
```bash
python tests/bdd/run_all_bdd_tests.py --pytest
```

**Run specific test:**
```bash
python tests/bdd/E2EComprehensiveTest.py
# or with test runner
python tests/bdd/run_all_bdd_tests.py --test E2EComprehensiveTest
```

**Run with pytest and generate HTML report:**
```bash
python tests/bdd/run_all_bdd_tests.py --pytest --html
```

### Advanced Usage

**Custom configuration:**
```bash
# Custom URL
BASE_URL=http://staging.example.com python tests/bdd/run_all_bdd_tests.py

# Visible browser (not headless)
HEADLESS=false python tests/bdd/run_all_bdd_tests.py

# Custom timeout (30 seconds)
TEST_TIMEOUT=30 python tests/bdd/run_all_bdd_tests.py

# All together
BASE_URL=http://localhost:9000 HEADLESS=false TEST_TIMEOUT=20 python tests/bdd/run_all_bdd_tests.py
```

**Using the enhanced test runner with CLI arguments:**
```bash
# Pytest mode with visible browser
python tests/bdd/run_all_bdd_tests.py --pytest --no-headless --verbose

# Run specific test with custom URL
python tests/bdd/run_all_bdd_tests.py --test ComprehensiveRoleBasedTest --url http://staging.local:8000

# Verbose output with HTML report
python tests/bdd/run_all_bdd_tests.py --pytest --html --verbose

# All options
python tests/bdd/run_all_bdd_tests.py \
  --pytest \
  --test E2EComprehensiveTest \
  --no-headless \
  --timeout 30 \
  --url http://localhost:8000 \
  --html \
  --verbose
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `BASE_URL` | `http://localhost:8000` | Server URL to test against |
| `HEADLESS` | `true` | Run browser in headless mode (true/false) |
| `TEST_TIMEOUT` | `15` | Selenium wait timeout in seconds |
| `CHROMEDRIVER_PATH` | (auto) | Path to chromedriver executable |
| `SKIP_PROMOTION` | `false` | Skip admin promotion tests (true/false) |
| `SKIP_DATABASE_CHECK` | `false` | Skip database verification (true/false) |

## Test Data

### Test Credentials

**Regular User:**
- Email: `testuser@example.com`
- Password: `password123`

**Admin User (promoted during test):**
- Email: `testuser@example.com` (same user promoted)
- Password: `password123`

### Profile Update Data

```python
{
    'first_name': 'Selenium',
    'last_name': 'Tester',
    'phone': '9999999999',
    'village': 'Testville'
}
```

### Sample Family Members

```python
[
    {
        'first_name': 'Member1',
        'last_name': 'Patel',
        'relationship': 'spouse',
        'birth_year': '1990'
    },
    {
        'first_name': 'Member2',
        'last_name': 'Patel',
        'relationship': 'child',
        'birth_year': '2015'
    }
]
```

## Expected Test Results

### E2EComprehensiveTest.py Output Example

```
====================================================================================================
  COMPREHENSIVE END-TO-END TEST SUITE
====================================================================================================

Configuration:
  BASE_URL:            http://localhost:8000
  HEADLESS:            True
  TEST_TIMEOUT:        15s

====================================================================================================
  PHASE 1: AUTHENTICATION
====================================================================================================

→ Testing login for testuser@example.com
   ✅ Successfully logged in

====================================================================================================
  PHASE 2: NAVBAR LINKS & NAVIGATION
====================================================================================================

→ Testing navbar links for USER role
   ✅ Authenticated regular user
      Found: ['Home', 'About', 'Contact', 'Dashboard', ...]
   ✅ Navbar Links (user) ✓ 7 links

→ Testing dashboard stats display
   Profile Completeness: Profile 75% complete
      ✅ 75%
   Family Members: 2 family member(s) found
      ✅ 2 members
   ✅ Stats Accuracy: All stats displayed accurately
   ✅ Dashboard Stats ✓ All stats displayed accurately

→ Testing dashboard links
   Found: 2/3 buttons
      ✅ Edit Profile
      ✅ Add Family Member
      ❌ View Profile
   ✅ Dashboard Links ✓ 2 links

... more phases ...

====================================================================================================
COMPREHENSIVE E2E TEST RESULTS
====================================================================================================
Total: 13/13 passed | 0/13 failed | 65.2s elapsed
====================================================================================================

🎉 ALL TESTS PASSED!
```

## Validation Matrix

### Navbar Links by Role

| Link | Guest | User | Admin | Notes |
|------|-------|------|-------|-------|
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

### Dashboard Stats Validation

| Element | Validation | Expected |
|---------|-----------|----------|
| Profile Completeness | % display accuracy | Shows current % |
| Family Member Count | Count accuracy | Shows actual count |
| Edit Profile Button | Clickability | Displayed and enabled |
| Add Family Button | Clickability | Displayed and enabled |
| Member Table | Presence | Shows member list |
| Upcoming Events | Presence | Shows events |

## Test Results & Artifacts

### Results Directory

```
tests/bdd/results/
├── test-results-1699567890.json           # JSON report with stats
├── report-1699567890.html                 # Pytest HTML report
├── E2E-exception-1699567890.png           # Screenshot on error
├── E2E-exception-1699567890.html          # HTML page source on error
└── ...
```

### JSON Report Format

```json
{
  "timestamp": "2024-11-09T12:34:56.789Z",
  "total_tests": 13,
  "passed": 13,
  "failed": 0,
  "elapsed": 65.2,
  "results": {
    "E2EComprehensiveTest.py": {
      "passed": true,
      "elapsed": 65.2
    }
  },
  "config": {
    "base_url": "http://localhost:8000",
    "headless": true,
    "timeout": "15",
    "runner": "direct"
  }
}
```

## Troubleshooting

### Test Fails: Navbar Links Not Found

**Issue:** Tests can't find navbar links
**Solutions:**
1. Check navbar HTML is present: `curl http://localhost:8000/`
2. Verify navbar element selector: Check browser DevTools
3. Increase timeout: `TEST_TIMEOUT=30`
4. Check for JavaScript errors in browser console

### Test Fails: Dashboard Stats Missing

**Issue:** Profile completeness or family count not displaying
**Solutions:**
1. Verify element IDs exist:
   - Profile completeness: `id="profilePercentText"`
   - Family member table: `<table>` with rows
2. Check backend stats calculation logic
3. Verify CSS is loading correctly
4. Check for JavaScript errors

### Test Fails: Admin Promotion Failed

**Issue:** Cannot promote test user to admin
**Solutions:**
1. Verify `simple_promote.php` script exists
2. Check PHP is installed: `which php`
3. Verify database credentials in script
4. Check database is running and accessible
5. Skip with: `SKIP_PROMOTION=true`

### Test Timeout

**Issue:** Tests timeout and don't complete
**Solutions:**
1. Increase timeout: `TEST_TIMEOUT=30` or higher
2. Check server is responsive: `curl http://localhost:8000/`
3. Check for slow JavaScript: `HEADLESS=false` to see browser
4. Reduce concurrent tests

### Import Error: Cannot Import Validators

**Issue:** `ImportError: No module named 'navbar_links_validator'`
**Solutions:**
1. Verify utils files exist:
   - `tests/bdd/utils/navbar_links_validator.py`
   - `tests/bdd/utils/dashboard_stats_validator.py`
2. Verify Python path is correct
3. Run from correct directory: `cd /path/to/project`

## CI/CD Integration

### GitHub Actions Example

```yaml
name: BDD Tests
on: [push, pull_request]

jobs:
  bdd-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Set up Python
        uses: actions/setup-python@v2
        with:
          python-version: '3.9'
      
      - name: Install dependencies
        run: |
          pip install selenium pytest pytest-html
          apt-get update && apt-get install -y chromium-browser chromium-chromedriver
      
      - name: Start server
        run: |
          php -S localhost:8000 > server.log 2>&1 &
          sleep 2
      
      - name: Run BDD tests
        env:
          BASE_URL: http://localhost:8000
          HEADLESS: true
        run: python tests/bdd/run_all_bdd_tests.py --pytest --html
      
      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v2
        with:
          name: bdd-test-results
          path: tests/bdd/results/
```

## Adding New Tests

### Using the Validators in New Tests

```python
from utils.navbar_links_validator import NavbarLinksValidator
from utils.dashboard_stats_validator import DashboardStatsValidator

def test_navbar_links(driver):
    """Test navbar links"""
    validator = NavbarLinksValidator(driver, timeout=15)
    result = validator.validate_for_role('user')
    assert result['passed'], f"Missing: {result['missing_links']}"

def test_dashboard_stats(driver):
    """Test dashboard statistics"""
    validator = DashboardStatsValidator(driver, timeout=15)
    stats = validator.get_all_dashboard_stats()
    assert stats['stats_accuracy']['passed'], "Stats not displaying correctly"
```

### Template for New Test Function

```python
def test_new_feature(driver, test_results):
    """Test description"""
    log_step("Testing NEW FEATURE")
    
    try:
        driver.get(f'{BASE_URL}/page-url')
        time.sleep(1)
        
        # Test logic here
        element = driver.find_element(By.ID, 'element-id')
        assert element.is_displayed(), "Element not visible"
        
        print(f"   ✅ Test passed")
        test_results.record('Category', 'Test Name', True, '✓ Details')
        return True
        
    except AssertionError as e:
        print(f"   ❌ Assertion failed: {e}")
        test_results.record('Category', 'Test Name', False, str(e)[:50])
        save_debug(driver, 'test-name')
        return False
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        test_results.record('Category', 'Test Name', False, str(e)[:50])
        save_debug(driver, 'test-name-exception')
        return False
```

## Performance Benchmarks

Expected test execution times:

| Component | Time |
|-----------|------|
| Guest Navigation | ~5s |
| User Authentication | ~8s |
| Navbar Links (User) | ~5s |
| Dashboard Stats | ~8s |
| Dashboard Links | ~5s |
| Profile Management | ~10s |
| Family Operations | ~20s (per operation) |
| Admin Features | ~15s |
| Database Verification | ~5s |
| **Total Suite** | **~60-80s** |

## Known Issues & Limitations

1. **Modal Popup Timing** - First modal load may take 1-2 seconds
2. **Form Pre-filling** - Some browsers cache form values
3. **Database Queries** - Requires MySQL access configured
4. **Admin Promotion** - Some tests require PHP helper script
5. **Parallel Execution** - Tests are sequential to avoid DB conflicts
6. **Fuzzy Link Matching** - Case-insensitive partial matching (may match unintended links)

## Support & Debugging

### Enable Debug Output

```bash
# Run with Python debug logging
python -u tests/bdd/E2EComprehensiveTest.py 2>&1 | tee test-run.log
```

### View Debug Artifacts

```bash
# Screenshot on error
open tests/bdd/results/test-name-1699567890.png

# HTML page source
cat tests/bdd/results/test-name-1699567890.html | head -50
```

### Check Test Results JSON

```bash
# View latest results
ls -lt tests/bdd/results/test-results-*.json | head -1 | awk '{print $NF}' | xargs cat | jq '.'
```

### Enable Verbose Output

```bash
python tests/bdd/run_all_bdd_tests.py --pytest --verbose
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
- [ ] Extended role-based testing scenarios
- [ ] Custom alert/banner validation
- [ ] Form validation testing

## Quick Reference Commands

```bash
# Run all tests
python tests/bdd/run_all_bdd_tests.py

# Run with pytest
python tests/bdd/run_all_bdd_tests.py --pytest

# Run specific test
python tests/bdd/E2EComprehensiveTest.py

# Run with visible browser
HEADLESS=false python tests/bdd/run_all_bdd_tests.py

# Run with increased timeout
TEST_TIMEOUT=30 python tests/bdd/run_all_bdd_tests.py

# Run and generate HTML report
python tests/bdd/run_all_bdd_tests.py --pytest --html

# View latest test results
cat tests/bdd/results/test-results-*.json | tail -1 | jq '.'
```

## Contact & Support

For issues or questions about the BDD test suite, check the test results directory for detailed debug information.

# Test Suite Documentation

This directory contains all automated tests for the Umashakti Dham application, organized into two categories:

## Directory Structure

```
tests/
├── tdd/                    # Unit Tests (PHP-based, Test-Driven Development)
├── bdd/                    # Integration & Behavioral Tests (Python/Selenium)
├── README.md              # This file
├── run_all_tests.sh       # Master test runner
└── COVERAGE.md            # Test coverage documentation
```

## Quick Start

### Run All Tests
```bash
cd /Users/sarthak/Sites/umashaktidham.org
bash tests/run_all_tests.sh
```

### Run Unit Tests Only (TDD)
```bash
bash tests/run_tdd_tests.sh
```

### Run Integration Tests Only (BDD)
```bash
bash tests/run_bdd_tests.sh
```

## Test Categories

### TDD (Test-Driven Development) - `/tests/tdd/`

**Technology:** PHPUnit, PHP unit tests

**Purpose:** Unit and integration tests for backend functionality

**Files:**
- `BootstrapCleanupAnalyzer.php` - Bootstrap configuration validation
- `EventServiceTest.php` - Event service tests
- `PasswordResetTest.php` - Password reset functionality
- `RegistrationTest.php` - User registration logic
- `SimpleTest.php` - Basic smoke tests
- `LinkTester.php` - Link validation
- `route_smoke.php` - Route smoke tests
- `integration_harness.php` - Integration test harness

**How to Run:**

Run a single test:
```bash
cd /Users/sarthak/Sites/umashaktidham.org
php tests/tdd/RegistrationTest.php
```

Run all TDD tests with PHPUnit:
```bash
./vendor/bin/phpunit tests/tdd/
```

### BDD (Behavior-Driven Development) - `/tests/bdd/`

**Technology:** Python, Selenium WebDriver

**Purpose:** End-to-end behavioral tests, UI testing, user workflows

**Files:**
- `E2EComprehensiveTest.py` - Full user journey tests
- `SeleniumTestSignUpLogin.py` - Authentication flows
- `test_family_management.py` - Family member CRUD
- `test_dashboard_buttons.py` - Dashboard UI interactions
- `test_self_edit.py` - Self profile editing
- `test_success_banners.py` - Success/error message validation
- `test_admin_features.py` - Admin panel functionality
- `test_password_security.py` - Password security tests
- `test_save_button.py` - Form save operations
- `run_all_tests.py` - Python test orchestrator

**Requirements:**
- Python 3.8+
- Selenium WebDriver
- ChromeDriver (for Chrome browser automation)
- Chrome/Firefox browser

**Setup:**
```bash
# Install Python dependencies
pip install selenium pytest pytest-timeout

# Download ChromeDriver from:
# https://chromedriver.chromium.org/
# And add to PATH or specify path in tests
```

**How to Run:**

Run a single test:
```bash
cd /Users/sarthak/Sites/umashaktidham.org
python tests/bdd/test_self_edit.py
```

Run all BDD tests:
```bash
python tests/bdd/run_all_tests.py
```

Run with pytest (recommended):
```bash
pytest tests/bdd/ -v
```

Run specific test class:
```bash
pytest tests/bdd/test_family_management.py::TestFamilyManagement -v
```

Run tests with timeout (60 seconds):
```bash
pytest tests/bdd/ -v --timeout=60
```

## Test Execution Examples

### Example 1: Run All Tests
```bash
cd /Users/sarthak/Sites/umashaktidham.org
bash tests/run_all_tests.sh
```

Output:
```
=== Running TDD Tests ===
Running: tests/tdd/BootstrapCleanupAnalyzer.php
✅ Bootstrap test passed
...

=== Running BDD Tests ===
Running: tests/bdd/E2EComprehensiveTest.py
✅ All scenarios passed
...
```

### Example 2: Run Only Registration Tests
```bash
cd /Users/sarthak/Sites/umashaktidham.org
php tests/tdd/RegistrationTest.php
```

### Example 3: Run Only Dashboard Tests
```bash
cd /Users/sarthak/Sites/umashaktidham.org
pytest tests/bdd/test_dashboard_buttons.py -v
```

### Example 4: Run Tests with Parallel Execution
```bash
cd /Users/sarthak/Sites/umashaktidham.org
pytest tests/bdd/ -n auto  # Requires pytest-xdist plugin
```

## Continuous Integration

For CI/CD pipelines, use:

```bash
# GitHub Actions, GitLab CI, etc.
bash tests/run_all_tests.sh --ci

# Set environment variables
export HEADLESS=true        # Run browsers headless
export SKIP_SERVER=true     # Don't start PHP server (already running)
export TIMEOUT=30           # Test timeout in seconds
```

## Test Maintenance

### Adding a New Unit Test (TDD)

1. Create file: `tests/tdd/MyNewTest.php`
2. Follow PHPUnit conventions
3. Run: `./vendor/bin/phpunit tests/tdd/MyNewTest.php`

### Adding a New Integration Test (BDD)

1. Create file: `tests/bdd/test_my_feature.py`
2. Use Selenium WebDriver
3. Run: `pytest tests/bdd/test_my_feature.py -v`

## Troubleshooting

### ChromeDriver Issues
```bash
# Download matching version
wget https://chromedriver.chromium.org/downloads

# Add to PATH
export PATH=$PATH:/path/to/chromedriver

# Verify
chromedriver --version
```

### Python Dependencies
```bash
# Reinstall dependencies
pip install -r requirements.txt

# Or install individually
pip install selenium pytest pytest-timeout
```

### PHP Version
```bash
# Verify PHP version
php --version

# Required: PHP 8.0+
```

## Coverage Reports

After running tests, view coverage:

```bash
# PHP Coverage
./vendor/bin/phpunit --coverage-html tests/coverage tests/tdd/

# Python Coverage
coverage run -m pytest tests/bdd/
coverage html
# Open htmlcov/index.html in browser
```

## CI/CD Integration

See `COVERAGE.md` for detailed test coverage information and CI/CD setup.

---

**Last Updated:** November 8, 2025  
**Test Framework Versions:**
- PHPUnit: 9.x+
- Selenium: 4.x+
- Python: 3.8+

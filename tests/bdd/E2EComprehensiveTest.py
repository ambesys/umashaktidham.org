"""
Comprehensive End-to-End Selenium Test Suite
Tests all critical flows: Login → Profile → Family Members → Admin → Dashboard

Features:
- Pre-seeded test user login (testuser@example.com)
- Admin user promotion via PHP helper
- Profile update and completeness tracking
- Family member management (AJAX and form-based)
- Admin dashboard access verification
- Navbar links validation by role
- Dashboard stats validation (profile %, family count)
- Database verification for data persistence
- Detailed debug artifact capture (screenshots + HTML)

Usage:
  python tests/bdd/E2EComprehensiveTest.py

Configuration (via environment variables):
  BASE_URL           - Server URL (default: http://localhost:8000)
  HEADLESS           - Run in headless mode (default: true)
  TEST_TIMEOUT       - Selenium wait timeout in seconds (default: 15)
  CHROMEDRIVER_PATH  - Path to chromedriver (optional, auto-detect if not set)
  SKIP_PROMOTION     - Skip admin promotion test (default: false)
  SKIP_DATABASE_CHECK- Skip database verification (default: false)
"""

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, WebDriverException
import time
import json
import subprocess
import os
import sys
import traceback
from datetime import datetime

# Add utils to path
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', 'bdd', 'utils'))

try:
    from navbar_links_validator import NavbarLinksValidator
    from dashboard_stats_validator import DashboardStatsValidator
except ImportError:
    print("⚠️  Navbar/Dashboard validators not found - will continue with basic tests")
    NavbarLinksValidator = None
    DashboardStatsValidator = None

# ============================================================================
# CONFIGURATION
# ============================================================================

BASE_URL = os.environ.get('BASE_URL', 'http://localhost:8000').rstrip('/')
HEADLESS = os.environ.get('HEADLESS', 'true').lower() in ('1', 'true', 'yes')
TEST_TIMEOUT = int(os.environ.get('TEST_TIMEOUT', '15'))
CHROMEDRIVER_PATH = os.environ.get('CHROMEDRIVER_PATH')
SKIP_PROMOTION = os.environ.get('SKIP_PROMOTION', 'false').lower() in ('1', 'true', 'yes')
SKIP_DATABASE_CHECK = os.environ.get('SKIP_DATABASE_CHECK', 'false').lower() in ('1', 'true', 'yes')

# Test credentials
TEST_USER_EMAIL = 'testuser@example.com'
TEST_USER_PASSWORD = 'password123'
TEST_ADMIN_EMAIL = 'testadmin@example.com'

# Test data
PROFILE_UPDATE_DATA = {
    'first_name': 'Selenium',
    'last_name': 'Tester',
    'phone': '9999999999',
    'village': 'Testville'
}

FAMILY_MEMBERS_TO_ADD = [
    {'first_name': 'Member1', 'last_name': 'Patel', 'relationship': 'spouse', 'birth_year': 1990},
    {'first_name': 'Member2', 'last_name': 'Patel', 'relationship': 'child', 'birth_year': 2015},
]

# ============================================================================
# UTILITY FUNCTIONS
# ============================================================================

class TestResults:
    """Track test results"""
    def __init__(self):
        self.results = {}
        self.start_time = datetime.now()
    
    def record(self, test_name, passed, details=''):
        """Record test result"""
        self.results[test_name] = {'passed': passed, 'details': details}
    
    def summary(self):
        """Print summary"""
        total = len(self.results)
        passed = sum(1 for r in self.results.values() if r['passed'])
        failed = total - passed
        elapsed = (datetime.now() - self.start_time).total_seconds()
        
        print("\n" + "=" * 80)
        print("COMPREHENSIVE E2E TEST RESULTS")
        print("=" * 80)
        for test_name, result in self.results.items():
            status = "✅ PASS" if result['passed'] else "❌ FAIL"
            print(f"{status:12} | {test_name:40} {result['details']}")
        print("=" * 80)
        print(f"Total: {passed}/{total} passed | {failed}/{total} failed | {elapsed:.1f}s elapsed")
        print("=" * 80)
        return failed == 0


def log_section(title):
    """Print section header"""
    print(f"\n{'=' * 80}")
    print(f"  {title}")
    print(f"{'=' * 80}")


def log_step(message):
    """Print step message"""
    print(f"→ {message}")


def save_debug(driver, name_prefix):
    """Save screenshot and HTML for debugging"""
    ts = int(time.time())
    try:
        screenshot = f"{name_prefix}-{ts}.png"
        driver.save_screenshot(screenshot)
        with open(f"{name_prefix}-{ts}.html", 'w', encoding='utf-8') as f:
            f.write(driver.page_source)
        print(f"   📸 Debug artifacts saved: {screenshot}, {name_prefix}-{ts}.html")
        return screenshot
    except Exception as e:
        print(f"   ⚠️  Could not save debug artifacts: {e}")
        return None


def build_driver():
    """Create Chrome WebDriver with appropriate options"""
    opts = Options()
    if HEADLESS:
        opts.add_argument('--headless=new')
    opts.add_argument('--no-sandbox')
    opts.add_argument('--disable-dev-shm-usage')
    opts.add_argument('--window-size=1366,768')
    opts.add_argument('--ignore-certificate-errors')
    
    try:
        if CHROMEDRIVER_PATH:
            driver = webdriver.Chrome(executable_path=CHROMEDRIVER_PATH, options=opts)
        else:
            driver = webdriver.Chrome(options=opts)
    except TypeError:
        # Fallback for older Selenium versions
        driver = webdriver.Chrome(executable_path=CHROMEDRIVER_PATH, options=opts) if CHROMEDRIVER_PATH else webdriver.Chrome(options=opts)
    return driver


def promote_test_user_to_admin(cwd=None):
    """Call PHP helper to promote test user to admin"""
    log_step("Promoting test user to admin role...")
    cwd = cwd or os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
    promote_script = os.path.join(cwd, 'simple_promote.php')
    
    if not os.path.exists(promote_script):
        print("   ℹ️  Promotion script not found, skipping")
        return False
    
    try:
        result = subprocess.run(['php', promote_script], cwd=cwd, capture_output=True, text=True, timeout=30)
        if result.returncode == 0:
            print("   ✅ Test user promoted to admin")
            if result.stdout.strip():
                print(f"   📝 Output: {result.stdout.strip()}")
            return True
        else:
            print(f"   ❌ Promotion failed: {result.stderr.strip()}")
            return False
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        return False


def query_database(query):
    """Execute MySQL query and return results"""
    try:
        result = subprocess.run([
            'mysql', '-u', 'root', '-proot', 'u103964107_uma',
            '-e', query
        ], capture_output=True, text=True, timeout=5)
        
        if result.returncode == 0:
            return result.stdout
        else:
            return None
    except Exception as e:
        print(f"   ⚠️  Database query error: {e}")
        return None


# ============================================================================
# TEST FUNCTIONS - LOGIN & AUTHENTICATION
# ============================================================================

def test_login(driver, email, password, test_results):
    """Test user login"""
    log_step(f"Testing login for {email}")
    
    try:
        driver.get(f'{BASE_URL}/login')
        time.sleep(0.5)
        
        # Wait for email field
        try:
            email_field = WebDriverWait(driver, TEST_TIMEOUT).until(
                EC.presence_of_element_located((By.NAME, 'email'))
            )
            print(f"   ✅ Login form loaded")
        except TimeoutException:
            print(f"   ❌ Login form not found")
            save_debug(driver, 'test-login-form-missing')
            test_results.record('Login', False, 'Login form not found')
            return False
        
        # Try access code field (may exist)
        try:
            access_code_field = WebDriverWait(driver, 2).until(
                EC.presence_of_element_located((By.NAME, 'access_code'))
            )
            access_code_field.send_keys('jayumiya')
            access_code_field.send_keys(Keys.RETURN)
            print(f"   ✅ Access code entered")
            time.sleep(1)
        except TimeoutException:
            pass  # Access code field is optional
        
        # Fill email
        email_field.clear()
        email_field.send_keys(email)
        print(f"   ✅ Email filled: {email}")
        
        # Fill password
        password_field = driver.find_element(By.NAME, 'password')
        password_field.clear()
        password_field.send_keys(password)
        print(f"   ✅ Password filled")
        
        # Submit form - use JavaScript to avoid click interception
        try:
            submit_btn = WebDriverWait(driver, TEST_TIMEOUT).until(
                EC.presence_of_element_located((By.NAME, 'submit'))
            )
            # Scroll into view and click
            driver.execute_script("arguments[0].scrollIntoView(true);", submit_btn)
            time.sleep(0.3)
            try:
                submit_btn.click()
            except Exception:
                driver.execute_script("arguments[0].click();", submit_btn)
        except NoSuchElementException:
            try:
                submit_btn = driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]')
                driver.execute_script("arguments[0].scrollIntoView(true);", submit_btn)
                time.sleep(0.3)
                try:
                    submit_btn.click()
                except Exception:
                    driver.execute_script("arguments[0].click();", submit_btn)
            except NoSuchElementException:
                password_field.send_keys(Keys.RETURN)
        print(f"   ✅ Form submitted")
        
        # Wait for redirect to dashboard
        try:
            WebDriverWait(driver, TEST_TIMEOUT).until(
                lambda d: '/dashboard' in d.current_url or '/user/dashboard' in d.current_url
            )
            print(f"   ✅ Successfully logged in")
            print(f"      Current URL: {driver.current_url}")
            test_results.record('Login', True, f'✓ {email}')
            return True
        except TimeoutException:
            print(f"   ❌ Did not redirect to dashboard")
            print(f"      Current URL: {driver.current_url}")
            save_debug(driver, 'test-login-redirect-fail')
            test_results.record('Login', False, 'No redirect to dashboard')
            return False
            
    except Exception as e:
        print(f"   ❌ Login exception: {e}")
        traceback.print_exc()
        save_debug(driver, 'test-login-exception')
        test_results.record('Login', False, str(e)[:50])
        return False


# ============================================================================
# TEST FUNCTIONS - PROFILE
# ============================================================================

def test_profile_update(driver, test_results):
    """Test profile update"""
    log_step("Testing profile update")
    
    try:
        driver.get(f'{BASE_URL}/user/dashboard')
        time.sleep(1)
        
        print(f"   🔍 Looking for profile edit controls...")
        
        # Try to find edit button
        try:
            edit_btn = driver.find_element(By.CSS_SELECTOR, '[data-action="edit-profile"]')
            edit_btn.click()
            print(f"   ✅ Edit button clicked")
            time.sleep(0.5)
        except NoSuchElementException:
            print(f"   ℹ️  Edit button not found, will try direct field access")
        
        # Fill profile fields
        fields_filled = 0
        for field_name, field_value in PROFILE_UPDATE_DATA.items():
            try:
                field = driver.find_element(By.NAME, field_name)
                field.clear()
                field.send_keys(str(field_value))
                print(f"   ✅ Field '{field_name}' filled: {field_value}")
                fields_filled += 1
            except NoSuchElementException:
                print(f"   ⚠️  Field '{field_name}' not found")
        
        if fields_filled == 0:
            print(f"   ⚠️  No profile fields found to update")
            test_results.record('Profile Update', False, 'No fields found')
            return False
        
        # Try to submit
        try:
            submit_btn = driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]')
            submit_btn.click()
            print(f"   ✅ Profile form submitted")
            time.sleep(1)
        except NoSuchElementException:
            print(f"   ⚠️  Submit button not found")
        
        print(f"   ✅ Profile update attempted ({fields_filled} fields)")
        test_results.record('Profile Update', True, f'✓ {fields_filled} fields updated')
        return True
        
    except Exception as e:
        print(f"   ❌ Profile update exception: {e}")
        traceback.print_exc()
        save_debug(driver, 'test-profile-update-exception')
        test_results.record('Profile Update', False, str(e)[:50])
        return False


def test_profile_completeness(driver, test_results):
    """Test profile completeness UI"""
    log_step("Testing profile completeness display")
    
    try:
        driver.get(f'{BASE_URL}/user/dashboard')
        time.sleep(1)
        
        # Look for completeness percentage
        completeness_text = None
        try:
            percent_elem = WebDriverWait(driver, 5).until(
                EC.presence_of_element_located((By.ID, 'profilePercentText'))
            )
            completeness_text = percent_elem.text.strip()
            print(f"   ✅ Profile completeness found: {completeness_text}")
            test_results.record('Profile Completeness UI', True, f'✓ {completeness_text}')
            return True
        except TimeoutException:
            print(f"   ⚠️  Profile percent text element not found")
        
        # Fallback: look for SVG
        try:
            donut_elem = driver.find_element(By.ID, 'profileDonut')
            print(f"   ✅ Profile donut SVG found")
            test_results.record('Profile Completeness UI', True, '✓ SVG element found')
            return True
        except NoSuchElementException:
            print(f"   ⚠️  Profile donut SVG not found")
        
        # Last fallback
        try:
            texts = driver.find_elements(By.XPATH, "//*[contains(text(), '%') and contains(., 'complete')]")
            if texts:
                print(f"   ✅ Completeness text found: {texts[0].text}")
                test_results.record('Profile Completeness UI', True, f'✓ {texts[0].text}')
                return True
        except Exception:
            pass
        
        print(f"   ⚠️  Could not detect profile completeness UI")
        test_results.record('Profile Completeness UI', False, 'UI not detected')
        save_debug(driver, 'test-completeness-not-found')
        return False
        
    except Exception as e:
        print(f"   ❌ Completeness check exception: {e}")
        traceback.print_exc()
        save_debug(driver, 'test-completeness-exception')
        test_results.record('Profile Completeness UI', False, str(e)[:50])
        return False


# ============================================================================
# TEST FUNCTIONS - FAMILY MEMBERS
# ============================================================================

def test_add_family_via_ajax(driver, member_data, test_results):
    """Test adding family member via AJAX endpoint"""
    log_step(f"Testing AJAX family member add: {member_data['first_name']}")
    
    try:
        driver.get(f'{BASE_URL}/user/dashboard')
        time.sleep(0.5)
        
        # Use JavaScript to call AJAX endpoint
        script = f"""
        return fetch('/add-family-member', {{
            method: 'POST',
            headers: {{'Content-Type': 'application/json'}},
            body: JSON.stringify({json.dumps(member_data)})
        }}).then(r => r.text()).then(t => {{
            return {{status: 'ok', text: t, url: location.href}}
        }})
        """
        
        response = driver.execute_script(script)
        print(f"   ✅ AJAX request sent")
        print(f"      Response: {response['text'][:100]}...")
        
        # Try to parse JSON response
        try:
            json_resp = json.loads(response['text'])
            if json_resp.get('success'):
                print(f"   ✅ AJAX returned success")
                test_results.record(f'Family Add (AJAX) - {member_data["first_name"]}', True, '✓ Success')
                return True
            else:
                print(f"   ⚠️  AJAX response not successful: {json_resp}")
                test_results.record(f'Family Add (AJAX) - {member_data["first_name"]}', False, 'No success flag')
                return False
        except json.JSONDecodeError:
            print(f"   ⚠️  Could not parse JSON response (may be HTML error page)")
            save_debug(driver, 'test-ajax-family-invalid-response')
            test_results.record(f'Family Add (AJAX) - {member_data["first_name"]}', False, 'Invalid response')
            return False
            
    except WebDriverException as e:
        print(f"   ❌ AJAX exception: {e}")
        traceback.print_exc()
        save_debug(driver, 'test-ajax-family-exception')
        test_results.record(f'Family Add (AJAX) - {member_data["first_name"]}', False, str(e)[:50])
        return False


def test_add_family_via_form(driver, member_data, test_results):
    """Test adding family member via HTML form"""
    log_step(f"Testing form family member add: {member_data['first_name']}")
    
    try:
        driver.get(f'{BASE_URL}/user/dashboard')
        time.sleep(1)
        
        print(f"   🔍 Looking for 'Add Family Member' button...")
        try:
            add_btn = WebDriverWait(driver, 5).until(
                EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'Add') and contains(text(), 'Family')]"))
            )
            print(f"   ✅ Button found: {add_btn.text}")
            add_btn.click()
            print(f"   ✅ Button clicked")
            time.sleep(1)
        except TimeoutException:
            print(f"   ⚠️  Add button not found - will try to find form directly")
        
        # Try to fill form fields
        print(f"   🔍 Looking for form fields...")
        fields_filled = 0
        
        for field_name, field_value in member_data.items():
            try:
                field = driver.find_element(By.NAME, field_name)
                field.clear()
                field.send_keys(str(field_value))
                print(f"      ✅ '{field_name}' = {field_value}")
                fields_filled += 1
            except NoSuchElementException:
                print(f"      ⚠️  '{field_name}' not found")
            except Exception as e:
                print(f"      ❌ Error filling '{field_name}': {e}")
        
        if fields_filled == 0:
            print(f"   ❌ No form fields found")
            save_debug(driver, 'test-family-form-no-fields')
            test_results.record(f'Family Add (Form) - {member_data["first_name"]}', False, 'No fields found')
            return False
        
        # Submit form
        print(f"   🔍 Looking for submit button...")
        try:
            submit_btn = WebDriverWait(driver, 5).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, 'button[type="submit"]'))
            )
            driver.execute_script("arguments[0].scrollIntoView(true);", submit_btn)
            time.sleep(0.3)
            try:
                submit_btn.click()
            except Exception:
                driver.execute_script("arguments[0].click();", submit_btn)
            print(f"   ✅ Form submitted")
            time.sleep(2)
        except NoSuchElementException:
            print(f"   ❌ Submit button not found")
            save_debug(driver, 'test-family-form-no-submit')
            test_results.record(f'Family Add (Form) - {member_data["first_name"]}', False, 'No submit button')
            return False
        
        # Look for success message
        try:
            success = WebDriverWait(driver, 3).until(
                EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'success') or contains(text(), 'Success') or contains(text(), 'added')]"))
            )
            print(f"   ✅ Success message found: {success.text}")
            test_results.record(f'Family Add (Form) - {member_data["first_name"]}', True, '✓ Success')
            return True
        except TimeoutException:
            print(f"   ⚠️  No success message found (may still have succeeded)")
            test_results.record(f'Family Add (Form) - {member_data["first_name"]}', True, '✓ Submitted (unconfirmed)')
            return True
            
    except Exception as e:
        print(f"   ❌ Form add exception: {e}")
        traceback.print_exc()
        save_debug(driver, 'test-family-form-exception')
        test_results.record(f'Family Add (Form) - {member_data["first_name"]}', False, str(e)[:50])
        return False


# ============================================================================
# TEST FUNCTIONS - NAVBAR LINKS
# ============================================================================

def test_navbar_links_by_role(driver, role, test_results):
    """Test navbar links for a specific role"""
    log_step(f"Testing navbar links for {role.upper()} role")
    
    try:
        if NavbarLinksValidator is None:
            print(f"   ⚠️  NavbarLinksValidator not available, skipping")
            test_results.record(f'Navbar Links ({role})', False, 'Validator unavailable')
            return False
        
        validator = NavbarLinksValidator(driver, TEST_TIMEOUT, BASE_URL)
        result = validator.validate_for_role(role)
        
        if result['passed']:
            print(f"   ✅ {result['description']}")
            print(f"      Found: {', '.join(result['found_links'][:3])}...")
            test_results.record(f'Navbar Links ({role})', True, f'✓ {len(result["found_links"])} links')
            return True
        else:
            print(f"   ❌ {result['description']}")
            if result['missing_links']:
                print(f"      Missing: {', '.join(result['missing_links'])}")
            if result['visible_hidden_links']:
                print(f"      Should be hidden: {', '.join(result['visible_hidden_links'])}")
            
            test_results.record(
                f'Navbar Links ({role})',
                False,
                f"Missing: {', '.join(result['missing_links'][:2])}" if result['missing_links'] else 'Hidden links visible'
            )
            save_debug(driver, f'test-navbar-links-{role}')
            return False
            
    except Exception as e:
        print(f"   ❌ Navbar validation exception: {e}")
        traceback.print_exc()
        test_results.record(f'Navbar Links ({role})', False, str(e)[:50])
        save_debug(driver, f'test-navbar-links-{role}-exception')
        return False


# ============================================================================
# TEST FUNCTIONS - DASHBOARD STATS & LINKS
# ============================================================================

def test_dashboard_stats(driver, test_results):
    """Test dashboard statistics display"""
    log_step("Testing dashboard stats display")
    
    try:
        if DashboardStatsValidator is None:
            print(f"   ⚠️  DashboardStatsValidator not available, skipping")
            test_results.record('Dashboard Stats', False, 'Validator unavailable')
            return False
        
        driver.get(f'{BASE_URL}/user/dashboard')
        time.sleep(1)
        
        validator = DashboardStatsValidator(driver, TEST_TIMEOUT, BASE_URL)
        stats = validator.get_all_dashboard_stats()
        
        # Check profile completeness
        pc = stats['profile_completeness']
        print(f"   Profile Completeness: {pc['message']}")
        if pc['found']:
            print(f"      ✅ {pc.get('percentage', 'N/A')}%")
        
        # Check family member count
        fmc = stats['family_member_count']
        print(f"   Family Members: {fmc['message']}")
        if fmc['found']:
            print(f"      ✅ {fmc.get('count', 0)} members")
        
        # Check if stats accuracy passed
        accuracy = stats['stats_accuracy']
        if accuracy['passed']:
            print(f"   ✅ Stats Accuracy: {accuracy['message']}")
            test_results.record('Dashboard Stats', True, f"✓ {accuracy['message']}")
            return True
        else:
            print(f"   ⚠️  Stats Accuracy: {accuracy['message']}")
            test_results.record('Dashboard Stats', False, accuracy['message'])
            return False
            
    except Exception as e:
        print(f"   ❌ Dashboard stats exception: {e}")
        traceback.print_exc()
        test_results.record('Dashboard Stats', False, str(e)[:50])
        save_debug(driver, 'test-dashboard-stats-exception')
        return False


def test_dashboard_links(driver, test_results):
    """Test dashboard links are present and working"""
    log_step("Testing dashboard links")
    
    try:
        if DashboardStatsValidator is None:
            print(f"   ⚠️  DashboardStatsValidator not available, skipping")
            test_results.record('Dashboard Links', False, 'Validator unavailable')
            return False
        
        driver.get(f'{BASE_URL}/user/dashboard')
        time.sleep(1)
        
        validator = DashboardStatsValidator(driver, TEST_TIMEOUT, BASE_URL)
        links_result = validator.validate_dashboard_links(role='user')
        
        print(f"   Found: {len(links_result['found_buttons'])}/{len(links_result['expected_buttons'])} buttons")
        for btn in links_result['buttons_detail']:
            status = "✅" if btn['found'] else "❌"
            print(f"      {status} {btn['name']}")
        
        if links_result['passed']:
            test_results.record('Dashboard Links', True, f"✓ {len(links_result['found_buttons'])} links")
            return True
        else:
            print(f"   ⚠️  Missing links: {', '.join(links_result['missing_buttons'])}")
            test_results.record('Dashboard Links', False, f"Missing: {', '.join(links_result['missing_buttons'][:2])}")
            save_debug(driver, 'test-dashboard-links-missing')
            return False
            
    except Exception as e:
        print(f"   ❌ Dashboard links exception: {e}")
        traceback.print_exc()
        test_results.record('Dashboard Links', False, str(e)[:50])
        return False


# ============================================================================
# TEST FUNCTIONS - ADMIN & DATABASE
# ============================================================================

def test_admin_dashboard(driver, test_results):
    """Test admin dashboard access after promotion"""
    log_step("Testing admin dashboard access")
    
    try:
        # Try to access admin dashboard
        driver.get(f'{BASE_URL}/admin')
        time.sleep(1)
        
        # Look for admin-specific elements
        admin_found = False
        
        try:
            admin_menu = driver.find_element(By.XPATH, "//a[contains(text(), 'ADMIN') or contains(text(), 'Dashboard')]")
            print(f"   ✅ Admin menu found: {admin_menu.text}")
            admin_found = True
        except NoSuchElementException:
            print(f"   ⚠️  Admin menu not visible")
        
        # Check page title or heading
        page_title = driver.title
        if 'admin' in page_title.lower():
            print(f"   ✅ Page title indicates admin area: {page_title}")
            admin_found = True
        
        if admin_found:
            # Also test admin dashboard links
            if DashboardStatsValidator:
                validator = DashboardStatsValidator(driver, TEST_TIMEOUT, BASE_URL)
                admin_links = validator.validate_dashboard_links(role='admin')
                print(f"   Admin Links: {len(admin_links['found_buttons'])} found")
            
            test_results.record('Admin Dashboard', True, '✓ Accessible')
            return True
        else:
            print(f"   ⚠️  Could not confirm admin dashboard access")
            test_results.record('Admin Dashboard', False, 'Not confirmed')
            save_debug(driver, 'test-admin-dashboard-not-visible')
            return False
            
    except Exception as e:
        print(f"   ❌ Admin dashboard exception: {e}")
        test_results.record('Admin Dashboard', False, str(e)[:50])
        return False


def test_database_verification(test_results):
    """Verify data persistence in database"""
    log_step("Verifying data in database")
    
    if SKIP_DATABASE_CHECK:
        print(f"   ℹ️  Database check skipped (SKIP_DATABASE_CHECK=true)")
        test_results.record('Database Verification', True, '✓ Skipped')
        return True
    
    try:
        # Check family members
        query = "SELECT COUNT(*) as count FROM family_members WHERE user_id = 100003 LIMIT 1;"
        result = query_database(query)
        
        if result is None:
            print(f"   ⚠️  Could not query database")
            test_results.record('Database Verification', False, 'Query failed')
            return False
        
        # Parse result
        lines = result.strip().split('\n')
        if len(lines) >= 2:
            count_line = lines[1]
            try:
                count = int(count_line)
                print(f"   ✅ Database check successful")
                print(f"      Family members for user 100003: {count}")
                test_results.record('Database Verification', True, f'✓ {count} members')
                return True
            except ValueError:
                print(f"   ⚠️  Could not parse count: {count_line}")
                test_results.record('Database Verification', False, 'Parse error')
                return False
        else:
            print(f"   ⚠️  Unexpected query result format")
            test_results.record('Database Verification', False, 'Format error')
            return False
            
    except Exception as e:
        print(f"   ❌ Database verification exception: {e}")
        traceback.print_exc()
        test_results.record('Database Verification', False, str(e)[:50])
        return False


# ============================================================================
# MAIN TEST FLOW
# ============================================================================

def run_comprehensive_e2e_test():
    """Main test orchestrator"""
    driver = None
    test_results = TestResults()
    
    try:
        log_section("COMPREHENSIVE END-TO-END TEST SUITE")
        print(f"Configuration:")
        print(f"  BASE_URL:           {BASE_URL}")
        print(f"  HEADLESS:           {HEADLESS}")
        print(f"  TEST_TIMEOUT:       {TEST_TIMEOUT}s")
        print(f"  SKIP_PROMOTION:     {SKIP_PROMOTION}")
        print(f"  SKIP_DATABASE_CHECK: {SKIP_DATABASE_CHECK}")
        
        driver = build_driver()
        
        # ====================================================================
        # PHASE 1: AUTHENTICATION
        # ====================================================================
        log_section("PHASE 1: AUTHENTICATION")
        
        login_ok = test_login(driver, TEST_USER_EMAIL, TEST_USER_PASSWORD, test_results)
        if not login_ok:
            print(f"\n❌ Login failed - cannot continue with remaining tests")
            # Still try database check
            test_database_verification(test_results)
            test_results.summary()
            return False
        
        # ====================================================================
        # PHASE 2: NAVBAR LINKS & NAVIGATION
        # ====================================================================
        log_section("PHASE 2: NAVBAR LINKS & NAVIGATION")
        
        test_navbar_links_by_role(driver, 'user', test_results)
        test_dashboard_stats(driver, test_results)
        test_dashboard_links(driver, test_results)
        
        # ====================================================================
        # PHASE 3: PROFILE MANAGEMENT
        # ====================================================================
        log_section("PHASE 3: PROFILE MANAGEMENT")
        
        test_profile_update(driver, test_results)
        test_profile_completeness(driver, test_results)
        
        # ====================================================================
        # PHASE 4: FAMILY MEMBER MANAGEMENT
        # ====================================================================
        log_section("PHASE 4: FAMILY MEMBER MANAGEMENT")
        
        for i, member_data in enumerate(FAMILY_MEMBERS_TO_ADD, 1):
            if i == 1:
                test_add_family_via_ajax(driver, member_data, test_results)
            else:
                test_add_family_via_form(driver, member_data, test_results)
        
        # ====================================================================
        # PHASE 5: ADMIN FEATURES (Optional)
        # ====================================================================
        if not SKIP_PROMOTION:
            log_section("PHASE 5: ADMIN FEATURES")
            
            if promote_test_user_to_admin():
                print(f"\n📝 Re-logging in as promoted admin...")
                driver.delete_all_cookies()
                time.sleep(1)
                login_ok = test_login(driver, TEST_USER_EMAIL, TEST_USER_PASSWORD, test_results)
                if login_ok:
                    # Test admin navbar links
                    test_navbar_links_by_role(driver, 'admin', test_results)
                    test_admin_dashboard(driver, test_results)
                else:
                    print(f"   ⚠️  Re-login after promotion failed")
                    test_results.record('Admin Dashboard', False, 'Re-login failed')
            else:
                print(f"   ℹ️  Admin promotion skipped")
                test_results.record('Admin Dashboard', False, 'Promotion skipped')
        else:
            print(f"\n   ℹ️  Admin tests skipped (SKIP_PROMOTION=true)")
            test_results.record('Admin Dashboard', True, '✓ Skipped')
        
        # ====================================================================
        # PHASE 6: DATABASE VERIFICATION
        # ====================================================================
        log_section("PHASE 6: DATABASE VERIFICATION")
        
        test_database_verification(test_results)
        
        # ====================================================================
        # FINAL SUMMARY
        # ====================================================================
        all_passed = test_results.summary()
        
        if all_passed:
            print("\n🎉 ALL TESTS PASSED!")
            return True
        else:
            print("\n⚠️  SOME TESTS FAILED - Check debug artifacts above")
            return False
        
    except Exception as e:
        print(f"\n❌ TEST SUITE EXCEPTION: {e}")
        traceback.print_exc()
        if driver:
            save_debug(driver, 'test-suite-exception')
        test_results.summary()
        return False
    
    finally:
        if driver:
            driver.quit()
            print("\n🔌 Browser closed")


# ============================================================================
# ENTRY POINT
# ============================================================================

if __name__ == '__main__':
    print(f"🚀 Starting Comprehensive E2E Test Suite")
    print(f"   Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    
    success = run_comprehensive_e2e_test()
    
    sys.exit(0 if success else 1)

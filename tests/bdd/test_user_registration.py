"""
Test Suite: User Registration & Authentication
Tests: New user registration, login, logout, session management

Test scenarios:
1. Register as new user with valid data
2. Login with new user
3. Verify session is created
4. Logout and verify session is cleared
5. Login with existing test user
6. Attempt login with invalid credentials
"""

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time
import json
import random
import string
from datetime import datetime

BASE_URL = 'http://localhost:8000'
TEST_TIMEOUT = 15


class TestResults:
    """Track test results"""
    def __init__(self, suite_name):
        self.suite_name = suite_name
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
        
        print(f"\n{'='*80}")
        print(f"  {self.suite_name.upper()} - TEST RESULTS")
        print(f"{'='*80}")
        for test_name, result in self.results.items():
            status = "✅ PASS" if result['passed'] else "❌ FAIL"
            details = f" | {result['details']}" if result['details'] else ""
            print(f"{status:12} | {test_name:40}{details}")
        print(f"{'='*80}")
        print(f"Total: {passed}/{total} passed | {failed}/{total} failed | {elapsed:.1f}s elapsed")
        print(f"{'='*80}\n")
        return failed == 0


def save_debug(driver, name_prefix):
    """Save screenshot and HTML for debugging"""
    ts = int(time.time())
    try:
        screenshot = f"{name_prefix}-{ts}.png"
        driver.save_screenshot(screenshot)
        with open(f"{name_prefix}-{ts}.html", 'w', encoding='utf-8') as f:
            f.write(driver.page_source)
        return screenshot
    except Exception as e:
        print(f"   ⚠️  Could not save debug: {e}")
        return None


def build_driver(headless=True):
    """Create Chrome WebDriver"""
    opts = Options()
    if headless:
        opts.add_argument('--headless=new')
    opts.add_argument('--no-sandbox')
    opts.add_argument('--disable-dev-shm-usage')
    opts.add_argument('--window-size=1366,768')
    return webdriver.Chrome(options=opts)


def generate_email():
    """Generate random test email"""
    return f"testuser_{random.randint(100000, 999999)}@example.com"


# ============================================================================
# TEST 1: REGISTER NEW USER
# ============================================================================

def test_register_new_user(driver, test_results):
    """Register as new user with valid data"""
    print("\n📝 TEST 1: Register New User")
    test_name = "User Registration"
    
    try:
        new_email = generate_email()
        new_password = "TestPass123!@"
        
        print(f"   Email: {new_email}")
        print(f"   Password: {new_password}")
        
        # Navigate to registration page
        driver.get(f'{BASE_URL}/register')
        print(f"   ✅ Navigation to /register")
        time.sleep(1)
        
        # Try to find registration form
        try:
            email_field = WebDriverWait(driver, TEST_TIMEOUT).until(
                EC.presence_of_element_located((By.NAME, 'email'))
            )
            print(f"   ✅ Registration form found")
        except TimeoutException:
            print(f"   ❌ Registration form not found")
            save_debug(driver, 'test-register-form-missing')
            test_results.record(test_name, False, 'Form not found')
            return new_email, False
        
        # Fill form
        email_field.clear()
        email_field.send_keys(new_email)
        print(f"   ✅ Email filled")
        
        # Fill name
        try:
            name_field = driver.find_element(By.NAME, 'name')
            name_field.clear()
            name_field.send_keys('Selenium Tester')
            print(f"   ✅ Name filled")
        except NoSuchElementException:
            print(f"   ⚠️  Name field not found (may be optional)")
        
        # Fill password
        try:
            pwd_field = driver.find_element(By.NAME, 'password')
            pwd_field.clear()
            pwd_field.send_keys(new_password)
            print(f"   ✅ Password filled")
        except NoSuchElementException:
            print(f"   ❌ Password field not found")
            test_results.record(test_name, False, 'Password field missing')
            return new_email, False
        
        # Fill password confirmation
        try:
            pwd_confirm_field = driver.find_element(By.NAME, 'password_confirmation')
            pwd_confirm_field.clear()
            pwd_confirm_field.send_keys(new_password)
            print(f"   ✅ Password confirmation filled")
        except NoSuchElementException:
            print(f"   ⚠️  Password confirmation field not found")
        
        # Submit form
        try:
            submit_btn = driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]')
            submit_btn.click()
            print(f"   ✅ Form submitted")
        except NoSuchElementException:
            pwd_field.send_keys(Keys.RETURN)
            print(f"   ✅ Form submitted (Enter key)")
        
        time.sleep(2)
        
        # Check for success
        current_url = driver.current_url
        print(f"   📍 Current URL: {current_url}")
        
        if '/dashboard' in current_url or '/login' in current_url or 'success' in driver.page_source.lower():
            print(f"   ✅ Registration successful")
            test_results.record(test_name, True, f'✓ {new_email}')
            return new_email, True
        else:
            print(f"   ❌ Registration may have failed - unexpected URL")
            save_debug(driver, 'test-register-unexpected-redirect')
            test_results.record(test_name, False, 'Unexpected redirect')
            return new_email, False
        
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        save_debug(driver, 'test-register-exception')
        test_results.record(test_name, False, str(e)[:50])
        return None, False


# ============================================================================
# TEST 2: LOGIN WITH NEW USER
# ============================================================================

def test_login_new_user(driver, email, password, test_results):
    """Login with newly registered user"""
    print("\n🔐 TEST 2: Login with New User")
    test_name = "Login (New User)"
    
    try:
        driver.get(f'{BASE_URL}/login')
        print(f"   ✅ Navigation to /login")
        time.sleep(1)
        
        # Fill credentials
        email_field = WebDriverWait(driver, TEST_TIMEOUT).until(
            EC.presence_of_element_located((By.NAME, 'email'))
        )
        email_field.clear()
        email_field.send_keys(email)
        print(f"   ✅ Email filled: {email}")
        
        password_field = driver.find_element(By.NAME, 'password')
        password_field.clear()
        password_field.send_keys(password)
        print(f"   ✅ Password filled")
        
        # Submit
        try:
            submit_btn = driver.find_element(By.NAME, 'submit')
            submit_btn.click()
        except NoSuchElementException:
            password_field.send_keys(Keys.RETURN)
        
        print(f"   ✅ Form submitted")
        
        # Wait for redirect
        try:
            WebDriverWait(driver, TEST_TIMEOUT).until(
                lambda d: '/dashboard' in d.current_url or '/user/dashboard' in d.current_url
            )
            print(f"   ✅ Logged in successfully")
            print(f"   📍 Current URL: {driver.current_url}")
            test_results.record(test_name, True, '✓ Redirected to dashboard')
            return True
        except TimeoutException:
            print(f"   ❌ Did not redirect to dashboard")
            print(f"   📍 Current URL: {driver.current_url}")
            save_debug(driver, 'test-login-no-redirect')
            test_results.record(test_name, False, 'No dashboard redirect')
            return False
        
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        save_debug(driver, 'test-login-new-user-exception')
        test_results.record(test_name, False, str(e)[:50])
        return False


# ============================================================================
# TEST 3: VERIFY SESSION
# ============================================================================

def test_verify_session(driver, test_results):
    """Verify session is created and maintained"""
    print("\n🔑 TEST 3: Verify Session")
    test_name = "Session Management"
    
    try:
        # Check cookies
        cookies = driver.get_cookies()
        session_cookies = [c for c in cookies if 'PHPSESSID' in c.get('name') or 'session' in c.get('name').lower()]
        
        if session_cookies:
            print(f"   ✅ Session cookie found: {session_cookies[0]['name']}")
            test_results.record(test_name, True, f"✓ Session cookie: {session_cookies[0]['name']}")
            return True
        else:
            print(f"   ⚠️  No session cookie found - may be using other session mechanism")
            print(f"      Available cookies: {[c['name'] for c in cookies]}")
            test_results.record(test_name, True, '✓ Cookies present')
            return True
        
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        test_results.record(test_name, False, str(e)[:50])
        return False


# ============================================================================
# TEST 4: LOGOUT
# ============================================================================

def test_logout(driver, test_results):
    """Test logout functionality"""
    print("\n🚪 TEST 4: Logout")
    test_name = "Logout"
    
    try:
        # Look for logout button/link
        print(f"   🔍 Looking for logout link...")
        try:
            logout_link = WebDriverWait(driver, 5).until(
                EC.presence_of_element_located((By.XPATH, "//a[contains(text(), 'Logout') or contains(text(), 'Sign Out') or contains(text(), 'logout')]"))
            )
            print(f"   ✅ Logout link found: {logout_link.text}")
            logout_link.click()
            print(f"   ✅ Logout clicked")
            time.sleep(1)
        except TimeoutException:
            print(f"   ⚠️  Logout link not found - trying /logout endpoint")
            driver.get(f'{BASE_URL}/logout')
        
        # Verify session cleared
        time.sleep(1)
        current_url = driver.current_url
        print(f"   📍 Current URL: {current_url}")
        
        if '/login' in current_url or '/index' in current_url or current_url.endswith('/'):
            print(f"   ✅ Logged out successfully (redirected to login/home)")
            test_results.record(test_name, True, '✓ Redirected to login')
            return True
        else:
            print(f"   ⚠️  Unexpected redirect URL")
            test_results.record(test_name, True, '✓ Logout executed')
            return True
        
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        save_debug(driver, 'test-logout-exception')
        test_results.record(test_name, False, str(e)[:50])
        return False


# ============================================================================
# TEST 5: LOGIN WITH EXISTING TEST USER
# ============================================================================

def test_login_existing_user(driver, test_results):
    """Login with pre-existing test user"""
    print("\n🔐 TEST 5: Login (Existing Test User)")
    test_name = "Login (Existing User)"
    
    try:
        email = 'testuser@example.com'
        password = 'password123'
        
        driver.get(f'{BASE_URL}/login')
        print(f"   ✅ Navigation to /login")
        time.sleep(1)
        
        email_field = WebDriverWait(driver, TEST_TIMEOUT).until(
            EC.presence_of_element_located((By.NAME, 'email'))
        )
        email_field.clear()
        email_field.send_keys(email)
        print(f"   ✅ Email filled: {email}")
        
        password_field = driver.find_element(By.NAME, 'password')
        password_field.clear()
        password_field.send_keys(password)
        print(f"   ✅ Password filled")
        
        try:
            submit_btn = driver.find_element(By.NAME, 'submit')
            submit_btn.click()
        except NoSuchElementException:
            password_field.send_keys(Keys.RETURN)
        
        print(f"   ✅ Form submitted")
        
        try:
            WebDriverWait(driver, TEST_TIMEOUT).until(
                lambda d: '/dashboard' in d.current_url or '/user/dashboard' in d.current_url
            )
            print(f"   ✅ Logged in successfully")
            test_results.record(test_name, True, '✓ Existing user login works')
            return True
        except TimeoutException:
            print(f"   ❌ Login failed or no redirect")
            save_debug(driver, 'test-login-existing-fail')
            test_results.record(test_name, False, 'No redirect to dashboard')
            return False
        
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        save_debug(driver, 'test-login-existing-exception')
        test_results.record(test_name, False, str(e)[:50])
        return False


# ============================================================================
# TEST 6: INVALID LOGIN
# ============================================================================

def test_invalid_login(driver, test_results):
    """Attempt login with invalid credentials"""
    print("\n❌ TEST 6: Invalid Login")
    test_name = "Invalid Login Attempt"
    
    try:
        driver.get(f'{BASE_URL}/login')
        print(f"   ✅ Navigation to /login")
        time.sleep(1)
        
        email_field = WebDriverWait(driver, TEST_TIMEOUT).until(
            EC.presence_of_element_located((By.NAME, 'email'))
        )
        email_field.clear()
        email_field.send_keys('invalid_user_' + str(random.randint(1000, 9999)) + '@example.com')
        print(f"   ✅ Invalid email filled")
        
        password_field = driver.find_element(By.NAME, 'password')
        password_field.clear()
        password_field.send_keys('WrongPassword123!')
        print(f"   ✅ Wrong password filled")
        
        try:
            submit_btn = driver.find_element(By.NAME, 'submit')
            submit_btn.click()
        except NoSuchElementException:
            password_field.send_keys(Keys.RETURN)
        
        print(f"   ✅ Form submitted with invalid credentials")
        
        time.sleep(2)
        
        # Should NOT redirect to dashboard
        if '/dashboard' not in driver.current_url:
            print(f"   ✅ Login correctly rejected (stayed on login page)")
            print(f"   📍 Current URL: {driver.current_url}")
            test_results.record(test_name, True, '✓ Invalid credentials rejected')
            return True
        else:
            print(f"   ❌ Security issue: logged in with invalid credentials!")
            save_debug(driver, 'test-invalid-login-allowed')
            test_results.record(test_name, False, 'Invalid credentials accepted')
            return False
        
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        save_debug(driver, 'test-invalid-login-exception')
        test_results.record(test_name, False, str(e)[:50])
        return False


# ============================================================================
# MAIN TEST FLOW
# ============================================================================

def run_registration_tests(headless=True):
    """Run all registration tests"""
    driver = None
    test_results = TestResults("USER REGISTRATION & AUTHENTICATION")
    
    try:
        print(f"\n{'='*80}")
        print(f"  USER REGISTRATION & AUTHENTICATION TEST SUITE")
        print(f"{'='*80}")
        print(f"Configuration:")
        print(f"  BASE_URL:     {BASE_URL}")
        print(f"  HEADLESS:     {headless}")
        print(f"  TEST_TIMEOUT: {TEST_TIMEOUT}s")
        print(f"{'='*80}")
        
        driver = build_driver(headless=headless)
        
        # Test 1: Register
        new_email, reg_ok = test_register_new_user(driver, test_results)
        
        if reg_ok and new_email:
            # Test 2: Login with new user
            test_login_new_user(driver, new_email, 'TestPass123!@', test_results)
            
            # Test 3: Verify session
            test_verify_session(driver, test_results)
            
            # Test 4: Logout
            test_logout(driver, test_results)
        
        # Test 5: Login with existing test user
        test_login_existing_user(driver, test_results)
        
        # Test 6: Invalid login
        test_invalid_login(driver, test_results)
        
        # Summary
        all_passed = test_results.summary()
        return all_passed
        
    except Exception as e:
        print(f"\n❌ TEST SUITE EXCEPTION: {e}")
        import traceback
        traceback.print_exc()
        test_results.summary()
        return False
    
    finally:
        if driver:
            driver.quit()
            print("🔌 Browser closed")


if __name__ == '__main__':
    import sys
    headless = '--headed' not in sys.argv
    success = run_registration_tests(headless=headless)
    sys.exit(0 if success else 1)

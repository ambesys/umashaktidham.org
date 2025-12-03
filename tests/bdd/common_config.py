from selenium.common.exceptions import TimeoutException
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
import os
import json
import time
import sqlite3
import logging

# Placeholder constants and objects
SELENIUM_AVAILABLE = True
BCRYPT_AVAILABLE = False
SQLITE_AVAILABLE = True
PROJECT_ROOT = os.path.dirname(os.path.abspath(__file__))
USERS_FILE = os.path.join(PROJECT_ROOT, 'test_users.json')
BASE_URL = "http://localhost:8000"  # Update to your actual test server URL
TEST_TIMEOUT = 15
logger = logging.getLogger("bdd")

try:
    import bcrypt
    BCRYPT_AVAILABLE = True
except ImportError:
    pass

try:
    from selenium import webdriver
except ImportError:
    webdriver = None


def wait_for_clickable(driver, by, value, timeout=15):
    """Wait for an element to be clickable and return it."""
    try:
        return WebDriverWait(driver, timeout).until(
            EC.element_to_be_clickable((by, value))
        )
    except TimeoutException:
        return None


# ============================================================================
# SELENIUM DRIVER SETUP
# ============================================================================

def setup_webdriver():
    """Setup Chrome WebDriver with standard configuration"""
    if not SELENIUM_AVAILABLE:
        raise ImportError("Selenium is not available")
    
    try:
        options = webdriver.ChromeOptions()
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--window-size=1920,1080')
        options.add_argument('--headless=new')
        options.add_argument('--disable-gpu')
        options.add_argument('--disable-blink-features=AutomationControlled')
        options.add_experimental_option('excludeSwitches', ['enable-automation'])
        options.add_experimental_option('useAutomationExtension', False)
        driver = webdriver.Chrome(options=options)
        return driver
    except Exception as chrome_exc:
        print(f"ChromeDriver failed: {chrome_exc}. Trying Firefox/GeckoDriver...")
        from selenium.webdriver.firefox.options import Options as FirefoxOptions
        firefox_options = FirefoxOptions()
        firefox_options.add_argument('--headless')
        firefox_options.add_argument('--width=1920')
        firefox_options.add_argument('--height=1080')
        driver = webdriver.Firefox(options=firefox_options)
        return driver


def wait_for_element(driver, by, value, timeout=15):
    """Wait for an element to be present in the DOM and return it, or None on timeout."""
    try:
        return WebDriverWait(driver, timeout).until(EC.presence_of_element_located((by, value)))
    except TimeoutException:
        return None


# ============================================================================
# PASSWORD UTILITIES
# ============================================================================

def hash_password(password):
    """Hash password using bcrypt"""
    if not BCRYPT_AVAILABLE:
        raise ImportError("Bcrypt is not available")
    
    salt = bcrypt.gensalt(rounds=12)
    return bcrypt.hashpw(password.encode('utf-8'), salt).decode('utf-8')


def verify_password(password, hashed):
    """Verify password against hash"""
    if not BCRYPT_AVAILABLE:
        raise ImportError("Bcrypt is not available")
    
    return bcrypt.checkpw(password.encode('utf-8'), hashed.encode('utf-8'))


# ============================================================================
# DATABASE UTILITIES
# ============================================================================

def get_db_connection():
    """Get SQLite database connection"""
    if not SQLITE_AVAILABLE:
        raise ImportError("SQLite is not available")
    
    db_path = PROJECT_ROOT / 'umashaktidham.db'
    if not db_path.exists():
        raise FileNotFoundError(f"Database not found: {db_path}")
    
    conn = sqlite3.connect(str(db_path))
    conn.row_factory = sqlite3.Row
    return conn


# ============================================================================
# TEST DATA UTILITIES
# ============================================================================

def generate_test_user_email(prefix="testuser"):
    """Generate unique test user email with timestamp"""
    timestamp = int(time.time())
    return f"{prefix}{timestamp}@example.com"


def get_test_users():
    """Load test users from JSON file"""
    if USERS_FILE.exists():
        try:
            with open(USERS_FILE, 'r') as f:
                return json.load(f)
        except (json.JSONDecodeError, IOError):
            logger.warning("Failed to load test users file")
    
    return {"users": {}}


def save_test_users(users_data):
    """Save test users to JSON file"""
    try:
        with open(USERS_FILE, 'w') as f:
            json.dump(users_data, f, indent=2)
        logger.info(f"Test users saved to {USERS_FILE}")
    except IOError as e:
        logger.error(f"Failed to save test users: {e}")


# ============================================================================
# PRINTING UTILITIES
# ============================================================================

def print_header(title, width=88):
    """Print formatted header"""
    print("\n" + "=" * width)
    print(f"  {title}")
    print("=" * width)


def print_section(title, width=88):
    """Print formatted section"""
    print("\n" + "-" * width)
    print(f"  {title}")
    print("-" * width)


def print_test_result(test_id, test_name, status, duration, details=""):
    """Print formatted test result"""
    icon = "✅" if status.upper() == "PASS" else "❌"
    print(f"   {icon} {test_id}: {test_name} ({duration:.2f}s) - {status}")
    if details:
        print(f"      {details}")


# ============================================================================
# WAITS & RETRIES
# ============================================================================

# ============================================================================
# USER CREATION & LOGIN
# ============================================================================

def create_and_login_user(driver, email=None, password="Test@Password123", first_name="Test", last_name="User"):
    if email is None:
        email = generate_test_user_email()
    try:
        # Step 1: Register the user
        driver.get(f"{BASE_URL}/register")
        time.sleep(1)
        wait_reg = WebDriverWait(driver, TEST_TIMEOUT)
        email_field = wait_reg.until(EC.presence_of_element_located((By.NAME, "email")))
        email_field.clear()
        email_field.send_keys(email)
        password_field = driver.find_element(By.NAME, "password")
        password_field.clear()
        password_field.send_keys(password)
        confirm_password_field = driver.find_element(By.NAME, "confirm_password")
        confirm_password_field.clear()
        confirm_password_field.send_keys(password)
        first_name_field = driver.find_element(By.NAME, "first_name")
        first_name_field.clear()
        first_name_field.send_keys(first_name)
        last_name_field = driver.find_element(By.NAME, "last_name")
        last_name_field.clear()
        last_name_field.send_keys(last_name)
        terms_checkbox = driver.find_element(By.NAME, "terms")
        if not terms_checkbox.is_selected():
            driver.execute_script("arguments[0].click();", terms_checkbox)
        submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        driver.execute_script("arguments[0].scrollIntoView(true);", submit_btn)
        time.sleep(0.5)
        driver.execute_script("arguments[0].click();", submit_btn)
        time.sleep(2)
        # Check for registration errors
        error_elements = driver.find_elements(By.XPATH, "//*[contains(@class, 'error') or contains(@class, 'alert') or contains(@class, 'invalid')]")
        error_texts = [el.text for el in error_elements if el.text.strip()]
        if error_texts:
            logger.error(f"Registration error detected: {' | '.join(error_texts)}")
            screenshot_path = f"/tmp/registration-error-{int(time.time())}.png"
            driver.save_screenshot(screenshot_path)
            logger.info(f"Screenshot of registration error: {screenshot_path}")
            return {'success': False, 'error': 'Registration error: ' + ' | '.join(error_texts)}
        # Step 2: Login the user
        driver.get(f"{BASE_URL}/login")
        time.sleep(1)
        wait_login = WebDriverWait(driver, TEST_TIMEOUT)
        email_field = wait_login.until(EC.presence_of_element_located((By.NAME, "email")))
        email_field.clear()
        email_field.send_keys(email)
        password_field = driver.find_element(By.NAME, "password")
        password_field.clear()
        password_field.send_keys(password)
        submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        driver.execute_script("arguments[0].scrollIntoView(true);", submit_btn)
        time.sleep(0.5)
        driver.execute_script("arguments[0].click();", submit_btn)
        time.sleep(2)
        # Check for login errors
        error_elements = driver.find_elements(By.XPATH, "//*[contains(@class, 'error') or contains(@class, 'alert') or contains(@class, 'invalid')]")
        error_texts = [el.text for el in error_elements if el.text.strip()]
        if error_texts:
            logger.error(f"Login error detected: {' | '.join(error_texts)}")
            screenshot_path = f"/tmp/login-error-{int(time.time())}.png"
            driver.save_screenshot(screenshot_path)
            logger.info(f"Screenshot of login error: {screenshot_path}")
            return {'success': False, 'error': 'Login error: ' + ' | '.join(error_texts)}
        # Wait for dashboard
        time.sleep(2)
        wait_dashboard = WebDriverWait(driver, TEST_TIMEOUT)
        try:
            wait_dashboard.until(lambda d: "/dashboard" in d.current_url or "/user/dashboard" in d.current_url)
        except TimeoutException:
            driver.get(f"{BASE_URL}/user/dashboard")
            logger.info(f"Session cookies after dashboard navigation: {driver.get_cookies()}")
            try:
                dashboard_header = driver.find_element(By.XPATH, "//*[contains(text(), 'Welcome to Umashakti Dham')]")
                logger.info("Dashboard header detected. User appears to be logged in.")
            except Exception as e:
                logger.warning(f"Dashboard header not found. User may not be logged in: {e}")
            logger.info("Navigated to dashboard after login/session.")
            time.sleep(1)
        # After login, check for profile completion prompt
        time.sleep(2)
        body_text = driver.find_element(By.TAG_NAME, "body").text
        logger.info(f"Body text after login: {body_text[:200]}")
        logger.info(f"Current URL after login: {driver.current_url}")
        logger.info(f"Session cookies: {driver.get_cookies()}")
        if "Complete your profile" in body_text or "profile is" in body_text:
            logger.info("Detected profile completion prompt. Automating profile completion...")
            try:
                logger.info("Attempting to locate Edit Profile button...")
                edit_btn = driver.find_element(By.XPATH, "//button[contains(@data-action, 'edit-profile')]")
                logger.info("Edit Profile button found. Scrolling into view...")
                driver.execute_script("arguments[0].scrollIntoView(true);", edit_btn)
                time.sleep(1.5)
                logger.info("Clicking Edit Profile button...")
                edit_btn.click()
                logger.info("Clicked Edit Profile button.")
                time.sleep(2)
            except Exception as e:
                logger.warning(f"Edit Profile button not found or not clickable: {e}")
            logger.info("Waiting for profile form to appear...")
            wait_profile = WebDriverWait(driver, TEST_TIMEOUT + 5)
            form_found = False
            try:
                wait_profile.until(EC.presence_of_element_located((By.TAG_NAME, "form")))
                form_found = True
                logger.info("Profile form found!")
            except Exception as e:
                logger.warning(f"Profile form not found after Edit Profile click: {e}")
            form_fields = driver.find_elements(By.XPATH, "//form//*[@name]")
            field_names = [f.get_attribute("name") for f in form_fields]
            logger.info(f"Profile completion form fields detected: {field_names}")
            required_fields = {
                "first_name": first_name,
                "last_name": last_name,
                "email": email,
                "phone_e164": "1234567890",
                "occupation": "Tester",
                "mosal": "TestMosal",
                "village": "TestVillage",
                "business_info": "Test business info",
                "street_address": "123 Test St",
                "city": "TestCity",
                "state": "TestState",
                "zip_code": "12345",
                "country": "USA",
                "relationship": "self",
                "birth_year": "1990",
                "gender": "male"
            }
            filled_fields = []
            for name in field_names:
                value = required_fields.get(name, "TestValue")
                try:
                    field = driver.find_element(By.NAME, name)
                    field.clear()
                    field.send_keys(value)
                    filled_fields.append(name)
                except Exception as e:
                    logger.warning(f"Could not fill field {name}: {e}")
            logger.info(f"Filled profile fields: {filled_fields}")
            try:
                submit_btn = driver.find_element(By.CSS_SELECTOR, "form button[type='submit'], form button[type='button'][data-action*='save']")
                driver.execute_script("arguments[0].scrollIntoView(true);", submit_btn)
                time.sleep(0.5)
                submit_btn.click()
                time.sleep(2)
                screenshot_path = f"/tmp/profile-submit-{int(time.time())}.png"
                driver.save_screenshot(screenshot_path)
                logger.info(f"Screenshot after profile submit: {screenshot_path}")
                error_elements = driver.find_elements(By.XPATH, "//*[contains(@class, 'error') or contains(@class, 'alert') or contains(@class, 'invalid')]")
                error_texts = [el.text for el in error_elements if el.text.strip()]
                if error_texts:
                    logger.error(f"Profile completion error detected: {' | '.join(error_texts)}")
                    screenshot_path = f"/tmp/profile-error-{int(time.time())}.png"
                    driver.save_screenshot(screenshot_path)
                    logger.info(f"Screenshot of profile error: {screenshot_path}")
            except Exception as e:
                logger.error(f"Could not submit profile completion form: {e}")
            for _ in range(5):
                driver.get(f"{BASE_URL}/dashboard")
                time.sleep(2)
                logger.info(f"Waiting for dashboard after profile completion. Current URL: {driver.current_url}")
                logger.info(f"Session cookies: {driver.get_cookies()}")
                body_text = driver.find_element(By.TAG_NAME, "body").text
                if "/dashboard" in driver.current_url or "/user/dashboard" in driver.current_url:
                    if "Welcome to Umashakti Dham" in body_text or "Dashboard" in body_text:
                        logger.info("Dashboard loaded and user appears logged in after profile completion.")
                        break
        logger.info(f"Final URL before success check: {driver.current_url}")
        logger.info(f"Session cookies: {driver.get_cookies()}")
        for _ in range(3):
            driver.get(f"{BASE_URL}/dashboard")
            time.sleep(2)
            logger.info(f"Dashboard reload for session check. URL: {driver.current_url}")
            logger.info(f"Session cookies: {driver.get_cookies()}")
            body_text = driver.find_element(By.TAG_NAME, "body").text
            if ("Welcome to Umashakti Dham" in body_text or "Dashboard" in body_text) and ("Complete your profile" not in body_text):
                logger.info(f"User successfully created and logged in: {email}")
                return {
                    'email': email,
                    'password': password,
                    'first_name': first_name,
                    'last_name': last_name,
                    'success': True
                }
            time.sleep(1)
        for attempt in range(3):
            logger.info(f"Retrying login after profile completion. Attempt {attempt+1}")
            driver.get(f"{BASE_URL}/login")
            time.sleep(1)
            wait_retry = WebDriverWait(driver, TEST_TIMEOUT)
            email_field = wait_retry.until(EC.presence_of_element_located((By.NAME, "email")))
            email_field.clear()
            email_field.send_keys(email)
            password_field = driver.find_element(By.NAME, "password")
            password_field.clear()
            password_field.send_keys(password)
            submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            driver.execute_script("arguments[0].scrollIntoView(true);", submit_btn)
            time.sleep(0.5)
            driver.execute_script("arguments[0].click();", submit_btn)
            time.sleep(2)
            logger.info(f"URL after retry login: {driver.current_url}")
            logger.info(f"Session cookies: {driver.get_cookies()}")
            error_elements = driver.find_elements(By.XPATH, "//*[contains(@class, 'error') or contains(@class, 'alert') or contains(@class, 'invalid')]")
            error_texts = [el.text for el in error_elements if el.text.strip()]
            if error_texts:
                logger.error(f"Login error detected: {' | '.join(error_texts)}")
                screenshot_path = f"/tmp/login-error-{int(time.time())}.png"
                driver.save_screenshot(screenshot_path)
                logger.info(f"Screenshot of login error: {screenshot_path}")
                return {'success': False, 'error': 'Login error: ' + ' | '.join(error_texts)}
            if "/dashboard" in driver.current_url or "/user/dashboard" in driver.current_url:
                logger.info(f"User successfully created and logged in: {email}")
                return {
                    'email': email,
                    'password': password,
                    'first_name': first_name,
                    'last_name': last_name,
                    'success': True
                }
        logger.error(f"Failed to reach dashboard after registration/login: {driver.current_url}")
        logger.info(f"Session cookies: {driver.get_cookies()}")
        return {'success': False, 'error': 'Could not reach dashboard after registration/login'}
    except Exception as e:
        logger.error(f"Failed to create and login user: {e}")
        import traceback
        logger.error(traceback.format_exc())
        return {'success': False, 'error': str(e)}


class TestResultsManager:
    def __init__(self, results_file="results.json"):
        self.results_file = results_file
        self.results = {
            "test_suites": {},
            "summary": {},
            "metadata": {}
        }

    def add_suite(self, suite_name, metadata):
        if suite_name not in self.results["test_suites"]:
            self.results["test_suites"][suite_name] = {
                "metadata": metadata,
                "tests": []
            }

    def add_test_result(self, suite_name, test_id, test_name, status, duration, details=""):
        if suite_name not in self.results["test_suites"]:
            self.add_suite(suite_name, {})
        test_result = {
            "id": test_id,
            "name": test_name,
            "suite": suite_name,
            "status": status,
            "duration": duration,
            "timestamp": time.strftime("%Y-%m-%dT%H:%M:%S"),
            "details": details
        }
        self.results["test_suites"][suite_name]["tests"].append(test_result)
        self._update_suite_stats(suite_name)
        self._update_summary()
        self.save()
        return test_result

    def _update_suite_stats(self, suite_name):
        suite = self.results["test_suites"][suite_name]
        tests = suite["tests"]
        total = len(tests)
        passed = sum(1 for t in tests if t["status"].lower() == "pass")
        failed = total - passed
        duration = sum(t.get("duration", 0) for t in tests)
        pass_rate = (passed / total * 100) if total > 0 else 0
        suite["metadata"]["total"] = total
        suite["metadata"]["passed"] = passed
        suite["metadata"]["failed"] = failed
        suite["metadata"]["pass_rate"] = pass_rate
        suite["metadata"]["duration"] = duration
        suite["metadata"]["updated"] = time.strftime("%Y-%m-%dT%H:%M:%S")

    def _update_summary(self):
        suites = self.results["test_suites"]
        total_tests = sum(s["metadata"].get("total", 0) for s in suites.values())
        total_passed = sum(s["metadata"].get("passed", 0) for s in suites.values())
        total_failed = sum(s["metadata"].get("failed", 0) for s in suites.values())
        pass_rate = (total_passed / total_tests * 100) if total_tests > 0 else 0
        self.results["summary"]["total_suites"] = len(suites)
        self.results["summary"]["total_tests"] = total_tests
        self.results["summary"]["total_passed"] = total_passed
        self.results["summary"]["total_failed"] = total_failed
        self.results["summary"]["pass_rate"] = pass_rate
        self.results["metadata"]["updated"] = time.strftime("%Y-%m-%dT%H:%M:%S")

    def get_all_tests_flat(self):
        all_tests = []
        for suite_name, suite in self.results["test_suites"].items():
            for test in suite.get("tests", []):
                all_tests.append(test)
        return all_tests

    def save(self):
        try:
            with open(self.results_file, 'w') as f:
                json.dump(self.results, f, indent=2)
            logger.info(f"Results saved to {self.results_file}")
        except IOError as e:
            logger.error(f"Failed to save results: {e}")

    def to_dict(self):
        return self.results

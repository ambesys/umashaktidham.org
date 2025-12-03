"""
Test User Registration with Centralized Results Logger
Demonstrates integration of TestResultsLogger into test suite
"""

import unittest
import time
import sys
import os

# Add tests directory to path to import test_results_logger
sys.path.insert(0, os.path.dirname(__file__))

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from test_results_logger import TestResultsLogger


class TestUserRegistrationWithLogger(unittest.TestCase):
    """
    Test user registration with integrated results logging
    Shows how to use TestResultsLogger for centralized test tracking
    """
    
    BASE_URL = "http://localhost:8000"
    TEST_TIMEOUT = 15
    
    @classmethod
    def setUpClass(cls):
        """Initialize WebDriver and Test Results Logger"""
        # Initialize test results logger for this suite
        # Use correct path from project root
        results_dir = os.path.join(os.path.dirname(__file__), "results")
        cls.logger = TestResultsLogger("user_registration_logged", log_dir=results_dir)
        cls.registered_user = None  # Will be set during registration test
        
        # Set up Chrome options
        options = webdriver.ChromeOptions()
        # Uncomment to run headless:
        options.add_argument("--headless=new")
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--window-size=1920,1080")
        
        # Initialize WebDriver
        service = Service(ChromeDriverManager().install())
        cls.driver = webdriver.Chrome(service=service, options=options)
        cls.driver.implicitly_wait(10)
        
        print("\n" + "="*80)
        print("TEST SUITE: User Registration (with Results Logging)")
        print("="*80 + "\n")
    
    @classmethod
    def tearDownClass(cls):
        """Clean up and finalize test results"""
        cls.driver.quit()
        # Generate HTML dashboard after all tests complete
        cls.logger.finalize_session()
    
    def save_debug(self, name_prefix):
        """Save debug artifacts (screenshot and HTML) on failure"""
        timestamp = int(time.time())
        screenshot_file = f"{name_prefix}-{timestamp}.png"
        html_file = f"{name_prefix}-{timestamp}.html"
        
        try:
            self.driver.save_screenshot(screenshot_file)
            print(f"Screenshot saved: {screenshot_file}")
        except:
            pass
        
        try:
            with open(html_file, 'w') as f:
                f.write(self.driver.page_source)
            print(f"HTML saved: {html_file}")
        except:
            pass
    
    # ========================================================================
    # TEST 1: User Registration
    # ========================================================================
    def test_001_register_new_user(self):
        """REG-001: Test new user registration"""
        test_id = "REG-001"
        test_name = "User Registration"
        start_time = time.time()
        
        try:
            # Generate unique email with timestamp
            email = f"testuser_{int(time.time())}@example.com"
            password = "Test@Password123"
            first_name = "Test"
            last_name = "User"
            
            # Navigate to registration page directly
            self.driver.get(f"{self.BASE_URL}/register")
            
            # Wait for registration form to load
            WebDriverWait(self.driver, self.TEST_TIMEOUT).until(
                EC.presence_of_element_located((By.ID, "email"))
            )
            
            # Fill registration form (use ID and name attributes from actual form)
            self.driver.find_element(By.ID, "first_name").send_keys(first_name)
            self.driver.find_element(By.ID, "last_name").send_keys(last_name)
            self.driver.find_element(By.ID, "email").send_keys(email)
            self.driver.find_element(By.ID, "password").send_keys(password)
            self.driver.find_element(By.ID, "confirm_password").send_keys(password)
            
            # Accept terms
            terms_checkbox = self.driver.find_element(By.ID, "terms")
            if not terms_checkbox.is_selected():
                # Use JavaScript to click to avoid interception
                self.driver.execute_script("arguments[0].click();", terms_checkbox)
            
            # Submit form using JavaScript to avoid element click interception
            submit_button = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            self.driver.execute_script("arguments[0].scrollIntoView(true);", submit_button)
            time.sleep(0.5)
            self.driver.execute_script("arguments[0].click();", submit_button)
            
            # Wait for redirect to login page with success parameter
            WebDriverWait(self.driver, self.TEST_TIMEOUT).until(
                EC.url_contains("/login")
            )
            
            # Verify we're on login page
            current_url = self.driver.current_url
            assert "/login" in current_url, f"Did not redirect to login, got: {current_url}"
            
            # ✅ TEST PASSED - Record result
            duration = time.time() - start_time
            self.logger.record_test(
                test_id=test_id,
                test_name=test_name,
                passed=True,
                details=f"Successfully registered user: {email}",
                duration=duration
            )
            print(f"✅ PASS: {test_name} ({duration:.2f}s)")
            
            # Store for use in login tests (both as class and instance)
            TestUserRegistrationWithLogger.registered_user = {'email': email, 'password': password}
            self.registered_user = TestUserRegistrationWithLogger.registered_user
            
        except Exception as e:
            # ❌ TEST FAILED - Record result
            duration = time.time() - start_time
            self.logger.record_test(
                test_id=test_id,
                test_name=test_name,
                passed=False,
                details=str(e),
                duration=duration
            )
            self.save_debug(test_id)
            print(f"❌ FAIL: {test_name} - {str(e)}")
            raise
    
    # ========================================================================
    # TEST 2: Login with New User
    # ========================================================================
    def test_002_login_new_user(self):
        """REG-002: Test login with newly registered user"""
        test_id = "REG-002"
        test_name = "Login (New User)"
        start_time = time.time()
        
        try:
            # Check if we have a registered user from test 001
            if not hasattr(self, 'registered_user') or not self.registered_user:
                raise Exception("No registered user available. Test 001 must run first.")
            
            email = self.registered_user['email']
            password = self.registered_user['password']
            
            # Navigate to login page
            self.driver.get(f"{self.BASE_URL}/login")
            
            # Wait for login form
            WebDriverWait(self.driver, self.TEST_TIMEOUT).until(
                EC.presence_of_element_located((By.ID, "email"))
            )
            
            # Fill login form
            self.driver.find_element(By.ID, "email").send_keys(email)
            self.driver.find_element(By.ID, "password").send_keys(password)
            
            # Submit form
            submit_button = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            self.driver.execute_script("arguments[0].scrollIntoView(true);", submit_button)
            time.sleep(0.5)
            self.driver.execute_script("arguments[0].click();", submit_button)
            
            # Wait for redirect to dashboard
            WebDriverWait(self.driver, self.TEST_TIMEOUT).until(
                EC.url_contains("dashboard")
            )
            
            # ✅ TEST PASSED
            duration = time.time() - start_time
            self.logger.record_test(
                test_id=test_id,
                test_name=test_name,
                passed=True,
                details=f"Successfully logged in user: {email}",
                duration=duration
            )
            print(f"✅ PASS: {test_name} ({duration:.2f}s)")
            
        except Exception as e:
            # ❌ TEST FAILED
            duration = time.time() - start_time
            self.logger.record_test(
                test_id=test_id,
                test_name=test_name,
                passed=False,
                details=str(e),
                duration=duration
            )
            self.save_debug(test_id)
            print(f"❌ FAIL: {test_name} - {str(e)}")
            raise
    
    # ========================================================================
    # TEST 3: Session Management
    # ========================================================================
    def test_003_session_management(self):
        """REG-003: Test session management and authentication"""
        test_id = "REG-003"
        test_name = "Session Management"
        start_time = time.time()
        
        try:
            if not hasattr(self, 'registered_user') or not self.registered_user:
                raise Exception("No registered user available.")
            
            email = self.registered_user['email']
            password = self.registered_user['password']
            
            # Check if already logged in / on dashboard
            current_url = self.driver.current_url
            if "/dashboard" not in current_url:
                # Not on dashboard, need to login
                self.driver.get(f"{self.BASE_URL}/login")
                time.sleep(2)  # Wait for page load
                
                email_input = WebDriverWait(self.driver, self.TEST_TIMEOUT).until(
                    EC.presence_of_element_located((By.ID, "email"))
                )
                email_input.send_keys(email)
                self.driver.find_element(By.ID, "password").send_keys(password)
                
                submit_button = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
                self.driver.execute_script("arguments[0].scrollIntoView(true);", submit_button)
                time.sleep(0.5)
                self.driver.execute_script("arguments[0].click();", submit_button)
                
                # Wait for dashboard
                WebDriverWait(self.driver, self.TEST_TIMEOUT).until(
                    EC.url_contains("dashboard")
                )
            
            # Get cookies and verify session
            cookies = self.driver.get_cookies()
            session_found = any(c['name'] in ['PHPSESSID', 'SESSION', 'SESS'] for c in cookies)
            
            assert session_found or len(cookies) > 0, "No session cookie found"
            assert "/user/dashboard" in self.driver.current_url, "Not on dashboard after login"
            
            # ✅ TEST PASSED
            duration = time.time() - start_time
            self.logger.record_test(
                test_id=test_id,
                test_name=test_name,
                passed=True,
                details=f"Session established with {len(cookies)} cookies",
                duration=duration
            )
            print(f"✅ PASS: {test_name} ({duration:.2f}s)")
            
        except Exception as e:
            # ❌ TEST FAILED
            duration = time.time() - start_time
            self.logger.record_test(
                test_id=test_id,
                test_name=test_name,
                passed=False,
                details=str(e),
                duration=duration
            )
            self.save_debug(test_id)
            print(f"❌ FAIL: {test_name} - {str(e)}")
            raise
    
    # ========================================================================
    # TEST 4: Logout
    # ========================================================================
    def test_004_logout(self):
        """REG-004: Test logout functionality"""
        test_id = "REG-004"
        test_name = "Logout"
        start_time = time.time()
        
        try:
            if not hasattr(self, 'registered_user') or not self.registered_user:
                raise Exception("No registered user available.")
            
            email = self.registered_user['email']
            password = self.registered_user['password']
            
            # Must be logged in from previous tests or login again
            self.driver.get(f"{self.BASE_URL}/user/dashboard")
            
            # Check if we're logged in, if not, login
            current_url = self.driver.current_url
            if "/login" in current_url:
                # Not logged in, login first
                email_input = WebDriverWait(self.driver, self.TEST_TIMEOUT).until(
                    EC.presence_of_element_located((By.ID, "email"))
                )
                email_input.send_keys(email)
                self.driver.find_element(By.ID, "password").send_keys(password)
                submit_button = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
                self.driver.execute_script("arguments[0].scrollIntoView(true);", submit_button)
                time.sleep(0.5)
                self.driver.execute_script("arguments[0].click();", submit_button)
                
                WebDriverWait(self.driver, self.TEST_TIMEOUT).until(
                    EC.url_contains("dashboard")
                )
            
            # Now look for logout link - it could be in navbar or menu
            logout_links = self.driver.find_elements(By.PARTIAL_LINK_TEXT, "Logout")
            if logout_links:
                self.driver.execute_script("arguments[0].click();", logout_links[0])
            else:
                # Try to find in dropdown or menu
                try:
                    user_menu = WebDriverWait(self.driver, 5).until(
                        EC.element_to_be_clickable((By.CSS_SELECTOR, "[data-testid='user-menu'], .user-menu, #user-dropdown"))
                    )
                    self.driver.execute_script("arguments[0].click();", user_menu)
                    
                    logout_link = WebDriverWait(self.driver, 5).until(
                        EC.element_to_be_clickable((By.PARTIAL_LINK_TEXT, "Logout"))
                    )
                    self.driver.execute_script("arguments[0].click();", logout_link)
                except:
                    # If no logout button, try /logout endpoint
                    self.driver.get(f"{self.BASE_URL}/logout")
            
            # Wait for redirect to login/home page
            time.sleep(2)
            current_url = self.driver.current_url
            
            # Should not be on dashboard after logout
            assert "/user/dashboard" not in current_url and "/dashboard" not in current_url, \
                f"Still on dashboard after logout: {current_url}"
            
            # ✅ TEST PASSED
            duration = time.time() - start_time
            self.logger.record_test(
                test_id=test_id,
                test_name=test_name,
                passed=True,
                details=f"Successfully logged out, redirected to: {current_url}",
                duration=duration
            )
            print(f"✅ PASS: {test_name} ({duration:.2f}s)")
            
        except Exception as e:
            # ❌ TEST FAILED
            duration = time.time() - start_time
            self.logger.record_test(
                test_id=test_id,
                test_name=test_name,
                passed=False,
                details=str(e),
                duration=duration
            )
            self.save_debug(test_id)
            print(f"❌ FAIL: {test_name} - {str(e)}")
            raise
    
    # ========================================================================
    # TEST 5: Login with Existing User
    # ========================================================================
    def test_005_login_existing_user(self):
        """REG-005: Test login with existing test user (after logout)"""
        test_id = "REG-005"
        test_name = "Login (Existing User)"
        start_time = time.time()
        
        try:
            if not hasattr(self, 'registered_user') or not self.registered_user:
                raise Exception("No registered user available.")
            
            email = self.registered_user['email']
            password = self.registered_user['password']
            
            self.driver.get(f"{self.BASE_URL}/login")
            
            WebDriverWait(self.driver, self.TEST_TIMEOUT).until(
                EC.presence_of_element_located((By.ID, "email"))
            )
            
            self.driver.find_element(By.ID, "email").send_keys(email)
            self.driver.find_element(By.ID, "password").send_keys(password)
            
            submit_button = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            self.driver.execute_script("arguments[0].scrollIntoView(true);", submit_button)
            time.sleep(0.5)
            self.driver.execute_script("arguments[0].click();", submit_button)
            
            WebDriverWait(self.driver, self.TEST_TIMEOUT).until(
                EC.url_contains("dashboard")
            )
            
            # ✅ TEST PASSED
            duration = time.time() - start_time
            self.logger.record_test(
                test_id=test_id,
                test_name=test_name,
                passed=True,
                details=f"Successfully logged in existing user: {email}",
                duration=duration
            )
            print(f"✅ PASS: {test_name} ({duration:.2f}s)")
            
        except Exception as e:
            # ❌ TEST FAILED
            duration = time.time() - start_time
            self.logger.record_test(
                test_id=test_id,
                test_name=test_name,
                passed=False,
                details=str(e),
                duration=duration
            )
            self.save_debug(test_id)
            print(f"❌ FAIL: {test_name} - {str(e)}")
            raise
    
    # ========================================================================
    # TEST 6: Invalid Login
    # ========================================================================
    def test_006_invalid_login(self):
        """REG-006: Test invalid login rejection"""
        test_id = "REG-006"
        test_name = "Invalid Login Attempt"
        start_time = time.time()
        
        try:
            # First, ensure we're logged out by visiting logout
            self.driver.get(f"{self.BASE_URL}/logout")
            time.sleep(2)
            
            # Now navigate to login
            self.driver.get(f"{self.BASE_URL}/login")
            time.sleep(2)  # Wait for page load
            
            WebDriverWait(self.driver, self.TEST_TIMEOUT).until(
                EC.presence_of_element_located((By.ID, "email"))
            )
            
            # Try with wrong credentials
            self.driver.find_element(By.ID, "email").send_keys("nonexistent@example.com")
            self.driver.find_element(By.ID, "password").send_keys("wrongpassword")
            
            submit_button = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            self.driver.execute_script("arguments[0].scrollIntoView(true);", submit_button)
            time.sleep(0.5)
            self.driver.execute_script("arguments[0].click();", submit_button)
            
            # Wait a bit for error message or page to refresh
            time.sleep(2)
            current_url = self.driver.current_url
            page_source = self.driver.page_source.lower()
            
            # Either error message appears or we're still on login page
            error_found = "error" in page_source or "invalid" in page_source or "failed" in page_source
            still_on_login = "/login" in current_url
            
            assert error_found or still_on_login, \
                f"Invalid login was not properly rejected. URL: {current_url}, Has error: {error_found}"
            
            # ✅ TEST PASSED
            duration = time.time() - start_time
            self.logger.record_test(
                test_id=test_id,
                test_name=test_name,
                passed=True,
                details=f"Invalid login properly rejected (error_found: {error_found}, still_on_login: {still_on_login})",
                duration=duration
            )
            print(f"✅ PASS: {test_name} ({duration:.2f}s)")
            
        except Exception as e:
            # ❌ TEST FAILED
            duration = time.time() - start_time
            self.logger.record_test(
                test_id=test_id,
                test_name=test_name,
                passed=False,
                details=str(e),
                duration=duration
            )
            self.save_debug(test_id)
            print(f"❌ FAIL: {test_name} - {str(e)}")
            raise


if __name__ == "__main__":
    # Run tests with verbose output
    unittest.main(verbosity=2)

"""
User Registration Test Suite - Refactored with Common Config
Uses centralized configuration and test results management
"""

import unittest
import time
import sys
import os
from pathlib import Path

sys.path.insert(0, os.path.dirname(__file__))

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from common_config import (
    BASE_URL,
    TEST_TIMEOUT,
    setup_webdriver,
    TestResultsManager,
    logger,
    verify_password,
)


class TestUserRegistration(unittest.TestCase):
    """User registration and login test suite using common configuration"""
    
    SUITE_NAME = "user_registration"
    RESULTS_DIR = os.path.join(os.path.dirname(__file__), "results")
    
    @classmethod
    def setUpClass(cls):
        """Initialize WebDriver and results manager"""
        cls.driver = setup_webdriver()
        cls.results = TestResultsManager(Path(cls.RESULTS_DIR))
        cls.results.add_suite(cls.SUITE_NAME, {})
        cls.registered_user = None
        
        print("\n" + "="*80)
        print(f"TEST SUITE: {cls.SUITE_NAME.upper()}")
        print("="*80 + "\n")
    
    @classmethod
    def tearDownClass(cls):
        """Clean up and finalize"""
        try:
            cls.driver.quit()
        except:
            pass
        cls.results.save()
        print(f"\nResults saved to: {cls.RESULTS_DIR}/test_results.json\n")
    
    def save_debug(self, name_prefix):
        """Save debug artifacts on failure"""
        timestamp = int(time.time())
        try:
            screenshot = f"/tmp/{name_prefix}-{timestamp}.png"
            self.driver.save_screenshot(screenshot)
            logger.info(f"Screenshot: {screenshot}")
        except Exception as e:
            logger.error(f"Screenshot failed: {e}")
    
    def test_001_register_new_user(self):
        """REG-001: Test new user registration"""
        test_id = "REG-001"
        test_name = "User Registration"
        start_time = time.time()
        
        try:
            email = f"testuser_{int(time.time())}@example.com"
            password = "Test@Password123"
            first_name = "Test"
            last_name = "User"
            
            self.driver.get(f"{BASE_URL}/register")
            
            # Wait for form and fill it
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            email_field = wait.until(EC.presence_of_element_located((By.NAME, "email")))
            email_field.send_keys(email)
            
            self.driver.find_element(By.NAME, "password").send_keys(password)
            self.driver.find_element(By.NAME, "confirm_password").send_keys(password)
            self.driver.find_element(By.NAME, "first_name").send_keys(first_name)
            self.driver.find_element(By.NAME, "last_name").send_keys(last_name)
            
            # Submit form
            self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
            
            # Wait for success
            time.sleep(2)
            
            # Store for later tests
            self.__class__.registered_user = {
                'email': email,
                'password': password,
                'first_name': first_name,
                'last_name': last_name
            }
            
            duration = time.time() - start_time
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "PASS",
                duration,
                f"User {email} registered successfully"
            )
            print(f"✅ {test_id}: {test_name} - PASS ({duration:.2f}s)")
            
        except Exception as e:
            duration = time.time() - start_time
            error_msg = f"Registration failed: {str(e)[:200]}"
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "FAIL",
                duration,
                error_msg
            )
            self.save_debug("registration-error")
            print(f"❌ {test_id}: {test_name} - FAIL ({duration:.2f}s)")
            self.fail(error_msg)
    
    def test_002_login_new_user(self):
        """REG-002: Test login with newly registered user"""
        test_id = "REG-002"
        test_name = "Login (New User)"
        start_time = time.time()
        
        try:
            if not self.registered_user:
                raise Exception("No registered user from test_001")
            
            self.driver.get(f"{BASE_URL}/login")
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            email_field = wait.until(EC.presence_of_element_located((By.NAME, "email")))
            email_field.send_keys(self.registered_user['email'])
            
            self.driver.find_element(By.NAME, "password").send_keys(self.registered_user['password'])
            self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
            
            # Wait for redirect to dashboard
            wait.until(lambda d: "dashboard" in d.current_url or "user" in d.current_url)
            
            duration = time.time() - start_time
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "PASS",
                duration,
                f"Redirected to {self.driver.current_url}"
            )
            print(f"✅ {test_id}: {test_name} - PASS ({duration:.2f}s)")
            
        except Exception as e:
            duration = time.time() - start_time
            error_msg = f"Login failed: {str(e)[:200]}"
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "FAIL",
                duration,
                error_msg
            )
            self.save_debug("login-error")
            print(f"❌ {test_id}: {test_name} - FAIL ({duration:.2f}s)")
            self.fail(error_msg)
    
    def test_003_session_management(self):
        """REG-003: Test session management"""
        test_id = "REG-003"
        test_name = "Session Management"
        start_time = time.time()
        
        try:
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Check session exists
            cookies = self.driver.get_cookies()
            session_exists = any(c['name'] in ['PHPSESSID', 'session'] for c in cookies)
            
            if not session_exists:
                raise Exception("Session cookie not found")
            
            duration = time.time() - start_time
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "PASS",
                duration,
                f"Session found with {len(cookies)} cookies"
            )
            print(f"✅ {test_id}: {test_name} - PASS ({duration:.2f}s)")
            
        except Exception as e:
            duration = time.time() - start_time
            error_msg = f"Session check failed: {str(e)[:200]}"
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "FAIL",
                duration,
                error_msg
            )
            print(f"❌ {test_id}: {test_name} - FAIL ({duration:.2f}s)")
            self.fail(error_msg)
    
    def test_004_logout(self):
        """REG-004: Test logout functionality"""
        test_id = "REG-004"
        test_name = "Logout"
        start_time = time.time()
        
        try:
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Look for logout button
            logout_btn = wait.until(
                EC.element_to_be_clickable((By.LINK_TEXT, "Logout"))
            )
            logout_btn.click()
            
            # Should redirect to home or login
            time.sleep(1)
            
            duration = time.time() - start_time
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "PASS",
                duration,
                f"Logged out, redirected to {self.driver.current_url}"
            )
            print(f"✅ {test_id}: {test_name} - PASS ({duration:.2f}s)")
            
        except Exception as e:
            duration = time.time() - start_time
            error_msg = f"Logout failed: {str(e)[:200]}"
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "FAIL",
                duration,
                error_msg
            )
            self.save_debug("logout-error")
            print(f"❌ {test_id}: {test_name} - FAIL ({duration:.2f}s)")
            self.fail(error_msg)
    
    def test_005_login_existing_user(self):
        """REG-005: Test login with existing user"""
        test_id = "REG-005"
        test_name = "Login (Existing User)"
        start_time = time.time()
        
        try:
            if not self.registered_user:
                raise Exception("No registered user from test_001")
            
            self.driver.get(f"{BASE_URL}/login")
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            email_field = wait.until(EC.presence_of_element_located((By.NAME, "email")))
            email_field.send_keys(self.registered_user['email'])
            
            self.driver.find_element(By.NAME, "password").send_keys(self.registered_user['password'])
            self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
            
            # Wait for redirect
            wait.until(lambda d: "dashboard" in d.current_url or "user" in d.current_url)
            
            duration = time.time() - start_time
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "PASS",
                duration,
                f"Existing user logged in, redirected to {self.driver.current_url}"
            )
            print(f"✅ {test_id}: {test_name} - PASS ({duration:.2f}s)")
            
        except Exception as e:
            duration = time.time() - start_time
            error_msg = f"Existing user login failed: {str(e)[:200]}"
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "FAIL",
                duration,
                error_msg
            )
            self.save_debug("existing-login-error")
            print(f"❌ {test_id}: {test_name} - FAIL ({duration:.2f}s)")
            self.fail(error_msg)
    
    def test_006_invalid_login(self):
        """REG-006: Test invalid login attempt"""
        test_id = "REG-006"
        test_name = "Invalid Login Attempt"
        start_time = time.time()
        
        try:
            self.driver.get(f"{BASE_URL}/login")
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            email_field = wait.until(EC.presence_of_element_located((By.NAME, "email")))
            email_field.send_keys("nonexistent@example.com")
            
            self.driver.find_element(By.NAME, "password").send_keys("WrongPassword123")
            self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
            
            # Should show error message
            time.sleep(1)
            current_url = self.driver.current_url
            
            # Check if still on login page (indicating failed login)
            if "login" not in current_url:
                raise Exception("Should remain on login page after invalid attempt")
            
            duration = time.time() - start_time
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "PASS",
                duration,
                "Invalid login correctly rejected"
            )
            print(f"✅ {test_id}: {test_name} - PASS ({duration:.2f}s)")
            
        except Exception as e:
            duration = time.time() - start_time
            error_msg = f"Invalid login test failed: {str(e)[:200]}"
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "FAIL",
                duration,
                error_msg
            )
            self.save_debug("invalid-login-error")
            print(f"❌ {test_id}: {test_name} - FAIL ({duration:.2f}s)")
            self.fail(error_msg)


if __name__ == "__main__":
    # Run tests with unittest
    loader = unittest.TestLoader()
    suite = loader.loadTestsFromTestCase(TestUserRegistration)
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)
    
    # Print summary
    print("\n" + "="*80)
    print("TEST SUMMARY")
    print("="*80)
    print(f"Tests Run: {result.testsRun}")
    print(f"Tests Passed: {result.testsRun - len(result.failures) - len(result.errors)}")
    print(f"Tests Failed: {len(result.failures) + len(result.errors)}")
    print("="*80 + "\n")
    
    # Generate dashboard
    try:
        from generate_dashboard import generate_results_dashboard
        generate_results_dashboard()
    except Exception as e:
        print(f"Dashboard generation skipped: {e}")

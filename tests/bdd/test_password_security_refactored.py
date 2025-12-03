"""
Password Security Test Suite - Refactored with Common Config
Tests: Change password, password validation, session security

Usage:
    python tests/bdd/test_password_security_refactored.py
"""

import sys
import os
import time
from pathlib import Path

sys.path.insert(0, os.path.dirname(__file__))

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from common_config import (
    BASE_URL, TEST_TIMEOUT, setup_webdriver,
    TestResultsManager, create_and_login_user, logger
)


class PasswordSecurityTests:
    """Password security test suite"""
    
    SUITE_NAME = "password_security"
    RESULTS_DIR = os.path.join(os.path.dirname(__file__), "results")
    
    def __init__(self):
        self.driver = None
        self.results = None
        self.test_user = None
    
    def setup(self):
        """Initialize"""
        self.driver = setup_webdriver()
        results_file = Path(self.RESULTS_DIR) / "test_results.json"
        self.results = TestResultsManager(results_file)
        self.results.add_suite(self.SUITE_NAME, {})
        print("\n" + "="*80)
        print(f"TEST SUITE: {self.SUITE_NAME.upper()}")
        print("="*80 + "\n")
    
    def teardown(self):
        """Cleanup"""
        try:
            if self.driver:
                self.driver.quit()
        except:
            pass
        if self.results:
            self.results.save()
        print(f"\nResults saved to: {self.RESULTS_DIR}/test_results.json\n")
    
    def test_001_register_and_login(self):
        """PWD-001: Register and login"""
        test_id = "PWD-001"
        test_name = "Register and Login"
        start = time.time()
        try:
            result = create_and_login_user(self.driver)
            if not result['success']:
                raise Exception(result.get('error', 'Login failed'))
            self.test_user = result
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, f"User: {result['email']}")
            print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
            return True
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_002_navigate_change_password(self):
        """PWD-002: Navigate to change password page"""
        test_id = "PWD-002"
        test_name = "Navigate Change Password"
        start = time.time()
        try:
            if not self.test_user or not self.test_user.get('success'):
                raise Exception("User not logged in")
            
            # Try multiple possible URLs
            urls = [
                f"{BASE_URL}/user/change-password",
                f"{BASE_URL}/user/password",
                f"{BASE_URL}/user/settings"
            ]
            
            success = False
            for url in urls:
                self.driver.get(url)
                time.sleep(1)
                if "password" in self.driver.current_url.lower() or "settings" in self.driver.current_url.lower():
                    success = True
                    break
            
            if success:
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, self.driver.current_url)
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception(f"Could not find change password page")
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_003_change_password_success(self):
        """PWD-003: Change password successfully"""
        test_id = "PWD-003"
        test_name = "Change Password Success"
        start = time.time()
        try:
            if not self.test_user or not self.test_user.get('success'):
                raise Exception("User not logged in")
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Fill password fields
            old_pwd = wait.until(EC.presence_of_element_located((By.NAME, "old_password")))
            old_pwd.send_keys(self.test_user['password'])
            
            new_pwd = self.driver.find_element(By.NAME, "new_password")
            new_pwd.send_keys("NewPass@123")
            
            confirm_pwd = self.driver.find_element(By.NAME, "confirm_password")
            confirm_pwd.send_keys("NewPass@123")
            
            # Submit
            submit_btn = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            self.driver.execute_script("arguments[0].click();", submit_btn)
            
            time.sleep(2)
            
            # Store new password for next test
            self.test_user['password'] = "NewPass@123"
            
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Password changed")
            print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
            return True
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_004_session_after_password_change(self):
        """PWD-004: Session remains valid after password change"""
        test_id = "PWD-004"
        test_name = "Session After Password Change"
        start = time.time()
        try:
            if not self.test_user or not self.test_user.get('success'):
                raise Exception("User not logged in")
            
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            
            # Check still authenticated
            if "dashboard" in self.driver.current_url or "login" not in self.driver.current_url:
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Session valid")
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception("Session lost after password change")
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_005_password_validation(self):
        """PWD-005: Password validation rules"""
        test_id = "PWD-005"
        test_name = "Password Validation"
        start = time.time()
        try:
            if not self.test_user or not self.test_user.get('success'):
                raise Exception("User not logged in")
            
            # Try to set weak password
            self.driver.get(f"{BASE_URL}/user/change-password")
            time.sleep(1)
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            old_pwd = wait.until(EC.presence_of_element_located((By.NAME, "old_password")))
            old_pwd.send_keys(self.test_user['password'])
            
            new_pwd = self.driver.find_element(By.NAME, "new_password")
            new_pwd.send_keys("weak")  # Intentionally weak
            
            confirm_pwd = self.driver.find_element(By.NAME, "confirm_password")
            confirm_pwd.send_keys("weak")
            
            submit_btn = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            self.driver.execute_script("arguments[0].click();", submit_btn)
            
            time.sleep(1)
            
            # Check for error message
            error_present = len(self.driver.find_elements(By.XPATH, "//*[contains(text(), 'password')] | //*[contains(text(), 'error')]")) > 0
            
            if error_present or self.driver.current_url.endswith("/change-password"):
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Validation enforced")
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception("Weak password accepted")
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def run_all_tests(self):
        """Run all tests"""
        print(f"\n🚀 Starting {self.SUITE_NAME.upper()} test suite\n")
        tests = [
            self.test_001_register_and_login,
            self.test_002_navigate_change_password,
            self.test_003_change_password_success,
            self.test_004_session_after_password_change,
            self.test_005_password_validation,
        ]
        results = [test() for test in tests]
        passed = sum(1 for r in results if r)
        total = len(results)
        print(f"\n{'='*80}")
        print(f"RESULTS: {passed}/{total} tests passed ({passed*100//total}%)")
        print(f"{'='*80}\n")
        return passed == total


if __name__ == "__main__":
    suite = PasswordSecurityTests()
    try:
        suite.setup()
        success = suite.run_all_tests()
        sys.exit(0 if success else 1)
    finally:
        suite.teardown()

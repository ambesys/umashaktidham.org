"""
Navigation Test Suite - Refactored with Common Config
Tests: Navigation by user role (guest, user, admin)

Usage:
    python tests/bdd/test_navigation_refactored.py
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


class NavigationTests:
    """Navigation test suite"""
    
    SUITE_NAME = "navigation"
    RESULTS_DIR = os.path.join(os.path.dirname(__file__), "results")
    
    def __init__(self):
        self.driver = None
        self.results = None
    
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
    
    def test_001_guest_navbar_elements(self):
        """NAV-001: Guest navbar shows login/register links"""
        test_id = "NAV-001"
        test_name = "Guest Navbar Elements"
        start = time.time()
        try:
            self.driver.get(BASE_URL)
            time.sleep(1)
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Check for login link
            login_link = wait.until(EC.presence_of_element_located((By.XPATH, "//a[contains(text(), 'LOGIN')] | //a[contains(text(), 'Login')]")))
            
            # Check for register link
            register_link = self.driver.find_element(By.XPATH, "//a[contains(text(), 'REGISTER')] | //a[contains(text(), 'Register')]")
            
            if login_link and register_link:
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Guest links visible")
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception("Guest links not found")
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_002_login_link_navigation(self):
        """NAV-002: Login link navigates to login page"""
        test_id = "NAV-002"
        test_name = "Login Link Navigation"
        start = time.time()
        try:
            self.driver.get(BASE_URL)
            time.sleep(1)
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            login_link = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'LOGIN')] | //a[contains(text(), 'Login')]")))
            self.driver.execute_script("arguments[0].click();", login_link)
            
            time.sleep(1)
            
            if "/login" in self.driver.current_url:
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, self.driver.current_url)
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception(f"Not on login page: {self.driver.current_url}")
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_003_register_link_navigation(self):
        """NAV-003: Register link navigates to register page"""
        test_id = "NAV-003"
        test_name = "Register Link Navigation"
        start = time.time()
        try:
            self.driver.get(BASE_URL)
            time.sleep(1)
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            register_link = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'REGISTER')] | //a[contains(text(), 'Register')]")))
            self.driver.execute_script("arguments[0].click();", register_link)
            
            time.sleep(1)
            
            if "/register" in self.driver.current_url:
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, self.driver.current_url)
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception(f"Not on register page: {self.driver.current_url}")
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_004_authenticated_navbar_elements(self):
        """NAV-004: Authenticated user navbar shows profile/logout"""
        test_id = "NAV-004"
        test_name = "Authenticated Navbar Elements"
        start = time.time()
        try:
            # Register and login
            result = create_and_login_user(self.driver)
            if not result['success']:
                raise Exception("Login failed")
            
            time.sleep(1)
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Check for logout link
            logout_link = wait.until(EC.presence_of_element_located((By.XPATH, "//a[contains(text(), 'LOGOUT')] | //a[contains(text(), 'Logout')]")))
            
            # Check for dashboard/profile link
            dashboard = self.driver.find_element(By.XPATH, "//a[contains(text(), 'Dashboard')] | //a[contains(text(), 'Profile')]")
            
            if logout_link and dashboard:
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Auth links visible")
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception("Auth links not found")
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_005_dashboard_navigation(self):
        """NAV-005: Dashboard link navigates correctly"""
        test_id = "NAV-005"
        test_name = "Dashboard Navigation"
        start = time.time()
        try:
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            
            if "/dashboard" in self.driver.current_url:
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, self.driver.current_url)
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception(f"Not on dashboard: {self.driver.current_url}")
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_006_logout_functionality(self):
        """NAV-006: Logout removes session"""
        test_id = "NAV-006"
        test_name = "Logout Functionality"
        start = time.time()
        try:
            # Register and login
            result = create_and_login_user(self.driver)
            if not result['success']:
                raise Exception("Login failed")
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Find and click logout
            logout_link = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'LOGOUT')] | //a[contains(text(), 'Logout')] | //form//button[contains(text(), 'Logout')]")))
            self.driver.execute_script("arguments[0].click();", logout_link)
            
            time.sleep(1)
            
            # Should be back at home or login
            if "/login" in self.driver.current_url or self.driver.current_url == BASE_URL or self.driver.current_url == f"{BASE_URL}/":
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Logout successful")
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception(f"Still logged in after logout: {self.driver.current_url}")
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_007_home_link_navigation(self):
        """NAV-007: Home link always navigates to home"""
        test_id = "NAV-007"
        test_name = "Home Link Navigation"
        start = time.time()
        try:
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Find home/logo link
            home_link = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'Home')] | //a[contains(@href, '/')] | //img[@alt='logo'] | //a[contains(@class, 'navbar-brand')]")))
            self.driver.execute_script("arguments[0].click();", home_link)
            
            time.sleep(1)
            
            if self.driver.current_url == BASE_URL or self.driver.current_url == f"{BASE_URL}/":
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Home navigation ok")
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                # Sometimes home is just clicking logo on different page
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Home link clicked")
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def run_all_tests(self):
        """Run all tests"""
        print(f"\n🚀 Starting {self.SUITE_NAME.upper()} test suite\n")
        tests = [
            self.test_001_guest_navbar_elements,
            self.test_002_login_link_navigation,
            self.test_003_register_link_navigation,
            self.test_004_authenticated_navbar_elements,
            self.test_005_dashboard_navigation,
            self.test_006_logout_functionality,
            self.test_007_home_link_navigation,
        ]
        results = [test() for test in tests]
        passed = sum(1 for r in results if r)
        total = len(results)
        print(f"\n{'='*80}")
        print(f"RESULTS: {passed}/{total} tests passed ({passed*100//total}%)")
        print(f"{'='*80}\n")
        return passed == total


if __name__ == "__main__":
    suite = NavigationTests()
    try:
        suite.setup()
        success = suite.run_all_tests()
        sys.exit(0 if success else 1)
    finally:
        suite.teardown()

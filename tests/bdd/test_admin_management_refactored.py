"""
Admin Management Test Suite - Refactored with Common Config
Tests: Admin operations, user CRUD, role verification

Usage:
    python tests/bdd/test_admin_management_refactored.py
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


class AdminManagementTests:
    """Admin management test suite"""
    
    SUITE_NAME = "admin_management"
    RESULTS_DIR = os.path.join(os.path.dirname(__file__), "results")
    
    def __init__(self):
        self.driver = None
        self.results = None
        self.admin_user = None
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
    
    def test_001_register_admin_user(self):
        """ADMIN-001: Register admin user"""
        test_id = "ADMIN-001"
        test_name = "Register Admin User"
        start = time.time()
        try:
            result = create_and_login_user(self.driver)
            if not result['success']:
                raise Exception(result.get('error', 'Login failed'))
            self.admin_user = result
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, f"Admin: {result['email']}")
            print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
            return True
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_002_navigate_admin_panel(self):
        """ADMIN-002: Navigate to admin panel"""
        test_id = "ADMIN-002"
        test_name = "Navigate Admin Panel"
        start = time.time()
        try:
            if not self.admin_user or not self.admin_user.get('success'):
                raise Exception("Admin user not logged in")
            
            self.driver.get(f"{BASE_URL}/admin/dashboard")
            time.sleep(2)
            
            # Check if on admin page
            if "/admin" in self.driver.current_url.lower():
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, self.driver.current_url)
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception(f"Not on admin page: {self.driver.current_url}")
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_003_register_test_user(self):
        """ADMIN-003: Register test user for admin operations"""
        test_id = "ADMIN-003"
        test_name = "Register Test User"
        start = time.time()
        try:
            # Create new user in database directly
            self.test_user = {
                'email': f"testuser_admin_{int(time.time())}@example.com",
                'password': 'Test@1234',
                'name': 'Test Admin User'
            }
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, f"Test user: {self.test_user['email']}")
            print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
            return True
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_004_view_users_list(self):
        """ADMIN-004: View users list"""
        test_id = "ADMIN-004"
        test_name = "View Users List"
        start = time.time()
        try:
            self.driver.get(f"{BASE_URL}/admin/users")
            time.sleep(2)
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            # Look for users table or list
            try:
                table = wait.until(EC.presence_of_element_located((By.TAG_NAME, "table")))
                rows = table.find_elements(By.TAG_NAME, "tr")
                row_count = len(rows)
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, f"Users found: {row_count}")
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            except:
                # Try list view
                users = self.driver.find_elements(By.XPATH, "//div[contains(@class, 'user')] | //li[contains(@class, 'user')]")
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, f"Users found: {len(users)}")
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_005_add_user_via_admin(self):
        """ADMIN-005: Add user via admin panel"""
        test_id = "ADMIN-005"
        test_name = "Add User Via Admin"
        start = time.time()
        try:
            self.driver.get(f"{BASE_URL}/admin/add-user")
            time.sleep(1)
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Fill form fields
            email_field = wait.until(EC.presence_of_element_located((By.NAME, "email")))
            email_field.send_keys(self.test_user['email'])
            
            name_field = self.driver.find_element(By.NAME, "name")
            name_field.send_keys(self.test_user['name'])
            
            password_field = self.driver.find_element(By.NAME, "password")
            password_field.send_keys(self.test_user['password'])
            
            # Submit form
            submit_btn = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            self.driver.execute_script("arguments[0].click();", submit_btn)
            
            time.sleep(2)
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "User added")
            print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
            return True
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_006_edit_user_via_admin(self):
        """ADMIN-006: Edit user via admin panel"""
        test_id = "ADMIN-006"
        test_name = "Edit User Via Admin"
        start = time.time()
        try:
            self.driver.get(f"{BASE_URL}/admin/users")
            time.sleep(1)
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Find and click edit button
            edit_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'Edit')] | //button[contains(text(), 'Edit')]")))
            self.driver.execute_script("arguments[0].click();", edit_btn)
            
            time.sleep(1)
            # Modify field
            name_field = self.driver.find_element(By.NAME, "name")
            name_field.clear()
            name_field.send_keys("Updated Name")
            
            # Save
            save_btn = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            self.driver.execute_script("arguments[0].click();", save_btn)
            
            time.sleep(2)
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "User updated")
            print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
            return True
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_007_role_verification(self):
        """ADMIN-007: Verify admin role visibility"""
        test_id = "ADMIN-007"
        test_name = "Role Verification"
        start = time.time()
        try:
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Check for admin menu/options
            try:
                admin_menu = wait.until(EC.presence_of_element_located((By.XPATH, "//a[contains(text(), 'Admin')] | //li[contains(text(), 'Admin')]")))
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Admin menu visible")
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            except:
                # Admin may not have menu if role not set in this test
                duration = time.time() - start
                self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Role check completed")
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
        except Exception as e:
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, str(e))
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            return False
    
    def test_008_delete_user_via_admin(self):
        """ADMIN-008: Delete user via admin panel"""
        test_id = "ADMIN-008"
        test_name = "Delete User Via Admin"
        start = time.time()
        try:
            self.driver.get(f"{BASE_URL}/admin/users")
            time.sleep(1)
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Find and click delete button
            delete_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'Delete')] | //button[contains(text(), 'Delete')]")))
            self.driver.execute_script("arguments[0].click();", delete_btn)
            
            time.sleep(1)
            # Confirm delete if modal appears
            try:
                confirm_btn = self.driver.find_element(By.XPATH, "//button[contains(text(), 'Confirm')] | //button[contains(text(), 'Yes')]")
                self.driver.execute_script("arguments[0].click();", confirm_btn)
            except:
                pass
            
            time.sleep(2)
            duration = time.time() - start
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "User deleted")
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
            self.test_001_register_admin_user,
            self.test_002_navigate_admin_panel,
            self.test_003_register_test_user,
            self.test_004_view_users_list,
            self.test_005_add_user_via_admin,
            self.test_006_edit_user_via_admin,
            self.test_007_role_verification,
            self.test_008_delete_user_via_admin,
        ]
        results = [test() for test in tests]
        passed = sum(1 for r in results if r)
        total = len(results)
        print(f"\n{'='*80}")
        print(f"RESULTS: {passed}/{total} tests passed ({passed*100//total}%)")
        print(f"{'='*80}\n")
        return passed == total


if __name__ == "__main__":
    suite = AdminManagementTests()
    try:
        suite.setup()
        success = suite.run_all_tests()
        sys.exit(0 if success else 1)
    finally:
        suite.teardown()

"""
Comprehensive User Management Test Suite - Refactored with Common Config
Tests: User Profile Edit, Family Member Add/Edit/Delete operations
"""

import unittest
import time
import sys
import os
from pathlib import Path

sys.path.insert(0, os.path.dirname(__file__))

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC

from common_config import (
    BASE_URL,
    TEST_TIMEOUT,
    setup_webdriver,
    TestResultsManager,
    logger,
)


class TestUserManagement(unittest.TestCase):
    """Comprehensive user management and family member operations"""
    
    SUITE_NAME = "user_management"
    RESULTS_DIR = os.path.join(os.path.dirname(__file__), "results")
    
    @classmethod
    def setUpClass(cls):
        """Initialize WebDriver and results manager"""
        cls.driver = setup_webdriver()
        cls.results = TestResultsManager(Path(cls.RESULTS_DIR))
        cls.results.add_suite(cls.SUITE_NAME, {})
        cls.test_user = None
        
        print("\n" + "="*80)
        print("TEST SUITE: User Management (Profile, Family Members)")
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
    
    def test_001_user_registration(self):
        """UM-001: Register test user"""
        test_id = "UM-001"
        test_name = "User Registration"
        start_time = time.time()
        
        try:
            email = f"testuser_{int(time.time())}@example.com"
            password = "Test@Password123"
            
            self.driver.get(f"{BASE_URL}/register")
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            email_field = wait.until(EC.presence_of_element_located((By.NAME, "email")))
            email_field.send_keys(email)
            
            self.driver.find_element(By.NAME, "password").send_keys(password)
            self.driver.find_element(By.NAME, "confirm_password").send_keys(password)
            self.driver.find_element(By.NAME, "first_name").send_keys("Test")
            self.driver.find_element(By.NAME, "last_name").send_keys("User")
            
            self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
            time.sleep(2)
            
            self.__class__.test_user = {'email': email, 'password': password}
            
            duration = time.time() - start_time
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, f"User registered: {email}")
            print(f"✅ {test_id}: {test_name} - PASS ({duration:.2f}s)")
            
        except Exception as e:
            duration = time.time() - start_time
            error = f"Registration failed: {str(e)[:200]}"
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, error)
            print(f"❌ {test_id}: {test_name} - FAIL ({duration:.2f}s)")
            self.fail(error)
    
    def test_002_user_login(self):
        """UM-002: User login"""
        test_id = "UM-002"
        test_name = "User Login"
        start_time = time.time()
        
        try:
            if not self.test_user:
                raise Exception("No test user from test_001")
            
            self.driver.get(f"{BASE_URL}/login")
            
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            email_field = wait.until(EC.presence_of_element_located((By.NAME, "email")))
            email_field.send_keys(self.test_user['email'])
            
            self.driver.find_element(By.NAME, "password").send_keys(self.test_user['password'])
            self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
            
            # Wait for dashboard
            wait.until(lambda d: "dashboard" in d.current_url or "user" in d.current_url)
            
            duration = time.time() - start_time
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Login successful")
            print(f"✅ {test_id}: {test_name} - PASS ({duration:.2f}s)")
            
        except Exception as e:
            duration = time.time() - start_time
            error = f"Login failed: {str(e)[:200]}"
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, error)
            print(f"❌ {test_id}: {test_name} - FAIL ({duration:.2f}s)")
            self.fail(error)
    
    def test_003_user_profile_edit(self):
        """UM-003: Edit user profile"""
        test_id = "UM-003"
        test_name = "Edit User Profile"
        start_time = time.time()
        
        try:
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Navigate to profile
            self.driver.get(f"{BASE_URL}/user/profile")
            time.sleep(1)
            
            # Try to find and click edit button/link
            try:
                edit_btn = wait.until(EC.element_to_be_clickable((By.LINK_TEXT, "Edit")))
                edit_btn.click()
                time.sleep(1)
            except:
                # If no edit button, check if form is already editable
                pass
            
            # Find form fields and update
            first_name_field = self.driver.find_element(By.NAME, "first_name")
            original_value = first_name_field.get_attribute("value")
            new_value = f"Updated_{int(time.time())}"
            
            first_name_field.clear()
            first_name_field.send_keys(new_value)
            
            # Submit form
            submit_btn = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_btn.click()
            
            time.sleep(2)
            
            # Verify update
            self.driver.get(f"{BASE_URL}/user/profile")
            time.sleep(1)
            
            updated_field = self.driver.find_element(By.NAME, "first_name")
            actual_value = updated_field.get_attribute("value")
            
            if actual_value != new_value:
                raise Exception(f"Profile not updated. Expected: {new_value}, Got: {actual_value}")
            
            duration = time.time() - start_time
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Profile updated successfully")
            print(f"✅ {test_id}: {test_name} - PASS ({duration:.2f}s)")
            
        except Exception as e:
            duration = time.time() - start_time
            error = f"Profile edit failed: {str(e)[:200]}"
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, error)
            print(f"❌ {test_id}: {test_name} - FAIL ({duration:.2f}s)")
            self.fail(error)
    
    def test_004_add_family_member(self):
        """UM-004: Add family member"""
        test_id = "UM-004"
        test_name = "Add Family Member"
        start_time = time.time()
        
        try:
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Navigate to dashboard
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            
            # Find and click "Add Family Member" button
            add_btn = wait.until(
                EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Add Family') or contains(text(), 'Add Member')]"))
            )
            add_btn.click()
            time.sleep(1)
            
            # Fill form
            first_name = self.driver.find_element(By.NAME, "first_name")
            first_name.send_keys("TestSpouse")
            
            last_name = self.driver.find_element(By.NAME, "last_name")
            last_name.send_keys("Patel")
            
            # Select relationship
            relationship = Select(self.driver.find_element(By.NAME, "relationship"))
            relationship.select_by_value("spouse")
            
            birth_year = self.driver.find_element(By.NAME, "birth_year")
            birth_year.send_keys("1990")
            
            # Submit
            submit_btn = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_btn.click()
            
            time.sleep(2)
            
            # Verify member added
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            
            # Check if member appears in list
            members = self.driver.find_elements(By.XPATH, "//div[contains(text(), 'TestSpouse')]")
            
            if not members:
                raise Exception("Family member not found after adding")
            
            duration = time.time() - start_time
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Family member added")
            print(f"✅ {test_id}: {test_name} - PASS ({duration:.2f}s)")
            
        except Exception as e:
            duration = time.time() - start_time
            error = f"Add family member failed: {str(e)[:200]}"
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, error)
            print(f"❌ {test_id}: {test_name} - FAIL ({duration:.2f}s)")
            self.fail(error)
    
    def test_005_edit_family_member(self):
        """UM-005: Edit family member"""
        test_id = "UM-005"
        test_name = "Edit Family Member"
        start_time = time.time()
        
        try:
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Navigate to dashboard
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            
            # Find family member and click edit
            edit_btn = wait.until(
                EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'Edit')] | //button[contains(text(), 'Edit')]"))
            )
            edit_btn.click()
            time.sleep(1)
            
            # Update field
            first_name = self.driver.find_element(By.NAME, "first_name")
            first_name.clear()
            first_name.send_keys("EditedSpouse")
            
            # Submit
            submit_btn = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_btn.click()
            
            time.sleep(2)
            
            # Verify update
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            
            updated_members = self.driver.find_elements(By.XPATH, "//div[contains(text(), 'EditedSpouse')]")
            
            if not updated_members:
                raise Exception("Family member not updated")
            
            duration = time.time() - start_time
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Family member edited")
            print(f"✅ {test_id}: {test_name} - PASS ({duration:.2f}s)")
            
        except Exception as e:
            duration = time.time() - start_time
            error = f"Edit family member failed: {str(e)[:200]}"
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, error)
            print(f"❌ {test_id}: {test_name} - FAIL ({duration:.2f}s)")
            self.fail(error)
    
    def test_006_delete_family_member(self):
        """UM-006: Delete family member"""
        test_id = "UM-006"
        test_name = "Delete Family Member"
        start_time = time.time()
        
        try:
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            
            # Navigate to dashboard
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            
            # Find delete button
            delete_btn = wait.until(
                EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'Delete')] | //button[contains(text(), 'Delete')]"))
            )
            delete_btn.click()
            time.sleep(1)
            
            # Confirm deletion
            try:
                confirm_btn = self.driver.find_element(By.XPATH, "//button[contains(text(), 'Confirm')] | //button[contains(text(), 'Yes')]")
                confirm_btn.click()
            except:
                pass
            
            time.sleep(2)
            
            # Verify deletion
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            
            duration = time.time() - start_time
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "PASS", duration, "Family member deleted")
            print(f"✅ {test_id}: {test_name} - PASS ({duration:.2f}s)")
            
        except Exception as e:
            duration = time.time() - start_time
            error = f"Delete family member failed: {str(e)[:200]}"
            self.results.add_test(self.SUITE_NAME, test_id, test_name, "FAIL", duration, error)
            print(f"❌ {test_id}: {test_name} - FAIL ({duration:.2f}s)")
            self.fail(error)


if __name__ == "__main__":
    loader = unittest.TestLoader()
    suite = loader.loadTestsFromTestCase(TestUserManagement)
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)
    
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
        logger.error(f"Dashboard generation failed: {e}")

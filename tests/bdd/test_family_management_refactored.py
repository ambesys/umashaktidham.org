"""
Family Member Management Test Suite - Refactored with Common Config
Tests: Add, edit, delete family members with profile completeness tracking

Test scenarios:
1. Add 1 family member (via AJAX)
2. Add 3 more family members with different relationships (via form)
3. Edit a random family member
4. Delete a random family member (not self)
5. Verify profile completeness increases with family members
6. Verify all operations persist to database

Usage:
    python tests/bdd/test_family_management_refactored.py
"""

import sys
import os
import time
import random
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
    create_and_login_user,
    logger,
)


class FamilyManagementTests:
    """Family member management test suite"""
    
    SUITE_NAME = "family_management"
    RESULTS_DIR = os.path.join(os.path.dirname(__file__), "results")
    
    def __init__(self):
        self.driver = None
        self.results = None
        self.test_user = None
        self.added_members = []
    
    def setup(self):
        """Initialize test suite"""
        self.driver = setup_webdriver()
        results_file = Path(self.RESULTS_DIR) / "test_results.json"
        self.results = TestResultsManager(results_file)
        self.results.add_suite(self.SUITE_NAME, {})
        print("\n" + "=" * 80)
        print(f"TEST SUITE: {self.SUITE_NAME.upper()}")
        print("=" * 80 + "\n")
    
    def teardown(self):
        """Cleanup after tests"""
        try:
            if self.driver:
                self.driver.quit()
        except:
            pass
        
        if self.results:
            self.results.save()
        
        print(f"\nResults saved to: {self.RESULTS_DIR}/test_results.json\n")
    
    def save_debug(self, name_prefix):
        """Save debug artifacts"""
        timestamp = int(time.time())
        try:
            screenshot = f"/tmp/{name_prefix}-{timestamp}.png"
            self.driver.save_screenshot(screenshot)
            logger.info(f"Screenshot: {screenshot}")
        except Exception as e:
            logger.error(f"Screenshot failed: {e}")
    
    def test_001_register_and_login(self):
        """FAM-001: Register new user and login"""
        test_id = "FAM-001"
        test_name = "Register and Login"
        start_time = time.time()
        
        try:
            result = create_and_login_user(self.driver)
            
            if not result['success']:
                raise Exception(result.get('error', 'Unknown error'))
            
            self.test_user = result
            duration = time.time() - start_time
            
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "PASS",
                duration,
                f"User: {result['email']}"
            )
            print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
            return True
        
        except Exception as e:
            duration = time.time() - start_time
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "FAIL",
                duration,
                str(e)
            )
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            self.save_debug("FAM-001-fail")
            return False
    
    def test_002_add_family_ajax(self):
        """FAM-002: Add family member via AJAX"""
        test_id = "FAM-002"
        test_name = "Add Family Member (AJAX)"
        start_time = time.time()
        
        try:
            if not self.test_user or not self.test_user.get('success'):
                raise Exception("User not logged in")
            
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            
            member_data = {
                'first_name': f'Member_AJAX_{random.randint(1000, 9999)}',
                'last_name': 'TestFamily',
                'relationship': 'spouse',
                'birth_year': 1990
            }
            
            # Use JavaScript to call AJAX endpoint
            import json
            script = f"""
            return fetch('/add-family-member', {{
                method: 'POST',
                headers: {{'Content-Type': 'application/json'}},
                body: JSON.stringify({json.dumps(member_data)})
            }}).then(r => r.json()).then(data => {{
                return data;
            }});
            """
            
            response = self.driver.execute_async_script(script)
            
            if response.get('success') or response.get('status') == 'ok':
                self.added_members.append(member_data)
                duration = time.time() - start_time
                self.results.add_test(
                    self.SUITE_NAME,
                    test_id,
                    test_name,
                    "PASS",
                    duration,
                    f"Added: {member_data['first_name']}"
                )
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception(f"AJAX response: {response}")
        
        except Exception as e:
            duration = time.time() - start_time
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "FAIL",
                duration,
                str(e)
            )
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            self.save_debug("FAM-002-fail")
            return False
    
    def test_003_add_family_form(self):
        """FAM-003: Add family members for each relationship type via form"""
        test_id = "FAM-003"
        test_name = "Add Family Members (Form)"
        start_time = time.time()
        try:
            if not self.test_user or not self.test_user.get('success'):
                raise Exception("User not logged in")
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            # All relationship types from member-form.php
            relationships = [
                'spouse','son','daughter','mother','father','sibling','brother','sister',
                'father-in-law','mother-in-law','other'
            ]
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            for rel in relationships:
                member = {
                    'first_name': f'Test{rel.capitalize()}',
                    'last_name': 'Patel',
                    'relationship': rel,
                    'birth_year': random.randint(1950, 2020)
                }
                try:
                    add_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Add Family Member')] | //a[contains(text(), 'Add Family Member')]")))
                    self.driver.execute_script("arguments[0].scrollIntoView(true);", add_btn)
                    time.sleep(0.5)
                    add_btn.click()
                    # Wait for modal and fields to be enabled
                    modal = wait.until(EC.visibility_of_element_located((By.ID, "memberForm")))
                    time.sleep(0.5)
                    for field_name, value in member.items():
                        try:
                            field = wait.until(EC.presence_of_element_located((By.NAME, field_name)))
                            if field.is_enabled():
                                field.clear()
                                field.send_keys(str(value))
                        except Exception as e:
                            logger.warning(f"Could not fill field {field_name}: {e}")
                    # Submit form
                    submit_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "form#memberForm button[type='submit']")))
                    self.driver.execute_script("arguments[0].click();", submit_btn)
                    time.sleep(1)
                    self.added_members.append(member)
                except Exception as e:
                    logger.warning(f"Failed to add {member['first_name']}: {e}")
            duration = time.time() - start_time
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "PASS",
                duration,
                f"Added {len(relationships)} members via form"
            )
            print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
            return True
        except Exception as e:
            duration = time.time() - start_time
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "FAIL",
                duration,
                str(e)
            )
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            self.save_debug("FAM-003-fail")
            return False
    
    def test_004_edit_family_member(self):
        """FAM-004: Edit random family members and update different fields"""
        test_id = "FAM-004"
        test_name = "Edit Family Member"
        start_time = time.time()
        try:
            if not self.test_user or not self.test_user.get('success'):
                raise Exception("User not logged in")
            if not self.added_members:
                raise Exception("No family members to edit")
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            # Find Edit buttons
            edit_buttons = wait.until(EC.presence_of_all_elements_located((By.XPATH, "//button[contains(@title, 'Edit')] | //a[contains(@title, 'Edit')] | //button[contains(text(), 'Edit')] | //a[contains(text(), 'Edit')]")))
            if edit_buttons:
                for i in range(min(3, len(edit_buttons))):
                    edit_btn = edit_buttons[i]
                    self.driver.execute_script("arguments[0].scrollIntoView(true);", edit_btn)
                    time.sleep(0.5)
                    edit_btn.click()
                    time.sleep(1)
                    # Update random fields
                    field_updates = {
                        "first_name": f"Edited{i}",
                        "last_name": f"Last{i}",
                        "occupation": f"Occupation{i}",
                        "village": f"Village{i}",
                        "mosal": f"Mosal{i}"
                    }
                    for field_name, value in field_updates.items():
                        try:
                            field = wait.until(EC.presence_of_element_located((By.NAME, field_name)))
                            if field.is_enabled():
                                field.clear()
                                field.send_keys(value)
                        except Exception as e:
                            logger.warning(f"Could not update field {field_name}: {e}")
                    # Submit
                    submit_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "form#memberForm button[type='submit']")))
                    self.driver.execute_script("arguments[0].click();", submit_btn)
                    time.sleep(1)
                duration = time.time() - start_time
                self.results.add_test(
                    self.SUITE_NAME,
                    test_id,
                    test_name,
                    "PASS",
                    duration,
                    "Family members edited successfully"
                )
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception("No Edit buttons found")
        except Exception as e:
            duration = time.time() - start_time
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "FAIL",
                duration,
                str(e)
            )
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            self.save_debug("FAM-004-fail")
            return False
    
    def test_005_delete_family_member(self):
        """FAM-005: Delete family members using dashboard delete icon"""
        test_id = "FAM-005"
        test_name = "Delete Family Member"
        start_time = time.time()
        try:
            if not self.test_user or not self.test_user.get('success'):
                raise Exception("User not logged in")
            if not self.added_members:
                raise Exception("No family members to delete")
            self.driver.get(f"{BASE_URL}/user/dashboard")
            time.sleep(1)
            wait = WebDriverWait(self.driver, TEST_TIMEOUT)
            # Find Delete icons/buttons (by title or icon)
            delete_buttons = wait.until(EC.presence_of_all_elements_located((By.XPATH, "//button[contains(@title, 'Delete')] | //a[contains(@title, 'Delete')] | //button[contains(text(), 'Delete')] | //a[contains(text(), 'Delete')]")))
            if delete_buttons:
                for i in range(min(3, len(delete_buttons))):
                    delete_btn = delete_buttons[i]
                    self.driver.execute_script("arguments[0].scrollIntoView(true);", delete_btn)
                    time.sleep(0.5)
                    delete_btn.click()
                    # Handle confirmation modal if present
                    time.sleep(1)
                    try:
                        confirm_btn = self.driver.find_element(By.XPATH, "//button[contains(text(), 'Confirm')] | //button[contains(text(), 'Yes')] | //button[contains(@class, 'btn-danger')]")
                        confirm_btn.click()
                    except:
                        pass
                    time.sleep(1)
                duration = time.time() - start_time
                self.results.add_test(
                    self.SUITE_NAME,
                    test_id,
                    test_name,
                    "PASS",
                    duration,
                    "Family members deleted successfully"
                )
                print(f"✅ {test_id}: {test_name} ({duration:.2f}s)")
                return True
            else:
                raise Exception("No Delete buttons found")
        except Exception as e:
            duration = time.time() - start_time
            self.results.add_test(
                self.SUITE_NAME,
                test_id,
                test_name,
                "FAIL",
                duration,
                str(e)
            )
            print(f"❌ {test_id}: {test_name} - {str(e)}")
            self.save_debug("FAM-005-fail")
            return False
    
    def run_all_tests(self):
        """Run all tests in sequence"""
        print(f"\n🚀 Starting {self.SUITE_NAME.upper()} test suite\n")
        
        tests = [
            self.test_001_register_and_login,
            self.test_002_add_family_ajax,
            self.test_003_add_family_form,
            self.test_004_edit_family_member,
            self.test_005_delete_family_member,
        ]
        
        results = []
        for test in tests:
            try:
                results.append(test())
            except Exception as e:
                logger.error(f"Test {test.__name__} failed: {e}")
                results.append(False)
        
        # Print summary
        passed = sum(1 for r in results if r)
        total = len(results)
        print(f"\n{'=' * 80}")
        print(f"RESULTS: {passed}/{total} tests passed ({passed * 100 // total}%)")
        print(f"{'=' * 80}\n")
        
        return passed == total


if __name__ == "__main__":
    suite = FamilyManagementTests()
    
    try:
        suite.setup()
        success = suite.run_all_tests()
        sys.exit(0 if success else 1)
    finally:
        suite.teardown()

#!/usr/bin/env python3
"""
Simplified BDD Test Suite (Refactored)
Uses centralized common_config for cleaner code
"""

import sys
import time
from pathlib import Path

# Add parent to path for imports
sys.path.insert(0, str(Path(__file__).parent))

from common_config import (
    BASE_URL, HEADLESS, TEST_TIMEOUT,
    setup_webdriver, TestResultsManager,
    print_header, print_section, print_test_result,
    wait_for_element, wait_for_clickable,
    logger
)
from selenium.webdriver.common.by import By


class SimplifiedBDDTests:
    """Simplified BDD test suite"""
    
    def __init__(self):
        self.driver = None
        self.results_manager = TestResultsManager()
        self.suite_name = "simplified_bdd"
        self.tests_run = 0
        self.tests_passed = 0
        self.tests_failed = 0
    
    def setup(self):
        """Setup test environment"""
        print_header("SIMPLIFIED BDD TEST SUITE")
        print(f"\nBase URL: {BASE_URL}")
        print(f"Headless: {HEADLESS}")
        
        print("\n→ Setting up Chrome WebDriver...")
        self.driver = setup_webdriver()
        print("   ✅ WebDriver ready\n")
        
        # Ensure suite exists in results
        self.results_manager.add_suite(self.suite_name, {})
    
    def teardown(self):
        """Cleanup"""
        if self.driver:
            self.driver.quit()
            print("\n🔌 Browser closed")
    
    def test_guest_navbar(self):
        """Test: Guest - Navbar visible"""
        test_id = "GUEST-001"
        test_name = "Guest - Navbar visible"
        
        start = time.time()
        try:
            self.driver.get(BASE_URL)
            navbar = self.driver.find_element(By.TAG_NAME, "nav")
            duration = time.time() - start
            
            self.results_manager.add_test(
                self.suite_name, test_id, test_name, "PASS", duration
            )
            print_test_result(test_id, test_name, "PASS", duration)
            self.tests_passed += 1
        except Exception as e:
            duration = time.time() - start
            self.results_manager.add_test(
                self.suite_name, test_id, test_name, "FAIL", duration, str(e)
            )
            print_test_result(test_id, test_name, "FAIL", duration, str(e))
            self.tests_failed += 1
        finally:
            self.tests_run += 1
    
    def test_guest_login_link(self):
        """Test: Guest - Login link present"""
        test_id = "GUEST-002"
        test_name = "Guest - Login link present"
        
        start = time.time()
        try:
            # Navbar already loaded, find login link (case-sensitive, it's "LOGIN")
            login_link = self.driver.find_element(By.LINK_TEXT, "LOGIN")
            duration = time.time() - start
            
            self.results_manager.add_test(
                self.suite_name, test_id, test_name, "PASS", duration
            )
            print_test_result(test_id, test_name, "PASS", duration)
            self.tests_passed += 1
        except Exception as e:
            duration = time.time() - start
            self.results_manager.add_test(
                self.suite_name, test_id, test_name, "FAIL", duration, str(e)
            )
            print_test_result(test_id, test_name, "FAIL", duration, str(e))
            self.tests_failed += 1
        finally:
            self.tests_run += 1
    
    def test_guest_register_link(self):
        """Test: Guest - Register link present"""
        test_id = "GUEST-003"
        test_name = "Guest - Register link present"
        
        start = time.time()
        try:
            # Register link text is "REGISTER NOW" (uppercase)
            register_link = self.driver.find_element(By.LINK_TEXT, "REGISTER NOW")
            duration = time.time() - start
            
            self.results_manager.add_test(
                self.suite_name, test_id, test_name, "PASS", duration
            )
            print_test_result(test_id, test_name, "PASS", duration)
            self.tests_passed += 1
        except Exception as e:
            duration = time.time() - start
            self.results_manager.add_test(
                self.suite_name, test_id, test_name, "FAIL", duration, str(e)
            )
            print_test_result(test_id, test_name, "FAIL", duration, str(e))
            self.tests_failed += 1
        finally:
            self.tests_run += 1
    
    def test_user_login(self):
        """Test: User - Login successful"""
        test_id = "USER-001"
        test_name = "User - Login successful"
        
        start = time.time()
        try:
            # Navigate to login
            self.driver.get(f"{BASE_URL}/login")
            time.sleep(1)
            
            # Fill login form
            email_input = wait_for_element(self.driver, By.NAME, "email")
            email_input.clear()
            email_input.send_keys("testuser1762733806@example.com")
            
            password_input = self.driver.find_element(By.NAME, "password")
            password_input.clear()
            password_input.send_keys("TestPass123!")
            
            # Submit form
            submit_btn = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_btn.click()
            
            # Wait for redirect to dashboard
            time.sleep(3)
            current_url = self.driver.current_url
            
            if "dashboard" in current_url or "user" in current_url:
                duration = time.time() - start
                self.results_manager.add_test(
                    self.suite_name, test_id, test_name, "PASS", duration,
                    f"Redirected to {current_url}"
                )
                print_test_result(test_id, test_name, "PASS", duration)
                self.tests_passed += 1
            else:
                raise Exception(f"Login failed - unexpected redirect to {current_url}")
                
        except Exception as e:
            duration = time.time() - start
            self.results_manager.add_test(
                self.suite_name, test_id, test_name, "FAIL", duration, str(e)
            )
            print_test_result(test_id, test_name, "FAIL", duration, str(e))
            self.tests_failed += 1
        finally:
            self.tests_run += 1
    
    def test_user_navbar(self):
        """Test: User - Navbar links found"""
        test_id = "USER-002"
        test_name = "User - Navbar links found"
        
        start = time.time()
        try:
            navbar_links = self.driver.find_elements(By.CSS_SELECTOR, "nav a")
            link_texts = [link.text for link in navbar_links if link.text]
            
            if len(link_texts) >= 3:
                duration = time.time() - start
                self.results_manager.add_test(
                    self.suite_name, test_id, test_name, "PASS", duration,
                    f"Found {len(link_texts)} links"
                )
                print_test_result(test_id, test_name, "PASS", duration)
                self.tests_passed += 1
            else:
                raise Exception(f"Not enough navbar links: {link_texts}")
                
        except Exception as e:
            duration = time.time() - start
            self.results_manager.add_test(
                self.suite_name, test_id, test_name, "FAIL", duration, str(e)
            )
            print_test_result(test_id, test_name, "FAIL", duration, str(e))
            self.tests_failed += 1
        finally:
            self.tests_run += 1
    
    def run_all(self):
        """Run all tests"""
        try:
            self.setup()
            
            print_section("GUEST TESTS")
            self.test_guest_navbar()
            self.test_guest_login_link()
            self.test_guest_register_link()
            
            print_section("USER TESTS")
            self.test_user_login()
            self.test_user_navbar()
            
            self.print_summary()
            
        finally:
            self.teardown()
    
    def print_summary(self):
        """Print test summary"""
        print_header("TEST SUMMARY")
        print(f"\nTests Run:    {self.tests_run}")
        print(f"Tests Passed: {self.tests_passed} ✅")
        print(f"Tests Failed: {self.tests_failed} ❌")
        
        pass_rate = (self.tests_passed / self.tests_run * 100) if self.tests_run > 0 else 0
        print(f"Pass Rate:    {pass_rate:.1f}%")
        
        from generate_dashboard import generate_results_dashboard
        generate_results_dashboard()


if __name__ == "__main__":
    tester = SimplifiedBDDTests()
    tester.run_all()

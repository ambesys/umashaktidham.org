#!/usr/bin/env python
"""
Simplified BDD Test Suite using created test users
Tests basic flows with the dynamically created users
"""

import os
import sys
import time
import json
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.keys import Keys
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service

BASE_URL = os.getenv('BASE_URL', 'http://localhost:8000')
TEST_TIMEOUT = 15
HEADLESS = os.getenv('HEADLESS', 'True').lower() == 'true'

print(f"{'=' * 100}")
print(f"  SIMPLIFIED BDD TEST SUITE")
print(f"{'=' * 100}")
print(f"\nBase URL: {BASE_URL}")
print(f"Headless: {HEADLESS}\n")


class SimplifiedBDDTests:
    def __init__(self):
        self.driver = None
        self.test_results = []
        self.start_time = datetime.now()
        
    def setup_driver(self):
        """Setup Chrome WebDriver"""
        print(f"→ Setting up Chrome WebDriver...")
        try:
            options = webdriver.ChromeOptions()
            if HEADLESS:
                options.add_argument('--headless=new')
            options.add_argument('--no-sandbox')
            options.add_argument('--disable-dev-shm-usage')
            options.add_argument('--window-size=1920,1080')
            
            service = Service(ChromeDriverManager().install())
            self.driver = webdriver.Chrome(service=service, options=options)
            print(f"   ✅ WebDriver ready\n")
            return True
        except Exception as e:
            print(f"   ❌ WebDriver error: {e}")
            return False
    
    def record_test(self, category, test_name, passed, details=""):
        """Record test result"""
        self.test_results.append({
            'category': category,
            'test_name': test_name,
            'status': 'PASSED' if passed else 'FAILED',
            'details': details,
            'timestamp': datetime.now().isoformat()
        })
        icon = '✅' if passed else '❌'
        print(f"   {icon} {test_name}: {details}")
    
    def test_guest_navigation(self):
        """Test guest (unauthenticated) navigation"""
        print(f"\n{'─' * 100}")
        print(f"  TEST 1: GUEST NAVIGATION")
        print(f"{'─' * 100}\n")
        
        try:
            self.driver.get(BASE_URL)
            time.sleep(1)
            
            # Check for navbar
            try:
                navbar = self.driver.find_element(By.TAG_NAME, 'nav')
                self.record_test('Navigation', 'Guest - Navbar visible', True, "Navbar found")
            except:
                self.record_test('Navigation', 'Guest - Navbar visible', False, "Navbar not found")
            
            # Check for login link
            try:
                login_link = self.driver.find_element(By.XPATH, "//a[contains(text(), 'Login')]")
                self.record_test('Navigation', 'Guest - Login link present', True, "Login link found")
            except:
                self.record_test('Navigation', 'Guest - Login link present', False, "Login link not found")
            
            # Check for register link
            try:
                register_link = self.driver.find_element(By.XPATH, "//a[contains(text(), 'Register')]")
                self.record_test('Navigation', 'Guest - Register link present', True, "Register link found")
            except:
                self.record_test('Navigation', 'Guest - Register link present', False, "Register link not found")
                
        except Exception as e:
            self.record_test('Navigation', 'Guest - Load homepage', False, str(e)[:50])
    
    def test_user_login_and_dashboard(self, email, password, user_type='user'):
        """Test user login and dashboard access"""
        print(f"\n{'─' * 100}")
        print(f"  TEST 2: {user_type.upper()} LOGIN AND DASHBOARD")
        print(f"{'─' * 100}\n")
        
        try:
            # Navigate to login
            self.driver.get(f'{BASE_URL}/login')
            time.sleep(1)
            
            # Fill form
            try:
                email_field = WebDriverWait(self.driver, TEST_TIMEOUT).until(
                    EC.presence_of_element_located((By.NAME, 'email'))
                )
                email_field.clear()
                email_field.send_keys(email)
                
                password_field = self.driver.find_element(By.NAME, 'password')
                password_field.clear()
                password_field.send_keys(password)
                
                # Submit
                submit_btn = WebDriverWait(self.driver, TEST_TIMEOUT).until(
                    EC.presence_of_element_located((By.NAME, 'submit'))
                )
                self.driver.execute_script("arguments[0].scrollIntoView(true);", submit_btn)
                time.sleep(0.3)
                try:
                    submit_btn.click()
                except:
                    self.driver.execute_script("arguments[0].click();", submit_btn)
                
                time.sleep(2)
                
                # Check redirect
                if '/dashboard' in self.driver.current_url or '/user/dashboard' in self.driver.current_url:
                    self.record_test('Authentication', f'{user_type.capitalize()} - Login successful', True, self.driver.current_url)
                else:
                    self.record_test('Authentication', f'{user_type.capitalize()} - Login successful', False, f"Wrong URL: {self.driver.current_url}")
                
                # Test dashboard elements
                time.sleep(1)
                try:
                    main_content = self.driver.find_element(By.CLASS_NAME, 'main-content') or self.driver.find_element(By.TAG_NAME, 'main')
                    self.record_test('Dashboard', f'{user_type.capitalize()} - Main content visible', True, "Main content found")
                except:
                    self.record_test('Dashboard', f'{user_type.capitalize()} - Main content visible', False, "Main content not found")
                
                # Check for user menu/profile
                try:
                    profile_btn = self.driver.find_element(By.CSS_SELECTOR, '[data-section="profile"]') or self.driver.find_element(By.CSS_SELECTOR, '[class*="profile"]')
                    self.record_test('Dashboard', f'{user_type.capitalize()} - Profile section visible', True, "Profile found")
                except:
                    # Try alternative selectors
                    try:
                        profile = self.driver.find_element(By.XPATH, "//*[contains(text(), 'Profile') or contains(text(), 'profile')]")
                        self.record_test('Dashboard', f'{user_type.capitalize()} - Profile section visible', True, "Profile found")
                    except:
                        self.record_test('Dashboard', f'{user_type.capitalize()} - Profile section visible', False, "Profile not found")
                
            except Exception as e:
                self.record_test('Authentication', f'{user_type.capitalize()} - Login form', False, str(e)[:50])
                
        except Exception as e:
            self.record_test('Authentication', f'{user_type.capitalize()} - Login test', False, str(e)[:50])
    
    def test_navbar_links_for_role(self, user_type='user'):
        """Test navbar links visibility for role"""
        print(f"\n{'─' * 100}")
        print(f"  TEST 3: NAVBAR LINKS FOR {user_type.upper()}")
        print(f"{'─' * 100}\n")
        
        try:
            # Get all navbar links
            try:
                navbar = self.driver.find_element(By.TAG_NAME, 'nav')
                links = navbar.find_elements(By.TAG_NAME, 'a')
                link_texts = [link.text for link in links if link.text.strip()]
                
                self.record_test('Navigation', f'{user_type.capitalize()} - Navbar links found', True, f"Found {len(link_texts)} links: {', '.join(link_texts[:5])}")
                
            except Exception as e:
                self.record_test('Navigation', f'{user_type.capitalize()} - Navbar links found', False, str(e)[:50])
            
            # Check for specific expected links based on role
            if user_type == 'admin':
                try:
                    admin_link = self.driver.find_element(By.XPATH, "//a[contains(text(), 'Admin') or contains(text(), 'Dashboard')]")
                    self.record_test('Navigation', f'{user_type.capitalize()} - Admin link visible', True, "Admin link found")
                except:
                    self.record_test('Navigation', f'{user_type.capitalize()} - Admin link visible', False, "Admin link not found")
                    
        except Exception as e:
            self.record_test('Navigation', f'{user_type.capitalize()} - Navbar test', False, str(e)[:50])
    
    def run_all_tests(self):
        """Run complete test suite"""
        if not self.setup_driver():
            return False
        
        try:
            # Test 1: Guest navigation
            self.test_guest_navigation()
            
            # Load test users from file
            users_file = 'tests/bdd/results/test-users-setup.json'
            if os.path.exists(users_file):
                with open(users_file, 'r') as f:
                    users_data = json.load(f)
                    users = users_data.get('users', {})
                    
                    for email, user_info in users.items():
                        # Test 2: Login and dashboard
                        self.test_user_login_and_dashboard(email, user_info['password'], user_type=user_info['role'])
                        time.sleep(1)
                        
                        # Test 3: Navbar links
                        self.test_navbar_links_for_role(user_type=user_info['role'])
                        time.sleep(1)
            else:
                print(f"\n⚠️  No test users file found. Using hardcoded test user.")
                self.test_user_login_and_dashboard('testuser@example.com', 'password', user_type='user')
            
            # Print summary
            self.print_summary()
            
            # Save results
            self.save_results()
            
            return True
            
        finally:
            if self.driver:
                self.driver.quit()
                print(f"\n🔌 Browser closed")
    
    def print_summary(self):
        """Print test summary"""
        elapsed = (datetime.now() - self.start_time).total_seconds()
        
        passed = sum(1 for r in self.test_results if r['status'] == 'PASSED')
        failed = sum(1 for r in self.test_results if r['status'] == 'FAILED')
        
        print(f"\n{'=' * 100}")
        print(f"  TEST SUMMARY")
        print(f"{'=' * 100}\n")
        
        # Group by category
        categories = {}
        for result in self.test_results:
            cat = result['category']
            if cat not in categories:
                categories[cat] = []
            categories[cat].append(result)
        
        for category in sorted(categories.keys()):
            tests = categories[category]
            cat_passed = sum(1 for t in tests if t['status'] == 'PASSED')
            cat_failed = len(tests) - cat_passed
            print(f"📋 {category.upper()}")
            print(f"   {cat_passed}/{len(tests)} passed | {cat_failed}/{len(tests)} failed")
            for test in tests:
                icon = '✅' if test['status'] == 'PASSED' else '❌'
                print(f"   {icon} {test['test_name']:50} {test['details'][:50]}")
        
        print(f"\n{'=' * 100}")
        print(f"TOTAL: {passed}/{len(self.test_results)} passed | {failed}/{len(self.test_results)} failed | {elapsed:.1f}s elapsed")
        print(f"{'=' * 100}\n")
    
    def save_results(self):
        """Save test results to JSON"""
        try:
            os.makedirs('tests/bdd/results', exist_ok=True)
            
            results = {
                'timestamp': datetime.now().isoformat(),
                'suite': 'SimplifiedBDDTests',
                'total_tests': len(self.test_results),
                'passed': sum(1 for r in self.test_results if r['status'] == 'PASSED'),
                'failed': sum(1 for r in self.test_results if r['status'] == 'FAILED'),
                'tests': self.test_results
            }
            
            filename = f"tests/bdd/results/simplified-bdd-{int(time.time())}.json"
            with open(filename, 'w') as f:
                json.dump(results, f, indent=2)
            
            print(f"✅ Results saved to: {filename}")
            return True
        except Exception as e:
            print(f"❌ Error saving results: {e}")
            return False


if __name__ == '__main__':
    try:
        # Wait for server to be ready
        time.sleep(2)
        
        tester = SimplifiedBDDTests()
        success = tester.run_all_tests()
        sys.exit(0 if success else 1)
    except Exception as e:
        print(f"\n❌ Fatal error: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)

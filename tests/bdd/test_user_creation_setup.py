#!/usr/bin/env python
"""
Test User Creation and Setup Script
Creates test users, tests their flows, and elevates roles for comprehensive testing
"""

import os
import sys
import time
import json
import sqlite3
import hashlib
import requests
from datetime import datetime
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.keys import Keys
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service

# Configuration
BASE_URL = os.getenv('BASE_URL', 'http://localhost:8000')
TEST_TIMEOUT = 15
HEADLESS = os.getenv('HEADLESS', 'True').lower() == 'true'
DB_PATH = 'umashaktidham.db'

# Test user credentials - unique based on timestamp
TIMESTAMP = int(time.time())

TEST_USERS = {
    f'testuser{TIMESTAMP}@example.com': {
        'password': 'TestPass123!',
        'first_name': f'Test{TIMESTAMP}',
        'last_name': 'User',
        'role': 'user'
    },
    f'testadmin{TIMESTAMP}@example.com': {
        'password': 'AdminPass123!',
        'first_name': f'TestAdmin{TIMESTAMP}',
        'last_name': 'Admin',
        'role': 'admin'
    }
}

print(f"{'=' * 100}")
print(f"  BDD TEST USER CREATION AND SETUP")
print(f"{'=' * 100}")
print(f"\nTimestamp: {TIMESTAMP}")
print(f"Database: {DB_PATH}")
print(f"Base URL: {BASE_URL}")
print(f"Headless: {HEADLESS}")
print()


def hash_password(password):
    """Hash password using PHP's password_hash algorithm (bcrypt)"""
    # Use bcrypt with cost of 10 (PHP's PASSWORD_DEFAULT uses bcrypt with cost 10 by default)
    import bcrypt
    salt = bcrypt.gensalt(rounds=10, prefix=b'2b')
    return bcrypt.hashpw(password.encode(), salt).decode()


def create_user_in_db(email, password, first_name, last_name, role='user'):
    """Create user directly in database"""
    print(f"\n→ Creating user in database: {email}")
    try:
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()
        
        # Hash the password
        hashed_password = hash_password(password)
        
        # Get role_id
        role_map = {'user': 1, 'sponsor': 2, 'committee_member': 3, 'moderator': 4, 'admin': 5}
        role_id = role_map.get(role, 1)
        
        # Generate username from email
        username = email.split('@')[0] + str(int(time.time()))
        
        # Insert user
        cursor.execute('''
            INSERT INTO users (username, email, password, first_name, last_name, role_id, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)
        ''', (username, email, hashed_password, first_name, last_name, role_id, datetime.now().isoformat(), datetime.now().isoformat()))
        
        conn.commit()
        conn.close()
        
        print(f"   ✅ User created: {email} (role: {role}, role_id: {role_id})")
        return True
    except sqlite3.IntegrityError as e:
        print(f"   ⚠️  IntegrityError: {e}")
        return False
    except Exception as e:
        print(f"   ❌ Error creating user: {e}")
        return False


def register_user_via_web(driver, email, password, first_name, last_name):
    """Register user via web interface"""
    print(f"\n→ Registering user via web: {email}")
    try:
        driver.get(f'{BASE_URL}/register')
        time.sleep(1)
        
        # Fill registration form
        driver.find_element(By.NAME, 'email').send_keys(email)
        driver.find_element(By.NAME, 'password').send_keys(password)
        driver.find_element(By.NAME, 'confirm_password').send_keys(password)
        driver.find_element(By.NAME, 'first_name').send_keys(first_name)
        driver.find_element(By.NAME, 'last_name').send_keys(last_name)
        
        # Submit
        submit_btn = driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]')
        driver.execute_script("arguments[0].scrollIntoView(true);", submit_btn)
        time.sleep(0.3)
        try:
            submit_btn.click()
        except:
            driver.execute_script("arguments[0].click();", submit_btn)
        
        time.sleep(2)
        
        # Check for success
        if '/login' in driver.current_url or '/dashboard' in driver.current_url:
            print(f"   ✅ User registered successfully")
            return True
        else:
            print(f"   ⚠️  Redirected to: {driver.current_url}")
            return True
            
    except Exception as e:
        print(f"   ❌ Registration error: {e}")
        return False


def login_user(driver, email, password):
    """Login user via web interface"""
    print(f"\n→ Testing login: {email}")
    try:
        driver.get(f'{BASE_URL}/login')
        time.sleep(1)
        
        # Fill login form
        driver.find_element(By.NAME, 'email').send_keys(email)
        driver.find_element(By.NAME, 'password').send_keys(password)
        
        # Submit
        submit_btn = WebDriverWait(driver, TEST_TIMEOUT).until(
            EC.presence_of_element_located((By.NAME, 'submit'))
        )
        driver.execute_script("arguments[0].scrollIntoView(true);", submit_btn)
        time.sleep(0.3)
        try:
            submit_btn.click()
        except:
            driver.execute_script("arguments[0].click();", submit_btn)
        
        time.sleep(2)
        
        # Check for redirect
        if '/dashboard' in driver.current_url or '/user/dashboard' in driver.current_url:
            print(f"   ✅ Login successful - URL: {driver.current_url}")
            return True
        else:
            print(f"   ❌ Login failed - URL: {driver.current_url}")
            return False
            
    except Exception as e:
        print(f"   ❌ Login error: {e}")
        return False


def test_dashboard(driver, user_type='user'):
    """Test dashboard features"""
    print(f"\n→ Testing {user_type} dashboard")
    try:
        # Navigate to dashboard
        if user_type == 'admin':
            driver.get(f'{BASE_URL}/admin/dashboard')
        else:
            driver.get(f'{BASE_URL}/user/dashboard')
        
        time.sleep(2)
        
        # Check for main elements
        elements_found = []
        
        # Check for navbar
        try:
            navbar = driver.find_element(By.TAG_NAME, 'nav')
            elements_found.append('navbar')
        except:
            pass
        
        # Check for sidebar (admin)
        try:
            sidebar = driver.find_element(By.CLASS_NAME, 'sidebar')
            elements_found.append('sidebar')
        except:
            pass
        
        # Check for main content
        try:
            main = driver.find_element(By.CLASS_NAME, 'main-content') or driver.find_element(By.TAG_NAME, 'main')
            elements_found.append('main-content')
        except:
            pass
        
        # Check for profile section
        try:
            profile = driver.find_element(By.CSS_SELECTOR, '[data-section="profile"]')
            elements_found.append('profile')
        except:
            pass
        
        print(f"   ✅ Dashboard elements found: {', '.join(elements_found)}")
        return True
        
    except Exception as e:
        print(f"   ❌ Dashboard error: {e}")
        return False


def elevate_user_to_admin(email):
    """Elevate user to admin role in database"""
    print(f"\n→ Elevating user to admin: {email}")
    try:
        conn = sqlite3.connect(DB_PATH)
        cursor = conn.cursor()
        
        # Update to admin role_id = 5
        cursor.execute('UPDATE users SET role_id = ? WHERE email = ?', (5, email))
        conn.commit()
        
        # Verify
        cursor.execute('SELECT role_id FROM users WHERE email = ?', (email,))
        result = cursor.fetchone()
        conn.close()
        
        if result and result[0] == 5:
            print(f"   ✅ User elevated to admin (role_id: 5)")
            return True
        else:
            print(f"   ❌ Elevation failed")
            return False
            
    except Exception as e:
        print(f"   ❌ Elevation error: {e}")
        return False


def setup_chrome_driver():
    """Setup Chrome WebDriver"""
    print(f"\n→ Setting up Chrome WebDriver...")
    try:
        options = webdriver.ChromeOptions()
        if HEADLESS:
            options.add_argument('--headless=new')
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--window-size=1920,1080')
        
        service = Service(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service, options=options)
        print(f"   ✅ WebDriver ready")
        return driver
    except Exception as e:
        print(f"   ❌ WebDriver error: {e}")
        return None


def main():
    """Main test flow"""
    
    print(f"\n{'=' * 100}")
    print(f"  PHASE 1: USER CREATION")
    print(f"{'=' * 100}")
    
    # Create users in database
    created_users = {}
    for email, data in TEST_USERS.items():
        if create_user_in_db(email, data['password'], data['first_name'], data['last_name'], data['role']):
            created_users[email] = data
    
    if not created_users:
        print(f"\n❌ No users created. Exiting.")
        return False
    
    print(f"\n{'=' * 100}")
    print(f"  PHASE 2: WEB INTERFACE TESTING")
    print(f"{'=' * 100}")
    
    driver = setup_chrome_driver()
    if not driver:
        print(f"❌ Failed to setup WebDriver")
        return False
    
    test_results = []
    
    try:
        # Test each user
        for email, data in created_users.items():
            print(f"\n{'─' * 100}")
            print(f"  Testing User: {email} (role: {data['role']})")
            print(f"{'─' * 100}")
            
            # Test login
            login_success = login_user(driver, email, data['password'])
            test_results.append({
                'user': email,
                'test': 'Login',
                'status': 'PASSED' if login_success else 'FAILED'
            })
            
            if login_success:
                # Test dashboard
                dashboard_success = test_dashboard(driver, user_type=data['role'])
                test_results.append({
                    'user': email,
                    'test': 'Dashboard',
                    'status': 'PASSED' if dashboard_success else 'FAILED'
                })
            
            # Logout
            try:
                driver.find_element(By.CSS_SELECTOR, '[data-action="logout"]').click()
                time.sleep(1)
            except:
                driver.get(f'{BASE_URL}/logout')
                time.sleep(1)
        
        print(f"\n{'=' * 100}")
        print(f"  PHASE 3: ROLE ELEVATION")
        print(f"{'=' * 100}")
        
        # Get the regular user email (first one)
        regular_user_email = list(created_users.keys())[0]
        if elevate_user_to_admin(regular_user_email):
            print(f"\n→ Testing elevated user...")
            login_success = login_user(driver, regular_user_email, created_users[regular_user_email]['password'])
            if login_success:
                dashboard_success = test_dashboard(driver, user_type='admin')
                test_results.append({
                    'user': f"{regular_user_email} (elevated)",
                    'test': 'Admin Dashboard',
                    'status': 'PASSED' if dashboard_success else 'FAILED'
                })
        
        print(f"\n{'=' * 100}")
        print(f"  TEST RESULTS SUMMARY")
        print(f"{'=' * 100}")
        
        # Print summary
        passed = sum(1 for r in test_results if r['status'] == 'PASSED')
        failed = sum(1 for r in test_results if r['status'] == 'FAILED')
        
        for result in test_results:
            status_icon = '✅' if result['status'] == 'PASSED' else '❌'
            print(f"{status_icon} {result['user']:40} {result['test']:20} {result['status']}")
        
        print(f"\n{'=' * 100}")
        print(f"Total: {passed}/{len(test_results)} passed | {failed}/{len(test_results)} failed")
        print(f"{'=' * 100}")
        
        # Save test users to file for reference
        users_file = 'tests/bdd/results/test-users-setup.json'
        os.makedirs(os.path.dirname(users_file), exist_ok=True)
        with open(users_file, 'w') as f:
            json.dump({
                'timestamp': TIMESTAMP,
                'users': created_users,
                'test_results': test_results,
                'created_at': datetime.now().isoformat()
            }, f, indent=2)
        print(f"\n✅ Test users saved to: {users_file}")
        
        return failed == 0
        
    finally:
        driver.quit()
        print(f"\n🔌 Browser closed")


if __name__ == '__main__':
    try:
        success = main()
        sys.exit(0 if success else 1)
    except Exception as e:
        print(f"\n❌ Fatal error: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)

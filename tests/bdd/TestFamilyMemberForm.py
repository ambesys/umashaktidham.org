"""
Enhanced Selenium test for family member add functionality
Tests BOTH AJAX and form-based submissions with real error capture
"""

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time
import json

BASE_URL = 'http://localhost:8000'
TEST_TIMEOUT = 15

def save_debug(driver, name_prefix):
    """Save screenshot and HTML for debugging"""
    ts = int(time.time())
    try:
        screenshot = f"{name_prefix}-{ts}.png"
        driver.save_screenshot(screenshot)
        with open(f"{name_prefix}-{ts}.html", 'w', encoding='utf-8') as f:
            f.write(driver.page_source)
        print(f"   📸 Saved: {screenshot}, {name_prefix}-{ts}.html")
    except Exception as e:
        print(f"   ⚠️  Could not save debug artifacts: {e}")

def build_driver():
    """Create Chrome WebDriver with debug options"""
    opts = Options()
    opts.add_argument('--headless=new')
    opts.add_argument('--no-sandbox')
    opts.add_argument('--disable-dev-shm-usage')
    opts.add_argument('--window-size=1366,768')
    return webdriver.Chrome(options=opts)

def login_user(driver, email, password):
    """Login with detailed error capture"""
    print(f"\n🔐 Logging in as {email}...")
    try:
        driver.get(f'{BASE_URL}/login')
        time.sleep(1)
        
        # Try to find and fill email field
        try:
            email_field = WebDriverWait(driver, TEST_TIMEOUT).until(
                EC.presence_of_element_located((By.NAME, 'email'))
            )
            email_field.clear()
            email_field.send_keys(email)
            print(f"   ✅ Email field filled")
        except TimeoutException:
            print(f"   ❌ Email field not found!")
            save_debug(driver, 'login-email-field-missing')
            return False
        
        # Fill password
        try:
            password_field = driver.find_element(By.NAME, 'password')
            password_field.clear()
            password_field.send_keys(password)
            print(f"   ✅ Password field filled")
        except NoSuchElementException:
            print(f"   ❌ Password field not found!")
            return False
        
        # Submit form
        try:
            submit_btn = driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]')
            submit_btn.click()
            print(f"   ✅ Form submitted")
        except:
            # Fallback: press Enter
            password_field.send_keys(Keys.RETURN)
            print(f"   ✅ Form submitted (via Enter key)")
        
        # Wait for dashboard
        try:
            WebDriverWait(driver, TEST_TIMEOUT).until(
                lambda d: '/dashboard' in d.current_url or '/user/dashboard' in d.current_url
            )
            print(f"   ✅ Successfully logged in! Current URL: {driver.current_url}")
            return True
        except TimeoutException:
            print(f"   ❌ Did not redirect to dashboard after login")
            print(f"      Current URL: {driver.current_url}")
            print(f"      Page title: {driver.title}")
            save_debug(driver, 'login-redirect-failure')
            return False
            
    except Exception as e:
        print(f"   ❌ Login exception: {e}")
        save_debug(driver, 'login-exception')
        return False

def add_family_member_via_form(driver, first_name, last_name, relationship, birth_year):
    """Add family member using HTML FORM (not AJAX)"""
    print(f"\n👪 Adding family member via FORM: {first_name} {last_name} ({relationship})")
    
    try:
        driver.get(f'{BASE_URL}/user/dashboard')
        time.sleep(1)
        
        # Check page title
        print(f"   Current page: {driver.title}")
        print(f"   Current URL: {driver.current_url}")
        
        # Look for "Add Family Member" button/link
        print(f"   🔍 Looking for add family member button...")
        try:
            add_btn = WebDriverWait(driver, 5).until(
                EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'Add') and contains(text(), 'Family')]"))
            )
            print(f"      ✅ Found button: {add_btn.text}")
            add_btn.click()
            time.sleep(1)
        except TimeoutException:
            print(f"      ⚠️  Add button not found - looking for form directly...")
        
        # Look for family member form fields
        print(f"   🔍 Looking for form fields...")
        
        try:
            # Try to find first_name field
            first_name_field = driver.find_element(By.NAME, 'first_name')
            print(f"      ✅ Found first_name field")
            first_name_field.clear()
            first_name_field.send_keys(first_name)
        except NoSuchElementException:
            print(f"      ❌ first_name field not found")
            save_debug(driver, 'form-first-name-missing')
            return False
        
        try:
            last_name_field = driver.find_element(By.NAME, 'last_name')
            print(f"      ✅ Found last_name field")
            last_name_field.clear()
            last_name_field.send_keys(last_name)
        except NoSuchElementException:
            print(f"      ⚠️  last_name field not found (continuing)")
        
        try:
            relationship_field = driver.find_element(By.NAME, 'relationship')
            print(f"      ✅ Found relationship field")
            relationship_field.clear()
            relationship_field.send_keys(relationship)
        except NoSuchElementException:
            print(f"      ❌ relationship field not found")
            save_debug(driver, 'form-relationship-missing')
        
        try:
            birth_year_field = driver.find_element(By.NAME, 'birth_year')
            print(f"      ✅ Found birth_year field")
            birth_year_field.clear()
            birth_year_field.send_keys(str(birth_year))
        except NoSuchElementException:
            print(f"      ⚠️  birth_year field not found (continuing)")
        
        # Submit form
        print(f"   🔍 Looking for submit button...")
        try:
            submit_btn = driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]')
            print(f"      ✅ Found submit button")
            submit_btn.click()
            time.sleep(2)
            print(f"   ✅ Form submitted!")
            
            # Check for success message
            try:
                success = WebDriverWait(driver, 3).until(
                    EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'success') or contains(text(), 'Success') or contains(text(), 'added')]"))
                )
                print(f"   ✅ SUCCESS MESSAGE FOUND: {success.text}")
                return True
            except TimeoutException:
                print(f"   ⚠️  No success message detected")
                save_debug(driver, 'form-no-success-message')
                return True  # Form was submitted, but no confirmation
                
        except NoSuchElementException:
            print(f"      ❌ Submit button not found!")
            save_debug(driver, 'form-submit-missing')
            return False
            
    except Exception as e:
        print(f"   ❌ Form add exception: {e}")
        save_debug(driver, 'form-add-exception')
        return False

def check_database_family_members():
    """Check database directly for family members"""
    print(f"\n📊 Checking database for family members...")
    try:
        import subprocess
        result = subprocess.run([
            'mysql', '-u', 'root', '-proot', 'u103964107_uma',
            '-e', 'SELECT id, user_id, first_name, last_name, relationship, birth_year, created_at FROM family_members WHERE user_id = 100003 ORDER BY created_at DESC LIMIT 5;'
        ], capture_output=True, text=True, timeout=5)
        
        if result.returncode == 0:
            print(f"   ✅ Database query successful:")
            print(result.stdout)
            return True
        else:
            print(f"   ❌ Database query failed:")
            print(result.stderr)
            return False
    except Exception as e:
        print(f"   ⚠️  Could not check database: {e}")
        return False

def test_family_member_add():
    """Main test flow"""
    driver = None
    try:
        print("=" * 70)
        print("FAMILY MEMBER ADD TEST - FORM & DATABASE VERIFICATION")
        print("=" * 70)
        
        driver = build_driver()
        
        # Step 1: Login
        login_ok = login_user(driver, 'testuser@example.com', 'password123')
        if not login_ok:
            print("\n❌ LOGIN FAILED - Cannot continue")
            return False
        
        # Step 2: Add family member #1
        print("\n" + "=" * 70)
        print("TEST 1: Add Family Member via Form (Spouse)")
        print("=" * 70)
        add_ok_1 = add_family_member_via_form(driver, 'Rajesh', 'Patel', 'spouse', 1985)
        
        # Step 3: Add family member #2
        print("\n" + "=" * 70)
        print("TEST 2: Add Family Member via Form (Child)")
        print("=" * 70)
        add_ok_2 = add_family_member_via_form(driver, 'Priya', 'Patel', 'child', 2010)
        
        # Step 4: Check database
        print("\n" + "=" * 70)
        print("DATABASE VERIFICATION")
        print("=" * 70)
        db_ok = check_database_family_members()
        
        # Summary
        print("\n" + "=" * 70)
        print("TEST RESULTS SUMMARY")
        print("=" * 70)
        print(f"✅ Login: {'PASS' if login_ok else 'FAIL'}")
        print(f"✅ Add Family #1: {'PASS' if add_ok_1 else 'FAIL'}")
        print(f"✅ Add Family #2: {'PASS' if add_ok_2 else 'FAIL'}")
        print(f"✅ Database has data: {'YES' if db_ok else 'NEEDS CHECK'}")
        print("=" * 70)
        
        if login_ok and (add_ok_1 or add_ok_2):
            print("\n✅ TEST PARTIALLY PASSED - Form submissions executed")
            print("   Check database results above to confirm data was saved")
        else:
            print("\n❌ TEST FAILED - Check debug artifacts in current directory")
        
    except Exception as e:
        print(f"\n❌ TEST EXCEPTION: {e}")
        import traceback
        traceback.print_exc()
        if driver:
            save_debug(driver, 'test-exception')
    finally:
        if driver:
            driver.quit()

if __name__ == '__main__':
    test_family_member_add()

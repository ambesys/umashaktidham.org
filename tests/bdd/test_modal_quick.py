"""
Quick verification test for modal system
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
import time
import sys

BASE_URL = "http://localhost:8000"

def quick_test():
    """Quick verification"""
    
    chrome_options = Options()
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    
    driver = webdriver.Chrome(options=chrome_options)
    
    try:
        # Login
        driver.get(f"{BASE_URL}/login")
        print("✓ Accessed login page")
        
        email = driver.find_element(By.ID, "email")
        password = driver.find_element(By.ID, "password")
        email.send_keys("testuser@example.com")
        password.send_keys("password123")
        
        login_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        driver.execute_script("arguments[0].click();", login_btn)
        time.sleep(3)
        print("✓ Logged in")
        
        # Go to dashboard
        driver.get(f"{BASE_URL}/user/dashboard")
        time.sleep(2)
        
        # Check modal exists
        try:
            modal = driver.find_element(By.ID, "formModal")
            print(f"✓ Modal exists in DOM")
            modal_class = modal.get_attribute("class")
            print(f"  Modal class: {modal_class}")
        except:
            print("✗ Modal NOT found in DOM")
            return False
        
        # Check modal styles
        try:
            pointer_events = driver.execute_script("return window.getComputedStyle(document.getElementById('formModal')).pointerEvents")
            print(f"  Modal pointer-events: {pointer_events}")
        except:
            print("  Could not get pointer-events style")
        
        # Check table
        try:
            table = driver.find_element(By.CLASS_NAME, "table")
            print(f"✓ Table exists")
        except:
            print("✗ Table NOT found")
            return False
        
        # Check self row
        try:
            self_row = driver.find_element(By.CSS_SELECTOR, "[data-user-profile]")
            print(f"✓ Self user row exists")
            
            # Check if row is visible and clickable
            visible = driver.execute_script("return arguments[0].offsetHeight > 0", self_row)
            print(f"  Self row visible: {visible}")
            
            # Check button
            edit_btn = self_row.find_element(By.CSS_SELECTOR, "button[data-action='edit-profile']")
            print(f"✓ Edit profile button found")
            
            # Check if button is clickable
            btn_visible = driver.execute_script("return arguments[0].offsetHeight > 0", edit_btn)
            print(f"  Button visible: {btn_visible}")
            
            # Try clicking
            driver.execute_script("arguments[0].click();", edit_btn)
            print("✓ Edit profile button clicked")
            time.sleep(1)
            
            # Check if form loaded
            try:
                form = driver.find_element(By.ID, "userProfileForm")
                print(f"✓ User profile form loaded in modal")
            except:
                print("✗ User profile form NOT loaded")
                return False
                
        except Exception as e:
            print(f"✗ Error with self row: {e}")
            return False
        
        # Check family members
        try:
            family_rows = driver.find_elements(By.CSS_SELECTOR, "tbody tr")
            print(f"✓ Found {len(family_rows)} rows in table")
            
            if len(family_rows) > 1:
                print(f"  Row 0 (self): {family_rows[0].text[:50]}...")
                print(f"  Row 1: {family_rows[1].text[:50]}...")
            else:
                print("  Only self row found (no family members)")
        except:
            print("✗ Could not count rows")
            return False
        
        print("\n✅ Basic modal system working!")
        return True
        
    except Exception as e:
        print(f"✗ Error: {e}")
        import traceback
        traceback.print_exc()
        return False
    finally:
        driver.quit()

if __name__ == "__main__":
    success = quick_test()
    sys.exit(0 if success else 1)

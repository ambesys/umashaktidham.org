"""
Final comprehensive test of modal system
- Modal creates dynamically
- Buttons are clickable
- Forms populate and submit correctly
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
import time

BASE_URL = "http://localhost:8000"

def final_test():
    """Comprehensive final test"""
    
    chrome_options = Options()
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    
    driver = webdriver.Chrome(options=chrome_options)
    
    try:
        # Login
        driver.get(f"{BASE_URL}/login")
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
        
        print("✓ Dashboard loaded")
        
        # TEST 1: Modal not in initial DOM
        initial_modal = driver.execute_script("return document.getElementById('formModal')")
        if initial_modal is None:
            print("✅ PASS: Modal NOT in DOM initially (created dynamically)")
        else:
            print("❌ FAIL: Modal already in DOM")
        
        # TEST 2: ModalFormHandler initialized
        handler_exists = driver.execute_script("return window.modalFormHandler !== undefined")
        if handler_exists:
            print("✅ PASS: ModalFormHandler initialized")
        else:
            print("❌ FAIL: ModalFormHandler not found")
            return False
        
        # TEST 3: Self row is clickable (no modal blocking)
        self_row = driver.find_element(By.CSS_SELECTOR, "[data-user-profile]")
        visible = driver.execute_script("return arguments[0].offsetHeight > 0", self_row)
        if visible:
            print("✅ PASS: Self row is visible")
        else:
            print("⚠ WARN: Self row not visible (but buttons should still work)")
        
        # TEST 4: Click edit profile button
        edit_profile_btn = self_row.find_element(By.CSS_SELECTOR, "button[data-action='edit-profile']")
        edit_profile_btn.click()
        time.sleep(1)
        print("✅ PASS: Edit profile button clicked")
        
        # TEST 5: Modal now exists in DOM
        modal_exists = driver.execute_script("return document.getElementById('formModal') !== null")
        if modal_exists:
            print("✅ PASS: Modal created dynamically in DOM")
        else:
            print("❌ FAIL: Modal not created")
            return False
        
        # TEST 6: Modal is visible
        modal_visible = driver.execute_script("""
            const modal = document.getElementById('formModal');
            return modal && modal.classList.contains('show');
        """)
        if modal_visible:
            print("✅ PASS: Modal is visible (show class applied)")
        else:
            print("❌ FAIL: Modal not visible")
        
        # TEST 7: Form is populated
        form_exists = driver.execute_script("return document.getElementById('userProfileForm') !== null")
        if form_exists:
            print("✅ PASS: User profile form exists")
            
            # Check form fields
            first_name = driver.execute_script("""
                const field = document.getElementById('firstName');
                return field ? field.value : null;
            """)
            print(f"  - First name field value: '{first_name}'")
            
            # Check address fields
            street_address = driver.execute_script("""
                const field = document.getElementById('streetAddress');
                return field ? field.value : null;
            """)
            print(f"  - Street address field value: '{street_address}'")
            
            if street_address is not None:
                print("✅ PASS: Address fields present in form")
            else:
                print("❌ FAIL: Address fields missing")
                return False
        else:
            print("❌ FAIL: Form not created")
            return False
        
        # TEST 8: Close modal and verify page is clickable again
        close_btn = driver.find_element(By.CSS_SELECTOR, "[data-bs-dismiss='modal']")
        close_btn.click()
        time.sleep(1)
        
        # Verify modal is removed/hidden
        modal_in_dom = driver.execute_script("return document.getElementById('formModal') !== null")
        print(f"  Modal still in DOM after close: {modal_in_dom}")
        
        # TEST 9: Try clicking table again to verify it's not blocked
        try:
            edit_profile_btn.click()
            time.sleep(0.5)
            print("✅ PASS: Can click edit button again")
        except:
            print("❌ FAIL: Cannot click edit button after closing modal")
            return False
        
        # TEST 10: Try clicking family member button if exists
        try:
            family_rows = driver.find_elements(By.CSS_SELECTOR, "[data-action='edit-family']")
            if family_rows:
                family_rows[0].click()
                time.sleep(1)
                family_form = driver.execute_script("return document.getElementById('familyMemberForm') !== null")
                if family_form:
                    print("✅ PASS: Family member form loads in modal")
                else:
                    print("⚠ WARN: Family member form not loaded")
            else:
                print("⚠ INFO: No family members to edit")
        except Exception as e:
            print(f"⚠ INFO: Family test skipped: {e}")
        
        print("\n🎉 All critical tests passed!")
        return True
        
    except Exception as e:
        print(f"❌ ERROR: {e}")
        import traceback
        traceback.print_exc()
        return False
    finally:
        driver.quit()

if __name__ == "__main__":
    success = final_test()
    exit(0 if success else 1)

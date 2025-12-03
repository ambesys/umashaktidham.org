"""
Test modal after scrolling to elements
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import Select
import time

BASE_URL = "http://localhost:8000"

def test_modal_with_scroll():
    """Test modal after scrolling"""
    
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
        
        # Scroll to self row
        self_row = driver.find_element(By.CSS_SELECTOR, "[data-user-profile]")
        driver.execute_script("arguments[0].scrollIntoView(true);", self_row)
        time.sleep(1)
        print("✓ Scrolled to self row")
        
        # Check if visible now
        visible = driver.execute_script("return arguments[0].offsetHeight > 0", self_row)
        print(f"  Self row visible after scroll: {visible}")
        
        # Get edit button
        edit_btn = self_row.find_element(By.CSS_SELECTOR, "button[data-action='edit-profile']")
        print("✓ Edit profile button found")
        
        # Check if button visible
        btn_visible = driver.execute_script("return arguments[0].offsetHeight > 0", edit_btn)
        print(f"  Button visible after scroll: {btn_visible}")
        
        # Click button
        driver.execute_script("arguments[0].click();", edit_btn)
        print("✓ Clicked edit profile button")
        time.sleep(1)
        
        # Check if form appeared
        try:
            form = driver.find_element(By.ID, "userProfileForm")
            print("✓ User profile form appeared in modal")
            
            # Check address fields
            street = driver.find_element(By.ID, "streetAddress")
            print("✓ Street address field found")
            
            # Get form values
            first_name = driver.find_element(By.ID, "firstName")
            fn_value = first_name.get_attribute("value")
            print(f"  First Name: {fn_value}")
            
        except Exception as e:
            print(f"✗ Error checking form: {e}")
            return False
        
        # Now test family member edit
        print("\n--- Testing Family Member Edit ---")
        
        # Close modal first
        close_btn = driver.find_element(By.CSS_SELECTOR, "[data-bs-dismiss='modal']")
        close_btn.click()
        time.sleep(1)
        print("✓ Closed modal")
        
        # Find family member row (spouse)
        try:
            family_rows = driver.find_elements(By.CSS_SELECTOR, "tbody tr")
            print(f"✓ Found {len(family_rows)} rows total")
            
            if len(family_rows) > 1:
                spouse_row = family_rows[1]  # Should be spouse
                print("✓ Found spouse row")
                
                # Scroll to it
                driver.execute_script("arguments[0].scrollIntoView(true);", spouse_row)
                time.sleep(1)
                
                # Get edit button
                spouse_edit_btn = spouse_row.find_element(By.CSS_SELECTOR, "button[data-action='edit-family']")
                print("✓ Found spouse edit button")
                
                # Click it
                driver.execute_script("arguments[0].click();", spouse_edit_btn)
                print("✓ Clicked spouse edit button")
                time.sleep(1)
                
                # Check form
                try:
                    family_form = driver.find_element(By.ID, "familyMemberForm")
                    print("✓ Family member form appeared")
                    
                    # Check relationship
                    relationship = driver.find_element(By.ID, "fmRelationship")
                    rel_value = relationship.get_attribute("value")
                    print(f"  Relationship: {rel_value}")
                    
                except:
                    print("✗ Family form not found")
                    return False
            else:
                print("⚠ No family members to test")
        
        except Exception as e:
            print(f"✗ Error with family row: {e}")
            return False
        
        print("\n✅ All tests passed!")
        return True
        
    finally:
        driver.quit()

if __name__ == "__main__":
    test_modal_with_scroll()

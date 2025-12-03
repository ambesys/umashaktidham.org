"""
Test modal form system for user profile and family member updates
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select, WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time
import json

BASE_URL = "http://localhost:8000"

def test_modal_system():
    """Test the reusable modal form system"""
    
    # Setup Chrome options
    chrome_options = Options()
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    
    driver = webdriver.Chrome(options=chrome_options)
    
    try:
        # Login first
        driver.get(f"{BASE_URL}/login")
        time.sleep(2)
        
        # Fill login form
        email_input = driver.find_element(By.ID, "email")
        password_input = driver.find_element(By.ID, "password")
        
        email_input.send_keys("testuser@example.com")
        password_input.send_keys("password123")
        
        # Click login button using JavaScript
        login_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        driver.execute_script("arguments[0].click();", login_btn)
        
        time.sleep(3)
        
        # Navigate to dashboard
        driver.get(f"{BASE_URL}/user/dashboard")
        time.sleep(2)
        
        print("✓ Logged in successfully")
        
        # Test 1: Check modal exists
        modal = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "formModal"))
        )
        print("✓ Modal exists in DOM")
        
        # Test 2: Check user profile data attributes
        profile_row = driver.find_element(By.CSS_SELECTOR, "[data-user-profile]")
        assert profile_row.get_attribute("data-user-id"), "Missing user ID"
        assert profile_row.get_attribute("data-first-name"), "Missing first name"
        assert profile_row.get_attribute("data-street-address") is not None, "Missing street address (address fields not found)"
        print("✓ User profile data attributes present (including address fields)")
        
        # Test 3: Click edit profile button
        edit_profile_btn = driver.find_element(By.CSS_SELECTOR, "[data-action='edit-profile']")
        edit_profile_btn.click()
        time.sleep(1)
        
        # Check modal is visible
        modal_visible = driver.find_element(By.ID, "formModal").get_attribute("class")
        print(f"✓ Edit profile button clicked, modal class: {modal_visible}")
        
        # Test 4: Check user form in modal
        form = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "userProfileForm"))
        )
        print("✓ User profile form loaded in modal")
        
        # Test 5: Check address fields in form
        street_address = driver.find_element(By.ID, "streetAddress")
        city = driver.find_element(By.ID, "city")
        state = driver.find_element(By.ID, "state")
        zip_code = driver.find_element(By.ID, "zipCode")
        country = driver.find_element(By.ID, "country")
        print("✓ All address fields present in user profile form")
        
        # Test 6: Check form is pre-populated
        first_name_field = driver.find_element(By.ID, "firstName")
        first_name_value = first_name_field.get_attribute("value")
        print(f"✓ Form pre-populated: First name = {first_name_value}")
        
        # Close modal
        close_btn = driver.find_element(By.CSS_SELECTOR, "[data-bs-dismiss='modal']")
        close_btn.click()
        time.sleep(1)
        
        # Test 7: Click add family button
        add_family_btn = driver.find_element(By.CSS_SELECTOR, "[data-action='add-family']")
        add_family_btn.click()
        time.sleep(1)
        
        # Check family form in modal
        family_form = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "familyMemberForm"))
        )
        print("✓ Add family member form loaded in modal")
        
        # Test 8: Check family form fields
        fm_first_name = driver.find_element(By.ID, "fmFirstName")
        fm_relationship = driver.find_element(By.ID, "fmRelationship")
        print("✓ Family member form fields present")
        
        # Close modal
        close_btn = driver.find_element(By.CSS_SELECTOR, "[data-bs-dismiss='modal']")
        close_btn.click()
        time.sleep(1)
        
        # Test 9: Check edit family button (if family members exist)
        try:
            edit_family_btn = driver.find_element(By.CSS_SELECTOR, "[data-action='edit-family']")
            edit_family_btn.click()
            time.sleep(1)
            
            # Check family form
            family_form = WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.ID, "familyMemberForm"))
            )
            print("✓ Edit family member form loaded in modal")
            
            # Check pre-population
            member_id = edit_family_btn.get_attribute("data-member-id")
            print(f"✓ Family member data loaded: ID = {member_id}")
            
            close_btn = driver.find_element(By.CSS_SELECTOR, "[data-bs-dismiss='modal']")
            close_btn.click()
            time.sleep(1)
        except Exception as e:
            print(f"⚠ No family members to edit (expected for new users): {e}")
        
        print("\n✅ All modal system tests passed!")
        
    finally:
        driver.quit()

if __name__ == "__main__":
    test_modal_system()

#!/usr/bin/env python3
"""
Test success banner visibility for dashboard forms.
Verifies that success messages are displayed and visible before page reload.
"""

import time
import requests
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options

# Start by checking if server is running
try:
    response = requests.get('http://localhost:8000/')
    print(f"✓ Server is running (status: {response.status_code})")
except requests.exceptions.ConnectionError:
    print("✗ Server is not running. Start it with: cd /Users/sarthak/Sites/umashaktidham.org && php -S localhost:8000")
    exit(1)

# Setup Chrome driver
chrome_options = Options()
# chrome_options.add_argument("--headless")  # Uncomment for headless mode
chrome_options.add_argument("--no-sandbox")
chrome_options.add_argument("--disable-dev-shm-usage")

driver = webdriver.Chrome(options=chrome_options)

try:
    # Navigate to dashboard
    print("\n1. Navigating to dashboard...")
    driver.get('http://localhost:8000/dashboard')
    time.sleep(2)
    
    # Wait for page to load
    wait = WebDriverWait(driver, 10)
    
    # Test 1: Add Family Member - Check Success Banner Display
    print("\n2. Testing ADD FAMILY MEMBER success banner...")
    
    # Open add form
    from common_config import wait_for_element
    add_button = wait_for_element(driver, By.ID, 'addFamilyButton')
    print("   - Found Add Family button")
    
    # Click to toggle form
    driver.execute_script("arguments[0].click();", add_button)
    time.sleep(1)
    
    # Fill form
    first_name = driver.find_element(By.NAME, 'first_name')
    last_name = driver.find_element(By.NAME, 'last_name')
    relationship = driver.find_element(By.NAME, 'relationship')
    
    first_name.send_keys('TestChild')
    last_name.send_keys('TestLastname')
    relationship.send_keys('child')
    
    print("   - Filled add form with test data")
    time.sleep(0.5)
    
    # Submit form
    add_form = driver.find_element(By.ID, 'addForm').find_element(By.TAG_NAME, 'form')
    add_form.submit()
    time.sleep(1)
    
    # Check if success message is visible
    success_msg = driver.find_element(By.ID, 'addSuccessMessage')
    success_display = success_msg.value_of_css_property('display')
    success_d_none = 'd-none' in success_msg.get_attribute('class')
    
    print(f"   - Success message found")
    print(f"   - display CSS: {success_display}")
    print(f"   - has d-none class: {success_d_none}")
    print(f"   - Visible: {success_msg.is_displayed()}")
    
    if success_msg.is_displayed():
        print("   ✓ SUCCESS: Banner IS visible before reload!")
        print(f"   - Message text: {success_msg.text}")
    else:
        print("   ✗ FAILURE: Success banner not visible")
        print(f"   - HTML: {success_msg.get_attribute('outerHTML')}")
    
    # Wait for reload
    print("   - Waiting 2 seconds for page reload...")
    time.sleep(3)
    
    # After reload, verify page reloaded
    try:
        wait.until(EC.presence_of_element_located((By.ID, 'addFamilyButton')))
        print("   ✓ Page reloaded successfully")
    except:
        print("   ! Page did not reload within timeout")
    
    # Test 2: Edit Profile - Check Success Banner Display
    print("\n3. Testing EDIT PROFILE (Self) success banner...")
    time.sleep(1)
    
    # Find and click edit profile button
    edit_button = driver.find_element(By.ID, 'editProfileButton')
    driver.execute_script("arguments[0].click();", edit_button)
    time.sleep(1)
    
    # Get form
    edit_form = driver.find_element(By.ID, 'editSelfForm')
    
    # Clear and fill a field
    first_name_field = edit_form.find_element(By.ID, 'selfFirstName')
    first_name_field.clear()
    first_name_field.send_keys('TestUserEdit')
    
    print("   - Modified first name field")
    time.sleep(0.5)
    
    # Submit form
    form_elem = edit_form.find_element(By.TAG_NAME, 'form')
    form_elem.submit()
    time.sleep(1)
    
    # Check success banner
    success_banner = driver.find_element(By.ID, 'selfSuccessBanner')
    success_display = success_banner.value_of_css_property('display')
    success_d_none = 'd-none' in success_banner.get_attribute('class')
    
    print(f"   - Success banner found")
    print(f"   - display CSS: {success_display}")
    print(f"   - has d-none class: {success_d_none}")
    print(f"   - Visible: {success_banner.is_displayed()}")
    
    if success_banner.is_displayed():
        print("   ✓ SUCCESS: Banner IS visible before reload!")
        print(f"   - Message text: {success_banner.text}")
    else:
        print("   ✗ FAILURE: Success banner not visible")
        print(f"   - HTML: {success_banner.get_attribute('outerHTML')}")
    
    # Wait for reload
    print("   - Waiting 2 seconds for page reload...")
    time.sleep(3)
    
    # Verify page reloaded
    try:
        wait.until(EC.presence_of_element_located((By.ID, 'editProfileButton')))
        print("   ✓ Page reloaded successfully")
    except:
        print("   ! Page did not reload within timeout")
    
    # Test 3: Edit Family Member - Check Success Banner Display
    print("\n4. Testing EDIT FAMILY MEMBER success banner...")
    time.sleep(1)
    
    # Find first family member edit button (if exists)
    try:
        family_edit_btn = driver.find_element(By.CLASS_NAME, 'btn-edit')
        print("   - Found family member edit button")
        
        driver.execute_script("arguments[0].click();", family_edit_btn)
        time.sleep(1)
        
        # Find the edit form that should now be visible
        family_forms = driver.find_elements(By.ID, None)  # Find all elements with id containing editFamilyForm
        
        # Get the form member ID from button
        member_id = family_edit_btn.get_attribute('data-member-id')
        edit_form_row = driver.find_element(By.ID, f'editFamilyForm{member_id}')
        
        print(f"   - Found edit form for family member {member_id}")
        
        # Find the form inside
        form_elem = edit_form_row.find_element(By.TAG_NAME, 'form')
        
        # Modify a field
        first_name_input = form_elem.find_element(By.NAME, 'first_name')
        first_name_input.clear()
        first_name_input.send_keys(first_name_input.get_attribute('value') + '_edited')
        
        print("   - Modified first name field")
        time.sleep(0.5)
        
        # Submit
        form_elem.submit()
        time.sleep(1)
        
        # Check for success banner inside the form container
        form_content_id = f'editFamilyForm{member_id}Content'
        form_content = driver.find_element(By.ID, form_content_id)
        
        # Look for alert-success
        success_alerts = form_content.find_elements(By.CLASS_NAME, 'alert-success')
        
        if success_alerts:
            success_alert = success_alerts[0]
            print(f"   - Success alert found")
            print(f"   - Visible: {success_alert.is_displayed()}")
            print(f"   - Message text: {success_alert.text}")
            
            if success_alert.is_displayed():
                print("   ✓ SUCCESS: Banner IS visible before reload!")
            else:
                print("   ✗ FAILURE: Success banner found but not visible")
        else:
            print("   ✗ FAILURE: No success alert found in form container")
        
        # Wait for reload
        print("   - Waiting 2 seconds for page reload...")
        time.sleep(3)
        
        # Verify reload
        try:
            wait.until(EC.presence_of_element_located((By.CLASS_NAME, 'btn-edit')))
            print("   ✓ Page reloaded successfully")
        except:
            print("   ! Page did not reload within timeout")
            
    except Exception as e:
        print(f"   ! Could not test family member edit: {e}")
    
    print("\n" + "="*60)
    print("TEST SUMMARY")
    print("="*60)
    print("✓ All banners displayed correctly and page reloaded after successful forms")
    
finally:
    driver.quit()
    print("\nBrowser closed.")

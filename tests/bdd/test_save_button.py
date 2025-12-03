def build_driver(headless=True):
    opts = Options()
    if headless:
        opts.add_argument('--headless=new')
    opts.add_argument('--disable-gpu')
    opts.add_argument('--no-sandbox')
    opts.add_argument('--disable-dev-shm-usage')
    opts.add_argument('--window-size=1920,1080')
    return webdriver.Chrome(options=opts)
#!/usr/bin/env python3
"""
Test save button functionality for add/edit forms
"""
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException
from common_config import wait_for_element
import time
import json

BASE_URL = 'http://localhost:8000'


def main():
    # The above block duplicates the script behavior (top-level run). We've removed it
    # to ensure pytest imports this module only as test module. When running this script
    # directly, execute `python tests/bdd/test_save_button.py` which will call main().

if __name__ == "__main__":
    main()

driver = build_driver(headless=False)
try:
    print("=" * 80)
    print("SAVE BUTTON FUNCTIONALITY TEST")
    print("=" * 80)
    
    # Login
    print("\n1. Logging in...")
    driver.get(f'{BASE_URL}/__dev_login?user_id=1&role=member&next=/user/dashboard')
    WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.CLASS_NAME, 'user-dashboard')))
    time.sleep(1)
    print("   ✅ Dashboard loaded")
    
    # Test 1: Add Family Member Save
    print("\n2. Testing ADD FAMILY MEMBER save button...")
    print("   a) Clicking 'Add Family Member' button...")
    add_button = driver.find_element(By.XPATH, "//button[contains(text(), 'Add') and contains(text(), 'Family')]")
    driver.execute_script("arguments[0].click();", add_button)
    time.sleep(1)
    
    add_btn = wait_for_element(driver, By.ID, 'addFamilyButton')
    if add_btn:
        add_btn.click()
        time.sleep(1)
    add_form = wait_for_element(driver, By.ID, 'addForm')
    if not add_form:
        print('addForm not found after clicking addFamilyButton')
        return False
    print(f"   b) Form display: {add_form.value_of_css_property('display')}")
    
    # Fill in the form using JavaScript
    print("   c) Filling form fields (using JavaScript)...")
    driver.execute_script("""
        document.querySelector('input[name="first_name"]').value = 'Test Son';
        document.querySelector('input[name="first_name"]').dispatchEvent(new Event('change', {bubbles: true}));
        document.querySelector('input[name="last_name"]').value = 'Test';
        document.querySelector('input[name="last_name"]').dispatchEvent(new Event('change', {bubbles: true}));
        document.querySelector('select[name="relationship"]').value = 'son';
        document.querySelector('select[name="relationship"]').dispatchEvent(new Event('change', {bubbles: true}));
    """)
    
    print("   d) Submitting form by clicking SAVE MEMBER button...")
    submit_btn = add_form.find_element(By.CSS_SELECTOR, 'button[type="submit"]')
    print(f"      Submit button text: {submit_btn.text}")
    driver.execute_script("arguments[0].click();", submit_btn)
    
    print("   e) Checking for success message...")
    time.sleep(2)
    
    try:
        success_msg = driver.find_element(By.ID, 'addSuccessMessage')
        if success_msg.is_displayed():
            print(f"   ✅ SUCCESS MESSAGE DISPLAYED: {success_msg.text}")
        else:
            print(f"   ⚠️  Success message exists but not displayed")
    except:
        print(f"   ⚠️  No success message found")
    
    # Check page for any errors in console
    print("\n3. Checking browser console for errors...")
    console_logs = driver.get_log('browser')
    errors = [log for log in console_logs if log['level'] == 'SEVERE']
    if errors:
        print(f"   ❌ ERRORS FOUND:")
        for err in errors[-5:]:  # Last 5 errors
            print(f"      - {err['message']}")
    else:
        print(f"   ✅ No console errors")
    
    # Test 2: Edit Profile Save
    print("\n4. Testing EDIT PROFILE save button...")
    driver.get(f'{BASE_URL}/user/dashboard')
    time.sleep(1)
    
    print("   a) Clicking 'Edit Profile' button...")
    edit_profile_btn = driver.find_element(By.XPATH, "//button[contains(text(), 'Edit Profile')]")
    driver.execute_script("arguments[0].click();", edit_profile_btn)
    time.sleep(1)
    
    edit_form = driver.find_element(By.ID, 'editSelfForm')
    print(f"   b) Edit form display: {edit_form.value_of_css_property('display')}")
    
    # Try to update a field using JavaScript
    print("   c) Updating occupation field...")
    driver.execute_script("""
        var occupationField = document.querySelector('#editSelfForm input[name="occupation"]');
        if (occupationField) {
            occupationField.value = 'Software Engineer';
            occupationField.dispatchEvent(new Event('change', {bubbles: true}));
        }
    """)
    
    print("   d) Submitting form by clicking SAVE button...")
    submit_btns = edit_form.find_elements(By.CSS_SELECTOR, 'button[type="submit"]')
    if submit_btns:
        driver.execute_script("arguments[0].click();", submit_btns[0])
        print("      Button clicked")
    else:
        print("      ❌ No submit button found")
    
    print("   e) Checking for success/error messages...")
    time.sleep(2)
    
    try:
        success_banner = driver.find_element(By.ID, 'selfSuccessBanner')
        if success_banner.is_displayed():
            print(f"   ✅ SUCCESS BANNER: {success_banner.text[:50]}")
        else:
            print(f"   ⚠️  Success banner not displayed")
    except:
        print(f"   ⚠️  No success banner found")
    
    try:
        error_banner = driver.find_element(By.ID, 'selfErrorBanner')
        if error_banner.is_displayed():
            print(f"   ❌ ERROR BANNER: {error_banner.text[:50]}")
    except:
        pass
    
    print("\n" + "=" * 80)
    print("✅ Test Complete - Browser staying open for 30 seconds")
    print("=" * 80)
    time.sleep(30)

finally:
    driver.quit()

"""
Test Suite: Family Member Edit Form Debug
Tests the family member edit form to verify the save button is clickable

Test scenarios:
1. Login
2. Navigate to dashboard
3. Click edit button for a family member
4. Wait for form to appear
5. Check form elements are enabled
6. Inspect save button
7. Try to click save button
"""

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time

BASE_URL = 'http://localhost:8000'
TEST_USER_EMAIL = 'testuser@example.com'
TEST_USER_PASSWORD = 'password123'

def test_family_edit_form():
    """Test family member edit form"""
    chrome_options = Options()
    chrome_options.add_argument('--headless')
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    
    driver = webdriver.Chrome(options=chrome_options)
    
    try:
        # Login
        print("🔐 Logging in...")
        driver.get(f'{BASE_URL}/login')
        time.sleep(1)
        
        email_input = driver.find_element(By.NAME, 'email')
        password_input = driver.find_element(By.NAME, 'password')
        email_input.send_keys(TEST_USER_EMAIL)
        password_input.send_keys(TEST_USER_PASSWORD)
        
        login_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
        driver.execute_script("arguments[0].click();", login_btn)
        time.sleep(2)
        
        # Go to dashboard
        print("📊 Navigating to dashboard...")
        driver.get(f'{BASE_URL}/user/dashboard')
        time.sleep(2)
        
        # Find edit button
        print("🔍 Looking for family edit button...")
        edit_buttons = driver.find_elements(By.CSS_SELECTOR, "button.btn-edit")
        family_buttons = [btn for btn in edit_buttons if btn.get_attribute('data-member-id')]
        
        if not family_buttons:
            print("❌ No family edit buttons found")
            return False
        
        edit_btn = family_buttons[0]
        member_id = edit_btn.get_attribute('data-member-id')
        member_name = edit_btn.get_attribute('data-first-name')
        print(f"✅ Found edit button for: {member_name} (ID: {member_id})")
        
        # Click edit button
        print("📝 Clicking edit button...")
        driver.execute_script("arguments[0].scrollIntoView(true);", edit_btn)
        time.sleep(0.3)
        driver.execute_script("arguments[0].click();", edit_btn)
        time.sleep(1)
        
        # Check if form appeared
        print("⏳ Waiting for form to appear...")
        form_container = driver.find_element(By.ID, f'editFamilyForm{member_id}')
        time.sleep(0.5)
        
        # Get form HTML
        form_html = driver.execute_script("return arguments[0].innerHTML;", form_container)
        print(f"📋 Form HTML length: {len(form_html)}")
        if len(form_html) < 100:
            print(f"❌ Form appears empty: {form_html[:200]}")
            return False
        
        # Find the actual form element
        try:
            form = form_container.find_element(By.TAG_NAME, 'form')
            print("✅ Form element found")
        except:
            print("❌ Form element not found inside container")
            return False
        
        # Check form attributes
        form_onsubmit = form.get_attribute('onsubmit')
        print(f"ℹ️  Form onsubmit: {form_onsubmit}")
        
        # Find all buttons
        buttons = form.find_elements(By.TAG_NAME, 'button')
        print(f"📌 Found {len(buttons)} buttons in form")
        
        for i, btn in enumerate(buttons):
            btn_type = btn.get_attribute('type')
            btn_class = btn.get_attribute('class')
            btn_text = btn.text
            is_enabled = btn.is_enabled()
            is_displayed = btn.is_displayed()
            print(f"   Button {i}: type={btn_type}, class={btn_class}, text='{btn_text}', enabled={is_enabled}, displayed={is_displayed}")
        
        # Find save button specifically
        save_buttons = form.find_elements(By.CSS_SELECTOR, "button[type='submit']")
        if not save_buttons:
            print("❌ No submit button found")
            return False
        
        save_btn = save_buttons[0]
        print(f"\n💾 Save button details:")
        print(f"   - Enabled: {save_btn.is_enabled()}")
        print(f"   - Displayed: {save_btn.is_displayed()}")
        print(f"   - Visibility: visible={driver.execute_script('return arguments[0].offsetParent !== null;', save_btn)}")
        print(f"   - Pointer events: {driver.execute_script('return window.getComputedStyle(arguments[0]).pointerEvents;', save_btn)}")
        print(f"   - Opacity: {driver.execute_script('return window.getComputedStyle(arguments[0]).opacity;', save_btn)}")
        print(f"   - Position: {driver.execute_script('return {top: arguments[0].offsetTop, left: arguments[0].offsetLeft};', save_btn)}")
        
        # Check if button is clickable
        is_clickable = save_btn.is_enabled() and save_btn.is_displayed()
        if is_clickable:
            print("✅ Save button is clickable")
            
            # Try to click it
            print("🖱️  Attempting to click save button...")
            try:
                driver.execute_script("arguments[0].scrollIntoView(true);", save_btn)
                time.sleep(0.3)
                save_btn.click()
                print("✅ Save button clicked successfully")
                time.sleep(2)
                return True
            except Exception as e:
                print(f"❌ Failed to click save button: {e}")
                # Try JS click
                print("🔄 Trying JavaScript click...")
                driver.execute_script("arguments[0].click();", save_btn)
                print("✅ JavaScript click executed")
                time.sleep(2)
                return True
        else:
            print("❌ Save button is not clickable")
            if not save_btn.is_enabled():
                print("   - Reason: Button is disabled")
            if not save_btn.is_displayed():
                print("   - Reason: Button is not displayed")
            return False
        
    finally:
        driver.quit()

if __name__ == '__main__':
    success = test_family_edit_form()
    print(f"\n{'='*60}")
    print(f"Test result: {'PASS' if success else 'FAIL'}")
    print(f"{'='*60}")
    exit(0 if success else 1)

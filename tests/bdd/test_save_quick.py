#!/usr/bin/env python3
"""
Quick test for save button functionality
"""
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from common_config import wait_for_element
import time

BASE_URL = 'http://localhost:8000'

opts = Options()
opts.add_argument('--headless=new')
opts.add_argument('--disable-gpu')
opts.add_argument('--no-sandbox')
driver = webdriver.Chrome(options=opts)

try:
    # Login
    driver.get(f'{BASE_URL}/__dev_login?user_id=1&role=member&next=/user/dashboard')
    WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.CLASS_NAME, 'user-dashboard')))
    time.sleep(1)
    print("✅ Logged in")
    
    # Click Add button
    add_button = driver.find_element(By.XPATH, "//button[contains(text(), 'Add') and contains(text(), 'Family')]")
    driver.execute_script("arguments[0].click();", add_button)
    time.sleep(0.5)
    print("✅ Add form opened")
    
    # Fill form
    driver.execute_script("""
        document.querySelector('input[name="first_name"]').value = 'Test';
        document.querySelector('input[name="last_name"]').value = 'Member';
        document.querySelector('select[name="relationship"]').value = 'son';
    """)
    print("✅ Form filled")
    
    # Submit
    add_btn = wait_for_element(driver, By.ID, 'addFamilyButton')
    if add_btn:
        add_btn.click()
        time.sleep(1)
    submit_btn = wait_for_element(driver, By.CSS_SELECTOR, '#addForm button[type="submit"]')
    if not submit_btn:
        print('Submit button not found after clicking addFamilyButton')
    else:
        driver.execute_script("arguments[0].click();", submit_btn)
        print("✅ Form submitted")
    driver.execute_script("arguments[0].click();", submit_btn)
    print("✅ Form submitted")
    
    # Check for success
    time.sleep(1)
    try:
        success = driver.find_element(By.ID, 'addSuccessMessage')
        is_visible = success.is_displayed()
        print(f"✅ Success message visible: {is_visible}")
        if is_visible:
            print(f"   Message: {success.text}")
    except Exception as e:
        print(f"❌ Error checking success: {e}")
    
finally:
    driver.quit()

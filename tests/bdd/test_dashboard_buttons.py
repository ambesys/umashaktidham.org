#!/usr/bin/env python3
"""
Test dashboard button clicks
"""

import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager

BASE_URL = "http://localhost:8000"

# Set up Chrome options
options = webdriver.ChromeOptions()
options.add_argument("--no-sandbox")
options.add_argument("--disable-dev-shm-usage")
options.add_argument("--window-size=1920,1080")
# NOT headless - let's see what's happening

# Initialize WebDriver
service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service, options=options)
driver.implicitly_wait(10)

try:
    print("\n" + "="*80)
    print("TEST: Dashboard Button Clicks")
    print("="*80 + "\n")
    
    # First, login using dev helper
    print("1. Logging in as admin user...")
    driver.get(f"{BASE_URL}/__dev_login?user_id=1&role=admin&next=/user/dashboard")
    time.sleep(2)
    print(f"   Current URL: {driver.current_url}")
    
    # Wait for dashboard to load
    print("2. Waiting for dashboard to load...")
    WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.CLASS_NAME, "user-dashboard"))
    )
    print("   ✓ Dashboard found")
    
    # Find the "Add Family Member" button
    print("3. Looking for 'Add Family Member' button...")
    buttons = driver.find_elements(By.XPATH, "//button[contains(text(), 'Add Family')]")
    print(f"   Found {len(buttons)} buttons with 'Add Family' text")
    
    if buttons:
        button = buttons[0]
        print(f"   Button visible: {button.is_displayed()}")
        print(f"   Button enabled: {button.is_enabled()}")
        
        # Try to get button attributes
        print(f"   Button onclick: {button.get_attribute('onclick')}")
        print(f"   Button class: {button.get_attribute('class')}")
        
        # Check if there's an overlay
        print("4. Checking for overlays...")
        try:
            button.click()
            print("   ✓ Button clicked successfully!")
        except Exception as e:
            print(f"   ✗ Button click failed: {str(e)}")
            
            # Try JavaScript click
            print("5. Trying JavaScript click...")
            driver.execute_script("arguments[0].click();", button)
            print("   ✓ JavaScript click succeeded!")
            
            # Check if form appeared
            time.sleep(1)
            add_form = driver.find_element(By.ID, "addForm")
            print(f"   Add form visible after JS click: {add_form.is_displayed()}")
    
    # Wait a bit to see the result
    time.sleep(3)
    
    # Try to find and click the admin users button
    print("6. Looking for 'Users' admin link...")
    user_links = driver.find_elements(By.XPATH, "//a[contains(text(), 'Users')]")
    print(f"   Found {len(user_links)} user links")
    
    if user_links:
        for link in user_links:
            if link.is_displayed():
                print(f"   Visible link text: {link.text}")
                link.click()
                time.sleep(2)
                print(f"   Current URL after clicking users: {driver.current_url}")
                break
    
    print("\n✅ Test completed")
    
    # Keep browser open for inspection
    print("Browser will close in 10 seconds...")
    time.sleep(10)
    
except Exception as e:
    print(f"\n❌ ERROR: {str(e)}")
    import traceback
    traceback.print_exc()
    time.sleep(5)
    
finally:
    driver.quit()
    print("\n" + "="*80 + "\n")

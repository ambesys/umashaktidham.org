#!/usr/bin/env python3
"""
Simple registration test for debugging
"""

import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager

BASE_URL = "http://localhost:8000"
TEST_TIMEOUT = 15

# Set up Chrome options
options = webdriver.ChromeOptions()
options.add_argument("--headless=new")
options.add_argument("--no-sandbox")
options.add_argument("--disable-dev-shm-usage")
options.add_argument("--window-size=1920,1080")

# Initialize WebDriver
service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service, options=options)
driver.implicitly_wait(10)

try:
    print("\n" + "="*80)
    print("TEST: Simple User Registration")
    print("="*80 + "\n")
    
    # Generate unique email
    email = f"testuser_{int(time.time())}@example.com"
    password = "Test@Password123"
    first_name = "Test"
    last_name = "User"
    
    print(f"Registering user: {email}")
    
    # Navigate to registration page
    print(f"1. Navigating to {BASE_URL}/register")
    driver.get(f"{BASE_URL}/register")
    
    print(f"   Current URL: {driver.current_url}")
    print(f"   Page title: {driver.title}")
    
    # Wait for registration form to load
    print("2. Waiting for registration form...")
    WebDriverWait(driver, TEST_TIMEOUT).until(
        EC.presence_of_element_located((By.ID, "email"))
    )
    print("   ✓ Form found")
    
    # Fill registration form
    print("3. Filling registration form...")
    driver.find_element(By.ID, "first_name").send_keys(first_name)
    print(f"   ✓ First name entered: {first_name}")
    
    driver.find_element(By.ID, "last_name").send_keys(last_name)
    print(f"   ✓ Last name entered: {last_name}")
    
    driver.find_element(By.ID, "email").send_keys(email)
    print(f"   ✓ Email entered: {email}")
    
    driver.find_element(By.ID, "password").send_keys(password)
    print(f"   ✓ Password entered")
    
    driver.find_element(By.ID, "confirm_password").send_keys(password)
    print(f"   ✓ Password confirmed")
    
    # Accept terms
    print("4. Accepting terms...")
    terms_checkbox = driver.find_element(By.ID, "terms")
    if not terms_checkbox.is_selected():
        # Use JavaScript to click instead of direct click (avoids interception)
        driver.execute_script("arguments[0].click();", terms_checkbox)
        print("   ✓ Terms accepted")
    else:
        print("   ✓ Terms already accepted")
    
    # Submit form
    print("5. Submitting form...")
    submit_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
    driver.execute_script("arguments[0].scrollIntoView(true);", submit_button)
    time.sleep(1)  # Wait for scroll to complete
    # Use JavaScript click instead of direct click to avoid interception
    driver.execute_script("arguments[0].click();", submit_button)
    print("   ✓ Form submitted")
    
    # Wait for redirect
    print("6. Waiting for redirect to login page...")
    WebDriverWait(driver, TEST_TIMEOUT).until(
        EC.url_contains("/login")
    )
    print(f"   ✓ Redirected to: {driver.current_url}")
    
    # Verify we're on login page
    current_url = driver.current_url
    if "/login" in current_url:
        print("\n✅ SUCCESS: User registration test passed!")
        print(f"   Registered user: {email}")
        print(f"   Password: {password}")
    else:
        print(f"\n❌ FAILED: Expected login page, got {current_url}")
        
except Exception as e:
    print(f"\n❌ ERROR: {str(e)}")
    import traceback
    traceback.print_exc()
    
    # Save debug artifacts
    try:
        driver.save_screenshot(f"test-error-{int(time.time())}.png")
        print("Screenshot saved")
    except:
        pass
        
finally:
    driver.quit()
    print("\n" + "="*80 + "\n")

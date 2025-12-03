#!/usr/bin/env python3
"""
Visual BDD Test Suite - Debug Mode
Run tests without headless so we can see what's happening in real-time
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import time
import json

BASE_URL = "http://localhost:8000"

def setup_driver():
    """Setup Chrome WebDriver - NOT HEADLESS"""
    chrome_options = Options()
    # Remove headless mode to see the browser
    # chrome_options.add_argument("--headless")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--window-size=1920,1080")
    
    driver = webdriver.Chrome(options=chrome_options)
    return driver

def test_guest_navigation(driver):
    """Test 1: Guest navigation"""
    print("\n" + "=" * 80)
    print("TEST 1: GUEST NAVIGATION")
    print("=" * 80)
    
    try:
        driver.get(f"{BASE_URL}/")
        print(f"✅ Navigated to home page")
        print(f"   Current URL: {driver.current_url}")
        print(f"   Page Title: {driver.title}")
        
        # Check page source
        page_source = driver.page_source
        print(f"   Page source length: {len(page_source)} chars")
        
        # Try to find navbar
        try:
            navbar = driver.find_element(By.TAG_NAME, "nav")
            print(f"✅ Found navbar: {navbar.get_attribute('class')}")
        except:
            print(f"❌ Navbar not found")
        
        # Try to find body content
        body = driver.find_element(By.TAG_NAME, "body")
        print(f"   Body HTML length: {len(body.get_attribute('outerHTML'))} chars")
        
        time.sleep(2)
        
    except Exception as e:
        print(f"❌ Error: {e}")

def test_login_page(driver):
    """Test 2: Login page"""
    print("\n" + "=" * 80)
    print("TEST 2: LOGIN PAGE")
    print("=" * 80)
    
    try:
        driver.get(f"{BASE_URL}/login")
        print(f"✅ Navigated to login page")
        print(f"   Current URL: {driver.current_url}")
        print(f"   Page Title: {driver.title}")
        
        # Wait a bit for redirects
        time.sleep(3)
        
        print(f"   After wait - Current URL: {driver.current_url}")
        
        # Check for login form
        try:
            form = driver.find_element(By.TAG_NAME, "form")
            print(f"✅ Found form: {form.get_attribute('id') or form.get_attribute('class')}")
            
            # List all inputs in form
            inputs = form.find_elements(By.TAG_NAME, "input")
            print(f"   Inputs found: {len(inputs)}")
            for inp in inputs:
                print(f"     - {inp.get_attribute('type')} [{inp.get_attribute('name')}]")
        except:
            print(f"❌ Form not found")
        
        # Check page source
        page_source = driver.page_source
        print(f"   Page source length: {len(page_source)} chars")
        print(f"   Has 'login': {'login' in page_source.lower()}")
        print(f"   Has 'email': {'email' in page_source.lower()}")
        
        time.sleep(2)
        
    except Exception as e:
        print(f"❌ Error: {e}")

def test_register_page(driver):
    """Test 3: Register page"""
    print("\n" + "=" * 80)
    print("TEST 3: REGISTER PAGE")
    print("=" * 80)
    
    try:
        driver.get(f"{BASE_URL}/register")
        print(f"✅ Navigated to register page")
        print(f"   Current URL: {driver.current_url}")
        print(f"   Page Title: {driver.title}")
        
        # Wait for potential redirects
        time.sleep(3)
        print(f"   After wait - Current URL: {driver.current_url}")
        
        # Check for register form
        forms = driver.find_elements(By.TAG_NAME, "form")
        print(f"   Forms found: {len(forms)}")
        
        time.sleep(2)
        
    except Exception as e:
        print(f"❌ Error: {e}")

def test_all_links(driver):
    """Test 4: Find all links on home page"""
    print("\n" + "=" * 80)
    print("TEST 4: ALL AVAILABLE LINKS")
    print("=" * 80)
    
    try:
        driver.get(f"{BASE_URL}/")
        time.sleep(2)
        
        links = driver.find_elements(By.TAG_NAME, "a")
        print(f"✅ Found {len(links)} links")
        for i, link in enumerate(links[:20]):  # Show first 20
            href = link.get_attribute("href")
            text = link.text
            print(f"   {i+1}. {text or '(no text)'} -> {href}")
        
        if len(links) > 20:
            print(f"   ... and {len(links) - 20} more links")
        
    except Exception as e:
        print(f"❌ Error: {e}")

def main():
    """Main execution"""
    print("\n" + "=" * 80)
    print("  VISUAL BDD TEST SUITE - DEBUG MODE")
    print("  Browser will be visible for debugging")
    print("=" * 80)
    
    driver = setup_driver()
    
    try:
        test_guest_navigation(driver)
        test_login_page(driver)
        test_register_page(driver)
        test_all_links(driver)
        
        print("\n" + "=" * 80)
        print("✅ TEST COMPLETE - Browser will remain open for inspection")
        print("   Press Ctrl+C to close the browser")
        print("=" * 80)
        
        # Keep browser open
        input("\nPress Enter to close browser...")
        
    finally:
        driver.quit()
        print("🔌 Browser closed")

if __name__ == "__main__":
    main()

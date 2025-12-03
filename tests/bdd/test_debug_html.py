"""
Debug the HTML structure and visibility issue
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
import time

BASE_URL = "http://localhost:8000"

def debug_test():
    """Debug HTML structure"""
    
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
        
        # Go to dashboard
        driver.get(f"{BASE_URL}/user/dashboard")
        time.sleep(2)
        
        # Get page source
        page_source = driver.page_source
        
        # Check if table is in HTML
        if '<table class="table' in page_source:
            print("✓ Table HTML found in page source")
        else:
            print("✗ Table HTML NOT in page source")
        
        # Check if self row data attributes are in HTML
        if 'data-user-profile' in page_source:
            print("✓ Self row data attributes found in page source")
        else:
            print("✗ Self row data attributes NOT in page source")
        
        # Check edit-profile button
        if 'data-action="edit-profile"' in page_source:
            print("✓ Edit profile button found in page source")
        else:
            print("✗ Edit profile button NOT in page source")
        
        # Check modal is in page
        if 'id="formModal"' in page_source:
            print("✓ Modal HTML found in page source")
        else:
            print("✗ Modal HTML NOT in page source")
        
        # Check modal forms script is loaded
        if 'modal-forms.js' in page_source:
            print("✓ Modal forms script included")
        else:
            print("✗ Modal forms script NOT included")
        
        # Check for rendering issues - get table tbody
        try:
            tbody = driver.find_element(By.CSS_SELECTOR, "tbody#familyList")
            tbody_html = tbody.get_attribute("innerHTML")
            print(f"\n✓ tbody#familyList found")
            print(f"  tbody innerHTML length: {len(tbody_html)}")
            print(f"  tbody innerHTML preview: {tbody_html[:200]}...")
            
            # Count tr elements
            trs = tbody.find_elements(By.TAG_NAME, "tr")
            print(f"  tbody contains {len(trs)} tr elements")
            
            for i, tr in enumerate(trs[:2]):
                tr_html = tr.get_attribute("innerHTML")
                print(f"  Row {i}: {tr_html[:100]}...")
                
        except Exception as e:
            print(f"\n✗ Could not find tbody: {e}")
        
        # Check page dimensions
        viewport = driver.execute_script("return {width: window.innerWidth, height: window.innerHeight}")
        print(f"\nViewport: {viewport}")
        
        # Check if scroll is needed
        scroll_height = driver.execute_script("return document.documentElement.scrollHeight")
        print(f"Document scroll height: {scroll_height}")
        
        # Try scrolling
        driver.execute_script("window.scrollBy(0, 300);")
        time.sleep(1)
        
        # Re-check visibility after scroll
        try:
            self_row = driver.find_element(By.CSS_SELECTOR, "[data-user-profile]")
            visible = driver.execute_script("return arguments[0].offsetHeight > 0", self_row)
            print(f"After scroll - Self row visible: {visible}")
        except:
            print("After scroll - Still cannot find self row")
        
    finally:
        driver.quit()

if __name__ == "__main__":
    debug_test()

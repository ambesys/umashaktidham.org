"""
Debug JavaScript loading and modal handler
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
import time

BASE_URL = "http://localhost:8000"

def debug_js():
    """Debug JS loading"""
    
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
        
        # Check if ModalFormHandler exists
        handler_exists = driver.execute_script("return window.modalFormHandler !== undefined")
        print(f"✓ ModalFormHandler exists: {handler_exists}")
        
        if handler_exists:
            # Check modal handler properties
            current_form_type = driver.execute_script("return window.modalFormHandler.currentFormType")
            print(f"  Current form type: {current_form_type}")
            
            # Check modal instance
            modal_exists = driver.execute_script("return window.modalFormHandler.modal !== undefined")
            print(f"  Modal instance exists: {modal_exists}")
        
        # Check if modal element exists
        modal_elem = driver.execute_script("return document.getElementById('formModal') !== null")
        print(f"✓ Modal DOM element exists: {modal_elem}")
        
        # Check if Bootstrap is loaded
        bootstrap_exists = driver.execute_script("return window.bootstrap !== undefined")
        print(f"✓ Bootstrap loaded: {bootstrap_exists}")
        
        # Try to manually trigger the modal
        print("\n--- Attempting Manual Modal Trigger ---")
        try:
            result = driver.execute_script("""
                console.log('Attempting to open modal');
                if (window.modalFormHandler && window.modalFormHandler.modal) {
                    console.log('Modal handler exists, showing modal');
                    window.modalFormHandler.modal.show();
                    return 'Modal shown successfully';
                } else {
                    return 'Modal handler not found';
                }
            """)
            print(f"Result: {result}")
            time.sleep(1)
            
            # Check if form was loaded
            form_exists = driver.execute_script("return document.getElementById('userProfileForm') !== null")
            print(f"Form loaded after manual trigger: {form_exists}")
            
        except Exception as e:
            print(f"Error: {e}")
        
        # Check console for errors
        logs = driver.get_log('browser')
        if logs:
            print(f"\n--- Browser Console Logs ---")
            for log in logs[-10:]:  # Last 10 logs
                if 'SEVERE' in str(log['level']) or 'WARNING' in str(log['level']):
                    print(f"[{log['level']}] {log['message']}")
        
    finally:
        driver.quit()

if __name__ == "__main__":
    debug_js()

"""
Test the modal form population
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
import time

BASE_URL = "http://localhost:8000"

def test_form_population():
    """Test if form template is being populated"""
    
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
        
        # Check if template exists
        template_exists = driver.execute_script("""
            const template = document.getElementById('userProfileFormTemplate');
            return template !== null;
        """)
        print(f"✓ Form template exists in DOM: {template_exists}")
        
        if template_exists:
            # Get template content
            template_content = driver.execute_script("""
                const template = document.getElementById('userProfileFormTemplate');
                return template.innerHTML.substring(0, 200);
            """)
            print(f"  Template content preview: {template_content}...")
        
        # Try to call openUserProfileForm manually and log what happens
        result = driver.execute_script("""
            console.log('=== Calling openUserProfileForm ===');
            try {
                window.modalFormHandler.openUserProfileForm();
                console.log('openUserProfileForm completed');
                
                // Check what's in the modal body
                const modalBody = document.getElementById('formModalBody');
                console.log('Modal body HTML length:', modalBody.innerHTML.length);
                console.log('Modal body preview:', modalBody.innerHTML.substring(0, 200));
                
                return {
                    success: true,
                    bodyLength: modalBody.innerHTML.length,
                    formExists: document.getElementById('userProfileForm') !== null
                };
            } catch (e) {
                console.error('Error:', e.message);
                console.error('Stack:', e.stack);
                return { success: false, error: e.message };
            }
        """)
        
        print(f"Result: {result}")
        time.sleep(1)
        
        # Check if form now exists
        form_exists = driver.execute_script("return document.getElementById('userProfileForm') !== null")
        print(f"Form exists after call: {form_exists}")
        
        if form_exists:
            # Check if modal is visible
            modal_visible = driver.execute_script("""
                const modal = document.getElementById('formModal');
                return {
                    hasShowClass: modal.classList.contains('show'),
                    ariaHidden: modal.getAttribute('aria-hidden')
                };
            """)
            print(f"Modal state: {modal_visible}")
            
            # Get form field values
            user_id = driver.execute_script("return document.getElementById('firstName')?.value || 'NOT FOUND'")
            print(f"First name field value: {user_id}")
        
        # Check browser logs for errors
        logs = driver.get_log('browser')
        if logs:
            print(f"\nBrowser logs:")
            for log in logs[-15:]:
                print(f"[{log['level']}] {log['message']}")
        
    finally:
        driver.quit()

if __name__ == "__main__":
    test_form_population()

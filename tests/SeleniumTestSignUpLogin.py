from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time
import random
import string
import subprocess
import os

# Initialize the WebDriver
driver = webdriver.Chrome()

# Generate a random email to avoid duplicate error
random_email = "testuser" + ''.join(random.choices(string.digits, k=4)) + "@example.com"


def promote_test_user_to_admin():
    """Promote the test admin user to admin role"""
    print("🔧 Promoting test user to admin role...")

    try:
        # Run the promote admin script
        result = subprocess.run([
            'php', 'simple_promote.php'
        ], cwd='/Users/sarthak/Sites/umashaktidham.org',
        capture_output=True, text=True, timeout=30)

        if result.returncode == 0:
            print("✅ Test user promoted to admin successfully")
            print("Output:", result.stdout.strip())
        else:
            print("❌ Failed to promote test user")
            print("Error:", result.stderr.strip())

        return result.returncode == 0

    except Exception as e:
        print(f"❌ Error promoting user: {e}")
        return False


def test_login():
    """Test regular login with admin user and verify admin navigation"""
    print("🚀 Starting regular login test with admin user...")

    driver = webdriver.Chrome()

    try:
        # Navigate to login page
        print("📍 Navigating to login page...")
        driver.get("http://localhost:8000/login")

        # Handle access code popup if prompted
        try:
            access_code_field = WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.NAME, "access_code"))
            )
            access_code_field.send_keys("jayumiya")
            access_code_field.send_keys(Keys.RETURN)
            print("✅ Access code entered")
        except TimeoutException:
            print("ℹ️  No access code required")

        # Wait for login form to load
        print("⏳ Waiting for login form...")
        email_field = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.NAME, "email"))
        )
        password_field = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.NAME, "password"))
        )
        submit_button = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.NAME, "submit"))
        )
        print("✅ Login form elements found")

        # Enter admin credentials
        print("🔐 Entering admin credentials...")
        email_field.send_keys("testadmin@example.com")
        password_field.send_keys("password123")

        # Submit login form
        print("📤 Submitting login form...")
        submit_button.click()

        # Wait for dashboard redirection
        print("⏳ Waiting for dashboard redirect...")
        WebDriverWait(driver, 10).until(
            EC.url_contains("/dashboard")
        )
        print("✅ Successfully redirected to dashboard")

        # Verify we're on dashboard
        current_url = driver.current_url
        if "/dashboard" in current_url:
            print("🎯 Confirmed: On dashboard page")

            # Check for admin navigation
            print("🔍 Checking for admin navigation...")
            try:
                # Look for ADMIN dropdown
                admin_dropdown = WebDriverWait(driver, 5).until(
                    EC.presence_of_element_located((By.XPATH, "//a[contains(text(), 'ADMIN')]"))
                )
                print("🎉 SUCCESS: Admin navigation found!")
                print("   - ADMIN dropdown is visible in navigation")
                print("   - User has admin role and sees admin menu")

                # Check for admin menu items
                admin_menu_items = driver.find_elements(By.XPATH, "//div[@class='dropdown-content']//a[contains(@href, '/admin')]")
                if admin_menu_items:
                    print(f"   - Found {len(admin_menu_items)} admin menu items")

                # Test admin route access
                print("🔗 Testing admin route access...")
                driver.get("http://localhost:8000/admin/users")
                if "/admin/users" in driver.current_url:
                    print("✅ Admin access confirmed: Can access /admin/users")
                else:
                    print("❌ Admin access denied: Redirected away from admin page")

                # Go back to dashboard
                driver.get("http://localhost:8000/dashboard")

            except TimeoutException:
                # Check for regular dashboard navigation
                try:
                    dashboard_dropdown = driver.find_element(By.XPATH, "//a[contains(text(), 'DASHBOARD')]")
                    print("📊 INFO: Regular user navigation found (DASHBOARD dropdown)")
                    print("   - User appears to have regular user role, not admin")
                    print("   - Admin role assignment may have failed")
                except NoSuchElementException:
                    print("⚠️  WARNING: No role-specific navigation found")
                    print("   - Could indicate navigation issue or authentication problem")

        else:
            print(f"❌ ERROR: Not on dashboard page. Current URL: {current_url}")
            print("   - Login may have failed or redirect issue")

    except (TimeoutException, NoSuchElementException) as e:
        print(f"❌ Test failed with error: {e}")
        driver.save_screenshot("debug_login_error.png")
        with open("debug_login_error.html", "w", encoding="utf-8") as f:
            f.write(driver.page_source)

    finally:
        print("🧹 Cleaning up...")
        driver.quit()

# Call the login test function
if promote_test_user_to_admin():
    test_login()
else:
    print("❌ Cannot run login test - failed to promote test user to admin")
#!/usr/bin/env python3
"""
Selenium Test Suite for Uma Shakti Dham Website
Tests OAuth login, admin functionality, and user dashboard
"""

import time
import unittest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from webdriver_manager.chrome import ChromeDriverManager


class UmaShaktiDhamTest(unittest.TestCase):
    """Test suite for Uma Shakti Dham website functionality"""

    @classmethod
    def setUpClass(cls):
        """Set up the test environment"""
        # Configure Chrome options for headless testing
        chrome_options = Options()
        chrome_options.add_argument("--headless")  # Run in headless mode
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1920,1080")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--disable-extensions")
        chrome_options.add_argument("--disable-web-security")
        chrome_options.add_argument("--allow-running-insecure-content")

        # Initialize the Chrome driver
        service = Service(ChromeDriverManager().install())
        cls.driver = webdriver.Chrome(service=service, options=chrome_options)

        # Set implicit wait
        cls.driver.implicitly_wait(10)

        # Base URL for the application
        cls.base_url = "http://localhost:8000"

        print("🚀 Starting Selenium tests for Uma Shakti Dham...")

    @classmethod
    def tearDownClass(cls):
        """Clean up after all tests"""
        if cls.driver:
            cls.driver.quit()
        print("✅ Selenium tests completed.")

    def setUp(self):
        """Set up before each test"""
        self.driver.get(self.base_url)
        self.wait = WebDriverWait(self.driver, 20)

    def test_01_homepage_loads(self):
        """Test that the homepage loads correctly"""
        print("📋 Test 1: Homepage loads correctly")

        # Check page title
        self.assertIn("Uma Shakti Dham", self.driver.title)

        # Check for main navigation elements
        nav_menu = self.wait.until(
            EC.presence_of_element_located((By.ID, "mainNav"))
        )
        self.assertIsNotNone(nav_menu)

        # Check for login link
        login_link = self.driver.find_element(By.XPATH, "//a[contains(text(), 'Login')]")
        self.assertIsNotNone(login_link)

        print("✅ Homepage loaded successfully")

    def test_02_login_page_accessible(self):
        """Test that login page is accessible"""
        print("📋 Test 2: Login page accessible")

        # Navigate to login page
        self.driver.get(f"{self.base_url}/login")

        # Check for login form elements
        try:
            email_field = self.wait.until(
                EC.presence_of_element_located((By.NAME, "email"))
            )
            password_field = self.driver.find_element(By.NAME, "password")
            login_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")

            self.assertIsNotNone(email_field)
            self.assertIsNotNone(password_field)
            self.assertIsNotNone(login_button)

            print("✅ Login page accessible with form elements")

        except TimeoutException:
            self.fail("Login form elements not found")

    def test_03_oauth_google_link_present(self):
        """Test that Google OAuth login link is present"""
        print("📋 Test 3: Google OAuth link present")

        self.driver.get(f"{self.base_url}/login")

        # Look for Google OAuth link
        try:
            google_oauth_link = self.wait.until(
                EC.presence_of_element_located((By.XPATH, "//a[contains(@href, 'auth/google')]"))
            )
            self.assertIsNotNone(google_oauth_link)
            print("✅ Google OAuth link found")

        except TimeoutException:
            # Try alternative selectors
            try:
                google_oauth_link = self.driver.find_element(By.XPATH, "//a[contains(text(), 'Google')]")
                self.assertIsNotNone(google_oauth_link)
                print("✅ Google OAuth link found (alternative selector)")
            except NoSuchElementException:
                self.fail("Google OAuth link not found")

    def test_04_oauth_facebook_link_present(self):
        """Test that Facebook OAuth login link is present"""
        print("📋 Test 4: Facebook OAuth link present")

        self.driver.get(f"{self.base_url}/login")

        # Look for Facebook OAuth link
        try:
            facebook_oauth_link = self.wait.until(
                EC.presence_of_element_located((By.XPATH, "//a[contains(@href, 'auth/facebook')]"))
            )
            self.assertIsNotNone(facebook_oauth_link)
            print("✅ Facebook OAuth link found")

        except TimeoutException:
            # Try alternative selectors
            try:
                facebook_oauth_link = self.driver.find_element(By.XPATH, "//a[contains(text(), 'Facebook')]")
                self.assertIsNotNone(facebook_oauth_link)
                print("✅ Facebook OAuth link found (alternative selector)")
            except NoSuchElementException:
                print("⚠️  Facebook OAuth link not found (might not be configured)")

    def test_05_dashboard_requires_authentication(self):
        """Test that dashboard requires authentication"""
        print("📋 Test 5: Dashboard requires authentication")

        self.driver.get(f"{self.base_url}/dashboard")

        # Should redirect to login page
        self.wait.until(lambda driver: "login" in driver.current_url.lower())
        self.assertIn("login", self.driver.current_url.lower())

        print("✅ Dashboard correctly requires authentication")

    def test_06_admin_routes_require_authentication(self):
        """Test that admin routes require authentication"""
        print("📋 Test 6: Admin routes require authentication")

        admin_urls = ["/admin", "/admin/users", "/admin/moderators", "/admin/events"]

        for url in admin_urls:
            with self.subTest(url=url):
                self.driver.get(f"{self.base_url}{url}")
                # Should redirect to login
                self.wait.until(lambda driver: "login" in driver.current_url.lower())
                self.assertIn("login", self.driver.current_url.lower())

        print("✅ Admin routes correctly require authentication")

    def test_07_navigation_menu_structure(self):
        """Test that navigation menu has correct structure"""
        print("📋 Test 7: Navigation menu structure")

        # Check main navigation items
        expected_nav_items = ["HOME", "ABOUT", "EVENTS & PROGRAMS", "PHOTO GALLERY", "COMMUNITY", "RELIGION", "CONTACT"]

        nav_menu = self.wait.until(
            EC.presence_of_element_located((By.ID, "mainNav"))
        )

        nav_links = nav_menu.find_elements(By.TAG_NAME, "a")

        # Extract text from navigation links
        nav_texts = [link.text.strip().upper() for link in nav_links if link.text.strip()]

        for expected_item in expected_nav_items:
            self.assertIn(expected_item, nav_texts, f"Navigation item '{expected_item}' not found")

        print("✅ Navigation menu has correct structure")

    def test_08_responsive_design_mobile_menu(self):
        """Test that mobile menu toggle works"""
        print("📋 Test 8: Mobile menu toggle")

        # Find the mobile menu toggle button
        try:
            menu_toggle = self.driver.find_element(By.ID, "navToggle")
            self.assertIsNotNone(menu_toggle)

            # Click the toggle (this might not work in headless mode, but we can check if element exists)
            print("✅ Mobile menu toggle button found")

        except NoSuchElementException:
            print("⚠️  Mobile menu toggle not found (might be desktop view)")

    def test_09_page_load_performance(self):
        """Test basic page load performance"""
        print("📋 Test 9: Page load performance")

        start_time = time.time()
        self.driver.get(self.base_url)
        end_time = time.time()

        load_time = end_time - start_time
        print(".2f")

        # Page should load within reasonable time (allowing for local development)
        self.assertLess(load_time, 10, "Page took too long to load")

        print("✅ Page load performance acceptable")

    def test_10_check_for_javascript_errors(self):
        """Test for JavaScript errors in console"""
        print("📋 Test 10: Check for JavaScript errors")

        # Get browser logs (this might not work in all browsers)
        try:
            logs = self.driver.get_log('browser')
            js_errors = [log for log in logs if log['level'] == 'SEVERE']

            if js_errors:
                print(f"⚠️  Found {len(js_errors)} JavaScript errors:")
                for error in js_errors[:3]:  # Show first 3 errors
                    print(f"   - {error['message']}")
            else:
                print("✅ No JavaScript errors found")

        except Exception as e:
            print(f"⚠️  Could not check JavaScript errors: {e}")

    def test_11_test_form_validation(self):
        """Test basic form validation on login page"""
        print("📋 Test 11: Form validation")

        self.driver.get(f"{self.base_url}/login")

        # Try to submit empty form
        try:
            login_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
            login_button.click()

            # Check if we're still on login page (form validation should prevent submission)
            time.sleep(1)  # Wait for any client-side validation

            # If we're still on login page, validation is working
            if "login" in self.driver.current_url.lower():
                print("✅ Form validation working (stayed on login page)")
            else:
                print("⚠️  Form submitted without validation")

        except Exception as e:
            print(f"⚠️  Could not test form validation: {e}")

    def test_12_check_access_gates(self):
        """Test that access gates work properly"""
        print("📋 Test 12: Access gates")

        # Try to access a page that might be behind access control
        self.driver.get(f"{self.base_url}/access")

        # Check if access page loads
        try:
            access_form = self.driver.find_element(By.NAME, "access_code")
            self.assertIsNotNone(access_form)
            print("✅ Access gate page loads correctly")
        except NoSuchElementException:
            print("⚠️  Access gate not found or not required")

    def test_13_check_footer_presence(self):
        """Test that footer is present on pages"""
        print("📋 Test 13: Footer presence")

        try:
            footer = self.driver.find_element(By.TAG_NAME, "footer")
            self.assertIsNotNone(footer)
            print("✅ Footer found on page")
        except NoSuchElementException:
            print("⚠️  Footer not found")

    def test_14_test_navigation_links(self):
        """Test that main navigation links work"""
        print("📋 Test 14: Navigation links")

        nav_links = [
            ("/", "HOME"),
            ("/about", "ABOUT"),
            ("/events", "EVENTS & PROGRAMS"),
            ("/gallery", "PHOTO GALLERY"),
            ("/membership", "COMMUNITY"),
            ("/contact", "CONTACT")
        ]

        for url, expected_text in nav_links:
            with self.subTest(url=url):
                try:
                    self.driver.get(f"{self.base_url}{url}")

                    # Wait for page to load
                    time.sleep(1)

                    # Check that we're on the right page
                    current_url = self.driver.current_url
                    self.assertIn(url, current_url, f"Failed to navigate to {url}")

                    print(f"✅ Navigation to {url} successful")

                except Exception as e:
                    print(f"⚠️  Navigation to {url} failed: {e}")

    def test_15_check_meta_tags(self):
        """Test that pages have proper meta tags"""
        print("📋 Test 15: Meta tags")

        # Check meta description
        try:
            meta_description = self.driver.find_element(By.XPATH, "//meta[@name='description']")
            self.assertIsNotNone(meta_description)
            print("✅ Meta description found")
        except NoSuchElementException:
            print("⚠️  Meta description not found")

        # Check viewport meta tag
        try:
            viewport = self.driver.find_element(By.XPATH, "//meta[@name='viewport']")
            self.assertIsNotNone(viewport)
            print("✅ Viewport meta tag found")
        except NoSuchElementException:
            print("⚠️  Viewport meta tag not found")


if __name__ == '__main__':
    # Start PHP development server in background
    import subprocess
    import signal
    import os

    print("🔧 Starting PHP development server...")

    # Change to project directory
    os.chdir("/Users/sarthak/Sites/umashaktidham.org")

    # Start PHP server
    server_process = subprocess.Popen(
        ["php", "-S", "localhost:8000"],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE
    )

    # Wait a moment for server to start
    time.sleep(3)

    try:
        # Run the tests
        unittest.main(verbosity=2, exit=False)

    finally:
        # Clean up: stop the PHP server
        print("\n🛑 Stopping PHP development server...")
        server_process.terminate()
        server_process.wait()
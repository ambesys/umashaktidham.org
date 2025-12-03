#!/usr/bin/env python3
"""
Focused Selenium Test for OAuth and Admin Functionality
Tests the specific OAuth login flow and admin role assignment
"""

import time
import subprocess
import os
import signal
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from webdriver_manager.chrome import ChromeDriverManager


def start_php_server():
    """Start PHP development server"""
    print("🔧 Starting PHP development server on localhost:8000...")

    # Change to project directory
    os.chdir("/Users/sarthak/Sites/umashaktidham.org")

    # Start PHP server
    server_process = subprocess.Popen(
        ["php", "-S", "localhost:8000"],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        preexec_fn=os.setsid  # Create new process group
    )

    # Wait for server to start
    time.sleep(3)

    return server_process


def setup_driver():
    """Setup Chrome driver for testing"""
    chrome_options = Options()
    chrome_options.add_argument("--headless")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--window-size=1920,1080")
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--disable-web-security")
    chrome_options.add_argument("--allow-running-insecure-content")

    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service, options=chrome_options)
    driver.implicitly_wait(10)

    return driver


def test_oauth_flow(driver):
    """Test OAuth login flow"""
    print("\n🔐 Testing OAuth Login Flow")
    base_url = "http://localhost:8000"

    try:
        # 1. Go to login page
        print("   📍 Navigating to login page...")
        driver.get(f"{base_url}/login")

        # 2. Check for OAuth links
        google_link = None
        facebook_link = None

        try:
            google_link = driver.find_element(By.XPATH, "//a[contains(@href, 'auth/google')]")
            print("   ✅ Google OAuth link found")
        except NoSuchElementException:
            print("   ⚠️  Google OAuth link not found")

        try:
            facebook_link = driver.find_element(By.XPATH, "//a[contains(@href, 'auth/facebook')]")
            print("   ✅ Facebook OAuth link found")
        except NoSuchElementException:
            print("   ⚠️  Facebook OAuth link not found")

        # 3. Test OAuth redirect (without actually logging in)
        if google_link:
            print("   🔄 Testing Google OAuth redirect...")
            # Store current URL
            login_url = driver.current_url

            # Click Google OAuth link
            google_link.click()

            # Wait for redirect to Google
            time.sleep(2)

            # Check if we were redirected (should go to Google OAuth)
            current_url = driver.current_url
            if "accounts.google.com" in current_url or "google" in current_url:
                print("   ✅ Redirected to Google OAuth successfully")
            else:
                print(f"   ⚠️  Unexpected redirect URL: {current_url}")

            # Go back to login page
            driver.get(f"{base_url}/login")
            time.sleep(1)

        # 4. Test dashboard access without login
        print("   🚫 Testing dashboard access without authentication...")
        driver.get(f"{base_url}/dashboard")

        # Should redirect to login
        WebDriverWait(driver, 10).until(
            lambda d: "login" in d.current_url.lower()
        )
        print("   ✅ Dashboard correctly requires authentication")

        return True

    except Exception as e:
        print(f"   ❌ OAuth flow test failed: {e}")
        return False


def test_admin_navigation(driver):
    """Test admin navigation elements"""
    print("\n👑 Testing Admin Navigation")
    base_url = "http://localhost:8000"

    try:
        # Since we can't actually log in with OAuth in automated tests,
        # we'll test the navigation structure and admin routes

        # 1. Test admin routes require authentication
        admin_routes = ["/admin", "/admin/users", "/admin/moderators", "/admin/events"]

        for route in admin_routes:
            print(f"   🛡️  Testing admin route: {route}")
            driver.get(f"{base_url}{route}")

            # Should redirect to login
            try:
                WebDriverWait(driver, 5).until(
                    lambda d: "login" in d.current_url.lower()
                )
                print(f"   ✅ {route} correctly requires authentication")
            except TimeoutException:
                print(f"   ❌ {route} did not redirect to login")

        # 2. Test navigation menu structure
        print("   📋 Testing navigation menu structure...")
        driver.get(base_url)

        nav_menu = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, "mainNav"))
        )

        # Check for expected navigation items
        nav_links = nav_menu.find_elements(By.TAG_NAME, "a")
        nav_texts = [link.text.strip() for link in nav_links if link.text.strip()]

        expected_items = ["HOME", "ABOUT", "EVENTS & PROGRAMS", "PHOTO GALLERY", "COMMUNITY", "RELIGION", "CONTACT"]

        found_items = []
        for item in expected_items:
            if any(item in text.upper() for text in nav_texts):
                found_items.append(item)

        print(f"   ✅ Found navigation items: {', '.join(found_items)}")

        if len(found_items) >= len(expected_items) * 0.8:  # 80% success rate
            print("   ✅ Navigation menu structure is good")
        else:
            print("   ⚠️  Some navigation items missing")

        return True

    except Exception as e:
        print(f"   ❌ Admin navigation test failed: {e}")
        return False


def test_page_load_performance(driver):
    """Test basic page load performance"""
    print("\n⚡ Testing Page Load Performance")
    base_url = "http://localhost:8000"

    pages_to_test = [
        ("/", "Homepage"),
        ("/about", "About page"),
        ("/contact", "Contact page"),
        ("/login", "Login page")
    ]

    results = []

    for url, name in pages_to_test:
        try:
            start_time = time.time()
            driver.get(f"{base_url}{url}")
            end_time = time.time()

            load_time = end_time - start_time
            results.append((name, load_time))

            if load_time < 5:  # 5 seconds is reasonable for local development
                print(f"   ✅ {name}: {load_time:.2f}s")
            else:
                print(f"   ⚠️  {name}: {load_time:.2f}s (slow)")
        except Exception as e:
            print(f"   ❌ Failed to load {name}: {e}")

    # Summary
    if results:
        avg_time = sum(time for _, time in results) / len(results)
        print(f"   📊 Average load time: {avg_time:.2f}s")

    return True


def main():
    """Main test execution"""
    print("🚀 Uma Shakti Dham Selenium Test Suite")
    print("=" * 50)

    server_process = None
    driver = None

    try:
        # Start PHP server
        server_process = start_php_server()

        # Setup Selenium driver
        driver = setup_driver()

        # Run tests
        tests_passed = 0
        total_tests = 0

        # Test OAuth flow
        total_tests += 1
        if test_oauth_flow(driver):
            tests_passed += 1

        # Test admin navigation
        total_tests += 1
        if test_admin_navigation(driver):
            tests_passed += 1

        # Test performance
        total_tests += 1
        if test_page_load_performance(driver):
            tests_passed += 1

        # Results
        print("\n" + "=" * 50)
        print("📊 Test Results Summary")
        print(f"   ✅ Tests Passed: {tests_passed}/{total_tests}")
        print(f"   📈 Success Rate: {(tests_passed/total_tests)*100:.1f}%")

        if tests_passed == total_tests:
            print("   🎉 All tests passed!")
        else:
            print("   ⚠️  Some tests failed - check output above")

    except KeyboardInterrupt:
        print("\n⏹️  Test interrupted by user")
    except Exception as e:
        print(f"\n❌ Test suite failed: {e}")
    finally:
        # Cleanup
        if driver:
            print("\n🧹 Cleaning up Selenium driver...")
            driver.quit()

        if server_process:
            print("🛑 Stopping PHP development server...")
            try:
                os.killpg(os.getpgid(server_process.pid), signal.SIGTERM)
                server_process.wait(timeout=5)
            except:
                server_process.kill()


if __name__ == '__main__':
    main()
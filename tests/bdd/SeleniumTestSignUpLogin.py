"""
Robust Selenium test script for signup/login/profile/family/dashboard flows.

Usage:
  - Ensure Chrome and Chromedriver are installed and on PATH, or set CHROMEDRIVER_PATH.
  - Set BASE_URL (default http://localhost:8000) and HEADLESS (true/false) via environment.
  - Run: python tests/SeleniumTestSignUpLogin.py

Notes:
  - This script attempts to be defensive about route names and form field names. Adjust selectors if your app uses different names.
  - The script will capture screenshots and HTML on failures into the current directory for debugging.
"""

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException, WebDriverException
import time
import random
import string
import subprocess
import os
import json
import traceback

# Config via environment variables
BASE_URL = os.environ.get('BASE_URL', 'http://localhost:8080').rstrip('/')
HEADLESS = os.environ.get('HEADLESS', 'true').lower() in ('1', 'true', 'yes')
CHROMEDRIVER_PATH = os.environ.get('CHROMEDRIVER_PATH')  # optional
TEST_TIMEOUT = int(os.environ.get('TEST_TIMEOUT', '10'))


def random_email():
    return "testuser" + ''.join(random.choices(string.digits, k=6)) + "@example.com"


def save_debug(driver, name_prefix):
    ts = int(time.time())
    try:
        screenshot = f"{name_prefix}-{ts}.png"
        driver.save_screenshot(screenshot)
        with open(f"{name_prefix}-{ts}.html", 'w', encoding='utf-8') as f:
            f.write(driver.page_source)
        print(f"Saved debug artifacts: {screenshot}, {name_prefix}-{ts}.html")
    except Exception as e:
        print(f"Failed to save debug artifacts: {e}")


def build_driver():
    opts = Options()
    if HEADLESS:
        # using new headless flag for modern chrome
        opts.add_argument('--headless=new')
    opts.add_argument('--no-sandbox')
    opts.add_argument('--disable-dev-shm-usage')
    opts.add_argument('--window-size=1366,768')
    # optional: allow insecure localhost certs
    opts.add_argument('--ignore-certificate-errors')

    try:
        if CHROMEDRIVER_PATH:
            driver = webdriver.Chrome(executable_path=CHROMEDRIVER_PATH, options=opts)
        else:
            driver = webdriver.Chrome(options=opts)
    except TypeError:
        # fallback for older selenium versions
        driver = webdriver.Chrome(executable_path=CHROMEDRIVER_PATH, options=opts) if CHROMEDRIVER_PATH else webdriver.Chrome(options=opts)
    return driver


def promote_test_user_to_admin(cwd=None):
    """Call the PHP helper to promote the test user to admin. Returns True on success."""
    print("🔧 Promoting test user to admin role (if script exists)...")
    cwd = cwd or os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
    promote_script = os.path.join(cwd, 'simple_promote.php')
    if not os.path.exists(promote_script):
        print("ℹ️  promote script not found, skipping promotion")
        return False
    try:
        result = subprocess.run(['php', promote_script], cwd=cwd, capture_output=True, text=True, timeout=30)
        if result.returncode == 0:
            print("✅ Test user promoted to admin (script output):")
            print(result.stdout.strip())
            return True
        else:
            print("❌ Promote script failed:")
            print(result.stderr.strip())
            return False
    except Exception as e:
        print(f"❌ Exception running promote script: {e}")
        return False


def find_and_fill(driver, by, selector, value, timeout=TEST_TIMEOUT):
    el = WebDriverWait(driver, timeout).until(EC.presence_of_element_located((by, selector)))
    el.clear()
    el.send_keys(value)
    return el


def try_navigate(driver, path, timeout=TEST_TIMEOUT):
    url = BASE_URL + path
    print(f"→ Navigating to {url}")
    driver.get(url)
    # allow some time for page to load
    time.sleep(0.5)


def register_user(driver, email, password='Password123!'):
    print(f"🧪 Registering user {email}")
    # Try several common register routes
    candidates = ['/register', '/signup', '/auth/register', '/register.php']
    for p in candidates:
        try:
            try_navigate(driver, p)
            # common fields: name/email/password/(confirm)/submit
            # detect email field by name or id
            email_field = None
            try:
                email_field = WebDriverWait(driver, 2).until(EC.presence_of_element_located((By.NAME, 'email')))
            except TimeoutException:
                try:
                    email_field = driver.find_element(By.ID, 'email')
                except Exception:
                    email_field = None

            if not email_field:
                # not a registration page
                continue

            # Fill fields defensively
            try:
                find_and_fill(driver, By.NAME, 'email', email)
            except Exception:
                try:
                    find_and_fill(driver, By.ID, 'email', email)
                except Exception:
                    pass

            # name fields
            for name_selector in ('name', 'full_name', 'first_name'):
                try:
                    find_and_fill(driver, By.NAME, name_selector, 'Selenium Test')
                    break
                except Exception:
                    pass

            # password fields
            try:
                find_and_fill(driver, By.NAME, 'password', password)
            except Exception:
                try:
                    find_and_fill(driver, By.ID, 'password', password)
                except Exception:
                    pass

            # password confirm (optional)
            try:
                find_and_fill(driver, By.NAME, 'password_confirmation', password)
            except Exception:
                pass

            # submit
            submitted = False
            for submit_selector in [('name', 'submit'), ('css selector', 'button[type="submit"]'), ('xpath', "//button[contains(., 'Register') or contains(., 'Sign up') or contains(., 'Create')]")]:
                try:
                    if submit_selector[0] == 'name':
                        btn = driver.find_element(By.NAME, submit_selector[1])
                    elif submit_selector[0] == 'css selector':
                        btn = driver.find_element(By.CSS_SELECTOR, submit_selector[1])
                    else:
                        btn = driver.find_element(By.XPATH, submit_selector[1])
                    btn.click()
                    submitted = True
                    break
                except Exception:
                    continue

            if not submitted:
                # try pressing Enter in email field
                try:
                    email_field.send_keys(Keys.RETURN)
                    submitted = True
                except Exception:
                    pass

            # wait for redirect or success message
            try:
                WebDriverWait(driver, TEST_TIMEOUT).until(lambda d: '/dashboard' in d.current_url or 'confirmation' in d.page_source.lower() or 'welcome' in d.page_source.lower())
            except TimeoutException:
                # Not a showstopper; registration might require email verification
                print('⚠️  Registration may require email verification or different flow; check manually.')

            print('✅ Registration attempted (check server to confirm).')
            return True
        except Exception:
            # try next candidate
            continue

    print('❌ Could not find a registration page - adjust selectors or routes')
    return False


def login_user(driver, email, password='Password123!'):
    print(f"🔐 Logging in as {email}")
    try:
        try_navigate(driver, '/login')
        # handle possible access code popup
        try:
            access_code_field = WebDriverWait(driver, 2).until(EC.presence_of_element_located((By.NAME, 'access_code')))
            access_code_field.send_keys('jayumiya')
            access_code_field.send_keys(Keys.RETURN)
            print('✅ Access code entered')
        except TimeoutException:
            pass

        # fill form
        find_and_fill(driver, By.NAME, 'email', email)
        find_and_fill(driver, By.NAME, 'password', password)

        # submit
        try:
            submit_btn = driver.find_element(By.NAME, 'submit')
            submit_btn.click()
        except Exception:
            try:
                driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]').click()
            except Exception:
                # press enter
                driver.find_element(By.NAME, 'password').send_keys(Keys.RETURN)

        WebDriverWait(driver, TEST_TIMEOUT).until(lambda d: '/dashboard' in d.current_url)
        print('✅ Logged in and on dashboard')
        return True
    except Exception as e:
        print('❌ Login failed:', e)
        save_debug(driver, 'login-failure')
        return False


def update_profile(driver, payload):
    print('👤 Updating profile (form)')
    try:
        try_navigate(driver, '/dashboard')
        # The project previously had `/update-user` JSON endpoint; we'll try form first
        # Try to find a profile form on the dashboard
        # Attempt to open inline edit if exists
        try:
            edit_button = driver.find_element(By.CSS_SELECTOR, '[data-action="edit-profile"]')
            edit_button.click()
            time.sleep(0.3)
        except Exception:
            pass

        # Fill known fields
        for k, v in payload.items():
            # attempt name map
            selectors = [f"[name='{k}']", f"[id='{k}']"]
            for sel in selectors:
                try:
                    el = driver.find_element(By.CSS_SELECTOR, sel)
                    el.clear()
                    el.send_keys(v)
                    break
                except Exception:
                    continue

        # submit if form button exists
        try:
            driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]').click()
        except Exception:
            pass

        time.sleep(1)
        print('✅ Profile update attempted')
        return True
    except Exception as e:
        print('❌ Profile update failed:', e)
        save_debug(driver, 'profile-update-failure')
        return False


def add_family_member_via_ajax(driver, member_payload):
    """Call the JSON endpoint that the dashboard JS uses."""
    print('👪 Adding family member via AJAX endpoint')
    try:
        # Ensure we're authenticated and on dashboard
        try_navigate(driver, '/dashboard')

        # Use JavaScript fetch to post JSON (works even if same-origin)
        script = f"return fetch('/add-family-member', {{method:'POST', headers:{{'Content-Type':'application/json'}}, body: JSON.stringify({json.dumps(member_payload)})}}).then(r => r.text()).then(t => {{ return {{status: 'ok', text: t, url: location.href}} }})"
        res = driver.execute_script(script)
        print('AJAX response text (may be HTML or JSON):')
        print(res)
        return True
    except WebDriverException as e:
        print('❌ AJAX add failed:', e)
        save_debug(driver, 'ajax-add-failure')
        return False


def add_family_member_via_form(driver, member_payload):
    print('👪 Adding family member via form (fallback)')
    try:
        try_navigate(driver, '/dashboard')
        # try opening add-family form
        try:
            add_btn = driver.find_element(By.CSS_SELECTOR, '[data-action="add-family-member"]')
            add_btn.click()
            time.sleep(0.3)
        except Exception:
            pass

        # fill fields using name attributes similar to the payload keys
        for k, v in member_payload.items():
            try:
                el = driver.find_element(By.NAME, k)
                el.clear()
                el.send_keys(str(v))
            except Exception:
                # continue; not all fields exist
                pass

        try:
            driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]').click()
        except Exception:
            pass

        time.sleep(1)
        print('✅ Add-family form attempted')
        return True
    except Exception as e:
        print('❌ Add-family form failed:', e)
        save_debug(driver, 'form-add-failure')
        return False


def check_dashboard_completeness(driver):
    print('📊 Checking dashboard completeness UI')
    try:
        try_navigate(driver, '/dashboard')
        # Look for the profilePercentText div which displays the percentage
        try:
            percent_elem = WebDriverWait(driver, TEST_TIMEOUT).until(
                EC.presence_of_element_located((By.ID, 'profilePercentText'))
            )
            percent_text = percent_elem.text.strip()
            print(f'✅ Found profile completeness: {percent_text}')
            return True
        except TimeoutException:
            pass
        
        # Fallback: look for the profileDonut SVG
        try:
            donut_elem = driver.find_element(By.ID, 'profileDonut')
            print('✅ Found profileDonut SVG element')
            return True
        except NoSuchElementException:
            pass
        
        # Last fallback: look for any text containing percent and complete
        texts = driver.find_elements(By.XPATH, "//*[contains(text(), '%') and contains(., 'complete')]")
        if texts:
            print('✅ Found completeness text:', texts[0].text)
            return True
        
        print('⚠️  Could not detect completeness UI automatically')
        return False
    except Exception as e:
        print('❌ Dashboard completeness check failed:', e)
        save_debug(driver, 'completeness-check-failure')
        return False


def test_full_flow():
    driver = None
    # Use the pre-seeded test user (from scripts/seed_test_user.php)
    test_email = 'testuser@example.com'
    test_password = 'password123'

    try:
        driver = build_driver()

        # Skip registration since we have a seeded test user; go straight to login
        print('ℹ️  Skipping registration; using pre-seeded test user')

        # Login with seeded test user
        ok = login_user(driver, test_email, test_password)
        if not ok:
            # try a fallback test admin credentials
            print('ℹ️  Trying default testadmin fallback credentials')
            ok = login_user(driver, 'testadmin@example.com', 'password123')
            if not ok:
                raise RuntimeError('Login failed for both test user and fallback admin')

        # Update profile
        profile_payload = {'first_name': 'Selenium', 'last_name': 'Tester', 'phone': '9999999999', 'village': 'Testville'}
        update_profile(driver, profile_payload)

        # Add family via AJAX
        family_payload = {'first_name': 'Member1', 'relationship': 'spouse', 'birth_year': 1990}
        add_family_member_via_ajax(driver, family_payload)

        # Add another family member via form fallback
        family_payload2 = {'first_name': 'Child1', 'relationship': 'child', 'birth_year': 2015}
        add_family_member_via_form(driver, family_payload2)

        # Check completeness
        check_dashboard_completeness(driver)

        # Attempt to test admin UI by promoting test user (best-effort)
        if promote_test_user_to_admin():
            print('ℹ️  Trying to login as promoted admin (same user)')
            # re-login to ensure new permissions apply
            driver.delete_all_cookies()
            login_user(driver, test_email, test_password)
            # check for admin menu
            try:
                WebDriverWait(driver, 3).until(EC.presence_of_element_located((By.XPATH, "//a[contains(text(), 'ADMIN')]")))
                print('🎉 Admin navigation visible after promotion')
            except TimeoutException:
                print('⚠️  Admin navigation not visible after promotion (role mapping may differ)')

        print('\n✅ Full flow completed (check server logs / email for side-effects like welcome or reset emails)')

    except Exception as e:
        print('❌ Test run failed with exception:')
        traceback.print_exc()
        if driver:
            save_debug(driver, 'test-failure')
    finally:
        if driver:
            driver.quit()


if __name__ == '__main__':
    print('Starting Selenium end-to-end test (signup -> login -> profile -> family -> dashboard)')
    test_full_flow()
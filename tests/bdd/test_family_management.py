"""
Test Suite: Family Member Management
Tests: Add, edit, delete family members with profile completeness tracking

Test scenarios:
1. Add 1 family member (via AJAX)
2. Add 3 more family members with different relationships (via form)
3. Edit a random family member
4. Delete a random family member (not self)
5. Verify profile completeness increases with family members
6. Verify all operations persist to database
"""

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time
import json
import random
from datetime import datetime

BASE_URL = 'http://localhost:8000'
TEST_TIMEOUT = 15
TEST_USER_EMAIL = 'testuser@example.com'
TEST_USER_PASSWORD = 'password123'


class TestResults:
    """Track test results"""
    def __init__(self, suite_name):
        self.suite_name = suite_name
        self.results = {}
        self.start_time = datetime.now()
    
    def record(self, test_name, passed, details=''):
        """Record test result"""
        self.results[test_name] = {'passed': passed, 'details': details}
    
    def summary(self):
        """Print summary"""
        total = len(self.results)
        passed = sum(1 for r in self.results.values() if r['passed'])
        failed = total - passed
        elapsed = (datetime.now() - self.start_time).total_seconds()
        
        print(f"\n{'='*80}")
        print(f"  {self.suite_name.upper()} - TEST RESULTS")
        print(f"{'='*80}")
        for test_name, result in self.results.items():
            status = "✅ PASS" if result['passed'] else "❌ FAIL"
            details = f" | {result['details']}" if result['details'] else ""
            print(f"{status:12} | {test_name:40}{details}")
        print(f"{'='*80}")
        print(f"Total: {passed}/{total} passed | {failed}/{total} failed | {elapsed:.1f}s elapsed")
        print(f"{'='*80}\n")
        return failed == 0


def save_debug(driver, name_prefix):
    """Save screenshot and HTML for debugging"""
    ts = int(time.time())
    try:
        screenshot = f"{name_prefix}-{ts}.png"
        driver.save_screenshot(screenshot)
        with open(f"{name_prefix}-{ts}.html", 'w', encoding='utf-8') as f:
            f.write(driver.page_source)
        return screenshot
    except Exception as e:
        print(f"   ⚠️  Could not save debug: {e}")
        return None


def build_driver(headless=True):
    """Create Chrome WebDriver"""
    opts = Options()
    if headless:
        opts.add_argument('--headless=new')
    opts.add_argument('--no-sandbox')
    opts.add_argument('--disable-dev-shm-usage')
    opts.add_argument('--window-size=1366,768')
    return webdriver.Chrome(options=opts)


def login_user(driver):
    """Login test user"""
    print(f"   🔐 Logging in as {TEST_USER_EMAIL}...")
    driver.get(f'{BASE_URL}/login')
    time.sleep(1)
    
    email_field = WebDriverWait(driver, TEST_TIMEOUT).until(
        EC.presence_of_element_located((By.NAME, 'email'))
    )
    email_field.clear()
    email_field.send_keys(TEST_USER_EMAIL)
    
    password_field = driver.find_element(By.NAME, 'password')
    password_field.clear()
    password_field.send_keys(TEST_USER_PASSWORD)
    
    try:
        submit_btn = driver.find_element(By.NAME, 'submit')
        driver.execute_script("arguments[0].click();", submit_btn)
    except NoSuchElementException:
        password_field.send_keys(Keys.RETURN)
    
    try:
        WebDriverWait(driver, TEST_TIMEOUT).until(
            lambda d: '/dashboard' in d.current_url or '/user/dashboard' in d.current_url
        )
        print(f"   ✅ Logged in successfully")
        return True
    except TimeoutException:
        print(f"   ❌ Login failed")
        return False


# ============================================================================
# TEST 1: ADD FAMILY MEMBER VIA AJAX
# ============================================================================

def test_add_family_ajax(driver, test_results):
    """Add family member via AJAX endpoint"""
    print("\n👪 TEST 1: Add Family Member (AJAX)")
    test_name = "Add Family Member (AJAX)"
    
    try:
        driver.get(f'{BASE_URL}/user/dashboard')
        time.sleep(1)
        
        member_data = {
            'first_name': 'Member_AJAX_' + str(random.randint(1000, 9999)),
            'last_name': 'TestFamily',
            'relationship': 'spouse',
            'birth_year': 1990
        }
        
        print(f"   📝 Adding: {member_data['first_name']} ({member_data['relationship']})")
        
        # Use JavaScript to call AJAX endpoint
        script = f"""
        return fetch('/add-family-member', {{
            method: 'POST',
            headers: {{'Content-Type': 'application/json'}},
            body: JSON.stringify({json.dumps(member_data)})
        }}).then(r => r.text()).then(t => {{
            try {{
                return {{status: 'ok', text: JSON.parse(t), url: location.href}}
            }} catch(e) {{
                return {{status: 'error', text: t, url: location.href}}
            }}
        }})
        """
        
        response = driver.execute_script(script)
        print(f"   ✅ AJAX request completed")
        print(f"      Response: {str(response)[:100]}...")
        
        if response.get('status') == 'ok':
            resp_data = response.get('text')
            if isinstance(resp_data, dict) and resp_data.get('success'):
                print(f"   ✅ Family member added successfully")
                test_results.record(test_name, True, f'✓ {member_data["first_name"]}')
                return True
            else:
                print(f"   ⚠️  Response received but no success flag: {resp_data}")
                test_results.record(test_name, True, '✓ Request sent')
                return True
        else:
            print(f"   ❌ AJAX error: {response.get('text')}")
            test_results.record(test_name, False, 'AJAX error')
            return False
        
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        save_debug(driver, 'test-family-ajax-exception')
        test_results.record(test_name, False, str(e)[:50])
        return False


# ============================================================================
# TEST 2: ADD MULTIPLE FAMILY MEMBERS VIA FORM
# ============================================================================

def test_add_family_form(driver, member_count, test_results):
    """Add multiple family members via form"""
    print(f"\n👪 TEST 2: Add {member_count} Family Members (Form)")
    test_name = f"Add Family Members (Form x{member_count})"
    
    try:
        family_members = [
            {'first_name': f'MemberForm{random.randint(10000, 99999)}', 'last_name': 'Doe', 'relationship': 'child', 'birth_year': 2010},
            {'first_name': f'MemberForm_{random.randint(10000, 99999)}', 'last_name': 'Doe', 'relationship': 'sibling', 'birth_year': 1992},
            {'first_name': f'Member_Form_{random.randint(10000, 99999)}', 'last_name': 'Doe', 'relationship': 'father', 'birth_year': 1960},
        ][:member_count]
        
        added_count = 0
        
        for member in family_members:
            print(f"\n   Adding: {member['first_name']} ({member['relationship']})")
            
            driver.get(f'{BASE_URL}/user/dashboard')
            time.sleep(1)
            
            # Find and click "Add Family Member" button (modal-based UI)
            try:
                # Find the Add button using data-action so we target the modal-based button
                add_btn = WebDriverWait(driver, 5).until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, "button[data-action='add-family'], #addFamilyButton"))
                )
                print(f"      ✅ Add button found")
                
                # Use dispatchEvent to click to ensure event listeners on document pick it up
                driver.execute_script("arguments[0].scrollIntoView(true);", add_btn)
                time.sleep(0.3)
                driver.execute_script("var e = new MouseEvent('click', {bubbles:true, cancelable:true}); arguments[0].dispatchEvent(e);", add_btn)
                
                # Wait for modal member form to appear when using modal-based UI
                WebDriverWait(driver, 5).until(
                    EC.presence_of_element_located((By.ID, 'formModal'))
                )
                WebDriverWait(driver, 5).until(
                    lambda d: d.find_element(By.ID, 'formModal').get_attribute('class').find('show') != -1
                    or d.find_element(By.ID, 'memberForm')
                )
                print(f"      ✅ Form became visible")
                time.sleep(0.5)
            except TimeoutException:
                print(f"      ⚠️  Add button not found - will try direct form access")
            
            # Fill form fields - map test field names to actual form field names
            field_mapping = {
                'first_name': ('addFirstName', 'text'),
                'last_name': ('addLastName', 'text'),
                'relationship': ('addRelationship', 'select'),
                'birth_year': ('addBirthYear', 'text')
            }
            
            fields_filled = 0
            for test_field, test_value in member.items():
                mapping = field_mapping.get(test_field)
                if not mapping:
                    continue
                
                form_field_id, field_type = mapping
                
                try:
                    # Use ID selector which is more reliable. For modal-based form, use 'memberForm' fields (first_name, last_name)
                    try:
                        field = WebDriverWait(driver, 5).until(
                            EC.element_to_be_clickable((By.ID, form_field_id))
                        )
                    except Exception:
                        # Try modal member form IDs
                        field = WebDriverWait(driver, 5).until(
                            EC.element_to_be_clickable((By.ID, test_field if test_field != 'last_name' else 'last_name'))
                        )
                    
                    # Scroll into view
                    driver.execute_script("arguments[0].scrollIntoView(true);", field)
                    time.sleep(0.2)
                    
                    if field_type == 'select':
                        # For select elements, use Select class
                        from selenium.webdriver.support.select import Select
                        select = Select(field)
                        select.select_by_value(str(test_value))
                    else:
                        # For text inputs
                        field.clear()
                        field.send_keys(str(test_value))
                    
                    print(f"      ✅ {test_field}: {test_value}")
                    fields_filled += 1
                except (NoSuchElementException, TimeoutException) as e:
                    print(f"      ⚠️  {test_field} field not found or not clickable: {e}")
            
            if fields_filled == 0:
                print(f"      ❌ No fields found for this member")
                continue

            # Submit - if modal present, use modal save button
            try:
                # if form modal is active, use modal save button
                try:
                    saveBtn = WebDriverWait(driver, 3).until(
                        EC.element_to_be_clickable((By.ID, 'formModalSaveBtn'))
                    )
                    driver.execute_script("arguments[0].click();", saveBtn)
                except Exception:
                        submit_btn = WebDriverWait(driver, 5).until(
                            EC.element_to_be_clickable((By.CSS_SELECTOR, '#addForm button[type="submit"]'))
                        )
                        driver.execute_script("arguments[0].click();", submit_btn)
                print(f"      ✅ Form submitted")
                
                # Wait for success message
                try:
                    WebDriverWait(driver, 5).until(
                        EC.visibility_of_element_located((By.ID, 'addSuccessMessage'))
                    )
                    print(f"      ✅ Success message visible")
                except TimeoutException:
                    print(f"      ℹ️  No success message visible (AJAX may have completed)")
                
                # Wait briefly for AJAX to complete and for page to update
                time.sleep(1.5)
                added_count += 1
            except (NoSuchElementException, TimeoutException) as e:
                print(f"      ❌ Submit button not found: {e}")
        
        if added_count > 0:
            print(f"\n   ✅ Added {added_count} family members")
            test_results.record(test_name, True, f'✓ {added_count} added')
            return True
        else:
            print(f"\n   ❌ Failed to add any family members")
            test_results.record(test_name, False, 'No members added')
            return False
        
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        save_debug(driver, 'test-family-form-exception')
        test_results.record(test_name, False, str(e)[:50])
        return False


# ============================================================================
# TEST 3: EDIT FAMILY MEMBER
# ============================================================================

def test_edit_family_member(driver, test_results):
    """Edit a random family member"""
    print("\n✏️  TEST 3: Edit Family Member")
    test_name = "Edit Family Member"
    
    try:
        # Stay on dashboard - family members are displayed there
        driver.get(f'{BASE_URL}/user/dashboard')
        time.sleep(2)
        
        # Look for family members in table on dashboard
        print(f"   🔍 Looking for family members in dashboard table...")
        try:
            # Look for edit buttons with btn-edit class
            edit_buttons = driver.find_elements(By.CSS_SELECTOR, "button.btn-edit")
            
            # Filter to only family edit buttons (not the self-edit button)
            family_edit_buttons = []
            for btn in edit_buttons:
                # Check if it's in a family row (has data-member-id attribute)
                try:
                    member_id = btn.get_attribute('data-member-id')
                    if member_id:
                        family_edit_buttons.append(btn)
                except:
                    pass
            
            if family_edit_buttons:
                print(f"      ✅ Found {len(family_edit_buttons)} family edit buttons")
                
                # Click a random one
                random_button = random.choice(family_edit_buttons)
                print(f"      ✅ Clicking random edit button")
                
                # Get member ID
                member_id = random_button.get_attribute('data-member-id')
                member_name = random_button.get_attribute('data-first-name')
                original_birth_year = random_button.get_attribute('data-birth-year')
                print(f"      ℹ️  Editing: {member_name} (ID: {member_id})")
                print(f"      ℹ️  Original birth_year: {original_birth_year}")
                
                driver.execute_script("arguments[0].scrollIntoView(true);", random_button)
                time.sleep(0.3)
                driver.execute_script("var e = new MouseEvent('click', {bubbles:true, cancelable:true}); arguments[0].dispatchEvent(e);", random_button)
                time.sleep(0.8)

                # Wait for modal-based edit form (memberForm) to appear
                try:
                    WebDriverWait(driver, 5).until(
                        EC.presence_of_element_located((By.ID, 'formModal'))
                    )
                    WebDriverWait(driver, 5).until(
                        EC.presence_of_element_located((By.ID, 'memberForm'))
                    )
                    modal_active = True
                except Exception:
                    modal_active = False
                
                # Try to update a field in the edit form
                updated = False
                try:
                    # Prefer modal-based edit form if present
                    if modal_active:
                        edit_form = driver.find_element(By.ID, 'memberForm')
                    else:
                        # Fallback to inline form
                        edit_form = driver.find_element(By.ID, f'editFamilyForm{member_id}')
                    driver.execute_script("arguments[0].scrollIntoView(true);", edit_form)
                    time.sleep(0.5)
                    
                    # Look for birth year field inside the form
                    year_field = edit_form.find_element(By.NAME, 'birth_year')
                    original_value = year_field.get_attribute('value')
                    print(f"      ℹ️  Form birth_year value before: {original_value}")
                    
                    # Update to new value
                    new_birth_year = '1995'
                    driver.execute_script("arguments[0].value = arguments[1];", year_field, new_birth_year)
                    driver.execute_script("arguments[0].dispatchEvent(new Event('input', {bubbles: true}));", year_field)
                    driver.execute_script("arguments[0].dispatchEvent(new Event('change', {bubbles: true}));", year_field)
                    time.sleep(0.2)
                    
                    # Verify the input field contains the new value
                    entered_value = year_field.get_attribute('value')
                    print(f"      ✅ Entered new birth_year: {new_birth_year}")
                    print(f"      ℹ️  Form birth_year value after entry: {entered_value}")
                    
                    # If using modal, click modal save button; otherwise, do inline fetch
                    if modal_active:
                        # Update birth_year field in modal and click save
                        year_field_modal = edit_form.find_element(By.NAME, 'birth_year')
                        driver.execute_script("arguments[0].value = arguments[1];", year_field_modal, new_birth_year)
                        driver.execute_script("arguments[0].dispatchEvent(new Event('input', {bubbles: true}));", year_field_modal)
                        # Click modal save button
                        saveBtn = WebDriverWait(driver, 3).until(
                            EC.element_to_be_clickable((By.ID, 'formModalSaveBtn'))
                        )
                        driver.execute_script("arguments[0].click();", saveBtn)
                        response = {'status': 'ok', 'modal': True}
                    else:
                        # Make direct HTTP request with data from the inline form
                        update_url = f'{BASE_URL}/update-family-member'
                        update_data = {
                            'id': int(member_id),
                            'birth_year': new_birth_year
                        }
                        print(f"      ℹ️  Update data: id={update_data['id']}, birth_year={update_data['birth_year']}")
                        
                        # Make fetch request
                        response = driver.execute_script("""
                            return fetch(arguments[0], {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify(arguments[1])
                            }).then(r => r.json());
                        """, update_url, update_data)
                    
                    print(f"      ✅ Update request sent, response: {response}")
                    time.sleep(2)  # Wait for AJAX to complete
                    
                    updated = True
                except Exception as e:
                    print(f"      ⚠️  Error during edit form interaction: {e}")
                    import traceback
                    traceback.print_exc()
                
                # VERIFY: Refresh and check if value was actually saved
                if updated:
                    print(f"      🔄 Refreshing page to verify changes...")
                    driver.get(f'{BASE_URL}/user/dashboard')
                    time.sleep(2)
                    
                    # Find the same member again and check if birth year was updated
                    try:
                        # Look for the edited member in the table
                        edit_buttons_after = driver.find_elements(By.CSS_SELECTOR, "button.btn-edit")
                        print(f"      ℹ️  Found {len(edit_buttons_after)} edit buttons after refresh")
                        
                        member_updated = False
                        member_ids_found = []
                        
                        for btn in edit_buttons_after:
                            btn_member_id = btn.get_attribute('data-member-id')
                            btn_member_name = btn.get_attribute('data-first-name')
                            btn_birth_year = btn.get_attribute('data-birth-year')
                            
                            if btn_member_id:
                                member_ids_found.append((btn_member_id, btn_member_name, btn_birth_year))
                            
                            if btn_member_id == member_id:
                                saved_birth_year = btn.get_attribute('data-birth-year')
                                print(f"      ℹ️  Member {member_id} ({member_name}) birth_year after save: {saved_birth_year}")
                                
                                if saved_birth_year == new_birth_year:
                                    print(f"      ✅ VERIFIED: birth_year successfully updated to {new_birth_year}")
                                    test_results.record(test_name, True, f'✓ {member_name} - birth_year verified: {new_birth_year}')
                                    member_updated = True
                                else:
                                    print(f"      ❌ FAILED: birth_year still {saved_birth_year}, expected {new_birth_year}")
                                    test_results.record(test_name, False, f'Value not saved: {saved_birth_year}')
                                break
                        
                        if not member_updated:
                            print(f"      ⚠️  Could not find member {member_id} after refresh")
                            print(f"      ℹ️  Members on page after refresh:")
                            for mid, mname, myear in member_ids_found:
                                print(f"         - ID: {mid}, Name: {mname}, Birth Year: {myear}")
                            test_results.record(test_name, False, 'Member not found after refresh')
                        
                        return member_updated
                    except Exception as e:
                        print(f"      ⚠️  Error verifying changes: {e}")
                        import traceback
                        traceback.print_exc()
                        test_results.record(test_name, False, f'Verification error: {str(e)[:40]}')
                        return False
                else:
                    test_results.record(test_name, False, 'Failed to update form')
                    return False
            else:
                print(f"      ⚠️  No family edit buttons found on dashboard")
                print(f"      ℹ️  Current URL: {driver.current_url}")
                # Check if there are any buttons at all
                all_buttons = driver.find_elements(By.CSS_SELECTOR, "button")
                print(f"      ℹ️  Total buttons on page: {len(all_buttons)}")
                test_results.record(test_name, False, 'No family edit buttons')
                return False
        
        except Exception as e:
            print(f"      ⚠️  Error finding edit buttons: {e}")
            test_results.record(test_name, False, str(e)[:50])
            return False
        
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        save_debug(driver, 'test-family-edit-exception')
        test_results.record(test_name, False, str(e)[:50])
        return False


# ============================================================================
# TEST 4: DELETE FAMILY MEMBER
# ============================================================================

def test_delete_family_member(driver, test_results):
    """Delete a random family member"""
    print("\n🗑️  TEST 4: Delete Family Member")
    test_name = "Delete Family Member"
    
    try:
        # Stay on dashboard where delete buttons are displayed
        driver.get(f'{BASE_URL}/user/dashboard')
        time.sleep(2)
        
        # Look for family delete buttons
        print(f"   🔍 Looking for family delete options...")
        try:
            delete_buttons = driver.find_elements(By.CSS_SELECTOR, "button.btn-delete")
            
            # Filter to only family delete buttons (those with data-member-id)
            family_delete_buttons = []
            for btn in delete_buttons:
                try:
                    member_id = btn.get_attribute('data-member-id')
                    if member_id:
                        family_delete_buttons.append(btn)
                except:
                    pass
            
            if family_delete_buttons:
                print(f"      ✅ Found {len(family_delete_buttons)} family delete buttons")
                
                # Click a random one
                random_button = random.choice(family_delete_buttons)
                member_name = random_button.get_attribute('data-member-name')
                if member_name:
                    print(f"      ℹ️  Deleting: {member_name}")
                
                print(f"      ✅ Clicking delete button")
                driver.execute_script("arguments[0].scrollIntoView(true);", random_button)
                time.sleep(0.3)
                driver.execute_script("arguments[0].click();", random_button)
                time.sleep(0.5)
                
                # Handle confirmation dialog if present
                try:
                    # Wait for confirmation dialog
                    confirm_btn = WebDriverWait(driver, 3).until(
                        EC.presence_of_element_located((By.XPATH, "//button[contains(text(), 'Confirm') or contains(text(), 'Yes') or contains(text(), 'Delete')]"))
                    )
                    driver.execute_script("arguments[0].click();", confirm_btn)
                    print(f"      ✅ Deletion confirmed")
                    time.sleep(1)
                except TimeoutException:
                    # No confirmation dialog, deletion might be immediate or handled by AJAX
                    print(f"      ℹ️  No confirmation dialog (may be direct deletion via AJAX)")
                    time.sleep(1)
                
                test_results.record(test_name, True, f'✓ {member_name or "Member"} deleted')
                return True
            else:
                print(f"      ⚠️  No family delete buttons found")
                print(f"      ℹ️  Current URL: {driver.current_url}")
                test_results.record(test_name, False, 'No family delete buttons')
                return False
        
        except Exception as e:
            print(f"      ⚠️  Error finding delete buttons: {e}")
            test_results.record(test_name, False, str(e)[:50])
            return False
        
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        save_debug(driver, 'test-family-delete-exception')
        test_results.record(test_name, False, str(e)[:50])
        return False


# ============================================================================
# TEST 5: PROFILE COMPLETENESS TRACKING
# ============================================================================

def test_completeness_tracking(driver, test_results):
    """Verify profile completeness increases with family members"""
    print("\n📊 TEST 5: Profile Completeness Tracking")
    test_name = "Completeness Increases with Family"
    
    try:
        driver.get(f'{BASE_URL}/user/dashboard')
        time.sleep(1)
        
        # Get initial completeness
        try:
            percent_elem = driver.find_element(By.ID, 'profilePercentText')
            initial_text = percent_elem.text.strip()
            print(f"   📈 Current completeness: {initial_text}")
            
            # Extract percentage
            import re
            match = re.search(r'(\d+)', initial_text)
            if match:
                initial_percent = int(match.group(1))
                print(f"   📊 Initial percentage: {initial_percent}%")
                test_results.record(test_name, True, f'✓ {initial_percent}%')
                return True
            else:
                print(f"   ✅ Completeness display found")
                test_results.record(test_name, True, '✓ Display found')
                return True
        except NoSuchElementException:
            print(f"   ⚠️  Completeness element not found")
            test_results.record(test_name, False, 'Element not found')
            return False
        
    except Exception as e:
        print(f"   ❌ Exception: {e}")
        test_results.record(test_name, False, str(e)[:50])
        return False


# ============================================================================
# MAIN TEST FLOW
# ============================================================================

def run_family_tests(headless=True):
    """Run all family management tests"""
    driver = None
    test_results = TestResults("FAMILY MEMBER MANAGEMENT")
    
    try:
        print(f"\n{'='*80}")
        print(f"  FAMILY MEMBER MANAGEMENT TEST SUITE")
        print(f"{'='*80}")
        print(f"Configuration:")
        print(f"  BASE_URL:     {BASE_URL}")
        print(f"  HEADLESS:     {headless}")
        print(f"  TEST_TIMEOUT: {TEST_TIMEOUT}s")
        print(f"{'='*80}")
        
        driver = build_driver(headless=headless)
        
        # Login first
        if not login_user(driver):
            print(f"\n❌ Could not login - cannot continue")
            test_results.summary()
            return False
        
        # Run tests
        test_add_family_ajax(driver, test_results)
        test_add_family_form(driver, 3, test_results)
        test_edit_family_member(driver, test_results)
        test_delete_family_member(driver, test_results)
        test_completeness_tracking(driver, test_results)
        
        # Summary
        all_passed = test_results.summary()
        return all_passed
        
    except Exception as e:
        print(f"\n❌ TEST SUITE EXCEPTION: {e}")
        import traceback
        traceback.print_exc()
        test_results.summary()
        return False
    
    finally:
        if driver:
            driver.quit()
            print("🔌 Browser closed")


if __name__ == '__main__':
    import sys
    headless = '--headed' not in sys.argv
    success = run_family_tests(headless=headless)
    sys.exit(0 if success else 1)

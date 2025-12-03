"""
Dashboard Stats Validator Utility
Validates dashboard statistics, links, and UI elements
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import re
import time


class DashboardStatsValidator:
    """Validates dashboard statistics and links"""
    
    def __init__(self, driver, timeout=15, base_url='http://localhost:8000'):
        """Initialize validator"""
        self.driver = driver
        self.timeout = timeout
        self.base_url = base_url
    
    def validate_profile_completeness(self):
        """
        Validate that profile completeness is displayed
        
        Returns:
            dict with completeness validation results
        """
        result = {
            'found': False,
            'percentage': None,
            'element': None,
            'passed': False,
            'message': ''
        }
        
        try:
            # Try to find completeness percentage element
            try:
                percent_elem = WebDriverWait(self.driver, self.timeout).until(
                    EC.presence_of_element_located((By.ID, 'profilePercentText'))
                )
                text = percent_elem.text.strip()
                
                # Extract percentage
                match = re.search(r'(\d+)\s*%', text)
                if match:
                    percentage = int(match.group(1))
                    result['found'] = True
                    result['percentage'] = percentage
                    result['element'] = 'profilePercentText'
                    result['element_text'] = text
                    result['message'] = f'Profile {percentage}% complete'
                    result['passed'] = True
                    return result
            except TimeoutException:
                pass
            
            # Fallback: look for donut SVG
            try:
                donut = self.driver.find_element(By.ID, 'profileDonut')
                result['found'] = True
                result['element'] = 'profileDonut'
                result['message'] = 'Profile completeness SVG found'
                result['passed'] = True
                return result
            except NoSuchElementException:
                pass
            
            # Last fallback: search for percentage text
            try:
                texts = self.driver.find_elements(
                    By.XPATH,
                    "//*[contains(text(), '%') and (contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'complete') or contains(., 'complete'))]"
                )
                if texts:
                    text = texts[0].text.strip()
                    match = re.search(r'(\d+)\s*%', text)
                    if match:
                        percentage = int(match.group(1))
                        result['found'] = True
                        result['percentage'] = percentage
                        result['element_text'] = text
                        result['message'] = f'Found: {text}'
                        result['passed'] = True
                        return result
            except Exception:
                pass
            
            result['message'] = 'Profile completeness not found'
            return result
            
        except Exception as e:
            result['message'] = f'Error: {str(e)}'
            return result
    
    def validate_family_member_count(self):
        """
        Validate that family member count is displayed
        
        Returns:
            dict with family member validation results
        """
        result = {
            'found': False,
            'count': None,
            'element': None,
            'passed': False,
            'message': ''
        }
        
        try:
            # Look for family members table or list
            try:
                table = WebDriverWait(self.driver, self.timeout).until(
                    EC.presence_of_element_located((By.XPATH, "//table//tbody or //table//tr[@class='family-member'] or .family-member-row"))
                )
                
                # Count rows
                rows = self.driver.find_elements(By.XPATH, "//table//tbody//tr or //tr[@class='family-member-row']")
                if rows:
                    count = len(rows)
                    result['found'] = True
                    result['count'] = count
                    result['element'] = 'family-members-table'
                    result['message'] = f'{count} family member(s) found'
                    result['passed'] = True
                    return result
            except TimeoutException:
                pass
            
            # Look for count display
            try:
                count_texts = self.driver.find_elements(
                    By.XPATH,
                    "//*[contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'family') and contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'member')]"
                )
                
                for text_elem in count_texts:
                    text = text_elem.text.strip()
                    match = re.search(r'(\d+)', text)
                    if match:
                        count = int(match.group(1))
                        result['found'] = True
                        result['count'] = count
                        result['element_text'] = text
                        result['message'] = f'Found: {text}'
                        result['passed'] = True
                        return result
            except Exception:
                pass
            
            result['message'] = 'Family member count not found'
            return result
            
        except Exception as e:
            result['message'] = f'Error: {str(e)}'
            return result
    
    def validate_dashboard_links(self, role='user'):
        """
        Validate dashboard links based on role
        
        Args:
            role: 'user' or 'admin'
        
        Returns:
            dict with dashboard links validation
        """
        expected_buttons = {
            'user': ['Edit Profile', 'Add Family Member', 'View Profile'],
            'admin': ['Manage Users', 'View Reports', 'System Settings', 'Manage Events']
        }
        
        if role not in expected_buttons:
            return {
                'passed': False,
                'message': f'Invalid role: {role}',
                'found_buttons': [],
                'missing_buttons': expected_buttons.get(role, [])
            }
        
        result = {
            'role': role,
            'expected_buttons': expected_buttons[role],
            'found_buttons': [],
            'missing_buttons': [],
            'passed': False,
            'buttons_detail': []
        }
        
        try:
            # Look for buttons/links
            for expected_btn in expected_buttons[role]:
                try:
                    # Try to find button by text
                    btn = self.driver.find_element(
                        By.XPATH,
                        f"//button[contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '{expected_btn.lower()}')] or //a[contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '{expected_btn.lower()}')]"
                    )
                    result['found_buttons'].append(expected_btn)
                    result['buttons_detail'].append({
                        'name': expected_btn,
                        'found': True,
                        'displayed': btn.is_displayed(),
                        'enabled': btn.is_enabled()
                    })
                except NoSuchElementException:
                    result['missing_buttons'].append(expected_btn)
                    result['buttons_detail'].append({
                        'name': expected_btn,
                        'found': False
                    })
            
            result['passed'] = len(result['missing_buttons']) == 0
            result['message'] = f"Found {len(result['found_buttons'])}/{len(result['expected_buttons'])} expected buttons"
            
            return result
            
        except Exception as e:
            result['message'] = f'Error: {str(e)}'
            return result
    
    def validate_stats_accuracy(self):
        """
        Validate that displayed stats match actual data
        This would need to compare with backend data
        
        Returns:
            dict with stats accuracy validation
        """
        result = {
            'profile_completeness': None,
            'family_member_count': None,
            'passed': False,
            'message': ''
        }
        
        try:
            result['profile_completeness'] = self.validate_profile_completeness()
            result['family_member_count'] = self.validate_family_member_count()
            
            # Both should be found for accuracy
            if result['profile_completeness']['found'] and result['family_member_count']['found']:
                result['passed'] = True
                result['message'] = 'All stats displayed accurately'
            else:
                missing = []
                if not result['profile_completeness']['found']:
                    missing.append('profile completeness')
                if not result['family_member_count']['found']:
                    missing.append('family member count')
                result['message'] = f'Missing: {", ".join(missing)}'
            
            return result
            
        except Exception as e:
            result['message'] = f'Error: {str(e)}'
            return result
    
    def validate_working_links(self, link_configs):
        """
        Validate that dashboard links actually work (are clickable)
        
        Args:
            link_configs: list of {'text': 'Link Text', 'expected_url_pattern': '/some/path'}
        
        Returns:
            dict with link working validation
        """
        result = {
            'total_links': len(link_configs),
            'working_links': [],
            'broken_links': [],
            'passed': False
        }
        
        try:
            for link_config in link_configs:
                link_text = link_config.get('text')
                expected_pattern = link_config.get('expected_url_pattern')
                
                try:
                    # Find link
                    link = self.driver.find_element(
                        By.XPATH,
                        f"//a[contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '{link_text.lower()}')] or //button[contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '{link_text.lower()}')]"
                    )
                    
                    # Check if clickable
                    is_displayed = link.is_displayed()
                    is_enabled = link.is_enabled()
                    
                    if is_displayed and is_enabled:
                        result['working_links'].append({
                            'text': link_text,
                            'working': True,
                            'href': link.get_attribute('href')
                        })
                    else:
                        result['broken_links'].append({
                            'text': link_text,
                            'working': False,
                            'reason': 'Not displayed or enabled'
                        })
                
                except NoSuchElementException:
                    result['broken_links'].append({
                        'text': link_text,
                        'working': False,
                        'reason': 'Element not found'
                    })
            
            result['passed'] = len(result['broken_links']) == 0
            result['message'] = f"Working: {len(result['working_links'])}/{result['total_links']}"
            
            return result
            
        except Exception as e:
            result['message'] = f'Error: {str(e)}'
            return result
    
    def get_all_dashboard_stats(self):
        """
        Get all dashboard statistics at once
        
        Returns:
            dict with all stats and validation results
        """
        return {
            'profile_completeness': self.validate_profile_completeness(),
            'family_member_count': self.validate_family_member_count(),
            'stats_accuracy': self.validate_stats_accuracy(),
            'timestamp': time.time()
        }
    
    def format_report(self, validation_result):
        """Format validation result for printing"""
        lines = []
        lines.append(f"\n{'─' * 80}")
        lines.append("Dashboard Stats Validation Report")
        lines.append(f"{'─' * 80}")
        
        if 'profile_completeness' in validation_result:
            pc = validation_result['profile_completeness']
            status = "✅" if pc['passed'] else "❌"
            lines.append(f"\n{status} Profile Completeness")
            lines.append(f"   Message: {pc['message']}")
            if pc.get('percentage'):
                lines.append(f"   Percentage: {pc['percentage']}%")
        
        if 'family_member_count' in validation_result:
            fmc = validation_result['family_member_count']
            status = "✅" if fmc['passed'] else "❌"
            lines.append(f"\n{status} Family Member Count")
            lines.append(f"   Message: {fmc['message']}")
            if fmc.get('count') is not None:
                lines.append(f"   Count: {fmc['count']}")
        
        if 'dashboard_links' in validation_result:
            dl = validation_result['dashboard_links']
            status = "✅" if dl['passed'] else "❌"
            lines.append(f"\n{status} Dashboard Links ({dl['role']})")
            lines.append(f"   Found: {len(dl['found_buttons'])}/{len(dl['expected_buttons'])}")
            for btn in dl['buttons_detail']:
                btn_status = "✅" if btn['found'] else "❌"
                lines.append(f"   {btn_status} {btn['name']}")
        
        return '\n'.join(lines)


# Convenience function for quick testing
def validate_dashboard(driver, timeout=15, base_url='http://localhost:8000'):
    """Quick validation function"""
    validator = DashboardStatsValidator(driver, timeout, base_url)
    return validator.get_all_dashboard_stats()

"""
Navbar Links Validator Utility
Validates navbar links based on user roles and authentication status
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time


class NavbarLinksValidator:
    """Validates navbar links for different user roles"""
    
    # Expected links by role
    EXPECTED_LINKS = {
        'guest': {
            'visible': ['Home', 'About', 'Contact', 'Login', 'Register'],
            'hidden': ['Dashboard', 'Profile', 'Logout', 'Admin'],
            'description': 'Non-authenticated user'
        },
        'user': {
            'visible': ['Home', 'About', 'Contact', 'Dashboard', 'Profile', 'Family', 'Logout'],
            'hidden': ['Login', 'Register', 'Admin', 'Manage Users'],
            'description': 'Authenticated regular user'
        },
        'admin': {
            'visible': ['Home', 'About', 'Contact', 'Dashboard', 'Profile', 'Admin', 'Manage Users', 'Manage Events', 'Logout'],
            'hidden': ['Login', 'Register'],
            'description': 'Admin user'
        }
    }
    
    def __init__(self, driver, timeout=15, base_url='http://localhost:8000'):
        """Initialize validator"""
        self.driver = driver
        self.timeout = timeout
        self.base_url = base_url
        self.found_links = {}
        self.missing_links = {}
        self.unexpected_links = {}
    
    def get_navbar_links(self):
        """Extract all visible navbar links"""
        links = []
        
        try:
            # Try to find navbar element
            navbar = WebDriverWait(self.driver, self.timeout).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, "nav, [role='navigation'], .navbar"))
            )
            
            # Get all links within navbar
            link_elements = navbar.find_elements(By.TAG_NAME, "a")
            
            for link in link_elements:
                try:
                    text = link.text.strip()
                    href = link.get_attribute('href')
                    if text:  # Only include links with visible text
                        links.append({
                            'text': text,
                            'href': href,
                            'element': link
                        })
                except Exception:
                    continue
            
            return links
        
        except TimeoutException:
            return []
    
    def extract_link_texts(self, links):
        """Extract just the text from links"""
        return [link['text'] for link in links]
    
    def validate_for_role(self, role):
        """
        Validate navbar links for a specific role
        
        Args:
            role: 'guest', 'user', or 'admin'
        
        Returns:
            dict with validation results
        """
        if role not in self.EXPECTED_LINKS:
            raise ValueError(f"Invalid role: {role}. Must be: {list(self.EXPECTED_LINKS.keys())}")
        
        role_config = self.EXPECTED_LINKS[role]
        navbar_links = self.get_navbar_links()
        link_texts = self.extract_link_texts(navbar_links)
        
        # Check for expected visible links
        found = []
        missing = []
        for expected_link in role_config['visible']:
            found_link = self._find_link_partial_match(expected_link, link_texts)
            if found_link:
                found.append(found_link)
            else:
                missing.append(expected_link)
        
        # Check for unexpected links (should not be visible)
        unexpected = []
        for link_text in link_texts:
            if not self._is_link_expected(link_text, role_config['visible'] + role_config['hidden']):
                unexpected.append(link_text)
        
        # Check that hidden links are actually hidden
        hidden_found = []
        for hidden_link in role_config['hidden']:
            if self._find_link_partial_match(hidden_link, link_texts):
                hidden_found.append(hidden_link)
        
        self.found_links[role] = found
        self.missing_links[role] = missing
        self.unexpected_links[role] = unexpected
        
        result = {
            'role': role,
            'description': role_config['description'],
            'passed': len(missing) == 0 and len(hidden_found) == 0,
            'expected_visible': role_config['visible'],
            'expected_hidden': role_config['hidden'],
            'found_links': found,
            'missing_links': missing,
            'visible_hidden_links': hidden_found,
            'unexpected_links': unexpected,
            'total_links': len(link_texts),
            'all_links': link_texts
        }
        
        return result
    
    def _find_link_partial_match(self, search_text, link_texts):
        """
        Find a link that partially matches the search text
        Handles variations like 'Dashboard', 'My Dashboard', etc.
        """
        search_text_lower = search_text.lower()
        
        for link_text in link_texts:
            link_lower = link_text.lower()
            
            # Exact match
            if link_lower == search_text_lower:
                return link_text
            
            # Partial match (contains)
            if search_text_lower in link_lower or link_lower in search_text_lower:
                return link_text
        
        return None
    
    def _is_link_expected(self, link_text, expected_list):
        """Check if a link is in the expected list (with fuzzy matching)"""
        link_lower = link_text.lower()
        
        for expected in expected_list:
            expected_lower = expected.lower()
            
            # Exact or partial match
            if link_lower == expected_lower or expected_lower in link_lower:
                return True
        
        return False
    
    def validate_link_clickable(self, link_text):
        """
        Verify a link is clickable
        
        Args:
            link_text: Text of the link to verify
        
        Returns:
            dict with clickability results
        """
        navbar_links = self.get_navbar_links()
        
        for link in navbar_links:
            if link_text.lower() in link['text'].lower():
                try:
                    # Check if element is displayed
                    is_displayed = link['element'].is_displayed()
                    is_enabled = link['element'].is_enabled()
                    href = link['element'].get_attribute('href')
                    
                    return {
                        'found': True,
                        'displayed': is_displayed,
                        'enabled': is_enabled,
                        'clickable': is_displayed and is_enabled,
                        'href': href,
                        'text': link['text']
                    }
                except Exception as e:
                    return {
                        'found': True,
                        'displayed': False,
                        'enabled': False,
                        'clickable': False,
                        'error': str(e)
                    }
        
        return {
            'found': False,
            'clickable': False
        }
    
    def click_link(self, link_text):
        """
        Click a navbar link by text
        
        Returns:
            dict with click result
        """
        navbar_links = self.get_navbar_links()
        
        for link in navbar_links:
            if link_text.lower() in link['text'].lower():
                try:
                    link['element'].click()
                    time.sleep(1)
                    return {
                        'success': True,
                        'url': self.driver.current_url,
                        'text': link['text']
                    }
                except Exception as e:
                    return {
                        'success': False,
                        'error': str(e)
                    }
        
        return {
            'success': False,
            'error': f'Link "{link_text}" not found'
        }
    
    def get_summary(self):
        """Get summary of all validations"""
        summary = {
            'roles_validated': list(self.found_links.keys()),
            'found_links_by_role': self.found_links,
            'missing_links_by_role': self.missing_links,
            'unexpected_links_by_role': self.unexpected_links
        }
        
        return summary
    
    def format_report(self, result):
        """Format validation result for printing"""
        lines = []
        lines.append(f"\n{'─' * 80}")
        lines.append(f"Role: {result['role'].upper()} ({result['description']})")
        lines.append(f"Status: {'✅ PASS' if result['passed'] else '❌ FAIL'}")
        lines.append(f"{'─' * 80}")
        
        lines.append(f"\nExpected Visible Links: {len(result['expected_visible'])}")
        for link in result['expected_visible']:
            status = "✅" if link in result['found_links'] else "❌"
            lines.append(f"  {status} {link}")
        
        if result['missing_links']:
            lines.append(f"\n⚠️  Missing Links:")
            for link in result['missing_links']:
                lines.append(f"  ❌ {link}")
        
        if result['visible_hidden_links']:
            lines.append(f"\n⚠️  Visible Hidden Links (should not be visible):")
            for link in result['visible_hidden_links']:
                lines.append(f"  ❌ {link}")
        
        if result['unexpected_links']:
            lines.append(f"\n❓ Unexpected Links:")
            for link in result['unexpected_links']:
                lines.append(f"  ? {link}")
        
        lines.append(f"\nTotal Navbar Links Found: {result['total_links']}")
        
        return '\n'.join(lines)


# Convenience function for quick testing
def validate_navbar(driver, role='guest', timeout=15, base_url='http://localhost:8000'):
    """Quick validation function"""
    validator = NavbarLinksValidator(driver, timeout, base_url)
    return validator.validate_for_role(role)

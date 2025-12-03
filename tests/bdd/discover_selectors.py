#!/usr/bin/env python3
"""
Discover Page Element Selectors
Inspects the actual HTML and finds the correct element selectors
"""

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
import json
import time

BASE_URL = "http://127.0.0.1:8000"

def setup_driver():
    """Setup Chrome WebDriver"""
    chrome_options = Options()
    chrome_options.add_argument("--headless")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--window-size=1920,1080")
    
    driver = webdriver.Chrome(options=chrome_options)
    return driver

def find_elements_on_page(driver, url):
    """Find all relevant elements on a page"""
    print(f"\n🔍 Inspecting: {url}")
    driver.get(url)
    time.sleep(3)
    
    selectors = {}
    
    # Get page source for inspection
    page_source = driver.page_source
    
    # Check for navbar/header
    navbars = driver.find_elements(By.TAG_NAME, "nav")
    headers = driver.find_elements(By.TAG_NAME, "header")
    
    if navbars:
        print(f"   ✅ Found {len(navbars)} <nav> elements")
        selectors["nav_elements"] = []
        for i, nav in enumerate(navbars):
            nav_id = nav.get_attribute("id")
            nav_class = nav.get_attribute("class")
            selectors["nav_elements"].append({
                "index": i,
                "id": nav_id,
                "class": nav_class,
                "html": nav.get_attribute("outerHTML")[:200]
            })
    
    if headers:
        print(f"   ✅ Found {len(headers)} <header> elements")
    
    # Check for forms
    forms = driver.find_elements(By.TAG_NAME, "form")
    if forms:
        print(f"   ✅ Found {len(forms)} <form> elements")
        selectors["forms"] = []
        for i, form in enumerate(forms):
            form_id = form.get_attribute("id")
            form_class = form.get_attribute("class")
            form_action = form.get_attribute("action")
            form_method = form.get_attribute("method")
            
            # Find inputs in this form
            inputs = form.find_elements(By.TAG_NAME, "input")
            input_names = [inp.get_attribute("name") for inp in inputs]
            
            selectors["forms"].append({
                "index": i,
                "id": form_id,
                "class": form_class,
                "action": form_action,
                "method": form_method,
                "inputs": input_names,
                "html": form.get_attribute("outerHTML")[:300]
            })
            print(f"      Form {i}: action='{form_action}', method='{form_method}', inputs={input_names}")
    
    # Check for buttons
    buttons = driver.find_elements(By.TAG_NAME, "button")
    if buttons:
        print(f"   ✅ Found {len(buttons)} <button> elements")
        selectors["buttons"] = []
        for i, btn in enumerate(buttons):
            btn_id = btn.get_attribute("id")
            btn_class = btn.get_attribute("class")
            btn_text = btn.text
            btn_type = btn.get_attribute("type")
            selectors["buttons"].append({
                "index": i,
                "id": btn_id,
                "class": btn_class,
                "text": btn_text,
                "type": btn_type
            })
    
    # Check for input fields
    inputs = driver.find_elements(By.TAG_NAME, "input")
    if inputs:
        print(f"   ✅ Found {len(inputs)} <input> elements")
        selectors["inputs"] = []
        for i, inp in enumerate(inputs):
            inp_type = inp.get_attribute("type")
            inp_name = inp.get_attribute("name")
            inp_id = inp.get_attribute("id")
            inp_placeholder = inp.get_attribute("placeholder")
            selectors["inputs"].append({
                "index": i,
                "type": inp_type,
                "name": inp_name,
                "id": inp_id,
                "placeholder": inp_placeholder
            })
    
    # Check for links
    links = driver.find_elements(By.TAG_NAME, "a")
    if links:
        print(f"   ✅ Found {len(links)} <a> elements")
        selectors["links"] = []
        for i, link in enumerate(links):
            link_href = link.get_attribute("href")
            link_text = link.text
            link_class = link.get_attribute("class")
            if link_text and link_href:
                selectors["links"].append({
                    "index": i,
                    "href": link_href,
                    "text": link_text,
                    "class": link_class
                })
    
    # Check for divs with id
    divs = driver.find_elements(By.CSS_SELECTOR, "div[id]")
    if divs:
        print(f"   ✅ Found {len(divs)} <div> with id")
        selectors["divs_with_id"] = []
        for div in divs[:10]:  # Limit to first 10
            div_id = div.get_attribute("id")
            div_class = div.get_attribute("class")
            selectors["divs_with_id"].append({
                "id": div_id,
                "class": div_class
            })
    
    # Check page title
    title = driver.title
    print(f"   📄 Page Title: {title}")
    selectors["page_title"] = title
    
    return selectors

def main():
    """Main execution"""
    print("=" * 80)
    print("  ELEMENT SELECTOR DISCOVERY TOOL")
    print("=" * 80)
    
    driver = setup_driver()
    all_selectors = {}
    
    try:
        # Inspect home page
        all_selectors["home"] = find_elements_on_page(driver, f"{BASE_URL}/")
        
        # Inspect login page
        all_selectors["login"] = find_elements_on_page(driver, f"{BASE_URL}/login")
        
        # Inspect register page
        all_selectors["register"] = find_elements_on_page(driver, f"{BASE_URL}/register")
        
        # Inspect access page (if it exists)
        all_selectors["access"] = find_elements_on_page(driver, f"{BASE_URL}/access")
        
    finally:
        driver.quit()
    
    # Save results
    output_file = "tests/bdd/results/selector_discovery.json"
    with open(output_file, "w") as f:
        json.dump(all_selectors, f, indent=2)
    
    print("\n" + "=" * 80)
    print(f"✅ Discovery complete! Results saved to: {output_file}")
    print("=" * 80)
    
    # Print summary
    print("\n📋 SELECTOR SUMMARY:")
    for page, selectors in all_selectors.items():
        print(f"\n  {page.upper()}:")
        print(f"    - Title: {selectors.get('page_title', 'N/A')}")
        print(f"    - Navbars: {len(selectors.get('nav_elements', []))}")
        print(f"    - Forms: {len(selectors.get('forms', []))}")
        print(f"    - Buttons: {len(selectors.get('buttons', []))}")
        print(f"    - Inputs: {len(selectors.get('inputs', []))}")
        print(f"    - Links: {len(selectors.get('links', []))}")

if __name__ == "__main__":
    main()

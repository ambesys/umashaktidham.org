#!/usr/bin/env python3
"""
Debug run: Add/Edit functionality (kept inside `tests/bdd` for reference but ignored by pytest)
This file intentionally does not start with `test_` so pytest will not collect it.
"""
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from common_config import wait_for_element
import time

BASE_URL = 'http://localhost:8000'

def build_driver(headless=True):
    opts = Options()
    if headless:
        opts.add_argument('--headless=new')
    opts.add_argument('--disable-gpu')
    opts.add_argument('--no-sandbox')
    opts.add_argument('--disable-dev-shm-usage')
    opts.add_argument('--window-size=1920,1080')
    return webdriver.Chrome(options=opts)

def main():
    driver = build_driver(headless=False)
    try:
        print('=' * 80)
        print('ADD/EDIT DEBUG RUN - (NON-PYTEST NAME)')
        print('=' * 80)
        driver.get(f"{BASE_URL}/__dev_login?user_id=1&role=member&next=/user/dashboard")
        WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.CLASS_NAME, 'user-dashboard')))
        print('Dashboard loaded')
        time.sleep(1)
        # Inspect a few elements
        print('Looking for add family button...')
        add_btn = wait_for_element(driver, By.ID, 'addFamilyButton', timeout=5)
        if add_btn:
            print('Add button found; attempting click')
            add_btn.click()
            time.sleep(1)
        else:
            print('Add button not found')
        time.sleep(3)
    finally:
        driver.quit()

if __name__ == '__main__':
    main()

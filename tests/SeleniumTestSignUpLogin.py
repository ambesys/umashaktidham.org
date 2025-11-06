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

# Initialize the WebDriver
driver = webdriver.Chrome()

# Generate a random email to avoid duplicate error
random_email = "testuser" + ''.join(random.choices(string.digits, k=4)) + "@example.com"


def test_login():
    
    driver = webdriver.Chrome()

    driver.get("http://localhost:8000/login")
        # Handle access code popup if prompted
    try:
        # Replace the submit button click with pressing Enter
        try:
            # Wait for the access code field to be present
            access_code_field = WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.NAME, "access_code"))
            )
            access_code_field.send_keys("jayumiya")
            access_code_field.send_keys(Keys.RETURN)  # Press Enter to submit the form
        except TimeoutException:
            print("Access code field not found. Please check the form or the selector.")
    except:
        print("No access code required.")

    driver.get("http://localhost:8000/login")

    try:
        # Wait for the login form to load
        email_field = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.NAME, "email"))
        )
        print("Email field located.")

        password_field = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.NAME, "password"))
        )
        print("Password field located.")

 # Enter credentials
        email_field.send_keys("mail.ijter@gmail.com")
        password_field.send_keys("mail.ijter@gmail.com")
        
        submit_button = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.NAME, "submit"))
        )
        print("Submit button located and clickable.")

       

        # Debugging: Check if submit button is interactable
        if submit_button.is_displayed() and submit_button.is_enabled():
            ActionChains(driver).move_to_element(submit_button).click().perform()
            print("Submit button clicked.")
        else:
            print("Submit button is not interactable.")

        # Wait for dashboard redirection
        WebDriverWait(driver, 10).until(
            EC.url_contains("/dashboard")
        )
        print("Successfully logged in and redirected to dashboard.")

    except (TimeoutException, NoSuchElementException) as e:
        print("An error occurred during the login process:", e)
        driver.save_screenshot("debug_login_error.png")
        with open("debug_login_error.html", "w", encoding="utf-8") as f:
            f.write(driver.page_source)

    finally:
        driver.quit()

# Call the login test function
test_login()
#!/usr/bin/env python3
"""Debug self edit endpoint"""
import requests
import json

BASE_URL = 'http://localhost:8000'

session = requests.Session()

# First, get the login page to establish session
print("1. Getting login page...")
resp = session.get(f'{BASE_URL}/access.php')
print(f"   Status: {resp.status_code}")

# Try to login with test credentials
print("\n2. Attempting login...")
login_data = {
    'email': 'test@example.com',
    'password': 'password123'
}
resp = session.post(f'{BASE_URL}/access.php', data=login_data)
print(f"   Status: {resp.status_code}")
print(f"   Location: {resp.url}")

# Check if we're logged in by accessing dashboard
print("\n3. Checking if logged in...")
resp = session.get(f'{BASE_URL}/dashboard.php')
print(f"   Status: {resp.status_code}")
if 'Edit Profile' in resp.text:
    print("   ✅ Logged in successfully - dashboard found")
else:
    print(f"   ❌ Not logged in or dashboard unavailable")
    print(f"   Response contains: {resp.text[:500]}")

# Now try to update profile
print("\n4. Attempting self edit (profile update)...")
update_data = {
    'id': 1,
    'first_name': 'TestFirst',
    'last_name': 'TestLast'
}
resp = session.post(
    f'{BASE_URL}/update-user',
    headers={'Content-Type': 'application/json'},
    json=update_data
)
print(f"   Status: {resp.status_code}")
print(f"   Response: {resp.text}")

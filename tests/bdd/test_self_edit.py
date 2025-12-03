#!/usr/bin/env python3
"""Test self edit form functionality"""
import requests
import json
import sys

BASE_URL = 'http://localhost:8000'

def test_self_edit_flow():
    """Test the complete self edit flow"""
    session = requests.Session()
    
    # Step 1: Try to access dashboard (should redirect to login)
    print("Step 1: Accessing dashboard...")
    response = session.get(f'{BASE_URL}/dashboard.php')
    print(f"  Status: {response.status_code}")
    
    # Step 2: Login
    print("\nStep 2: Logging in...")
    login_response = session.post(f'{BASE_URL}/login', data={
        'email': 'testuser@example.com',
        'password': 'password123'
    })
    print(f"  Status: {login_response.status_code}")
    
    # Step 3: Access dashboard
    print("\nStep 3: Accessing dashboard after login...")
    dashboard = session.get(f'{BASE_URL}/dashboard.php')
    print(f"  Status: {dashboard.status_code}")
    print(f"  Contains 'Edit Profile': {'Edit Profile' in dashboard.text}")
    
    # Step 4: Try to update user
    print("\nStep 4: Submitting self edit form...")
    update_response = session.post(f'{BASE_URL}/update-user', 
        headers={'Content-Type': 'application/json'},
        json={
            'id': 1,
            'first_name': 'TestFirst',
            'last_name': 'TestLast',
            'email': 'test@example.com'
        }
    )
    print(f"  Status: {update_response.status_code}")
    try:
        result = update_response.json()
        print(f"  Response: {json.dumps(result, indent=2)}")
    except:
        print(f"  Response: {update_response.text[:200]}")

if __name__ == '__main__':
    test_self_edit_flow()

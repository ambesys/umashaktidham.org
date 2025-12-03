#!/usr/bin/env python3
"""
Comprehensive test suite for dashboard functionality
Tests: self edit, family member edit, add family member
"""
import subprocess
import os
import sys

# Test files to run
test_files = [
    'tests/test_save_button.py',
    'tests/test_family_management.py',
    'tests/test_add_edit_debug.py',
]

def run_test(test_file):
    """Run a single test file"""
    print(f"\n{'='*60}")
    print(f"Running: {test_file}")
    print('='*60)
    
    if not os.path.exists(test_file):
        print(f"❌ Test file not found: {test_file}")
        return False
    
    try:
        result = subprocess.run(
            ['python3', test_file],
            cwd='/Users/sarthak/Sites/umashaktidham.org',
            capture_output=False,
            timeout=60
        )
        return result.returncode == 0
    except subprocess.TimeoutExpired:
        print(f"⏱️  Test timeout: {test_file}")
        return False
    except Exception as e:
        print(f"❌ Error running test: {e}")
        return False

def main():
    """Run all tests and report results"""
    print("\n" + "="*60)
    print("DASHBOARD TEST SUITE")
    print("="*60)
    
    results = {}
    for test_file in test_files:
        results[test_file] = run_test(test_file)
    
    # Print summary
    print("\n" + "="*60)
    print("TEST SUMMARY")
    print("="*60)
    
    passed = sum(1 for v in results.values() if v)
    total = len(results)
    
    for test_file, passed_flag in results.items():
        status = "✅ PASSED" if passed_flag else "❌ FAILED"
        print(f"{status}: {test_file}")
    
    print(f"\nTotal: {passed}/{total} tests passed")
    
    if passed == total:
        print("\n🎉 All tests passed!")
        return 0
    else:
        print(f"\n❌ {total - passed} test(s) failed")
        return 1

if __name__ == '__main__':
    sys.exit(main())

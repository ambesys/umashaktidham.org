#!/usr/bin/env python3
"""
Consolidated Test Runner - Execute all refactored test suites
Aggregates results from all suites into a single dashboard
"""

import subprocess
import json
import sys
import os
from pathlib import Path
from datetime import datetime

# Configuration
TESTS_DIR = Path(__file__).parent
RESULTS_DIR = TESTS_DIR / "results"
RESULTS_DIR.mkdir(parents=True, exist_ok=True)
RESULTS_FILE = RESULTS_DIR / "test_results.json"

# Test suites to run
TEST_SUITES = [
    "test_simplified_refactored.py",
    "test_user_registration_refactored.py",
    "test_user_management_refactored.py",
    "test_family_management_refactored.py",
    "test_profile_management_refactored.py",
    "test_admin_management_refactored.py",
    "test_password_security_refactored.py",
    "test_navigation_refactored.py",
]

# Only run if file exists
SUITES_TO_RUN = [s for s in TEST_SUITES if (TESTS_DIR / s).exists()]


def run_test(suite_name):
    """Run a single test suite"""
    print(f"\n{'='*80}")
    print(f"Running: {suite_name}")
    print(f"{'='*80}")
    
    try:
        result = subprocess.run(
            ["python", str(TESTS_DIR / suite_name)],
            cwd=str(TESTS_DIR.parent.parent),
            capture_output=True,
            text=True,
            timeout=300
        )
        
        if result.returncode == 0:
            print(f"✅ {suite_name} - PASS")
            return True
        else:
            print(f"❌ {suite_name} - FAIL")
            if result.stdout:
                print("STDOUT:", result.stdout[-500:])  # Last 500 chars
            if result.stderr:
                print("STDERR:", result.stderr[-500:])
            return False
    except subprocess.TimeoutExpired:
        print(f"⏱️ {suite_name} - TIMEOUT")
        return False
    except Exception as e:
        print(f"❌ {suite_name} - ERROR: {e}")
        return False


def aggregate_results():
    """Aggregate all results from JSON"""
    try:
        if RESULTS_FILE.exists():
            with open(RESULTS_FILE, 'r') as f:
                data = json.load(f)
            return data
        else:
            return {}
    except Exception as e:
        print(f"Error reading results: {e}")
        return {}


def print_summary(aggregated):
    """Print summary of all results"""
    print(f"\n{'='*80}")
    print(f"CONSOLIDATED TEST RESULTS")
    print(f"{'='*80}\n")
    
    total_tests = 0
    total_passed = 0
    total_failed = 0
    
    if 'suites' in aggregated:
        for suite_name, suite_data in aggregated['suites'].items():
            if 'tests' in suite_data:
                tests = suite_data['tests']
                passed = sum(1 for t in tests if t.get('status') == 'PASS')
                failed = len(tests) - passed
                total_tests += len(tests)
                total_passed += passed
                total_failed += failed
                
                status = "✅" if failed == 0 else "⚠️"
                print(f"{status} {suite_name}: {passed}/{len(tests)} passed")
    
    print(f"\n{'-'*80}")
    if total_tests > 0:
        pass_rate = (total_passed / total_tests) * 100
        print(f"TOTAL: {total_passed}/{total_tests} tests passed ({pass_rate:.1f}%)")
    else:
        print(f"TOTAL: No test results found")
    print(f"{'-'*80}\n")
    
    return total_passed, total_tests


def main():
    """Main execution"""
    print("\n" + "="*80)
    print(f"UMASHAKTIDHAM TEST SUITE - CONSOLIDATED RUNNER")
    print(f"Timestamp: {datetime.now().isoformat()}")
    print(f"Suites to run: {len(SUITES_TO_RUN)}")
    print("="*80)
    
    # Run all test suites
    results = {}
    for suite in SUITES_TO_RUN:
        success = run_test(suite)
        results[suite] = "PASS" if success else "FAIL"
    
    # Give results time to be written
    import time
    time.sleep(2)
    
    # Aggregate results
    aggregated = aggregate_results()
    passed, total = print_summary(aggregated)
    
    # Return exit code based on all tests passing
    if total > 0 and passed == total:
        print("✅ ALL TESTS PASSED!")
        return 0
    else:
        print(f"⚠️ Some tests failed or no results found")
        return 1


if __name__ == "__main__":
    sys.exit(main())

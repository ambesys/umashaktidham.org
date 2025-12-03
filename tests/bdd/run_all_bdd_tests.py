#!/usr/bin/env python3
"""
BDD Test Suite Runner
Executes all BDD tests with comprehensive reporting and support for multiple test runners

Usage:
  python tests/bdd/run_all_bdd_tests.py                    # Run with unittest
  python tests/bdd/run_all_bdd_tests.py --pytest           # Run with pytest
  python tests/bdd/run_all_bdd_tests.py --headless         # Run in headless mode
  python tests/bdd/run_all_bdd_tests.py --no-headless      # Run with visible browser
  python tests/bdd/run_all_bdd_tests.py --test ComprehensiveRoleBasedTest
  python tests/bdd/run_all_bdd_tests.py --verbose
  
Options:
  --pytest             - Use pytest instead of direct execution
  --test <name>        - Run specific test file
  --headless           - Force headless mode
  --no-headless        - Force visible browser
  --timeout <seconds>  - Set timeout
  --url <url>          - Set base URL
  --verbose            - Verbose output
  --html               - Generate HTML report (with pytest)
"""

import os
import sys
import subprocess
import json
import time
import argparse
from datetime import datetime
from pathlib import Path

# ============================================================================
# CONFIGURATION
# ============================================================================

TESTS_DIR = Path(__file__).parent
BASE_URL = os.environ.get('BASE_URL', 'http://localhost:8000')
HEADLESS = os.environ.get('HEADLESS', 'true').lower() != 'false'
TEST_TIMEOUT = os.environ.get('TEST_TIMEOUT', '15')

# Main test files to run (in order)
MAIN_TESTS = [
    'ComprehensiveRoleBasedTest.py',
    'E2EComprehensiveTest.py',
]

PYTEST_TESTS = [
    'test_*.py',
    'ComprehensiveRoleBasedTest.py',
    'E2EComprehensiveTest.py',
]

# ============================================================================
# UTILITIES
# ============================================================================

def log_header(title):
    """Print formatted header"""
    print("\n" + "=" * 100)
    print(f"  {title}")
    print("=" * 100)


def log_section(title):
    """Print section"""
    print(f"\n{'─' * 100}")
    print(f"  {title}")
    print(f"{'─' * 100}")


def log_step(message):
    """Print step"""
    print(f"→ {message}")


def log_success(message):
    """Log success"""
    print(f"✅ {message}")


def log_error(message):
    """Log error"""
    print(f"❌ {message}")


def log_warning(message):
    """Log warning"""
    print(f"⚠️  {message}")


# ============================================================================
# TEST RUNNER - DIRECT EXECUTION
# ============================================================================

def run_test_direct(test_file, verbose=False):
    """Run a single test file directly (unittest-style)"""
    test_path = TESTS_DIR / test_file
    
    if not test_path.exists():
        log_error(f"Test file not found: {test_path}")
        return False
    
    log_step(f"Running: {test_file}")
    
    try:
        env = os.environ.copy()
        env['BASE_URL'] = BASE_URL
        env['HEADLESS'] = 'true' if HEADLESS else 'false'
        env['TEST_TIMEOUT'] = str(TEST_TIMEOUT)
        
        result = subprocess.run(
            [sys.executable, str(test_path)],
            cwd=str(TESTS_DIR.parent.parent),
            env=env,
            capture_output=not verbose,
            timeout=600  # 10 minute timeout per test
        )
        
        success = result.returncode == 0
        status = "✅ PASSED" if success else "❌ FAILED"
        print(f"   {status}: {test_file}")
        
        return success
        
    except subprocess.TimeoutExpired:
        log_error(f"Test timed out (exceeded 10 minutes)")
        return False
    except Exception as e:
        log_error(f"Exception: {e}")
        return False


# ============================================================================
# TEST RUNNER - PYTEST
# ============================================================================

def run_tests_pytest(specific_test=None, verbose=False, html_report=False):
    """Run tests using pytest"""
    
    log_step("Running BDD tests with pytest")
    
    pytest_args = [
        'pytest',
        str(TESTS_DIR),
        '-v',
        '--tb=short',
    ]
    
    if specific_test:
        pytest_args.append(f'-k {specific_test}')
    
    if html_report:
        report_file = TESTS_DIR / 'results' / f"report-{int(time.time())}.html"
        pytest_args.append(f'--html={report_file}')
        print(f"   📊 HTML report: {report_file}")
    
    if verbose:
        pytest_args.append('-vv')
    
    print(f"   Command: {' '.join(pytest_args)}")
    
    try:
        result = subprocess.run(
            pytest_args,
            cwd=str(TESTS_DIR.parent.parent),
            timeout=600
        )
        
        return result.returncode == 0
        
    except subprocess.TimeoutExpired:
        log_error("Pytest timed out")
        return False
    except Exception as e:
        log_error(f"Pytest exception: {e}")
        return False


# ============================================================================
# MAIN ORCHESTRATOR
# ============================================================================

def main():
    """Main test orchestrator"""
    
    # Parse arguments
    parser = argparse.ArgumentParser(description='BDD Test Suite Runner')
    parser.add_argument('--pytest', action='store_true', help='Use pytest')
    parser.add_argument('--test', type=str, help='Run specific test')
    parser.add_argument('--headless', action='store_true', help='Force headless mode')
    parser.add_argument('--no-headless', action='store_true', help='Force visible browser')
    parser.add_argument('--timeout', type=int, help='Set timeout in seconds')
    parser.add_argument('--url', type=str, help='Set base URL')
    parser.add_argument('--verbose', '-v', action='store_true', help='Verbose output')
    parser.add_argument('--html', action='store_true', help='Generate HTML report')
    
    args = parser.parse_args()
    
    # Apply arguments
    global BASE_URL, HEADLESS, TEST_TIMEOUT
    
    if args.url:
        BASE_URL = args.url
    if args.headless:
        HEADLESS = True
    if args.no_headless:
        HEADLESS = False
    if args.timeout:
        TEST_TIMEOUT = str(args.timeout)
    
    # Set environment
    os.environ['BASE_URL'] = BASE_URL
    os.environ['HEADLESS'] = 'true' if HEADLESS else 'false'
    os.environ['TEST_TIMEOUT'] = str(TEST_TIMEOUT)
    
    # Print header
    log_header("BDD TEST SUITE RUNNER")
    
    print(f"\nConfiguration:")
    print(f"  BASE_URL:      {BASE_URL}")
    print(f"  HEADLESS:      {HEADLESS}")
    print(f"  TEST_TIMEOUT:  {TEST_TIMEOUT}s")
    print(f"  Test Runner:   {'pytest' if args.pytest else 'direct execution'}")
    print(f"  Time:          {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    
    # Create results directory
    results_dir = TESTS_DIR / 'results'
    results_dir.mkdir(exist_ok=True)
    
    # Run tests
    log_section("EXECUTING BDD TEST SUITE")
    
    results = {}
    total_start = time.time()
    
    if args.pytest:
        # Use pytest
        passed = run_tests_pytest(
            specific_test=args.test,
            verbose=args.verbose,
            html_report=args.html
        )
        results['pytest'] = {'passed': passed, 'elapsed': time.time() - total_start}
    else:
        # Run specific test or all tests
        test_files = [args.test] if args.test else MAIN_TESTS
        
        for test_file in test_files:
            test_start = time.time()
            passed = run_test_direct(test_file, verbose=args.verbose)
            elapsed = time.time() - test_start
            
            results[test_file] = {
                'passed': passed,
                'elapsed': elapsed
            }
    
    total_elapsed = time.time() - total_start
    
    # Summary
    log_header("TEST SUITE SUMMARY")
    
    passed_count = sum(1 for r in results.values() if r['passed'])
    failed_count = len(results) - passed_count
    
    print(f"\nResults:")
    for test_name, result in results.items():
        status = "✅ PASS" if result['passed'] else "❌ FAIL"
        elapsed = result['elapsed']
        print(f"  {status}  {test_name:50} ({elapsed:7.1f}s)")
    
    print(f"\n{'─' * 100}")
    print(f"Total:   {passed_count}/{len(results)} passed | {failed_count}/{len(results)} failed")
    print(f"Elapsed: {total_elapsed:.1f}s")
    print(f"{'─' * 100}")
    
    # Save results
    results_file = results_dir / f"test-results-{int(time.time())}.json"
    with open(results_file, 'w') as f:
        json.dump({
            'timestamp': datetime.now().isoformat(),
            'total_tests': len(results),
            'passed': passed_count,
            'failed': failed_count,
            'elapsed': total_elapsed,
            'results': results,
            'config': {
                'base_url': BASE_URL,
                'headless': HEADLESS,
                'timeout': TEST_TIMEOUT,
                'runner': 'pytest' if args.pytest else 'direct'
            }
        }, f, indent=2)
    
    log_success(f"Results saved: {results_file}")
    
    # Final status
    if failed_count == 0:
        log_header("🎉 ALL TESTS PASSED!")
        return 0
    else:
        log_header(f"⚠️  {failed_count} TEST(S) FAILED")
        return 1


if __name__ == '__main__':
    import traceback
    
    try:
        exit_code = main()
        sys.exit(exit_code)
    except Exception as e:
        print(f"\n❌ FATAL ERROR: {e}")
        traceback.print_exc()
        sys.exit(1)

#!/bin/bash
# Master test runner - runs all tests
# Usage: bash tests/run_all_tests.sh [--ci] [--verbose]

set -e

REPO_ROOT="/Users/sarthak/Sites/umashaktidham.org"
CI_MODE=false
VERBOSE=false

# Parse arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --ci) CI_MODE=true; shift ;;
    --verbose) VERBOSE=true; shift ;;
    *) shift ;;
  esac
done

echo "=================================="
echo "UMASHAKTI DHAM - MASTER TEST SUITE"
echo "=================================="
echo ""

cd "$REPO_ROOT"

# Ensure PHP server is running (if not in CI)
if [ "$CI_MODE" = false ]; then
    echo "Checking PHP server..."
    if ! ps aux | grep -v grep | grep -q "php -S localhost:8000"; then
        echo "Starting PHP server..."
        php -S localhost:8000 router.php > /tmp/php_server.log 2>&1 &
        sleep 2
    fi
    echo "✅ PHP server running on localhost:8000"
    echo ""
fi

# Run TDD Tests
echo "================================================"
echo "RUNNING TDD TESTS (Unit & Integration Tests)"
echo "================================================"
echo ""

if [ -d "tests/tdd" ] && [ "$(ls -A tests/tdd/)" ]; then
    if [ "$VERBOSE" = true ]; then
        bash tests/run_tdd_tests.sh --verbose
    else
        bash tests/run_tdd_tests.sh
    fi
    TDD_RESULT=$?
else
    echo "⚠️  No TDD tests found"
    TDD_RESULT=0
fi

echo ""
echo "================================================"
echo "RUNNING BDD TESTS (Integration & E2E Tests)"
echo "================================================"
echo ""

if [ -d "tests/bdd" ] && [ "$(ls -A tests/bdd/)" ]; then
    if [ "$VERBOSE" = true ]; then
        bash tests/run_bdd_tests.sh --verbose
    else
        bash tests/run_bdd_tests.sh
    fi
    BDD_RESULT=$?
else
    echo "⚠️  No BDD tests found"
    BDD_RESULT=0
fi

# Summary
echo ""
echo "================================================"
echo "TEST EXECUTION SUMMARY"
echo "================================================"
echo ""

if [ $TDD_RESULT -eq 0 ]; then
    echo "✅ TDD Tests: PASSED"
else
    echo "❌ TDD Tests: FAILED"
fi

if [ $BDD_RESULT -eq 0 ]; then
    echo "✅ BDD Tests: PASSED"
else
    echo "❌ BDD Tests: FAILED"
fi

echo ""

# Exit with failure if any tests failed
if [ $TDD_RESULT -ne 0 ] || [ $BDD_RESULT -ne 0 ]; then
    echo "❌ Some tests failed"
    exit 1
else
    echo "✅ All tests passed!"
    exit 0
fi

#!/usr/bin/env bash

# BDD Test Suite Quick Start Guide
# Run this script to execute all tests

echo "🚀 Starting BDD Test Suite..."
echo ""

# Check Python installation
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 not found. Please install Python 3.9 or later."
    exit 1
fi

echo "✅ Python $(python3 --version) found"
echo ""

# Check Selenium installation
python3 -c "import selenium" 2>/dev/null
if [ $? -ne 0 ]; then
    echo "📦 Installing Selenium..."
    pip3 install selenium
fi

echo "✅ Selenium installed"
echo ""

# Get configuration
BASE_URL="${BASE_URL:-http://localhost:8000}"
HEADLESS="${HEADLESS:-true}"
TEST_TIMEOUT="${TEST_TIMEOUT:-15}"

echo "Configuration:"
echo "  BASE_URL:      $BASE_URL"
echo "  HEADLESS:      $HEADLESS"
echo "  TEST_TIMEOUT:  ${TEST_TIMEOUT}s"
echo ""

# Create results directory
mkdir -p tests/bdd/results

# Run tests
cd "$(dirname "$0")/../.."  # Go to project root

export BASE_URL
export HEADLESS
export TEST_TIMEOUT

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Running: ComprehensiveRoleBasedTest.py"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

python3 tests/bdd/ComprehensiveRoleBasedTest.py
result1=$?

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test Results"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ $result1 -eq 0 ]; then
    echo "✅ ComprehensiveRoleBasedTest.py PASSED"
else
    echo "❌ ComprehensiveRoleBasedTest.py FAILED"
fi

echo ""
echo "📊 Results saved in: tests/bdd/results/"
echo ""

# Check if any tests failed
if [ $result1 -ne 0 ]; then
    echo "⚠️  Some tests failed. Check results for details."
    exit 1
else
    echo "🎉 All tests passed!"
    exit 0
fi

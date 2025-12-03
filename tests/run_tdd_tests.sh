#!/bin/bash
# TDD Test Runner - runs all unit tests
# Usage: bash tests/run_tdd_tests.sh [--verbose]

REPO_ROOT="/Users/sarthak/Sites/umashaktidham.org"
VERBOSE=false

while [[ $# -gt 0 ]]; do
  case $1 in
    --verbose) VERBOSE=true; shift ;;
    *) shift ;;
  esac
done

cd "$REPO_ROOT"

echo "Running TDD Tests (PHP Unit Tests)..."
echo ""

PASSED=0
FAILED=0
SKIPPED=0

for test_file in tests/tdd/*.php; do
    if [ -f "$test_file" ]; then
        filename=$(basename "$test_file")
        
        # Skip certain files
        if [[ "$filename" == "path" ]] || [[ "$filename" == *"venv"* ]]; then
            continue
        fi
        
        if [ "$VERBOSE" = true ]; then
            echo "Running: $filename"
            if php "$test_file" > /tmp/test_output.txt 2>&1; then
                echo "✅ $filename PASSED"
                ((PASSED++))
            else
                echo "❌ $filename FAILED"
                cat /tmp/test_output.txt
                ((FAILED++))
            fi
        else
            if php "$test_file" > /tmp/test_output.txt 2>&1; then
                echo "✅ $filename"
                ((PASSED++))
            else
                echo "❌ $filename"
                ((FAILED++))
            fi
        fi
    fi
done

# Also run with PHPUnit if available
if command -v ./vendor/bin/phpunit &> /dev/null; then
    echo ""
    echo "Running with PHPUnit..."
    if ./vendor/bin/phpunit tests/tdd/ --verbose; then
        :
    fi
fi

echo ""
echo "TDD Test Results: $PASSED passed, $FAILED failed"

if [ $FAILED -eq 0 ]; then
    exit 0
else
    exit 1
fi

#!/bin/bash
# BDD Test Runner - runs all integration/behavioral tests
# Usage: bash tests/run_bdd_tests.sh [--verbose]

REPO_ROOT="/Users/sarthak/Sites/umashaktidham.org"
VERBOSE=false

while [[ $# -gt 0 ]]; do
  case $1 in
    --verbose) VERBOSE=true; shift ;;
    *) shift ;;
  esac
done

cd "$REPO_ROOT"

echo "Running BDD Tests (Python Integration Tests)..."
echo ""

# Check if pytest is available
if ! command -v pytest &> /dev/null && ! command -v python3 -m pytest &> /dev/null; then
    echo "⚠️  pytest not found. Attempting to install..."
    pip install pytest pytest-timeout selenium
fi

# Run pytest on BDD tests
if [ "$VERBOSE" = true ]; then
    pytest tests/bdd/ -v --tb=short
else
    pytest tests/bdd/ -v --tb=line
fi

exit $?

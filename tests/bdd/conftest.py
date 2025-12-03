"""
Pytest configuration for BDD tests
Provides fixtures and hooks for comprehensive test suite

Usage:
  pytest tests/bdd/ -v
  pytest tests/bdd/ -v --html=report.html
  pytest tests/bdd/ -k "navbar" -v
"""

import pytest
import os
import sys
from datetime import datetime
from pathlib import Path

# Add utils to path
sys.path.insert(0, str(Path(__file__).parent / 'utils'))


# ============================================================================
# PYTEST CONFIGURATION
# ============================================================================

def pytest_configure(config):
    """Configure pytest"""
    config.addinivalue_line(
        "markers", "bdd: BDD test"
    )
    config.addinivalue_line(
        "markers", "navigation: Navigation/navbar tests"
    )
    config.addinivalue_line(
        "markers", "dashboard: Dashboard tests"
    )
    config.addinivalue_line(
        "markers", "admin: Admin feature tests"
    )
    config.addinivalue_line(
        "markers", "family: Family member management tests"
    )
    config.addinivalue_line(
        "markers", "profile: Profile management tests"
    )
    config.addinivalue_line(
        "markers", "stats: Statistics display tests"
    )


def pytest_collection_modifyitems(config, items):
    """Modify test collection"""
    for item in items:
        # Add BDD marker to all tests
        if 'bdd' in str(item.fspath):
            item.add_marker(pytest.mark.bdd)


def pytest_runtest_makereport(item, call):
    """Add custom report info"""
    if call.when == "call":
        item.add_marker(pytest.mark.bdd)


# ============================================================================
# PYTEST FIXTURES
# ============================================================================

@pytest.fixture(scope="session")
def bdd_config():
    """Get BDD configuration"""
    return {
        'base_url': os.environ.get('BASE_URL', 'http://localhost:8000').rstrip('/'),
        'headless': os.environ.get('HEADLESS', 'true').lower() in ('1', 'true', 'yes'),
        'timeout': int(os.environ.get('TEST_TIMEOUT', '15')),
        'chromedriver_path': os.environ.get('CHROMEDRIVER_PATH'),
    }


@pytest.fixture(scope="session")
def results_dir():
    """Get results directory"""
    results_path = Path(__file__).parent / 'results'
    results_path.mkdir(exist_ok=True)
    return results_path


@pytest.fixture
def test_start_time():
    """Record test start time"""
    return datetime.now()


@pytest.fixture(autouse=True)
def reset_environment():
    """Reset test environment between tests"""
    yield
    # Cleanup code here if needed


# ============================================================================
# PYTEST HOOKS
# ============================================================================

def pytest_report_header(config):
    """Print header with configuration"""
    bdd_config = {
        'base_url': os.environ.get('BASE_URL', 'http://localhost:8000'),
        'headless': os.environ.get('HEADLESS', 'true'),
        'timeout': os.environ.get('TEST_TIMEOUT', '15'),
    }
    
    lines = [
        "",
        "=" * 100,
        "  BDD TEST SUITE",
        "=" * 100,
        f"Base URL:  {bdd_config['base_url']}",
        f"Headless:  {bdd_config['headless']}",
        f"Timeout:   {bdd_config['timeout']}s",
        f"Time:      {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}",
        "=" * 100,
    ]
    
    return "\n".join(lines)


def pytest_sessionstart(session):
    """Called at test session start"""
    pass


def pytest_sessionfinish(session, exitstatus):
    """Called at test session end"""
    pass


# ============================================================================
# PYTEST INI CONFIGURATION
# ============================================================================

pytest_plugins = []

# Test discovery patterns
python_files = ['test_*.py', '*_test.py', 'E2E*.py', 'Comprehensive*.py']
python_classes = ['Test*']
python_functions = ['test_*']


# ============================================================================
# MARKERS
# ============================================================================

markers = {
    'bdd': 'BDD test',
    'navigation': 'Navigation and navbar tests',
    'dashboard': 'Dashboard functionality tests',
    'admin': 'Admin feature tests',
    'family': 'Family member management tests',
    'profile': 'Profile management tests',
    'stats': 'Statistics display tests',
    'slow': 'Slow running tests',
}

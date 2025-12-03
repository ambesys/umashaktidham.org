"""
BDD Test Utilities
Common utilities for BDD tests
"""

import os
import sys
import time
from datetime import datetime
from pathlib import Path


class BDDConfig:
    """BDD Test Configuration"""
    
    @staticmethod
    def get_config():
        """Get configuration from environment"""
        return {
            'base_url': os.environ.get('BASE_URL', 'http://localhost:8000').rstrip('/'),
            'headless': os.environ.get('HEADLESS', 'true').lower() in ('1', 'true', 'yes'),
            'timeout': int(os.environ.get('TEST_TIMEOUT', '15')),
            'chromedriver_path': os.environ.get('CHROMEDRIVER_PATH'),
        }


class BDDReporter:
    """BDD Test Reporting"""
    
    def __init__(self):
        self.results = {}
        self.start_time = datetime.now()
        self.results_dir = Path(__file__).parent / 'results'
        self.results_dir.mkdir(exist_ok=True)
    
    def record_result(self, test_name, passed, details='', duration=0):
        """Record a test result"""
        self.results[test_name] = {
            'passed': passed,
            'details': details,
            'duration': duration,
            'timestamp': datetime.now()
        }
    
    def summary(self):
        """Get summary"""
        total = len(self.results)
        passed = sum(1 for r in self.results.values() if r['passed'])
        failed = total - passed
        elapsed = (datetime.now() - self.start_time).total_seconds()
        
        return {
            'total': total,
            'passed': passed,
            'failed': failed,
            'elapsed': elapsed,
            'success': failed == 0
        }


class BDDLogger:
    """BDD Test Logging"""
    
    @staticmethod
    def section(title):
        """Print section header"""
        print(f"\n{'=' * 100}")
        print(f"  {title}")
        print(f"{'=' * 100}")
    
    @staticmethod
    def step(message):
        """Print step"""
        print(f"→ {message}")
    
    @staticmethod
    def success(message):
        """Print success message"""
        print(f"   ✅ {message}")
    
    @staticmethod
    def error(message):
        """Print error message"""
        print(f"   ❌ {message}")
    
    @staticmethod
    def warning(message):
        """Print warning message"""
        print(f"   ⚠️  {message}")
    
    @staticmethod
    def info(message):
        """Print info message"""
        print(f"   ℹ️  {message}")


def ensure_utils_path():
    """Ensure utils directory is in Python path"""
    utils_dir = Path(__file__).parent
    if str(utils_dir) not in sys.path:
        sys.path.insert(0, str(utils_dir))

#!/usr/bin/env python3
"""
Unified Test Results Collector and Reporter
Consolidates all test results from TDD and BDD suites into one report
"""

import os
import sys
import json
import subprocess
from datetime import datetime
from pathlib import Path
import re

class TestResultsCollector:
    """Collect and consolidate test results from all test suites"""
    
    def __init__(self, repo_root):
        self.repo_root = Path(repo_root)
        self.results = {
            'tdd': [],
            'bdd': [],
            'summary': {}
        }
        self.start_time = datetime.now()
        
    def run_tdd_tests(self):
        """Run all TDD tests and collect results"""
        print("\n" + "="*80)
        print("RUNNING TDD TESTS (Unit Tests)")
        print("="*80 + "\n")
        
        tdd_dir = self.repo_root / "tests" / "tdd"
        if not tdd_dir.exists():
            print("⚠️  TDD directory not found")
            return
        
        php_files = list(tdd_dir.glob("*.php"))
        for test_file in php_files:
            # Skip venv and path directories
            if 'venv' in str(test_file) or 'path' in str(test_file):
                continue
            
            filename = test_file.name
            print(f"Running: {filename}...", end=" ")
            
            try:
                result = subprocess.run(
                    ["php", str(test_file)],
                    capture_output=True,
                    text=True,
                    timeout=30,
                    cwd=str(self.repo_root)
                )
                
                passed = result.returncode == 0
                status = "✅ PASS" if passed else "❌ FAIL"
                print(status)
                
                self.results['tdd'].append({
                    'name': filename,
                    'passed': passed,
                    'output': result.stdout[:500] if result.stdout else '',
                    'error': result.stderr[:500] if result.stderr else ''
                })
            except subprocess.TimeoutExpired:
                print("❌ TIMEOUT")
                self.results['tdd'].append({
                    'name': filename,
                    'passed': False,
                    'error': 'Test timeout (30s)'
                })
            except Exception as e:
                print(f"❌ ERROR: {str(e)}")
                self.results['tdd'].append({
                    'name': filename,
                    'passed': False,
                    'error': str(e)
                })
    
    def run_bdd_tests(self):
        """Run all BDD tests using pytest"""
        print("\n" + "="*80)
        print("RUNNING BDD TESTS (Integration & E2E Tests)")
        print("="*80 + "\n")
        
        bdd_dir = self.repo_root / "tests" / "bdd"
        if not bdd_dir.exists():
            print("⚠️  BDD directory not found")
            return
        
        # Skip pytest (tests are standalone scripts, not pytest fixtures)
        # Run BDD tests manually as they're standalone Python scripts
        self._run_bdd_files_manually()
    
    def _run_bdd_files_manually(self):
        """Fallback: run BDD test files manually"""
        bdd_dir = self.repo_root / "tests" / "bdd"
        test_files = list(bdd_dir.glob("test_*.py"))
        
        for test_file in test_files:
            filename = test_file.name
            if filename.startswith('test_'):
                print(f"Running: {filename}...", end=" ")
                
                try:
                    result = subprocess.run(
                        ["python3", str(test_file)],
                        capture_output=True,
                        text=True,
                        timeout=60,
                        cwd=str(self.repo_root)
                    )
                    
                    passed = result.returncode == 0
                    status = "✅ PASS" if passed else "❌ FAIL"
                    print(status)
                    
                    self.results['bdd'].append({
                        'name': filename,
                        'passed': passed,
                        'output': result.stdout[:500] if result.stdout else '',
                        'error': result.stderr[:500] if result.stderr else ''
                    })
                except subprocess.TimeoutExpired:
                    print("❌ TIMEOUT")
                    self.results['bdd'].append({
                        'name': filename,
                        'passed': False,
                        'error': 'Test timeout (60s)'
                    })
                except Exception as e:
                    print(f"❌ ERROR")
                    self.results['bdd'].append({
                        'name': filename,
                        'passed': False,
                        'error': str(e)
                    })
    
    def generate_report(self):
        """Generate consolidated test report"""
        elapsed = (datetime.now() - self.start_time).total_seconds()
        
        tdd_passed = sum(1 for t in self.results['tdd'] if t['passed'])
        tdd_total = len(self.results['tdd'])
        
        bdd_passed = sum(1 for t in self.results['bdd'] if t['passed'])
        bdd_total = len(self.results['bdd'])
        
        total_passed = tdd_passed + bdd_passed
        total_tests = tdd_total + bdd_total
        
        self.results['summary'] = {
            'total_tests': total_tests,
            'total_passed': total_passed,
            'total_failed': total_tests - total_passed,
            'tdd_passed': tdd_passed,
            'tdd_total': tdd_total,
            'bdd_passed': bdd_passed,
            'bdd_total': bdd_total,
            'elapsed_seconds': elapsed,
            'timestamp': datetime.now().isoformat()
        }
        
        # Print report
        print("\n" + "="*80)
        print("CONSOLIDATED TEST RESULTS REPORT")
        print("="*80)
        
        print(f"\n📊 TDD Tests (Unit Tests)")
        print(f"   ✅ Passed: {tdd_passed}/{tdd_total}")
        if tdd_passed < tdd_total:
            print(f"   ❌ Failed: {tdd_total - tdd_passed}/{tdd_total}")
            for t in self.results['tdd']:
                if not t['passed']:
                    print(f"      • {t['name']}: {t.get('error', 'Unknown error')[:60]}")
        
        print(f"\n📊 BDD Tests (Integration & E2E Tests)")
        print(f"   ✅ Passed: {bdd_passed}/{bdd_total}")
        if bdd_passed < bdd_total:
            print(f"   ❌ Failed: {bdd_total - bdd_passed}/{bdd_total}")
            for t in self.results['bdd']:
                if not t['passed']:
                    print(f"      • {t['name']}: {t.get('error', 'Unknown error')[:60]}")
        
        print(f"\n{'='*80}")
        if total_tests > 0:
            print(f"🎯 OVERALL: {total_passed}/{total_tests} tests passed ({int(total_passed/total_tests*100)}%)")
        else:
            print(f"⚠️  NO TESTS FOUND - Please check that test files exist in /tests/tdd/ and /tests/bdd/")
        print(f"⏱️  Total Time: {elapsed:.1f} seconds")
        print(f"{'='*80}\n")
        
        return total_tests > 0 and (total_tests - total_passed == 0)
    
    def save_json_report(self, output_file=None):
        """Save results as JSON"""
        if output_file is None:
            output_file = self.repo_root / "tests" / "test_results.json"
        
        with open(output_file, 'w') as f:
            json.dump(self.results, f, indent=2)
        
        print(f"📄 JSON Report saved: {output_file}")
        return output_file
    
    def save_html_report(self, output_file=None):
        """Save results as HTML"""
        if output_file is None:
            output_file = self.repo_root / "tests" / "test_results.html"
        
        summary = self.results['summary']
        tdd_tests = self.results['tdd']
        bdd_tests = self.results['bdd']
        
        html = f"""
<!DOCTYPE html>
<html>
<head>
    <title>Test Results Report</title>
    <style>
        body {{
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }}
        .container {{
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }}
        h1 {{
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }}
        .summary {{
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 20px 0;
        }}
        .stat {{
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }}
        .stat.pass {{
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }}
        .stat.fail {{
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }}
        .stat h3 {{
            margin: 0;
            font-size: 28px;
        }}
        .stat p {{
            margin: 5px 0 0 0;
            color: #666;
        }}
        table {{
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }}
        th {{
            background: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
        }}
        tr {{
            border-bottom: 1px solid #ddd;
        }}
        tr:hover {{
            background: #f9f9f9;
        }}
        td {{
            padding: 12px;
        }}
        .pass {{
            color: #28a745;
            font-weight: bold;
        }}
        .fail {{
            color: #dc3545;
            font-weight: bold;
        }}
        .timestamp {{
            color: #666;
            font-size: 12px;
        }}
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Results Report</h1>
        
        <div class="summary">
            <div class="stat pass">
                <h3>{summary['total_passed']}/{summary['total_tests']}</h3>
                <p>Tests Passed</p>
            </div>
            <div class="stat fail">
                <h3>{summary['total_failed']}</h3>
                <p>Tests Failed</p>
            </div>
            <div class="stat">
                <h3>{summary['elapsed_seconds']:.1f}s</h3>
                <p>Total Time</p>
            </div>
        </div>
        
        <h2>📊 TDD Tests (Unit Tests)</h2>
        <p>{summary['tdd_passed']}/{summary['tdd_total']} passed</p>
        <table>
            <tr>
                <th>Test Name</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
"""
        
        for test in tdd_tests:
            status = '<span class="pass">✅ PASS</span>' if test['passed'] else '<span class="fail">❌ FAIL</span>'
            error = test.get('error', '')[:100]
            html += f"            <tr><td>{test['name']}</td><td>{status}</td><td>{error}</td></tr>\n"
        
        html += """
        </table>
        
        <h2>📊 BDD Tests (Integration & E2E Tests)</h2>
"""
        html += f"        <p>{summary['bdd_passed']}/{summary['bdd_total']} passed</p>\n"
        html += """        <table>
            <tr>
                <th>Test Name</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
"""
        
        for test in bdd_tests:
            status = '<span class="pass">✅ PASS</span>' if test['passed'] else '<span class="fail">❌ FAIL</span>'
            error = test.get('error', '')[:100]
            html += f"            <tr><td>{test['name']}</td><td>{status}</td><td>{error}</td></tr>\n"
        
        html += f"""
        </table>
        
        <p class="timestamp">Generated: {summary['timestamp']}</p>
    </div>
</body>
</html>
"""
        
        with open(output_file, 'w') as f:
            f.write(html)
        
        print(f"📊 HTML Report saved: {output_file}")
        return output_file


def main():
    """Main entry point"""
    # __file__ is /path/to/tests/test_results_consolidator.py
    # parent is /path/to/tests/
    # parent.parent is /path/to/umashaktidham.org/
    repo_root = Path(__file__).parent.parent
    
    print("""
╔════════════════════════════════════════════════════════════════════════════╗
║                    CONSOLIDATED TEST RESULTS COLLECTOR                     ║
║                  Umashakti Dham - All Test Suites                          ║
╚════════════════════════════════════════════════════════════════════════════╝
    """)
    
    collector = TestResultsCollector(repo_root)
    
    # Run tests
    collector.run_tdd_tests()
    collector.run_bdd_tests()
    
    # Generate reports
    success = collector.generate_report()
    
    # Save reports
    json_file = collector.save_json_report()
    html_file = collector.save_html_report()
    
    print(f"\n📌 To view HTML report: open file://{html_file}")
    print(f"📌 To view JSON report: cat {json_file}")
    
    return 0 if success else 1


if __name__ == '__main__':
    sys.exit(main())

"""
Test Results Logger & Tracker
Centralized module to track all test results with timestamps and status
Maintains both JSON for data and HTML for visualization
"""

import json
import os
from datetime import datetime
from pathlib import Path


class TestResultsLogger:
    """Track and persist test results to JSON and HTML"""
    
    def __init__(self, test_suite_name, log_dir="tests/results"):
        """
        Initialize test results logger
        
        Args:
            test_suite_name: Name of the test suite (e.g., "user_registration")
            log_dir: Directory to store results (creates if doesn't exist)
        """
        self.test_suite_name = test_suite_name
        self.log_dir = Path(log_dir)
        self.log_dir.mkdir(parents=True, exist_ok=True)
        
        self.json_file = self.log_dir / "test_results.json"
        self.html_file = self.log_dir / "test_results.html"
        
        self.session_results = []
        self.session_start = datetime.now()
        
        # Load existing results or initialize
        self.all_results = self._load_results()
    
    def _load_results(self):
        """Load existing results from JSON file"""
        if self.json_file.exists():
            try:
                with open(self.json_file, 'r') as f:
                    return json.load(f)
            except (json.JSONDecodeError, IOError):
                return []
        return []
    
    def record_test(self, test_id, test_name, passed, details="", duration=0):
        """
        Record a single test result
        
        Args:
            test_id: Test identifier (e.g., "REG-001")
            test_name: Human-readable test name
            passed: Boolean indicating if test passed
            details: Additional details/error message
            duration: Test duration in seconds
        """
        result = {
            "test_id": test_id,
            "test_name": test_name,
            "suite": self.test_suite_name,
            "passed": passed,
            "status": "PASS" if passed else "FAIL",
            "timestamp": datetime.now().isoformat(),
            "execution_time": duration,
            "details": details if not passed else ""
        }
        
        self.session_results.append(result)
        self.all_results.append(result)
        
        # Auto-save after each test
        self._save_results()
    
    def _save_results(self):
        """Save results to JSON file"""
        try:
            with open(self.json_file, 'w') as f:
                json.dump(self.all_results, f, indent=2)
        except IOError as e:
            print(f"Error saving test results: {e}")
    
    def finalize_session(self):
        """Finalize current session and update HTML dashboard"""
        session_end = datetime.now()
        duration = (session_end - self.session_start).total_seconds()
        
        # Calculate session statistics
        session_passed = sum(1 for r in self.session_results if r["passed"])
        session_total = len(self.session_results)
        
        print("\n" + "="*80)
        print(f"TEST SESSION SUMMARY - {self.test_suite_name}")
        print("="*80)
        print(f"Total Tests: {session_total}")
        print(f"Passed: {session_passed}/{session_total}")
        print(f"Failed: {session_total - session_passed}/{session_total}")
        print(f"Duration: {duration:.2f}s")
        print("="*80 + "\n")
        
        # Generate HTML dashboard
        self._generate_html_dashboard()
    
    def _generate_html_dashboard(self):
        """Generate comprehensive HTML dashboard of all test results"""
        
        # Calculate statistics
        total_tests = len(self.all_results)
        passed_tests = sum(1 for r in self.all_results if r["passed"])
        failed_tests = total_tests - passed_tests
        pass_rate = (passed_tests / total_tests * 100) if total_tests > 0 else 0
        
        # Group by suite
        suites = {}
        for result in self.all_results:
            suite = result["suite"]
            if suite not in suites:
                suites[suite] = {"passed": 0, "failed": 0, "tests": []}
            suites[suite]["tests"].append(result)
            if result["passed"]:
                suites[suite]["passed"] += 1
            else:
                suites[suite]["failed"] += 1
        
        # Generate suite summary rows
        suite_rows = ""
        for suite_name in sorted(suites.keys()):
            suite_data = suites[suite_name]
            suite_total = suite_data["passed"] + suite_data["failed"]
            suite_pass_rate = (suite_data["passed"] / suite_total * 100) if suite_total > 0 else 0
            
            status_color = "#28a745" if suite_data["failed"] == 0 else "#dc3545"
            status_badge = "✅ PASS" if suite_data["failed"] == 0 else "❌ FAIL"
            
            suite_rows += f"""
            <tr>
                <td class="suite-name">{suite_name}</td>
                <td class="text-center">{suite_total}</td>
                <td class="text-center passed">{suite_data['passed']}</td>
                <td class="text-center failed">{suite_data['failed']}</td>
                <td class="text-center"><span class="badge" style="background-color: {status_color}">{suite_pass_rate:.1f}%</span></td>
                <td class="text-center">{status_badge}</td>
            </tr>
            """
        
        # Generate detailed test rows
        test_rows = ""
        for result in sorted(self.all_results, key=lambda x: x["timestamp"], reverse=True):
            status_color = "#28a745" if result["passed"] else "#dc3545"
            status_text = "✅ PASS" if result["passed"] else "❌ FAIL"
            error_details = f"<br><small>{result['details']}</small>" if result['details'] else ""
            
            test_rows += f"""
            <tr class="{'pass-row' if result['passed'] else 'fail-row'}">
                <td>{result['test_id']}</td>
                <td>{result['test_name']}</td>
                <td>{result['suite']}</td>
                <td><span class="badge" style="background-color: {status_color}">{status_text}</span></td>
                <td>{result['execution_time']:.2f}s</td>
                <td>{result['timestamp']}</td>
                <td>{error_details}</td>
            </tr>
            """
        
        # HTML template
        html_content = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Results Dashboard</title>
    <style>
        * {{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }}
        
        body {{
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }}
        
        .container {{
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }}
        
        .header {{
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }}
        
        .header h1 {{
            font-size: 2.5em;
            margin-bottom: 10px;
        }}
        
        .header p {{
            font-size: 1.1em;
            opacity: 0.9;
        }}
        
        .stats {{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }}
        
        .stat-card {{
            background: white;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }}
        
        .stat-card h3 {{
            color: #667eea;
            font-size: 2em;
            margin-bottom: 5px;
        }}
        
        .stat-card p {{
            color: #666;
            font-size: 0.9em;
        }}
        
        .stat-card.passed h3 {{
            color: #28a745;
        }}
        
        .stat-card.failed h3 {{
            color: #dc3545;
        }}
        
        .content {{
            padding: 30px;
        }}
        
        .section {{
            margin-bottom: 40px;
        }}
        
        .section-title {{
            font-size: 1.5em;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }}
        
        table {{
            width: 100%;
            border-collapse: collapse;
            background: white;
        }}
        
        thead {{
            background: #f8f9fa;
        }}
        
        th {{
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }}
        
        td {{
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }}
        
        tr:hover {{
            background: #f8f9fa;
        }}
        
        .pass-row {{
            border-left: 4px solid #28a745;
        }}
        
        .fail-row {{
            border-left: 4px solid #dc3545;
            background: #fff5f5;
        }}
        
        .badge {{
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            color: white;
            font-size: 0.85em;
            font-weight: 600;
        }}
        
        .suite-name {{
            font-weight: 600;
            color: #667eea;
        }}
        
        .text-center {{
            text-align: center;
        }}
        
        .passed {{
            color: #28a745;
            font-weight: 600;
        }}
        
        .failed {{
            color: #dc3545;
            font-weight: 600;
        }}
        
        .filters {{
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }}
        
        .filter-btn {{
            padding: 8px 16px;
            border: 2px solid #dee2e6;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95em;
            transition: all 0.3s ease;
        }}
        
        .filter-btn.active {{
            background: #667eea;
            color: white;
            border-color: #667eea;
        }}
        
        .filter-btn:hover {{
            border-color: #667eea;
        }}
        
        .footer {{
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            border-top: 1px solid #dee2e6;
        }}
        
        .last-updated {{
            color: #999;
            font-size: 0.9em;
        }}
        
        .progress-bar {{
            width: 100%;
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 10px;
        }}
        
        .progress-fill {{
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9em;
            transition: width 0.3s ease;
        }}
        
        small {{
            color: #999;
            display: block;
            margin-top: 5px;
        }}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Test Results Dashboard</h1>
            <p>Comprehensive Test Suite Execution Report</p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h3>{total_tests}</h3>
                <p>Total Tests</p>
            </div>
            <div class="stat-card passed">
                <h3>{passed_tests}</h3>
                <p>Tests Passed</p>
            </div>
            <div class="stat-card failed">
                <h3>{failed_tests}</h3>
                <p>Tests Failed</p>
            </div>
            <div class="stat-card">
                <h3>{pass_rate:.1f}%</h3>
                <p>Pass Rate</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {pass_rate}%">{pass_rate:.1f}%</div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <!-- Test Suites Summary -->
            <div class="section">
                <h2 class="section-title">📊 Test Suites Summary</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Suite Name</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Passed</th>
                            <th class="text-center">Failed</th>
                            <th class="text-center">Pass Rate</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {suite_rows}
                    </tbody>
                </table>
            </div>
            
            <!-- Detailed Test Results -->
            <div class="section">
                <h2 class="section-title">📋 Detailed Test Results</h2>
                <div class="filters">
                    <button class="filter-btn active" onclick="filterTests('all')">All Tests</button>
                    <button class="filter-btn" onclick="filterTests('pass')">✅ Passed</button>
                    <button class="filter-btn" onclick="filterTests('fail')">❌ Failed</button>
                </div>
                <table id="test-table">
                    <thead>
                        <tr>
                            <th>Test ID</th>
                            <th>Test Name</th>
                            <th>Suite</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th>Timestamp</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody id="test-body">
                        {test_rows}
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Last Updated:</strong> {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}</p>
            <p class="last-updated">Dashboard auto-refreshes when new test results are recorded</p>
        </div>
    </div>
    
    <script>
        function filterTests(filter) {{
            const table = document.getElementById('test-table');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            // Update filter buttons
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter rows
            for (let row of rows) {{
                if (filter === 'all') {{
                    row.style.display = '';
                }} else if (filter === 'pass') {{
                    row.style.display = row.classList.contains('pass-row') ? '' : 'none';
                }} else if (filter === 'fail') {{
                    row.style.display = row.classList.contains('fail-row') ? '' : 'none';
                }}
            }}
        }}
        
        // Auto-refresh dashboard every 5 seconds if in background
        setInterval(() => {{
            // Could add fetch to reload data from JSON
        }}, 5000);
    </script>
</body>
</html>
"""
        
        try:
            with open(self.html_file, 'w') as f:
                f.write(html_content)
            print(f"\n✅ Test results dashboard generated: {self.html_file}")
            print(f"   Open in browser: file://{os.path.abspath(self.html_file)}\n")
        except IOError as e:
            print(f"Error generating HTML dashboard: {e}")
    
    def get_summary(self):
        """Get current session summary"""
        return {
            "suite": self.test_suite_name,
            "total": len(self.session_results),
            "passed": sum(1 for r in self.session_results if r["passed"]),
            "failed": sum(1 for r in self.session_results if not r["passed"])
        }

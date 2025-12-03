import json
from pathlib import Path
from datetime import datetime

def generate_results_dashboard(results_json_path=None):
    if results_json_path is None:
        results_json_path = Path(__file__).parent / 'results' / 'test_results.json'
    
    if not Path(results_json_path).exists():
        print(f"Results file not found: {results_json_path}")
        return
    
    with open(results_json_path, 'r') as f:
        results = json.load(f)
    
    summary = results.get('summary', {})
    test_suites = results.get('test_suites', {})
    
    total_tests = summary.get('total_tests', 0)
    total_passed = summary.get('total_passed', 0)
    total_failed = summary.get('total_failed', 0)
    pass_rate = summary.get('pass_rate', 0)
    
    # Generate suite summary rows
    suites_rows = ""
    for suite_name, suite in test_suites.items():
        meta = suite.get('metadata', {})
        total = meta.get('total', 0)
        passed = meta.get('passed', 0)
        failed = meta.get('failed', 0)
        suite_pass_rate = meta.get('pass_rate', 0)
        status = "PASS" if failed == 0 else "FAIL"
        badge_color = '#28a745' if failed == 0 else '#dc3545'
        
        suites_rows += f'<tr><td class="suite-name">{suite_name}</td><td class="text-center">{total}</td><td class="text-center passed">{passed}</td><td class="text-center failed">{failed}</td><td class="text-center"><span class="badge" style="background-color: {badge_color}">{suite_pass_rate:.1f}%</span></td><td class="text-center">{status}</td></tr>'
    
    # Get all tests flat list, sorted by timestamp
    all_tests = []
    for suite_name, suite in test_suites.items():
        for test in suite.get('tests', []):
            all_tests.append(test)
    
    all_tests.sort(key=lambda x: x.get('timestamp', ''), reverse=True)
    
    # Generate test detail rows
    tests_rows = ""
    for test in all_tests:
        test_id = test.get('id', 'N/A')
        test_name = test.get('name', 'Unknown')
        suite = test.get('suite', 'N/A')
        status = test.get('status', 'UNKNOWN')
        duration = test.get('duration', 0)
        timestamp = test.get('timestamp', '')
        details = test.get('details', '')
        
        try:
            dt = datetime.fromisoformat(timestamp)
            formatted_ts = dt.strftime('%Y-%m-%dT%H:%M:%S')
        except:
            formatted_ts = timestamp
        
        row_class = 'pass-row' if status.upper() == 'PASS' else 'fail-row'
        badge_color = '#28a745' if status.upper() == 'PASS' else '#dc3545'
        badge_text = 'PASS' if status.upper() == 'PASS' else 'FAIL'
        
        tests_rows += f'<tr class="{row_class}"><td class="test-id">{test_id}</td><td>{test_name}</td><td class="suite-name">{suite}</td><td><span class="badge" style="background-color: {badge_color}">{badge_text}</span></td><td class="duration">{duration:.2f}s</td><td>{formatted_ts}</td><td>{details}</td></tr>'
    
    last_updated = results.get('metadata', {}).get('updated', datetime.now().isoformat())
    try:
        dt = datetime.fromisoformat(last_updated)
        last_updated = dt.strftime('%Y-%m-%d %H:%M:%S')
    except:
        pass
    
    # Generate HTML
    html = '''<!DOCTYPE html>
<html>
<head>
    <title>Test Results Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; text-align: center; }
        .header h1 { font-size: 2.5em; margin: 0; }
        .header p { font-size: 1.1em; margin: 10px 0 0 0; opacity: 0.9; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; padding: 30px; background: #f8f9fa; border-bottom: 2px solid #e9ecef; }
        .stat-card { background: white; padding: 20px; border-radius: 6px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card h3 { color: #667eea; font-size: 2em; margin: 0 0 5px 0; }
        .stat-card p { color: #666; font-size: 0.9em; margin: 0; }
        .stat-card.passed h3 { color: #28a745; }
        .stat-card.failed h3 { color: #dc3545; }
        .content { padding: 30px; }
        .section { margin-bottom: 40px; }
        .section-title { font-size: 1.5em; color: #333; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 3px solid #667eea; }
        table { width: 100%; border-collapse: collapse; background: white; }
        thead { background: #f8f9fa; }
        th { padding: 15px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px 15px; border-bottom: 1px solid #dee2e6; }
        tr:hover { background: #f8f9fa; }
        .pass-row { border-left: 4px solid #28a745; }
        .fail-row { border-left: 4px solid #dc3545; background: #fff5f5; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; color: white; font-size: 0.85em; font-weight: 600; }
        .suite-name { font-weight: 600; color: #667eea; }
        .test-id { font-weight: 600; color: #555; font-family: monospace; }
        .text-center { text-align: center; }
        .passed { color: #28a745; font-weight: 600; }
        .failed { color: #dc3545; font-weight: 600; }
        .filters { display: flex; gap: 15px; margin-bottom: 20px; }
        .filter-btn { padding: 8px 16px; border: 2px solid #dee2e6; background: white; border-radius: 4px; cursor: pointer; font-size: 0.95em; transition: all 0.3s; }
        .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }
        .filter-btn:hover { border-color: #667eea; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; border-top: 1px solid #dee2e6; }
        .last-updated { color: #999; font-size: 0.9em; margin: 5px 0 0 0; }
        .progress-bar { width: 100%; height: 30px; background: #e9ecef; border-radius: 15px; overflow: hidden; margin-top: 10px; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745 0%, #20c997 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .duration { font-family: monospace; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Test Results Dashboard</h1>
            <p>Comprehensive Test Suite Execution Report</p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h3>''' + str(total_tests) + '''</h3>
                <p>Total Tests</p>
            </div>
            <div class="stat-card passed">
                <h3>''' + str(total_passed) + '''</h3>
                <p>Tests Passed</p>
            </div>
            <div class="stat-card failed">
                <h3>''' + str(total_failed) + '''</h3>
                <p>Tests Failed</p>
            </div>
            <div class="stat-card">
                <h3>''' + f'{pass_rate:.1f}' + '''%</h3>
                <p>Pass Rate</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ''' + f'{pass_rate:.1f}' + '''%;">''' + f'{pass_rate:.1f}' + '''%</div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="section">
                <h2 class="section-title">Test Suites Summary</h2>
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
''' + suites_rows + '''
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <h2 class="section-title">Detailed Test Results</h2>
                <div class="filters">
                    <button class="filter-btn active" onclick="filterTests('all')">All Tests</button>
                    <button class="filter-btn" onclick="filterTests('pass')">Passed</button>
                    <button class="filter-btn" onclick="filterTests('fail')">Failed</button>
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
''' + tests_rows + '''
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Last Updated:</strong> ''' + last_updated + '''</p>
            <p class="last-updated">Dashboard auto-refreshes when new test results are recorded</p>
        </div>
    </div>
    
    <script>
        function filterTests(filter) {
            const rows = document.querySelectorAll('#test-body tr');
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            for (let row of rows) {
                if (filter === 'all') {
                    row.style.display = '';
                } else if (filter === 'pass') {
                    row.style.display = row.classList.contains('pass-row') ? '' : 'none';
                } else if (filter === 'fail') {
                    row.style.display = row.classList.contains('fail-row') ? '' : 'none';
                }
            }
        }
    </script>
</body>
</html>'''
    
    output_path = Path(results_json_path).parent / 'test_results.html'
    with open(output_path, 'w') as f:
        f.write(html)
    
    print(f"Dashboard generated: {output_path}")
    return output_path

if __name__ == "__main__":
    generate_results_dashboard()

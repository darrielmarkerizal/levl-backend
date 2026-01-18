#!/bin/bash

# FrankenPHP Load Test Suite with Visual Comparison
# Tests multiple scenarios with increasing load

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color
BOLD='\033[1m'

# Configuration
BASE_URL="http://127.0.0.1:8000/dev/benchmark-api"
RESULTS_DIR="benchmark_results"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Create results directory
mkdir -p "$RESULTS_DIR"

# Check if wrk is installed
if ! command -v wrk &> /dev/null; then
    echo -e "${RED}Error: wrk is not installed${NC}"
    echo "Install with: brew install wrk"
    exit 1
fi

echo -e "${BOLD}${CYAN}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║         FrankenPHP Load Test Suite - wrk Edition          ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Test scenarios
declare -A SCENARIOS=(
    ["simple"]="Simple (No DB)"
    ["db"]="Database Query"
    ["enrollment"]="Enrollment Check"
    ["dashboard"]="Student Dashboard"
    ["courses"]="Course Listing"
)

# Load test configurations
declare -a TESTS=(
    "Light Load|2|50|10s"
    "Medium Load|4|200|30s"
    "Heavy Load|8|500|30s"
)

# Function to run wrk and parse results
run_wrk_test() {
    local scenario=$1
    local scenario_name=$2
    local threads=$3
    local connections=$4
    local duration=$5
    local test_name=$6
    
    echo -e "${BLUE}Running: ${scenario_name} - ${test_name}${NC}"
    echo -e "${CYAN}  Threads: ${threads}, Connections: ${connections}, Duration: ${duration}${NC}"
    
    local url="${BASE_URL}?mode=${scenario}"
    local output_file="${RESULTS_DIR}/${scenario}_${threads}t_${connections}c_${TIMESTAMP}.txt"
    
    # Run wrk and save output
    wrk -t${threads} -c${connections} -d${duration} --latency "${url}" > "${output_file}" 2>&1
    
    # Parse results
    local rps=$(grep "Requests/sec:" "${output_file}" | awk '{print $2}')
    local latency_avg=$(grep "Latency" "${output_file}" | head -1 | awk '{print $2}')
    local latency_p99=$(grep "99%" "${output_file}" | awk '{print $2}')
    local transfer=$(grep "Transfer/sec:" "${output_file}" | awk '{print $2}')
    
    # Store results
    echo "${test_name}|${rps}|${latency_avg}|${latency_p99}|${transfer}" >> "${RESULTS_DIR}/${scenario}_summary.csv"
    
    echo -e "${GREEN}  ✓ RPS: ${rps}, Latency: ${latency_avg}, P99: ${latency_p99}${NC}"
    echo ""
}

# Function to create visual comparison
create_visual_comparison() {
    echo -e "${BOLD}${MAGENTA}"
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║                  BENCHMARK RESULTS SUMMARY                 ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    
    for scenario in "${!SCENARIOS[@]}"; do
        local scenario_name="${SCENARIOS[$scenario]}"
        local summary_file="${RESULTS_DIR}/${scenario}_summary.csv"
        
        if [ ! -f "$summary_file" ]; then
            continue
        fi
        
        echo -e "${BOLD}${YELLOW}${scenario_name}${NC}"
        echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        
        printf "%-15s | %-12s | %-12s | %-12s | %-12s\n" "Test" "RPS" "Avg Latency" "P99 Latency" "Transfer/s"
        echo "────────────────┼──────────────┼──────────────┼──────────────┼──────────────"
        
        while IFS='|' read -r test_name rps latency_avg latency_p99 transfer; do
            printf "%-15s | ${GREEN}%-12s${NC} | %-12s | %-12s | %-12s\n" \
                "$test_name" "$rps" "$latency_avg" "$latency_p99" "$transfer"
        done < "$summary_file"
        
        echo ""
    done
}

# Function to create comparison chart
create_comparison_chart() {
    echo -e "${BOLD}${MAGENTA}"
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║              RPS COMPARISON CHART (Heavy Load)             ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    
    local max_rps=0
    declare -A heavy_load_rps
    
    # Find max RPS for scaling
    for scenario in "${!SCENARIOS[@]}"; do
        local summary_file="${RESULTS_DIR}/${scenario}_summary.csv"
        if [ -f "$summary_file" ]; then
            local rps=$(grep "Heavy Load" "$summary_file" | cut -d'|' -f2)
            if [ ! -z "$rps" ]; then
                heavy_load_rps[$scenario]=$rps
                if (( $(echo "$rps > $max_rps" | bc -l) )); then
                    max_rps=$rps
                fi
            fi
        fi
    done
    
    # Create bar chart
    for scenario in "${!SCENARIOS[@]}"; do
        local scenario_name="${SCENARIOS[$scenario]}"
        local rps="${heavy_load_rps[$scenario]}"
        
        if [ -z "$rps" ]; then
            continue
        fi
        
        # Calculate bar length (max 50 chars)
        local bar_length=$(echo "scale=0; ($rps / $max_rps) * 50" | bc)
        
        # Create bar
        local bar=""
        for ((i=0; i<bar_length; i++)); do
            bar="${bar}█"
        done
        
        printf "%-20s ${GREEN}%s${NC} %.2f\n" "$scenario_name" "$bar" "$rps"
    done
    
    echo ""
}

# Function to create latency comparison
create_latency_comparison() {
    echo -e "${BOLD}${MAGENTA}"
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║           LATENCY COMPARISON (Heavy Load - P99)            ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    
    printf "%-20s | %-15s | %-15s | %-15s\n" "Scenario" "Light Load" "Medium Load" "Heavy Load"
    echo "─────────────────────┼─────────────────┼─────────────────┼─────────────────"
    
    for scenario in "${!SCENARIOS[@]}"; do
        local scenario_name="${SCENARIOS[$scenario]}"
        local summary_file="${RESULTS_DIR}/${scenario}_summary.csv"
        
        if [ ! -f "$summary_file" ]; then
            continue
        fi
        
        local light=$(grep "Light Load" "$summary_file" | cut -d'|' -f4)
        local medium=$(grep "Medium Load" "$summary_file" | cut -d'|' -f4)
        local heavy=$(grep "Heavy Load" "$summary_file" | cut -d'|' -f4)
        
        printf "%-20s | %-15s | %-15s | ${YELLOW}%-15s${NC}\n" \
            "$scenario_name" "$light" "$medium" "$heavy"
    done
    
    echo ""
}

# Main execution
echo -e "${YELLOW}Starting benchmark tests...${NC}"
echo -e "${CYAN}This will take approximately 3-4 minutes${NC}"
echo ""

# Run all tests
for scenario in "${!SCENARIOS[@]}"; do
    scenario_name="${SCENARIOS[$scenario]}"
    
    echo -e "${BOLD}${BLUE}Testing Scenario: ${scenario_name}${NC}"
    echo ""
    
    # Initialize summary file
    echo "Test|RPS|Avg Latency|P99 Latency|Transfer" > "${RESULTS_DIR}/${scenario}_summary.csv"
    
    # Run each load test
    for test_config in "${TESTS[@]}"; do
        IFS='|' read -r test_name threads connections duration <<< "$test_config"
        run_wrk_test "$scenario" "$scenario_name" "$threads" "$connections" "$duration" "$test_name"
    done
    
    echo ""
done

# Create visualizations
create_visual_comparison
create_comparison_chart
create_latency_comparison

# Generate HTML report
echo -e "${YELLOW}Generating HTML report...${NC}"

cat > "${RESULTS_DIR}/report_${TIMESTAMP}.html" << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>FrankenPHP Benchmark Report</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { color: #2c3e50; text-align: center; }
        h2 { color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .chart-container {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin: 20px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th {
            background: #3498db;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        tr:hover { background: #f8f9fa; }
        .metric { font-weight: bold; color: #27ae60; }
        .timestamp { text-align: center; color: #7f8c8d; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🚀 FrankenPHP Load Test Report</h1>
    <p class="timestamp">Generated: TIMESTAMP_PLACEHOLDER</p>
    
    <div class="chart-container">
        <h2>Requests Per Second (RPS) Comparison</h2>
        <canvas id="rpsChart"></canvas>
    </div>
    
    <div class="chart-container">
        <h2>Latency Comparison (P99)</h2>
        <canvas id="latencyChart"></canvas>
    </div>
    
    <h2>Detailed Results</h2>
    <div id="tables"></div>
    
    <script>
        // Data will be injected here
        DATA_PLACEHOLDER
        
        // RPS Chart
        new Chart(document.getElementById('rpsChart'), {
            type: 'bar',
            data: {
                labels: scenarios,
                datasets: [
                    {
                        label: 'Light Load (50 conn)',
                        data: rpsData.light,
                        backgroundColor: 'rgba(52, 152, 219, 0.8)'
                    },
                    {
                        label: 'Medium Load (200 conn)',
                        data: rpsData.medium,
                        backgroundColor: 'rgba(46, 204, 113, 0.8)'
                    },
                    {
                        label: 'Heavy Load (500 conn)',
                        data: rpsData.heavy,
                        backgroundColor: 'rgba(231, 76, 60, 0.8)'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Requests/sec' } }
                }
            }
        });
        
        // Latency Chart
        new Chart(document.getElementById('latencyChart'), {
            type: 'line',
            data: {
                labels: scenarios,
                datasets: [
                    {
                        label: 'Light Load',
                        data: latencyData.light,
                        borderColor: 'rgba(52, 152, 219, 1)',
                        fill: false
                    },
                    {
                        label: 'Medium Load',
                        data: latencyData.medium,
                        borderColor: 'rgba(46, 204, 113, 1)',
                        fill: false
                    },
                    {
                        label: 'Heavy Load',
                        data: latencyData.heavy,
                        borderColor: 'rgba(231, 76, 60, 1)',
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Latency (ms)' } }
                }
            }
        });
    </script>
</body>
</html>
EOF

# Inject data into HTML
python3 - << PYTHON_SCRIPT
import os
import re
from datetime import datetime

results_dir = "${RESULTS_DIR}"
timestamp = "${TIMESTAMP}"

scenarios = []
rps_light = []
rps_medium = []
rps_heavy = []
lat_light = []
lat_medium = []
lat_heavy = []

scenario_names = {
    'simple': 'Simple (No DB)',
    'db': 'Database Query',
    'enrollment': 'Enrollment Check',
    'dashboard': 'Student Dashboard',
    'courses': 'Course Listing'
}

def parse_latency_to_ms(latency_str):
    """Convert latency string to milliseconds"""
    if not latency_str or latency_str == '':
        return 0
    
    # Remove any extra characters and normalize
    latency_str = latency_str.strip()
    
    # Parse value and unit
    if 'us' in latency_str:
        value = float(latency_str.replace('us', ''))
        return value / 1000  # microseconds to milliseconds
    elif 'ms' in latency_str:
        value = float(latency_str.replace('ms', ''))
        return value
    elif 's' in latency_str:
        value = float(latency_str.replace('s', ''))
        return value * 1000  # seconds to milliseconds
    else:
        # Try to parse as float (assume ms)
        try:
            return float(latency_str)
        except:
            return 0

for scenario, name in scenario_names.items():
    summary_file = f"{results_dir}/{scenario}_summary.csv"
    if not os.path.exists(summary_file):
        continue
    
    scenarios.append(name)
    
    with open(summary_file, 'r') as f:
        lines = f.readlines()[1:]  # Skip header
        
        for line in lines:
            parts = line.strip().split('|')
            test_name = parts[0]
            rps = float(parts[1])
            latency_p99 = parse_latency_to_ms(parts[3])
            
            if 'Light' in test_name:
                rps_light.append(rps)
                lat_light.append(latency_p99)
            elif 'Medium' in test_name:
                rps_medium.append(rps)
                lat_medium.append(latency_p99)
            elif 'Heavy' in test_name:
                rps_heavy.append(rps)
                lat_heavy.append(latency_p99)

# Generate JavaScript data
js_data = f"""
const scenarios = {scenarios};
const rpsData = {{
    light: {rps_light},
    medium: {rps_medium},
    heavy: {rps_heavy}
}};
const latencyData = {{
    light: {lat_light},
    medium: {lat_medium},
    heavy: {lat_heavy}
}};
"""

# Read HTML template
html_file = f"{results_dir}/report_{timestamp}.html"
with open(html_file, 'r') as f:
    html_content = f.read()

# Replace placeholders
html_content = html_content.replace('DATA_PLACEHOLDER', js_data)
html_content = html_content.replace('TIMESTAMP_PLACEHOLDER', datetime.now().strftime('%Y-%m-%d %H:%M:%S'))

# Write back
with open(html_file, 'w') as f:
    f.write(html_content)

print(f"HTML report generated: {html_file}")
PYTHON_SCRIPT

echo -e "${BOLD}${GREEN}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                  BENCHMARK COMPLETE! ✓                     ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo -e "${CYAN}Results saved to: ${RESULTS_DIR}/${NC}"
echo -e "${CYAN}HTML Report: ${RESULTS_DIR}/report_${TIMESTAMP}.html${NC}"
echo ""
echo -e "${YELLOW}Open the HTML report in your browser:${NC}"
echo -e "${GREEN}open ${RESULTS_DIR}/report_${TIMESTAMP}.html${NC}"

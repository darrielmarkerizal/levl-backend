#!/bin/bash

# FrankenPHP vs RoadRunner Benchmark Comparison Script
# This script runs benchmarks on both servers and compares results

set -e

echo "======================================"
echo "FrankenPHP vs RoadRunner Benchmark"
echo "======================================"
echo ""

# Configuration
BENCHMARK_URL="http://127.0.0.1:8000/dev/benchmark-api?mode=db"
REQUESTS=1000
CONCURRENCY=50

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to run benchmark
run_benchmark() {
    local server_name=$1
    echo -e "${BLUE}Running benchmark for ${server_name}...${NC}"
    ab -n $REQUESTS -c $CONCURRENCY "$BENCHMARK_URL" > "benchmark_${server_name}.txt" 2>&1
    
    # Extract key metrics
    local rps=$(grep "Requests per second" "benchmark_${server_name}.txt" | awk '{print $4}')
    local mean_time=$(grep "Time per request.*mean\)" "benchmark_${server_name}.txt" | head -1 | awk '{print $4}')
    local failed=$(grep "Failed requests" "benchmark_${server_name}.txt" | awk '{print $3}')
    local p50=$(grep "50%" "benchmark_${server_name}.txt" | awk '{print $2}')
    local p95=$(grep "95%" "benchmark_${server_name}.txt" | awk '{print $2}')
    local p99=$(grep "99%" "benchmark_${server_name}.txt" | awk '{print $2}')
    
    echo -e "${GREEN}Results for ${server_name}:${NC}"
    echo "  Requests/sec: $rps"
    echo "  Mean time: ${mean_time}ms"
    echo "  Failed requests: $failed"
    echo "  P50: ${p50}ms"
    echo "  P95: ${p95}ms"
    echo "  P99: ${p99}ms"
    echo ""
    
    # Store results
    echo "$rps|$mean_time|$failed|$p50|$p95|$p99" > "benchmark_${server_name}_summary.txt"
}

# Test 1: FrankenPHP
echo -e "${YELLOW}=== Testing FrankenPHP ===${NC}"
echo "Make sure FrankenPHP is running on port 8000"
echo "Press Enter to continue..."
read

run_benchmark "frankenphp"

# Test 2: RoadRunner
echo -e "${YELLOW}=== Testing RoadRunner ===${NC}"
echo "Please stop FrankenPHP and start RoadRunner on port 8000"
echo "Commands:"
echo "  php artisan octane:stop"
echo "  OCTANE_SERVER=roadrunner php artisan octane:start"
echo ""
echo "Press Enter when RoadRunner is ready..."
read

run_benchmark "roadrunner"

# Compare results
echo ""
echo "======================================"
echo "Comparison Summary"
echo "======================================"
echo ""

frankenphp_data=$(cat benchmark_frankenphp_summary.txt)
roadrunner_data=$(cat benchmark_roadrunner_summary.txt)

IFS='|' read -r fp_rps fp_mean fp_failed fp_p50 fp_p95 fp_p99 <<< "$frankenphp_data"
IFS='|' read -r rr_rps rr_mean rr_failed rr_p50 rr_p95 rr_p99 <<< "$roadrunner_data"

echo "Metric              | FrankenPHP    | RoadRunner    | Winner"
echo "--------------------+---------------+---------------+----------"
printf "Requests/sec        | %-13s | %-13s | " "$fp_rps" "$rr_rps"
if (( $(echo "$fp_rps > $rr_rps" | bc -l) )); then
    echo -e "${GREEN}FrankenPHP${NC}"
else
    echo -e "${GREEN}RoadRunner${NC}"
fi

printf "Mean time (ms)      | %-13s | %-13s | " "$fp_mean" "$rr_mean"
if (( $(echo "$fp_mean < $rr_mean" | bc -l) )); then
    echo -e "${GREEN}FrankenPHP${NC}"
else
    echo -e "${GREEN}RoadRunner${NC}"
fi

printf "Failed requests     | %-13s | %-13s | " "$fp_failed" "$rr_failed"
if (( fp_failed < rr_failed )); then
    echo -e "${GREEN}FrankenPHP${NC}"
else
    echo -e "${GREEN}RoadRunner${NC}"
fi

printf "P95 latency (ms)    | %-13s | %-13s | " "$fp_p95" "$rr_p95"
if (( fp_p95 < rr_p95 )); then
    echo -e "${GREEN}FrankenPHP${NC}"
else
    echo -e "${GREEN}RoadRunner${NC}"
fi

echo ""
echo "Full reports saved to:"
echo "  - benchmark_frankenphp.txt"
echo "  - benchmark_roadrunner.txt"

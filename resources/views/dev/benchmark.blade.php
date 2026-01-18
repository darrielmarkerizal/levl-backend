<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Octane vs PHP-FPM Benchmark</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-indigo-600 p-6 text-white">
            <h1 class="text-3xl font-bold">API Benchmark Tool</h1>
            <p class="text-indigo-200 mt-2">Compare Laravel Octane performance directly from your browser</p>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Configuration -->
            <div>
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Configuration</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Test Mode</label>
                        <select id="mode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                            <option value="simple">Simple (Framework Boot only)</option>
                            <option value="db">Database (Select 1)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Requests</label>
                        <input type="number" id="requests" value="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Concurrency (Browser Limit)</label>
                        <select id="concurrency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                            <option value="1">1 (Sequential)</option>
                            <option value="5">5 (Parallel)</option>
                            <option value="10">10 (Parallel)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Hourly Server Cost ($)</label>
                        <input type="number" id="cost" value="0.03571" step="0.00001" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                    </div>
                    <button id="startBtn" onclick="runBenchmark()" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Start Benchmark
                    </button>
                    <div id="status" class="hidden text-sm text-center text-gray-500"></div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Results</h2>
                <div class="space-y-4">
                     <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Time</span>
                        <span id="res-time" class="font-mono font-bold text-lg">-</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Avg Latency (Browser)</span>
                        <span id="res-latency" class="font-mono font-bold text-lg">-</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Min Response Time</span>
                        <span id="res-min" class="font-mono font-bold text-green-600">-</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Max Response Time</span>
                        <span id="res-max" class="font-mono font-bold text-red-600">-</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Avg Response Time (Server)</span>
                        <span id="res-avg" class="font-mono font-bold text-blue-600">-</span>
                    </div>
                    <div class="flex justify-between items-center bg-indigo-100 p-2 rounded">
                        <span class="text-indigo-800 font-medium">Requests / Sec</span>
                        <span id="res-rps" class="font-mono font-bold text-xl text-indigo-700">-</span>
                    </div>
                     <div class="flex justify-between items-center bg-green-50 p-2 rounded border border-green-100">
                        <div>
                            <span class="text-green-800 font-medium block">Est. Cost (1M Reqs)</span>
                            <span class="text-xs text-green-600">Based on throughput</span>
                        </div>
                        <span id="res-cost" class="font-mono font-bold text-xl text-green-700">-</span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Detailed Metrics (Last Req)</h3>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="text-gray-600">Memory:</div>
                            <div class="font-mono font-bold" id="res-mem">-</div>
                            <div class="text-gray-600">Peak Memory:</div>
                            <div class="font-mono font-bold" id="res-mem-peak">-</div>
                            <div class="text-gray-600">CPU Load (1m):</div>
                            <div class="font-mono font-bold" id="res-cpu">-</div>
                             <div class="text-gray-600">PID:</div>
                            <div class="font-mono font-bold" id="res-pid">-</div>
                        </div>
                        <p id="server-software" class="text-xs text-gray-400 mt-2 break-all font-mono">-</p>
                    </div>

                    <!-- Terminal Suggestion -->
                    <div id="terminal-section" class="hidden mt-4 pt-4 border-t border-gray-200 bg-gray-100 p-3 rounded">
                        <h3 class="text-sm font-medium text-gray-600 mb-1">🚀 For Maximum Speed (True Server Capacity)</h3>
                        <p class="text-xs text-gray-500 mb-2">Browsers are limited to ~6 concurrent connections. Run this in your terminal to test true server concurrency:</p>
                        <code id="terminal-cmd" class="block bg-gray-800 text-green-400 p-2 rounded text-xs overflow-x-auto select-all cursor-pointer"></code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function runBenchmark() {
            const mode = document.getElementById('mode').value;
            const totalRequests = parseInt(document.getElementById('requests').value);
            const concurrency = parseInt(document.getElementById('concurrency').value);
            const hourlyCost = parseFloat(document.getElementById('cost').value);
            const btn = document.getElementById('startBtn');
            const status = document.getElementById('status');

            // Reset
            btn.disabled = true;
            btn.classList.add('opacity-50');
            status.classList.remove('hidden');
            status.textContent = 'Running...';
            
            ['res-time', 'res-latency', 'res-rps', 'res-mem', 'res-mem-peak', 'res-cpu', 'res-pid', 'res-cost', 'res-min', 'res-max', 'res-avg'].forEach(id => document.getElementById(id).textContent = '-');

            const start = performance.now();
            let completed = 0;
            let lastServerInfo = '-';
            let lastMetrics = {};
            let responseTimes = [];

            async function makeRequest() {
                try {
                    const res = await fetch(`/dev/benchmark-api?mode=${mode}`);
                    const data = await res.json();
                    lastServerInfo = data.server;
                    lastMetrics = {
                        mem: data.memory_human,
                        memPeak: (data.memory_peak / 1024 / 1024).toFixed(2) + ' MB',
                        cpu: data.cpu_load ? data.cpu_load[0] : 'N/A',
                        pid: data.pid
                    };
                    if (data.response_time_ms) {
                        responseTimes.push(data.response_time_ms);
                    }
                } catch (e) {
                    console.error(e);
                } finally {
                    completed++;
                    status.textContent = `Completed ${completed} / ${totalRequests}`;
                }
            }

            // Simple concurrency runner
            const queue = Array(totalRequests).fill(null);
            const workers = Array(concurrency).fill(null).map(async () => {
                while(queue.length > 0) {
                    queue.pop();
                    if (completed < totalRequests) {
                         await makeRequest();
                    }
                }
            });

            await Promise.all(workers);

            const end = performance.now();
            const durationMs = end - start;
            const durationSec = durationMs / 1000;
            const rps = totalRequests / durationSec;
            const avgLatency = durationMs / totalRequests;
            
            // Calculate Cost for 1M requests
            // Hours needed for 1M requests = 1,000,000 / (RPS * 3600)
            const hoursFor1M = 1000000 / (rps * 3600);
            const costFor1M = hoursFor1M * hourlyCost;

            // Calculate response time stats
            const minResponseTime = responseTimes.length > 0 ? Math.min(...responseTimes) : 0;
            const maxResponseTime = responseTimes.length > 0 ? Math.max(...responseTimes) : 0;
            const avgResponseTime = responseTimes.length > 0 ? responseTimes.reduce((a, b) => a + b, 0) / responseTimes.length : 0;

            // Update UI
            document.getElementById('res-time').textContent = durationMs.toFixed(2) + ' ms';
            document.getElementById('res-latency').textContent = avgLatency.toFixed(2) + ' ms';
            document.getElementById('res-min').textContent = minResponseTime.toFixed(2) + ' ms';
            document.getElementById('res-max').textContent = maxResponseTime.toFixed(2) + ' ms';
            document.getElementById('res-avg').textContent = avgResponseTime.toFixed(2) + ' ms';
            document.getElementById('res-rps').textContent = rps.toFixed(2);
            document.getElementById('res-cost').textContent = '$' + costFor1M.toFixed(6);
            document.getElementById('server-software').textContent = lastServerInfo;
            
            // Detailed stats
            if (lastMetrics.mem) {
                document.getElementById('res-mem').textContent = lastMetrics.mem;
                document.getElementById('res-mem-peak').textContent = lastMetrics.memPeak;
                document.getElementById('res-cpu').textContent = lastMetrics.cpu;
                document.getElementById('res-pid').textContent = lastMetrics.pid;
            }

            // Show Terminal Command Recommendation
            const port = window.location.port;
            const cmd = `ab -n ${totalRequests} -c 50 http://127.0.0.1:${port}/dev/benchmark-api?mode=${mode}`;
            document.getElementById('terminal-cmd').textContent = cmd;
            document.getElementById('terminal-section').classList.remove('hidden');

            btn.disabled = false;
            btn.classList.remove('opacity-50');
            status.textContent = 'Done!';
        }
    </script>
</body>
</html>

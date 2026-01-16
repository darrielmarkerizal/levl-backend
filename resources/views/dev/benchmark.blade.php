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
                    <button id="startBtn" onclick="runBenchmark()" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Start Benchmark
                    </button>
                    <div id="status" class="hidden text-sm text-center text-gray-500"></div>
                </div>
            </div>

            <!-- Results -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Results</h2>
                <div class="space-y-4">
                     <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Time</span>
                        <span id="res-time" class="font-mono font-bold text-lg">-</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Avg Latency</span>
                        <span id="res-latency" class="font-mono font-bold text-lg">-</span>
                    </div>
                    <div class="flex justify-between items-center bg-indigo-100 p-2 rounded">
                        <span class="text-indigo-800 font-medium">Requests / Sec</span>
                        <span id="res-rps" class="font-mono font-bold text-xl text-indigo-700">-</span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Server Info (Last Req)</h3>
                        <p id="server-software" class="text-xs text-gray-400 break-all font-mono">-</p>
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
            const btn = document.getElementById('startBtn');
            const status = document.getElementById('status');

            // Reset
            btn.disabled = true;
            btn.classList.add('opacity-50');
            status.classList.remove('hidden');
            status.textContent = 'Running...';
            
            ['res-time', 'res-latency', 'res-rps'].forEach(id => document.getElementById(id).textContent = '-');

            const start = performance.now();
            let completed = 0;
            let lastServerInfo = '-';

            async function makeRequest() {
                try {
                    const res = await fetch(`/dev/benchmark-api?mode=${mode}`);
                    const data = await res.json();
                    lastServerInfo = data.server + ' (PID: ' + data.pid + ')';
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

            // Update UI
            document.getElementById('res-time').textContent = durationMs.toFixed(2) + ' ms';
            document.getElementById('res-latency').textContent = avgLatency.toFixed(2) + ' ms';
            document.getElementById('res-rps').textContent = rps.toFixed(2);
            document.getElementById('server-software').textContent = lastServerInfo;

            btn.disabled = false;
            btn.classList.remove('opacity-50');
            status.textContent = 'Done!';
        }
    </script>
</body>
</html>

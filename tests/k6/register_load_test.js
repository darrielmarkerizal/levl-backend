import http from 'k6/http';
import { check, sleep } from 'k6';
import { htmlReport } from "https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js";
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.1/index.js';

export let options = {
    // Skenario Beban (Load Scenario) - Test Register dengan User Baru
    stages: [
        { duration: '10s', target: 10 }, // Pemanasan: Naik ke 10 user
        { duration: '20s', target: 50 }, // Hajar: Naik ke 50 user (Sesuai Max Rozi)
        { duration: '30s', target: 50 }, // Tahan: Stabil di 50 user (Stress Test)
        { duration: '10s', target: 0 },  // Pendinginan: Turun ke 0
    ],
    // Ambang Batas (Thresholds) - Syarat Lulus Skripsi
    thresholds: {
        'http_req_duration': ['p(95)<500'], // 95% request harus di bawah 500ms (Standar API bagus)
        'http_req_failed': ['rate<0.01'],   // Error rate wajib di bawah 1%
    },
};

export default function () {
    // 1. Setup Header
    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    };

    // 2. Generate unique data untuk setiap request (Hindari duplicate error)
    const timestamp = Date.now();
    const random = Math.floor(Math.random() * 10000);
    const vuId = __VU; // Virtual User ID dari k6
    const iterationId = __ITER; // Iteration ID dari k6
    const uniqueEmail = `stress.test.${vuId}.${iterationId}.${timestamp}.${random}@test.com`;
    const uniqueUsername = `stresstest_${vuId}_${iterationId}_${timestamp}_${random}`;

    // 3. Hit Endpoint Register
    const url = 'http://127.0.0.1:8000/api/v1/auth/register';
    const payload = JSON.stringify({
        name: `Stress Test User ${vuId}-${iterationId}`,
        username: uniqueUsername,
        email: uniqueEmail,
        password: 'SecurePassword123!',
        password_confirmation: 'SecurePassword123!',
    });

    let res = http.post(url, payload, params);

    // 4. Validasi (Checks)
    const responseBody = res.json();
    check(res, {
        'status is 201 or 200': (r) => r.status === 201 || r.status === 200,
        'success is true': (r) => responseBody.success === true,
        'user created': (r) => responseBody?.data?.user !== undefined,
        'access_token received': (r) => responseBody?.data?.access_token !== undefined,
        'refresh_token received': (r) => responseBody?.data?.refresh_token !== undefined,
        'not throttled': (r) => r.status !== 429,
        'no duplicate error': (r) => r.status !== 422,
    });

    // Jeda antar request (User manusia tidak nge-klik secepat kilat)
    sleep(1);
}

// Cleanup otomatis setelah test selesai (k6 teardown)
export function teardown(data) {
    console.log('🧹 Test selesai! User dummy akan dihapus otomatis via bash script...');
    console.log('📊 Total requests:', data.metrics.http_reqs.values.count);
}

// Generate laporan HTML & Terminal
export function handleSummary(data) {
    return {
        "laporan_register_skripsi.html": htmlReport(data), // File HTML cantik
        "stdout": textSummary(data, { indent: " ", enableColors: true }), // Info di terminal
    };
}

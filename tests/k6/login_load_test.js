import http from 'k6/http';
import { check, sleep } from 'k6';
import { htmlReport } from "https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js";
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.1/index.js';

export let options = {
    // Skenario Beban (Load Scenario) - Meniru gaya Rozi tapi lebih modern
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

    // 2. Hit Endpoint (Misal: Login atau Register seperti Rozi)
    // Pastikan URL ini benar-benar ada di Laravel-mu
    const url = 'http://127.0.0.1:8000/api/v1/auth/login';
    const payload = JSON.stringify({
        login: 'student.demo@test.com', // Pastikan user ini ada di DB!
        password: 'password',
    });

    let res = http.post(url, payload, params);

    // DEBUG: Print response untuk cek struktur (uncomment jika perlu debug)
    // if (__ITER === 0 && __VU === 1) {
    //     console.log('Response Status:', res.status);
    //     console.log('Response Body:', res.body);
    // }

    // 3. Validasi (Checks)
    const responseBody = res.json();
    check(res, {
        'status is 200': (r) => r.status === 200,
        'success is true': (r) => responseBody.success === true,
        'access_token received': (r) => responseBody?.data?.access_token !== undefined,
        'refresh_token received': (r) => responseBody?.data?.refresh_token !== undefined,
        'not throttled': (r) => r.status !== 429,
        'response time OK': (r) => r.timings.duration < 2000, // Alert jika > 2 detik
    });

    // Jeda antar request (User manusia tidak nge-klik secepat kilat)
    sleep(1);
}

// Bagian ini sudah BENAR untuk generate laporan HTML & Terminal
export function handleSummary(data) {
    return {
        "laporan_skripsi.html": htmlReport(data), // File HTML cantik
        "stdout": textSummary(data, { indent: " ", enableColors: true }), // Info di terminal
    };
}
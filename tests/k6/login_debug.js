import http from 'k6/http';

// Script DEBUG untuk cek response structure
export let options = {
    vus: 1,
    iterations: 1,
};

export default function () {
    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    };

    const url = 'http://127.0.0.1:8000/api/v1/auth/login';
    const payload = JSON.stringify({
        login: 'student.demo@test.com',
        password: 'password',
    });

    console.log('🔍 Sending request to:', url);
    console.log('📦 Payload:', payload);
    console.log('');

    let res = http.post(url, payload, params);

    console.log('📊 Response Status:', res.status);
    console.log('⏱️  Response Time:', res.timings.duration, 'ms');
    console.log('');
    console.log('📄 Response Body:');
    console.log(res.body);
    console.log('');
    
    try {
        const json = res.json();
        console.log('🔍 Parsed JSON:');
        console.log(JSON.stringify(json, null, 2));
        console.log('');
        console.log('🔑 Token Path Checks:');
        console.log('  - data.token:', json?.data?.token);
        console.log('  - data.access_token:', json?.data?.access_token);
        console.log('  - token:', json?.token);
        console.log('  - access_token:', json?.access_token);
    } catch (e) {
        console.log('❌ Failed to parse JSON:', e);
    }
}

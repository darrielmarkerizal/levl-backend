import http from 'k6/http';

// Script DEBUG untuk cek response structure REGISTER
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

    const url = 'http://127.0.0.1:8000/api/v1/auth/register';
    const payload = JSON.stringify({
        name: "Debug Test User",
        username: `debugtest_${Date.now()}`,
        email: `debug.test.${Date.now()}@test.com`,
        password: 'SecurePassword123!',
        password_confirmation: 'SecurePassword123!',
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
        
        if (json.errors) {
            console.log('❌ Validation Errors:');
            console.log(JSON.stringify(json.errors, null, 2));
        }
        
        console.log('🔑 Token Path Checks:');
        console.log('  - data.access_token:', json?.data?.access_token);
        console.log('  - data.user:', json?.data?.user?.email);
    } catch (e) {
        console.log('❌ Failed to parse JSON:', e);
    }
}

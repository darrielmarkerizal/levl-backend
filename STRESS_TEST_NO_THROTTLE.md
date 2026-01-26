# Laravel Rate Limiting - Disabled for Stress Testing

## Perubahan yang Dilakukan

Throttling/Rate Limiting telah dimatikan untuk environment `testing` agar stress test bisa mengukur performa CPU/RAM server secara murni tanpa dibatasi oleh rate limiter.

### 1. **bootstrap/app.php**
- Middleware `ThrottleRequests` hanya diterapkan jika environment BUKAN `testing`
- Bypass throttle saat `APP_ENV=testing`

### 2. **app/Providers/AppServiceProvider.php**
- Rate limiter dikonfigurasi ke `Limit::none()` (unlimited) untuk environment `testing`
- Berlaku untuk semua limiter: `api`, `auth`, `enrollment`

### 3. **config/rate-limiting.php**
- Ditambahkan dokumentasi untuk stress testing
- Config tetap ada untuk production

---

## Cara Menggunakan

### Opsi 1: Set Environment ke Testing (RECOMMENDED)
```bash
# Edit .env atau copy dari .env.stress-test
APP_ENV=testing
```

Kemudian jalankan Octane:
```bash
php artisan octane:restart --server=frankenphp
```

### Opsi 2: Set Rate Limit ke Unlimited via .env
```bash
# Di .env
RATE_LIMIT_API_DEFAULT_MAX=999999
RATE_LIMIT_AUTH_MAX=999999
RATE_LIMIT_ENROLLMENT_MAX=999999
```

**NOTE:** Opsi 1 lebih aman karena throttling benar-benar dibypass di code level.

---

## Cara Menjalankan Stress Test

### Dengan k6
```bash
k6 run tests/k6/stress-test.js
```

### Dengan Apache Bench
```bash
# 1000 requests, 100 concurrent
ab -n 1000 -c 100 https://your-api.com/api/v1/courses
```

### Dengan wrk
```bash
# 10 threads, 100 connections, 30 detik
wrk -t10 -c100 -d30s https://your-api.com/api/v1/courses
```

---

## Kembalikan ke Production

Setelah stress test selesai, **JANGAN LUPA** kembalikan environment:

```bash
# Edit .env
APP_ENV=production  # atau staging/local

# Restart Octane
php artisan octane:restart --server=frankenphp
```

---

## Testing

Untuk memastikan throttling sudah mati:

```bash
# Test dengan curl (hit 100x dalam 1 detik)
for i in {1..100}; do 
  curl -s -o /dev/null -w "%{http_code}\n" https://your-api.com/api/v1/courses &
done
wait

# Jika throttling mati: semua return 200 atau 401 (auth)
# Jika throttling aktif: beberapa return 429 (Too Many Requests)
```

---

## Catatan Penting

⚠️ **JANGAN deploy ke production dengan `APP_ENV=testing`**

Rate limiting adalah pertahanan penting untuk mencegah:
- DDoS attacks
- Brute force login
- API abuse

Matikan throttling HANYA untuk:
- Stress testing di staging/dev
- Load testing untuk capacity planning
- Performance benchmarking

---

## Monitoring Saat Stress Test

Monitor server metrics:
```bash
# CPU & Memory usage
htop

# Octane worker status
php artisan octane:status

# Laravel logs
tail -f storage/logs/laravel.log
```

Metrics yang perlu diamati:
- **CPU Usage**: Target < 80% pada peak load
- **Memory**: Watch untuk memory leak
- **Response Time**: P50, P95, P99 latency
- **Error Rate**: Target < 1% errors

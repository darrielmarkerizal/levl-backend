# Laravel Octane Optimization

This project uses Laravel Octane to enhance performance by keeping the application in memory between requests.

## Quick Start

To start the Octane server:

```bash
php artisan octane:start
```

To restart after code changes:

```bash
php artisan octane:reload
```

To stop the server:

```bash
php artisan octane:stop
```

## Configuration

The Octane configuration is located in `config/octane.php`. Key settings include:

- **Workers**: Configured via `SWOOLE_WORKER_NUM` environment variable
- **Max Requests**: Configured via `SWOOLE_MAX_REQUEST` to prevent memory leaks
- **Garbage Collection**: Configured via `OCTANE_GARBAGE_COLLECTION` in MB

## Environment Variables

Copy the example environment file to configure Octane:

```bash
cp .env.octane .env
```

Then adjust the values as needed for your environment.

## Performance Monitoring

Use the monitoring script to check server statistics:

```bash
php monitor-octane.php
```

## Best Practices Implemented

1. **Worker Configuration**: Optimized worker count based on CPU cores
2. **Memory Management**: Automatic worker restart after max requests
3. **Database Connections**: Proper disconnection after each request
4. **Service Warming**: Common services pre-loaded for faster response times
5. **Garbage Collection**: Configurable thresholds to prevent memory leaks

## Troubleshooting

- If experiencing memory leaks, reduce `SWOOLE_MAX_REQUEST` value
- For high concurrency, increase `SWOOLE_WORKER_NUM` gradually
- Monitor logs at `storage/logs/swoole_http.log` for errors
- Use `php artisan octane:reload` instead of restart for code changes

## Testing

Run your tests normally - Octane will not interfere with PHPUnit tests.
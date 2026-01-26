# Laravel Octane Optimization Guide

Based on the research from multiple sources, here's a comprehensive guide to optimize Laravel Octane for maximum performance.

## 1. Infrastructure Configuration Optimizations

### Worker Configuration
- Set appropriate number of Swoole workers based on vCPU count (recommended 1-4 workers per vCPU)
- Configure workers in `octane.php`:

```php
'swoole' => [
    'options' => [
        'worker_num' => env('SWOOLE_WORKER_NUM', swoole_cpu_num()),
        'max_request' => env('SWOOLE_MAX_REQUEST', 500),
    ],
],
```

### System Limits Adjustments
- Increase `worker_connections` in nginx.conf
- Permanently increase open file descriptors by modifying:
  - `/etc/security/limits.conf`: Add `* soft nofile 65536` and `* hard nofile 65536`
  - `/etc/pam.d/common-session`: Add `session required pam_limits.so`
  - `/etc/pam.d/common-session-noninteractive`: Add `session required pam_limits.so`
  - `/etc/sysctl.conf`: Add `fs.file-max = 2097152`
  - `/etc/nginx/nginx.conf`: Add `worker_rlimit_nofile 65536;`

## 2. Database Connection Management

### Fix Transaction Issues
- Uncomment `DisconnectFromDatabases::class` in the `OperationTerminated` event in Octane configuration to properly disconnect from databases after each request
- Handle persistent database connections properly (Octane keeps them alive by default)

Current configuration already includes this:
```php
OperationTerminated::class => [
    FlushOnce::class,
    FlushTemporaryContainerInstances::class,
    DisconnectFromDatabases::class,  // This is already enabled
    CollectGarbage::class,
],
```

## 3. Data Isolation Best Practices

### Avoid Cross-Request Data Sharing
- Don't use static variables or singletons that persist across requests
- Instead of dynamic config settings, bind values directly to the request:
```php
// Instead of config(['game.mode' => \App\Enums\GameMode::APP])
$request->attributes->set('game_mode', \App\Enums\GameMode::APP);
```
- Always explicitly reset configuration values rather than relying on defaults

## 4. Race Condition Prevention

### Implement Locking Mechanisms
- Use cache locks for heavy operations that could be triggered simultaneously:
```php
return Cache::lock('active_players')->get(function() {
    return $this->getActivePlayers();
});
```
- Return default values while operations are locked to prevent blocking
- Move heavy operations to queues when possible to avoid impacting user experience

## 5. Memory Management

### Prevent Memory Leaks
- Set `max_request` to limit requests per worker and implement custom memory monitoring
- Configure garbage collection threshold:

```php
'garbage' => env('OCTANE_GARBAGE_COLLECTION', 50), // MB
```

### Advanced Garbage Collection Tuning
- Adjust PHP's garbage collection settings and force collection at intervals
- Use the `CollectGarbage` listener which is already included in your configuration

## 6. Connection Pooling Configuration

### Enable Persistent Database Connections
- Database connections remain persistent by default, improving performance
- To disconnect databases after each request, keep the `DisconnectFromDatabases::class` in your configuration

## 7. Custom Worker Initialization

### Pre-warm Services
- The current configuration already warms common services:

```php
'warm' => [
    'db',
    'cache',
    'log',
    'session',
    'url',
    'view',
    'translator',
    'queue',
],
```

## 8. Monitoring and Debugging Techniques

### Worker Monitoring
- Use `app()->get(\Swoole\Http\Server::class)->stats(1)` to monitor server statistics
- Track connection counts, worker utilization, and queued connections
- Monitor for worker exhaustion which causes request queuing

### Performance Tracking
- Monitor response times and connection queuing separately from application metrics
- Be aware that APM tools like Sentry may not capture queuing time before request processing

## 9. Recommended Environment Variables

Add these to your `.env` file:

```env
# Octane Server
OCTANE_SERVER=swoole

# Swoole Configuration
SWOOLE_WORKER_NUM=8
SWOOLE_MAX_REQUEST=500

# Octane Configuration
OCTANE_CACHE=true
OCTANE_GARBAGE_COLLECTION=50

# HTTPS
OCTANE_HTTPS=false
```

## 10. Production Deployment Strategies

- Perform hard restart (`octane:start`) when changing worker numbers in configuration
- Note that `octane:reload` only reloads existing workers, doesn't create new ones
- Implement zero-downtime deployments with blue-green deployment scripts

## 11. Performance Benchmarking

Use tools like `wrk` to measure the impact of tuning efforts:

```bash
# Example benchmark command
wrk -t12 -c400 -d30s http://your-domain.com/api/endpoint
```

## 12. Advanced Configuration Options

### Swoole-Specific Options
```php
'swoole' => [
    'options' => [
        'worker_num' => env('SWOOLE_WORKER_NUM', swoole_cpu_num()),
        'max_request' => env('SWOOLE_MAX_REQUEST', 500),
        'task_worker_num' => env('SWOOLE_TASK_WORKER_NUM', 8),
        'max_wait_time' => 60,
        'log_file' => storage_path('logs/swoole_http.log'),
        'heartbeat_check_interval' => 60,
        'heartbeat_idle_time' => 120,
    ],
],
```

## 13. Best Practices Summary

1. ❌ Avoid Static Variables and Properties
2. ⚠️ Use Singletons Carefully  
3. 🗄️ Database Connections Are Persistent by Default
4. ⚙️ Choose the Right Driver (Swoole vs RoadRunner)
5. 📊 Optimize Worker Counts
6. 🔄 Reset Workers After X Requests
7. 🔍 Monitor Queuing
8. ⚔️ Avoid Race Conditions

## 14. Common Issues and Solutions

### Issue: Memory Leaks
**Solution**: 
- Set `max_request` to reload workers after handling a specified number of requests
- Use `--max-requests=500` parameter to reload workers after handling 500 requests

### Issue: Race Conditions
**Solution**:
- Use atomic locks to prevent race conditions
- Implement proper caching strategies with locks

### Issue: Static Data Persistence
**Solution**:
- Avoid static variables and properties
- Reset state properly between requests
- Use request attributes instead of global state

## 15. Performance Monitoring

Monitor these key metrics:
- Response times (especially 95th percentile)
- Worker utilization
- Queue length
- Memory usage per worker
- Error rates
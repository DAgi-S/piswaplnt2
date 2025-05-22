# Redis Setup Guide for Rate Limiting

This guide explains how to set up Redis for rate limiting in the PiStockInt system.

## Windows Installation

1. Download Redis for Windows:
   - Visit https://github.com/microsoftarchive/redis/releases
   - Download the latest MSI installer (e.g., Redis-x64-3.0.504.msi)

2. Install Redis:
   - Run the MSI installer
   - Choose "Complete" installation
   - Add Redis installation folder to your system's PATH

3. Create Required Directories:
```powershell
mkdir C:\redis\data
mkdir C:\redis\logs
```

4. Copy Configuration:
   - Copy `config/redis/redis.windows.conf` to `C:\redis\redis.windows.conf`
   - Update the password in the configuration file:
     - Replace `your_redis_password_here` with a strong password

5. Install Redis as a Windows Service:
```powershell
redis-server --service-install C:\redis\redis.windows.conf --service-name RedisRateLimit
```

6. Start Redis Service:
```powershell
net start RedisRateLimit
```

## Security Configuration

1. Environment Variables:
   - Set the following environment variables:
```powershell
setx REDIS_HOST "127.0.0.1"
setx REDIS_PORT "6379"
setx REDIS_PASSWORD "your_redis_password_here"
```

2. Verify Installation:
```powershell
redis-cli
auth your_redis_password_here
ping
```
You should receive "PONG" as a response.

## PHP Configuration

1. Install PHP Redis Extension:
   - Download the appropriate version from https://pecl.php.net/package/redis
   - Place the DLL in your PHP ext directory
   - Add `extension=redis.so` to your php.ini

2. Verify PHP Redis:
```php
<?php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$redis->auth('your_redis_password_here');
echo $redis->ping();
```

## Testing Rate Limiting

1. Run the Security Test Suite:
```powershell
./vendor/bin/phpunit tests/SecurityTest.php
```

2. Monitor Redis:
```powershell
redis-cli
auth your_redis_password_here
monitor
```

## Troubleshooting

1. If Redis service fails to start:
   - Check Windows Event Viewer for errors
   - Verify configuration file path
   - Ensure directories exist and have proper permissions

2. If PHP can't connect to Redis:
   - Verify Redis service is running: `net start RedisRateLimit`
   - Check PHP Redis extension is loaded: `php -m | findstr redis`
   - Verify connection settings in your application

## Maintenance

1. Regular Tasks:
   - Monitor Redis memory usage
   - Check Redis logs for errors
   - Update password periodically
   - Backup Redis data directory

2. Monitoring Commands:
```powershell
redis-cli
auth your_redis_password_here
info memory
info stats
```

## Security Best Practices

1. Network Security:
   - Keep Redis bound to localhost (127.0.0.1)
   - Use strong passwords
   - Disable dangerous commands
   - Enable protected mode

2. Data Security:
   - Regular backups
   - Monitor access logs
   - Implement proper error handling
   - Use SSL/TLS for remote connections

## Additional Resources

- [Redis Documentation](https://redis.io/documentation)
- [Redis Windows](https://github.com/microsoftarchive/redis)
- [PHP Redis](https://github.com/phpredis/phpredis) 
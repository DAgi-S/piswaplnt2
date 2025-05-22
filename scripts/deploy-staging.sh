#!/bin/bash

# Staging Deployment Script
echo "Starting staging deployment..."

# Configuration
STAGING_DIR="/var/www/staging.pistocklnt.lebawi.net"
BACKUP_DIR="/var/www/backups/staging"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# Create backup of current deployment
echo "Creating backup..."
tar -czf "$BACKUP_DIR/backup_$TIMESTAMP.tar.gz" -C $STAGING_DIR .

# Update code from repository
echo "Updating code..."
git pull origin develop

# Install/update dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Set up environment
echo "Setting up environment..."
cp config/environments/staging.php config/jwt/config.php

# Create required directories
mkdir -p logs
chmod 755 logs
touch logs/staging-error.log
chmod 644 logs/staging-error.log

# Clear caches
echo "Clearing caches..."
redis-cli -h $REDIS_HOST -p $REDIS_PORT FLUSHDB

# Database migrations (if any)
echo "Running database migrations..."
php scripts/migrate.php --env=staging

# Restart services
echo "Restarting services..."
sudo systemctl restart php-fpm
sudo systemctl restart nginx
sudo systemctl restart websocket-server

# Verify deployment
echo "Verifying deployment..."
curl -s -o /dev/null -w "%{http_code}" https://staging.pistocklnt.lebawi.net/api/v2/auth/validate

echo "Deployment completed!" 
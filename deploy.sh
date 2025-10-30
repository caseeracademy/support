#!/bin/bash

# Caseer Academy Support System - Deployment Script
# This script automates the deployment process for production environments

set -e  # Exit on any error

echo "=========================================="
echo "Caseer Academy Support System Deployment"
echo "=========================================="
echo ""

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the project root."
    exit 1
fi

# Check PHP version
echo "🔍 Checking PHP version..."
PHP_VERSION=$(php -r "echo PHP_VERSION;")
PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;")
PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;")

if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 2 ]); then
    echo "❌ Error: PHP 8.2 or higher is required. Current version: $PHP_VERSION"
    exit 1
fi

echo "✅ PHP version: $PHP_VERSION"

# Check required PHP extensions
echo ""
echo "🔍 Checking required PHP extensions..."
REQUIRED_EXTENSIONS=("pdo" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath" "fileinfo")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -qi "^$ext$"; then
        MISSING_EXTENSIONS+=("$ext")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -gt 0 ]; then
    echo "❌ Error: Missing required PHP extensions: ${MISSING_EXTENSIONS[*]}"
    exit 1
fi

echo "✅ All required PHP extensions are installed"

# Check if .env file exists
echo ""
echo "🔍 Checking environment configuration..."
if [ ! -f ".env" ]; then
    echo "❌ Error: .env file not found."
    echo "Please copy .env.production.example to .env and configure it."
    exit 1
fi

echo "✅ Environment file found"

# Maintenance mode
echo ""
echo "🔧 Putting application into maintenance mode..."
php artisan down --retry=60 || echo "⚠️  Maintenance mode failed (might already be in maintenance)"

# Pull latest changes (if using git)
if [ -d ".git" ]; then
    echo ""
    echo "📥 Pulling latest changes from Git..."
    git pull origin main || git pull origin master || echo "⚠️  Git pull failed or not configured"
fi

# Install/update Composer dependencies
echo ""
echo "📦 Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction

# Clear and cache configuration
echo ""
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "💾 Caching configuration, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo ""
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Optimize Filament
echo ""
echo "⚡ Optimizing Filament..."
php artisan filament:optimize

# Set proper permissions
echo ""
echo "🔐 Setting proper permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || echo "⚠️  Could not change ownership (might need sudo)"

# Restart queue workers if supervisor is running
echo ""
if command -v supervisorctl &> /dev/null; then
    echo "🔄 Restarting queue workers..."
    sudo supervisorctl restart whatsapp-queue:* 2>/dev/null || echo "⚠️  Queue worker restart failed (supervisor might not be configured)"
else
    echo "⚠️  Supervisor not found - queue workers not restarted"
    echo "Remember to manually restart your queue workers if needed"
fi

# Restart PHP-FPM if available
echo ""
if command -v systemctl &> /dev/null; then
    echo "🔄 Restarting PHP-FPM..."
    sudo systemctl restart php8.4-fpm 2>/dev/null || \
    sudo systemctl restart php8.3-fpm 2>/dev/null || \
    sudo systemctl restart php-fpm 2>/dev/null || \
    echo "⚠️  PHP-FPM restart failed (service might not exist or need different command)"
fi

# Exit maintenance mode
echo ""
echo "✅ Bringing application back up..."
php artisan up

# Final summary
echo ""
echo "=========================================="
echo "✅ Deployment completed successfully!"
echo "=========================================="
echo ""
echo "📝 Post-deployment checklist:"
echo "  1. Check the application in your browser"
echo "  2. Verify queue workers are running (if using queues)"
echo "  3. Check logs: tail -f storage/logs/laravel.log"
echo "  4. Test critical features (login, tickets, student search)"
echo ""
echo "🎉 Deployment finished at $(date)"









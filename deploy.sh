#!/bin/bash

# Laravel Deployment Script
echo "🚀 Starting Laravel deployment..."

# Install/update dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Clear all caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Create storage link (this is the key part!)
echo "🔗 Creating storage link..."
php artisan storage:link --force

# Run migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions (if on Linux/Unix)
echo "🔐 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/app/public

echo "✅ Deployment completed successfully!"

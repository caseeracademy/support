# Production Deployment Guide

Complete guide for deploying the Caseer Academy Support System to a production server.

---

## 📋 Server Requirements

### Minimum Requirements

- **PHP:** 8.2 or higher (8.4 recommended)
- **Web Server:** Nginx or Apache
- **Database:** MySQL 8.0+ or PostgreSQL 13+ or MariaDB 10.3+
- **Memory:** 512MB minimum (1GB+ recommended)
- **Disk Space:** 500MB minimum

### Required PHP Extensions

```bash
php -m  # Check installed extensions
```

Required extensions:
- PDO
- mbstring
- OpenSSL
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- Fileinfo
- GD (for image processing)
- Zip (for composer)

Install missing extensions (Ubuntu/Debian):
```bash
sudo apt-get install php8.4-{mbstring,xml,bcmath,gd,zip,mysql,curl}
```

---

## 🚀 Initial Server Setup

### 1. Install Required Software

**Ubuntu/Debian:**
```bash
# Update package list
sudo apt-get update

# Install PHP 8.4 and required extensions
sudo apt-get install php8.4-fpm php8.4-{mbstring,xml,bcmath,gd,zip,mysql,curl,cli}

# Install Nginx
sudo apt-get install nginx

# Install MySQL
sudo apt-get install mysql-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Supervisor (for queue workers)
sudo apt-get install supervisor

# Install Git
sudo apt-get install git
```

### 2. Create Application User

```bash
sudo useradd -m -s /bin/bash caseer
sudo usermod -aG www-data caseer
```

### 3. Setup Project Directory

```bash
# Navigate to web root
cd /var/www

# Clone repository or upload files
sudo git clone https://github.com/your-repo/whatsapp-support.git caseer-support
# OR
sudo mkdir caseer-support && cd caseer-support
# Upload files via FTP/SFTP

# Set ownership
sudo chown -R caseer:www-data /var/www/caseer-support
sudo chmod -R 755 /var/www/caseer-support
sudo chmod -R 775 /var/www/caseer-support/storage
sudo chmod -R 775 /var/www/caseer-support/bootstrap/cache
```

---

## 🗄️ Database Setup

### MySQL Configuration

```bash
# Login to MySQL
sudo mysql

# Create database and user
CREATE DATABASE caseer_support CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'caseer_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON caseer_support.* TO 'caseer_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### PostgreSQL Configuration (Alternative)

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE caseer_support;
CREATE USER caseer_user WITH PASSWORD 'your_secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE caseer_support TO caseer_user;
\q
```

---

## ⚙️ Application Configuration

### 1. Environment Setup

```bash
cd /var/www/caseer-support

# Copy production environment template
cp .env.production.example .env

# Edit environment file
nano .env
```

**Important .env settings:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://support.caseer.academy

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=caseer_support
DB_USERNAME=caseer_user
DB_PASSWORD=your_secure_password_here

CASEER_API_URL=https://caseer.academy/wp-json/my-app/v1
CASEER_API_SECRET=your_api_secret_key_here

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_email@example.com
MAIL_PASSWORD=your_email_password
MAIL_FROM_ADDRESS="support@caseer.academy"
```

### 2. Install Dependencies

```bash
# Install Composer dependencies
composer install --optimize-autoloader --no-dev

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create admin user
php artisan make:filament-user
# Follow prompts to create admin account
```

### 3. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

---

## 🌐 Web Server Configuration

### Nginx Configuration

Create `/etc/nginx/sites-available/caseer-support`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name support.caseer.academy;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name support.caseer.academy;
    root /var/www/caseer-support/public;

    # SSL Configuration (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/support.caseer.academy/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/support.caseer.academy/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /var/log/nginx/caseer-support-access.log;
    error_log /var/log/nginx/caseer-support-error.log;

    index index.php index.html;
    charset utf-8;

    # Laravel public directory
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 365d;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/caseer-support /etc/nginx/sites-enabled/
sudo nginx -t  # Test configuration
sudo systemctl reload nginx
```

### Apache Configuration (Alternative)

Create `/etc/apache2/sites-available/caseer-support.conf`:

```apache
<VirtualHost *:80>
    ServerName support.caseer.academy
    Redirect permanent / https://support.caseer.academy/
</VirtualHost>

<VirtualHost *:443>
    ServerName support.caseer.academy
    DocumentRoot /var/www/caseer-support/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/support.caseer.academy/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/support.caseer.academy/privkey.pem

    <Directory /var/www/caseer-support/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/caseer-support-error.log
    CustomLog ${APACHE_LOG_DIR}/caseer-support-access.log combined
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite caseer-support.conf
sudo a2enmod rewrite ssl
sudo systemctl reload apache2
```

---

## 🔒 SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d support.caseer.academy

# Auto-renewal is configured automatically
# Test renewal
sudo certbot renew --dry-run
```

---

## 🔄 Queue Worker Setup (Supervisor)

Create `/etc/supervisor/conf.d/caseer-support-queue.conf`:

```ini
[program:caseer-support-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/caseer-support/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=caseer
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/caseer-support/storage/logs/queue.log
stopwaitsecs=3600
```

Start the queue worker:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start caseer-support-queue:*
```

Check status:

```bash
sudo supervisorctl status caseer-support-queue:*
```

---

## 🚀 Deployment Script Usage

The included `deploy.sh` script automates deployment:

```bash
cd /var/www/caseer-support

# Make script executable (if not already)
chmod +x deploy.sh

# Run deployment
./deploy.sh
```

**What the script does:**
1. ✅ Checks PHP version and extensions
2. ✅ Puts app in maintenance mode
3. ✅ Pulls latest code (if using Git)
4. ✅ Installs Composer dependencies
5. ✅ Clears and caches configs/routes/views
6. ✅ Runs database migrations
7. ✅ Optimizes Filament
8. ✅ Sets proper permissions
9. ✅ Restarts queue workers
10. ✅ Brings app back online

---

## 🔧 Post-Deployment Verification

### 1. Check Application

```bash
# Visit in browser
https://support.caseer.academy/admin

# Check logs
tail -f /var/www/caseer-support/storage/logs/laravel.log
```

### 2. Test Critical Features

- ✅ Login to admin panel
- ✅ View tickets
- ✅ Search students (API integration)
- ✅ Create new user
- ✅ Test webhook (create test order)

### 3. Monitor Performance

```bash
# Check disk space
df -h

# Check memory usage
free -m

# Check queue workers
sudo supervisorctl status

# Check Nginx/Apache status
sudo systemctl status nginx
# or
sudo systemctl status apache2
```

---

## 📊 Monitoring & Maintenance

### Log Rotation

Create `/etc/logrotate.d/caseer-support`:

```
/var/www/caseer-support/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 caseer www-data
    sharedscripts
}
```

### Database Backups

```bash
# Manual backup
mysqldump -u caseer_user -p caseer_support > backup-$(date +%Y%m%d).sql

# Automated daily backup (crontab)
0 2 * * * mysqldump -u caseer_user -p'password' caseer_support | gzip > /backups/caseer-$(date +\%Y\%m\%d).sql.gz
```

### Cron Jobs

Add to crontab (`crontab -e`):

```cron
# Laravel scheduler
* * * * * cd /var/www/caseer-support && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🐛 Troubleshooting

### Issue: 500 Internal Server Error

```bash
# Check logs
tail -50 /var/www/caseer-support/storage/logs/laravel.log
tail -50 /var/log/nginx/caseer-support-error.log

# Check permissions
sudo chown -R caseer:www-data /var/www/caseer-support
sudo chmod -R 755 /var/www/caseer-support
sudo chmod -R 775 /var/www/caseer-support/storage
sudo chmod -R 775 /var/www/caseer-support/bootstrap/cache
```

### Issue: Database Connection Failed

```bash
# Test database connection
mysql -u caseer_user -p caseer_support

# Check .env file
cat /var/www/caseer-support/.env | grep DB_
```

### Issue: Queue Workers Not Running

```bash
# Restart workers
sudo supervisorctl restart caseer-support-queue:*

# Check worker logs
tail -f /var/www/caseer-support/storage/logs/queue.log
```

### Issue: Webhook Not Working

```bash
# Check webhook endpoint is accessible
curl -X POST https://support.caseer.academy/webhook/order-status

# Check Nginx/Apache logs
tail -f /var/log/nginx/caseer-support-access.log
```

---

## 🔄 Updating the Application

```bash
cd /var/www/caseer-support

# Pull latest changes
git pull origin main

# Run deployment script
./deploy.sh
```

Or manually:

```bash
php artisan down
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart caseer-support-queue:*
php artisan up
```

---

## 📞 Support & Resources

- **Laravel Documentation:** https://laravel.com/docs
- **Filament Documentation:** https://filamentphp.com/docs
- **Server Setup:** https://forge.laravel.com (managed hosting)
- **Project Repository:** [Your Git repository URL]

---

## ✅ Pre-Launch Checklist

Before going live:

- [ ] SSL certificate installed and working
- [ ] Database configured and backed up
- [ ] `.env` file configured correctly
- [ ] APP_DEBUG=false in production
- [ ] Queue workers running via Supervisor
- [ ] Cron jobs configured for Laravel scheduler
- [ ] Log rotation configured
- [ ] Firewall configured (allow 80, 443)
- [ ] Admin user created
- [ ] Webhook URL configured on main website
- [ ] Test all critical features
- [ ] Monitor logs for errors
- [ ] Set up database backup automation

---

**Last Updated:** October 12, 2025  
**Version:** 1.0  
**Maintained By:** Caseer Academy Development Team


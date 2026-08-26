#!/bin/sh

# ساخت دیتابیس SQLite در صورت عدم وجود
touch /var/www/database/database.sqlite
chown -R www-data:www-data /var/www/database

# کش کردن کانفیگ‌ها و روت‌ها برای پرفورمنس بالا
php artisan config:cache
php artisan route:cache
php artisan view:cache

# اجرای مایگریشن‌ها و سیدرها
php artisan migrate --force

# اجرای برنامه‌های اصلی
exec /usr/bin/supervisord -c /etc/supervisord.conf

FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set Apache DocumentRoot to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy the rest of the application
COPY . .

# Re-run composer scripts after full copy
RUN composer dump-autoload --optimize

# Generate .env file using heredoc (avoids whitespace issues)
COPY <<'ENVFILE' /var/www/html/.env
APP_NAME=GanaderaSoft-API
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
LOG_CHANNEL=stack
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=ganaderasoft
DB_USERNAME=ganaderasoft_user
DB_PASSWORD=ganaderasoft_pass
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
ENVFILE
RUN php artisan key:generate

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Create entrypoint script
COPY <<'ENTRYPOINT' /usr/local/bin/entrypoint.sh
#!/bin/bash
set -e

# Override .env with runtime env vars if present
[ -n "$DB_HOST" ]     && sed -i "s/DB_HOST=.*/DB_HOST=$DB_HOST/" /var/www/html/.env
[ -n "$DB_DATABASE" ] && sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE/" /var/www/html/.env
[ -n "$DB_USERNAME" ] && sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/" /var/www/html/.env
[ -n "$DB_PASSWORD" ] && sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" /var/www/html/.env
[ -n "$APP_URL" ]     && sed -i "s|APP_URL=.*|APP_URL=$APP_URL|" /var/www/html/.env

# Wait for database to be ready using direct PDO connection test
echo "Waiting for database..."
DB_H="${DB_HOST:-db}"
DB_P="${DB_PORT:-3306}"
DB_D="${DB_DATABASE:-ganaderasoft}"
DB_U="${DB_USERNAME:-ganaderasoft_user}"
DB_PW="${DB_PASSWORD:-ganaderasoft_pass}"

until php -r "try { new PDO(\"mysql:host=$DB_H;port=$DB_P;dbname=$DB_D\", \"$DB_U\", \"$DB_PW\"); echo 'ok'; } catch(Exception \$e) { exit(1); }" 2>/dev/null; do
    echo "Database not ready, retrying in 3s..."
    sleep 3
done
echo "Database is ready!"

# Clear and cache config
php artisan config:clear
php artisan cache:clear

# Start Apache
apache2-foreground
ENTRYPOINT
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]

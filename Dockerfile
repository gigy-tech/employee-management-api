FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN php artisan config:clear

EXPOSE 10000

CMD php -r '$u=getenv("DB_URL"); echo "DB_URL_SET=".($u ? "YES" : "NO").PHP_EOL; echo "DB_URL_HOST=".($u ? (parse_url($u, PHP_URL_HOST) ?: "EMPTY_HOST") : "NOT_SET").PHP_EOL;' && php artisan config:clear && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=10000
FROM node:20-bookworm-slim AS assets

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        libxml2-dev \
        zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && ln -sfn ../storage/app/public public/storage \
    && chmod -R ug+rw storage bootstrap/cache

CMD ["sh", "-c", "export APP_URL=\"$(printf '%s' \"$APP_URL\" | tr -d '\\r\\n' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')\"; php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]

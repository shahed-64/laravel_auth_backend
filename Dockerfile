# ---------- Stage 1: Backend ----------
FROM php:8.2-cli AS backend

RUN apt-get update && apt-get install -y \
    git unzip zip curl libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

WORKDIR /var/www

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

# 👉 minimal env file (IMPORTANT)
RUN touch .env

# 👉 install dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts


# ---------- Stage 2: Frontend ----------
FROM node:22 AS frontend

WORKDIR /app

COPY --from=backend /var/www .

RUN npm install
RUN npm run build


# ---------- Stage 3: Production ----------
FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip zip curl libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

WORKDIR /var/www

COPY --from=backend /var/www .
COPY --from=frontend /app/public/build ./public/build

# 👉 permissions fix
RUN chmod -R 777 storage bootstrap/cache || true

# 👉 ensure .env exists
RUN touch .env

EXPOSE 10000

CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan config:cache && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT}

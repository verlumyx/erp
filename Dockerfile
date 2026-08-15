# =============================================================================
# Stage 1: Build PHP dependencies + frontend assets
# =============================================================================
FROM php:8.5-cli-alpine AS builder

RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    libzip-dev \
    nodejs \
    npm

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    xml \
    bcmath \
    zip \
    pcntl \
    intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies first (layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist

# Install pnpm (version pinned in package.json "packageManager")
RUN npm install -g pnpm@11.5.0

# Install Node dependencies (layer caching)
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile

# Copy application source
COPY . .

# Create a temporary .env with SQLite so artisan/wayfinder can boot during the asset build
RUN php -r "echo 'APP_KEY=base64:' . base64_encode(random_bytes(32)) . PHP_EOL;" > .env \
    && printf "APP_ENV=local\nDB_CONNECTION=sqlite\nDB_DATABASE=/tmp/build.db\n" >> .env \
    && touch /tmp/build.db \
    && php artisan package:discover --ansi \
    && pnpm run build

# =============================================================================
# Stage 2: Development runtime (PHP-FPM + Node + Composer, full dev deps)
# Used by docker-compose for local development. Has every tool the workflow
# needs (artisan, pest, vite/pnpm with PHP available for Wayfinder) so commands
# run *inside* this single container via `docker compose exec app ...`.
# =============================================================================
FROM php:8.5-fpm-alpine AS development

RUN apk add --no-cache \
    git \
    curl \
    unzip \
    libpng-dev \
    libxml2-dev \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    libzip-dev \
    netcat-openbsd \
    nodejs \
    npm

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    xml \
    bcmath \
    zip \
    pcntl \
    intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN npm install -g pnpm@11.5.0

WORKDIR /var/www/html

# Seed vendor/ (WITH dev dependencies) and node_modules into the image so the
# anonymous volumes in docker-compose start populated. The source itself is
# bind-mounted at runtime.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]

# =============================================================================
# Stage 3: PHP-FPM production runtime
# =============================================================================
FROM php:8.5-fpm-alpine AS production

RUN apk add --no-cache \
    libpng-dev \
    libxml2-dev \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    libzip-dev \
    netcat-openbsd

# Note: Zend OPcache is already compiled into the php:8.5 base image,
# so it must not be passed to docker-php-ext-install. It is configured
# via docker/php/opcache.ini below.
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    xml \
    bcmath \
    zip \
    pcntl \
    intl

WORKDIR /var/www/html

# Copy built application
COPY --from=builder /var/www/html .

# Remove build-time artifacts
RUN rm -rf .env node_modules

# Backup public dir for shared volume initialization
RUN cp -rp public /var/www/public-init

# PHP configuration
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]

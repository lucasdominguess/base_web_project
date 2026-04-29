# --- Stage 1: Vendor (Composer) ---
FROM composer:2.7 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
# Ignoramos platform limits para o Alpine enxuto isolar a montagem
RUN composer install --no-dev --no-scripts --prefer-dist --ignore-platform-reqs

COPY . .
RUN mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache storage/logs && \
    composer dump-autoload --optimize --no-scripts

# --- Stage 2: Frontend Assets (Node/Vite) ---
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build

# --- Stage 3: Runtime (Final / Render) ---
FROM php:8.3-fpm-alpine

ENV APP_ENV=production
ENV APP_DEBUG=false

# Instala pacotes críticos do sistema 
# Agrupamos extensões de compilação em .build-deps junto do $PHPIZE_DEPS
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    ca-certificates \
    postgresql-libs \
    libpng \
    libzip \
    icu-libs \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    linux-headers \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    libpng-dev \
    && docker-php-ext-install pdo_pgsql intl zip gd bcmath opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

# Arquitetura Sênior de Configuração INI (Otimização Prod)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    sed -i "s/memory_limit = .*/memory_limit = 512M/" "$PHP_INI_DIR/php.ini" && \
    sed -i "s/upload_max_filesize = .*/upload_max_filesize = 100M/" "$PHP_INI_DIR/php.ini" && \
    sed -i "s/post_max_size = .*/post_max_size = 100M/" "$PHP_INI_DIR/php.ini" && \
    echo 'opcache.enable = 1' >> "$PHP_INI_DIR/php.ini" && \
    echo 'opcache.memory_consumption = 256' >> "$PHP_INI_DIR/php.ini"

WORKDIR /var/www

# Ingestão de Código Fonte e Build Stages
COPY . .
COPY --from=vendor /app/vendor/ ./vendor/
COPY --from=frontend /app/public/build/ ./public/build/

# Setup de Arquivos da Infra (Render / Nginx / PHP-FPM / Worker Múltiplo)
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Permissões do container rodando como "rootless abstrato" via Nginx Worker Drops
RUN mkdir -p /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/logs \
    /var/www/bootstrap/cache \
    /var/www/public/build

RUN chmod +x /usr/local/bin/entrypoint.sh && \
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/public/build

EXPOSE 80

# Este container usa supervisor para manter multi-processo (nginx + fpm) vivo
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

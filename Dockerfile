FROM php:8.2-fpm-alpine

# Instalar dependências do sistema
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    icu-dev \
    freetype-dev

# Instalar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        bcmath \
        exif \
        pcntl \
        mbstring \
        intl \
        opcache

# Instalar Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Criar usuário não-root
RUN addgroup -g 1000 -S appgroup \
    && adduser -u 1000 -S appuser -G appgroup

# Diretório da aplicação
WORKDIR /var/www/html

# Copiar código da aplicação
COPY --chown=appuser:appgroup . .

# Instalar dependências PHP (sem dev em produção)
RUN composer install --optimize-autoloader --no-interaction --no-progress

# Ajustar permissões
RUN chown -R appuser:appgroup storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Configuração do PHP para produção
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

USER appuser

EXPOSE 9000

CMD ["php-fpm"]

# Build frontend assets
FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./

RUN mkdir -p public && npm run build


# Laravel application
FROM php:8.3-apache-bookworm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev libzip-dev libpng-dev libonig-dev \
    libcurl4-openssl-dev unzip git ca-certificates \
    && docker-php-ext-install -j"$(nproc)" \
       pdo_mysql mbstring bcmath intl zip gd curl \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Install Python 3.10
COPY --from=ghcr.io/astral-sh/uv:0.12.15 /uv /uvx /bin/

ENV UV_PYTHON_INSTALL_DIR=/opt/python

RUN uv python install 3.10 \
    && uv venv /opt/venv --python 3.10

ENV VIRTUAL_ENV=/opt/venv
ENV PATH="/opt/venv/bin:${PATH}"

# Install Python packages
COPY scripts/requirements.txt /tmp/requirements.txt

RUN uv pip install "numpy<2" -r /tmp/requirements.txt

RUN python -c "import pandas, sqlalchemy, requests; from selectolax.lexbor import LexborHTMLParser; print('Python ready')"

# Install Laravel
COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction

# Copy compiled frontend assets
COPY --from=frontend /app/public/build /var/www/html/public/build

# Laravel writable directories
RUN mkdir -p storage/app storage/framework/cache \
    storage/framework/views storage/framework/sessions \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

# Configure Apache for Railway
RUN a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && printf '%s\n' \
       '<VirtualHost *:8080>' \
       'DocumentRoot /var/www/html/public' \
       '<Directory /var/www/html/public>' \
       'AllowOverride All' \
       'Require all granted' \
       '</Directory>' \
       '</VirtualHost>' \
       > /etc/apache2/sites-available/000-default.conf

ENV PORT=8080

EXPOSE 8080

CMD ["apache2-foreground"]
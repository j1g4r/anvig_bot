FROM php:8.3-fpm

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    libzip-dev \
    libicu-dev \
    pkg-config \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    python3 \
    python3-pip \
    python3-venv \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js & NPM
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Set working directory
WORKDIR /var/www

# Copy Nginx config
COPY docker/nginx/default.conf /etc/nginx/sites-enabled/default
COPY docker/nginx/default.conf /etc/nginx/sites-available/default

# Copy Supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy Code
COPY . /var/www

# Create Python Venv and Install Dependencies
# Ensure any copied .venv is nuked first
RUN rm -rf /var/www/.venv && \
    python3 -m venv .venv && \
    . .venv/bin/activate && \
    pip install scikit-learn numpy

# Install PHP Dependencies
RUN composer install --no-dev --optimize-autoloader

# Install JS Dependencies & Build
RUN npm install --legacy-peer-deps && npm run build

# Permissions
RUN chown -R www-data:www-data /var/www

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

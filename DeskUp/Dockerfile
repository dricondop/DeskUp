FROM php:8.2-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    zip \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Configure git to use HTTPS instead of SSH
RUN git config --global url."https://github.com/".insteadOf git@github.com: && \
    git config --global url."https://".insteadOf git://

# Install php and npm
RUN composer install --no-dev --optimize-autoloader --prefer-dist
# Vite y Tailwind 4 require Node.js (optional, can be run from host)
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs || true
RUN npm install || true

CMD ["php-fpm"]
FROM php:8.4-cli

# Use pre-built extension installer (much faster than compiling from source)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql intl sodium

RUN apt-get update && apt-get install -y git unzip curl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /app
COPY . .
RUN composer install --no-interaction --optimize-autoloader --ignore-platform-reqs

EXPOSE 8000

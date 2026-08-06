FROM php:8.2-cli

# Install build deps required to compile pdo_mysql
USER root
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-libmysqlclient-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy application code
COPY . /app

# Expose default port (Railway uses $PORT)
EXPOSE 8080

# Start using PHP built-in server respecting PORT env
CMD ["sh", "-lc", "php -S 0.0.0.0:${PORT:-8080} -t ."]

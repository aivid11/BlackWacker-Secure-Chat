FROM dunglas/frankenphp:php8.2.33-bookworm

# Install pdo_mysql extension (Debian package provides the extension)
USER root
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        php8.2-mysql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy app files
COPY . /app

# Expose default port (Railway provides PORT env)
EXPOSE 8080

# Use PHP built-in server and respect PORT env variable
CMD ["sh", "-lc", "php -S 0.0.0.0:${PORT:-8080} -t ."]

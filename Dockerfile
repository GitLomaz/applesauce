# Use official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache for Cloud Run
# Cloud Run expects the app to listen on PORT environment variable
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# Create a custom Apache startup script
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Update Apache config with PORT from environment\n\
sed -i "s/Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf\n\
sed -i "s/<VirtualHost \\*:.*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf\n\
\n\
# Start Apache\n\
exec apache2-foreground' > /usr/local/bin/start-apache.sh && \
    chmod +x /usr/local/bin/start-apache.sh

# Expose port (Cloud Run will set this via PORT env var)
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:${PORT:-8080}/health.php || exit 1

# Start Apache
CMD ["/usr/local/bin/start-apache.sh"]

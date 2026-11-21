# Dockerfile — ACCESS Authentication
# PHP 8.3 with Apache and PostgreSQL PDO extension

FROM php:8.3-apache

# Install system dependencies and PostgreSQL client libraries
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Render routes traffic to the exposed container port.
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:10000>/' /etc/apache2/sites-available/000-default.conf

# Set the document root to the project directory
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Copy application files into the container
COPY . /var/www/html/

# Remove .env file from container (should not be committed anyway)
RUN rm -f /var/www/html/.env

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Run the database schema + seeder at container startup
# (Using a custom entrypoint script)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

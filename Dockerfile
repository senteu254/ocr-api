# Use official PHP with Apache
FROM php:8.2-apache

# Enable file uploads and required extensions
RUN docker-php-ext-install mysqli

# Copy your app into the container
COPY ./public/ /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

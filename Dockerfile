FROM php:8.1-apache

# Copiar el código del proyecto a la carpeta de Apache
COPY . /var/www/html/

# Habilitar mod_rewrite de Apache si usas URLs amigables
RUN a2enmod rewrite

# Instalar extensiones necesarias (por ejemplo pdo_mysql o pdo_pgsql)
RUN docker-php-ext-install pdo pdo_mysql
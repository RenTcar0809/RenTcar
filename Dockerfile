FROM php:8.1-apache

# Copiar el código del proyecto a la carpeta de Apache
COPY . /var/www/html/

# Habilitar mod_rewrite de Apache si usas URLs amigables
RUN a2enmod rewrite

# Instalar extensiones necesarias (por ejemplo pdo_mysql o pdo_pgsql)
RUN docker-php-ext-install pdo pdo_mysql


FROM php:8.2-apache

# Instalar dependencias necesarias para PostgreSQL en PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Copiar los archivos de tu proyecto al servidor web de Apache
COPY . /var/www/html/

# Habilitar mod_rewrite si tu proyecto lo necesita
RUN a2enmod rewrite
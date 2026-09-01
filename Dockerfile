FROM php:8.2-apache

# 1. Instalar dependencias del sistema para PostgreSQL y MySQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    && rm -rf /var/lib/apt/lists/*

# 2. Instalar extensiones de PHP para PostgreSQL y MySQL (PDO)
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql

# 3. Copiar el código del proyecto al servidor web de Apache
COPY . /var/www/html/

# 4. Habilitar mod_rewrite de Apache para URLs amigables
RUN a2enmod rewrite
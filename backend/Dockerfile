# Use a imagem oficial do PHP como base
FROM php:8.3-fpm

# Defina o diretório de trabalho
WORKDIR /var/www

# Instale as dependências necessárias
RUN apt-get update && apt-get install -y \
    zip \
    unzip

# Instale o Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instale as extensões do PHP necessárias
RUN docker-php-ext-install pdo pdo_mysql
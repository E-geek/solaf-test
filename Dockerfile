# syntax=docker/dockerfile:1
FROM php:7.4.33 as base
COPY --from=composer:2.5.4 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app/
COPY ./composer.json ./

RUN apt-get clean
RUN apt-get update
RUN apt-get install -y \
        git \
        tree \
        vim \
        wget \
        zip \
        postgresql-server-dev-13
RUN docker-php-ext-install pdo pdo_pgsql
RUN composer install
CMD ["/usr/local/bin/php", "bin/doctrine", "orm:schema-tool:update", "--force"]

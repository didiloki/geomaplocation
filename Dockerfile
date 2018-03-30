FROM php:7.1-apache

RUN docker-php-ext-install pdo pdo_mysql

COPY ./dump /docker-entrypoint-initdb.d

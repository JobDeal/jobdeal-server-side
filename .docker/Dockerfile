FROM php:7.3-apache

ARG USERNAME
ARG USER_ID

# Create user
RUN useradd -u ${USER_ID} ${USERNAME} --create-home --groups www-data,root

#Install GIT, GnuPG, Python2, NodeJS and NPM
RUN apt-get update && apt-get install -y git gnupg && \
    curl -sL https://deb.nodesource.com/setup_10.x | bash - && \
    apt-get install -y python2 nodejs tldr

#Add Laravel necessary php extensions
RUN apt-get install -y \
    unzip \
    vim \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    && docker-php-ext-install -j$(nproc) zip mysqli pdo_mysql \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd
# Create working directory
RUN mkdir -p /var/www/${USERNAME}
RUN chown -R ${USERNAME}:${USERNAME} /var/www/${USERNAME}
#RUN chmod -R 0755 /var/www/${USERNAME}
RUN chmod 0755 /var/www/${USERNAME}

ENV APACHE_DOCUMENT_ROOT /var/www/${USERNAME}/public
ENV APP_NAME ${USERNAME}
# Install composer from image. You may change it to the latest
COPY --from=composer:2.4.3 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/${USERNAME}
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
#RUN sed -ri -e 's!/;error_log = syslog!error_log = /var/log/apache2/error.log!g' /usr/local/etc/php/php.ini-developement
#RUN sed -ri -e 's!/;error_log = syslog!error_log = /var/log/apache2/error.log!g' /usr/local/etc/php/php.ini-production
# Make laravel feel comfortable with mod-rewrite
RUN a2enmod rewrite && service apache2 restart
EXPOSE 80

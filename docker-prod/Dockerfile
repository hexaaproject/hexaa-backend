FROM php:7.2.11-fpm-stretch
LABEL MAINTAINER Balázs SOLTÉSZ <solazs@sztaki.hu>

# Install some dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
                       wget \
                       unzip \
                       libicu-dev \
                       libmemcached-dev \
                       zlib1g-dev \
                       mysql-client \
                       sudo \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql intl bcmath \
    && pecl install memcached-3.0.4 \
    && docker-php-ext-enable memcached

# Copy custom php.ini
# COPY docker-prod/php.ini /usr/local/etc/php/

# Copy source to container
COPY docker-prod/config_prod.yml /root/
COPY docker-prod/config_dev.yml /root/
COPY . /opt/hexaa-backend

# Install composer
COPY docker-prod/composer_downloader.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/composer_downloader.sh \
    && cd /usr/local/bin/ \
    && /usr/local/bin/composer_downloader.sh \
    && mv /usr/local/bin/composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer

# Install dependencies and setup permissions etc.
RUN cd /opt/hexaa-backend \
    && chown www-data /opt/hexaa-backend -R \
    && mkdir /var/lib/php5 \
    && chown www-data /var/lib/php5 \
    && sudo -u www-data composer install -o \
    && chown root /opt/hexaa-backend -R \
    && chown www-data -R \
        /opt/hexaa-backend/app/cache \
        /opt/hexaa-backend/app/logs \
    && rm -rf app/cache/*

# Mount configuration files from host
VOLUME /opt/hexaa-backend/app/config

COPY docker-prod/boot.sh /usr/local/bin/

CMD ["/usr/local/bin/boot.sh"]

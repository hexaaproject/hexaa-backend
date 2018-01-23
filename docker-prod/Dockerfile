FROM php:7.2.1-fpm-stretch
LABEL MAINTAINER Balázs SOLTÉSZ <solazs@sztaki.hu>

# Install some dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
                       wget \
                       libicu-dev \
                       libmemcached-dev \
                       zlib1g-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql intl bcmath \
    && pecl install memcached-3.0.4 \
    && docker-php-ext-enable memcached

# Copy source to container
COPY . /opt/hexaa-backend

# Install composer
COPY docker-prod/composer_downloader.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/composer_downloader.sh \
    && cd /usr/local/bin/ \
    && /usr/local/bin/composer_downloader.sh \
    && mv /usr/local/bin/composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer
    
# Install dependencies
RUN cd /opt/hexaa-backend \
    && composer install -o \
    && rm -rf app/cache/* \
    && cd /var/www && rm -r html && ln -s html /opt/hexaa-backend/web 

VOLUME /opt/hexaa-backend

COPY docker-prod/boot.sh /usr/local/bin/

CMD ["/usr/local/bin/boot.sh"]

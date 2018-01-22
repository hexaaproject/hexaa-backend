FROM bitnami/php-fpm
LABEL MAINTAINER Balázs SOLTÉSZ <solazs@sztaki.hu>

COPY . /app

# Install composer
COPY docker-prod/composer_downloader.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/composer_downloader.sh \
    && cd /usr/local/bin/ \
    && /usr/local/bin/composer_downloader.sh \
    && mv /usr/local/bin/composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer
    
# Install dependencies
RUN cd /app \
    && composer install -o \
    && rm -rf /app/app/cache/*

VOLUME /app

COPY docker-prod/boot-and-start-php-fpm.sh /usr/local/bin/

CMD ["/usr/local/bin/boot-and-start-php-fpm.sh"]

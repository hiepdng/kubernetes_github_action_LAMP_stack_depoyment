### 1. Build Stage: Use the DHI Debian dev image to install tools
FROM dhi.io/php:8.5.8-debian13-dev AS builder

# create directory for mysql certificate
RUN mkdir -p /opt/php/mysql_certs

# Install redis
RUN git clone https://github.com/phpredis/phpredis.git \
    && cd phpredis \
    && phpize \
    && ./configure \
    && make \
    && make install
RUN echo "extension=redis.so" > /opt/php/etc/php/conf.d/redis.ini

# Install extensions
RUN cd /opt/php/src/ext/mysqli && phpize && ./configure && make install
RUN cd /opt/php/src/ext/pdo_mysql && phpize && ./configure && make install
RUN cd /opt/php/src/ext/sockets && phpize && ./configure && make install



### 2. Final Stage: Copy the compiled site into a minimal production image
FROM dhi.io/php:8.5.8-debian13-fpm

COPY --from=builder /opt/php/mysql_certs /opt/php/mysql_certs

## Copy all extensions above, including redis
COPY --from=builder /opt/php/lib/php/extensions /opt/php/lib/php/extensions/

## Config redis
COPY --from=builder /opt/php/etc/php/conf.d/redis.ini /opt/php/etc/php/conf.d/redis.ini


EXPOSE 9000

ENTRYPOINT ["/opt/php-8.5/sbin/php-fpm"]


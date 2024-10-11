FROM oven/bun:1-alpine AS assets

RUN --mount=type=cache,target=/var/cache/apk apk update && \
    apk upgrade && \
    mkdir -p /opt/source && \
    chown -R bun:bun /opt/source

USER bun
WORKDIR /opt/source

COPY --chown=bun:bun package.json bun.lockb ./
RUN bun install --frozen-lockfile

COPY --chown=bun:bun vite.config.js ./
COPY --chown=bun:bun resources/ resources/
COPY --chown=bun:bun .env.placeholder .env

RUN bun run build && \
    rm -rf resources/ node_modules/ .env vite.config.js


FROM php:8.3-zts-alpine

LABEL org.opencontainers.image.title="Laravel Template"
LABEL org.opencontainers.image.description="A highly-opinionated Laravel template."
LABEL org.opencontainers.image.authors="Saif Mahmud <xoxo@saifmahmud.name>"
LABEL org.opencontainers.image.url="https://saifmahmud.name/"
LABEL org.opencontainers.image.source="https://github.com/sikhlana/laravel-template"
LABEL org.opencontainers.image.licenses="MIT"

ENV PHP_INI_DIR=/usr/local/etc/php \
    XDG_CACHE_HOME=/var/cache

RUN --mount=type=cache,target=/var/cache/apk apk update && \
    apk upgrade && \
    apk add tini curl moreutils libstdc++ less envsubst && \
    mkdir -p /opt/source && \
    chown -R www-data:www-data /opt/source && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    sed -i 's/^variables_order = .*$/variables_order = "EGPCS"/' "$PHP_INI_DIR/php.ini" && \
    sed -i 's/^memory_limit = .*$/memory_limit = 256M/' "$PHP_INI_DIR/php.ini" && \
    sed -i 's/^post_max_size = .*$/post_max_size = 128M/' "$PHP_INI_DIR/php.ini" && \
    sed -i 's/^upload_max_filesize = .*$/upload_max_filesize = 128M/' "$PHP_INI_DIR/php.ini" && \
    sed -i 's/^;opcache.enable=1$/opcache.enable=1/' "$PHP_INI_DIR/php.ini" && \
    sed -i 's/^;opcache.enable_cli=0$/opcache.enable_cli=1/' "$PHP_INI_DIR/php.ini"

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN --mount=type=cache,target=/var/cache/apk --mount=type=cache,target=/tmp/pear \
    IPE_GD_WITHOUTAVIF=1 IPE_KEEP_SYSPKG_CACHE=1 \
    install-php-extensions bcmath bz2 excimer gd igbinary \
    intl lzf msgpack parallel pcntl opcache pdo_mysql \
    sockets zstd redis sodium swoole uv xsl zip

COPY --from=composer /usr/bin/composer /usr/bin/

USER www-data
WORKDIR /opt/source

COPY --chown=www-data:www-data composer.* ./
RUN --mount=type=cache,uid=82,gid=82,target=/var/cache/composer \
    composer install --ansi --no-interaction --no-progress --no-scripts

COPY --chown=www-data:www-data . .
COPY --from=assets --chown=www-data:www-data /opt/source/public/ public/
COPY --chmod=0755 docker-entrypoint.sh /entrypoint.sh

RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/testing storage/framework/views \
    storage/logs && \
    rm docker-entrypoint.sh

STOPSIGNAL SIGTERM

ENTRYPOINT ["/sbin/tini", "--", "/entrypoint.sh"]
CMD ["tinker"]

ARG PHP_VERSION=latest-php8.3

# ── base image for dev and prod ──────────────────────────────
FROM dunglas/frankenphp:${PHP_VERSION} AS baseimage

WORKDIR /web

RUN apt-get update \
    && apt-get install -y --no-install-recommends fwknop-client \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV PHPKNOCK_FWKNOP_CLI=/usr/bin/fwknop \
    PHPKNOCK_PATH_FS_TMP=/web/tmp \
    PHPKNOCK_PATH_FS_LOG=/web/log \
    PHPKNOCK_PATH_APPLICATION=/

COPY Caddyfile /etc/caddy/Caddyfile
RUN mkdir -p /web/tmp /web/log

EXPOSE 80
EXPOSE 443

# ── development ──────────────────────────────────────────────
FROM baseimage AS development
# No app code copied — expects volume mount for live editing

# ── builder (intermediate — installs composer deps) ──────────
FROM baseimage AS builder

WORKDIR /build
COPY --from=app app/composer.json app/composer.lock /build/app/
RUN composer install -d /build/app --no-dev --no-interaction \
    --prefer-dist --optimize-autoloader
COPY --from=app ./ /build/
RUN rm -rf /build/docker /build/app/tests /build/app/phpunit.xml \
    /build/app/phpstan.neon /build/log

# ── production ───────────────────────────────────────────────
FROM baseimage AS production

COPY --from=builder /build/app /web/app

HEALTHCHECK --interval=10s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:2019/reverse_proxy/upstreams || exit 1

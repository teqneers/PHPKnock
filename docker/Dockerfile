FROM dunglas/frankenphp:latest-php8.3

RUN apt-get update \
    && apt-get install -y --no-install-recommends fwknop-client \
    && rm -rf /var/lib/apt/lists/*

ENV PHPKNOCK_FWKNOP_CLI=/usr/bin/fwknop \
    PHPKNOCK_PATH_FS_TMP=/web/tmp \
    PHPKNOCK_PATH_APPLICATION=/

COPY Caddyfile /etc/caddy/Caddyfile

WORKDIR /app

EXPOSE 80
EXPOSE 443

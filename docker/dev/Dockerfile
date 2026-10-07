# Dev image: PHP + Node + Composer in one container, so `php artisan` is available
# to the frontend build (Wayfinder runs it) and nothing is installed on the host.
FROM php:8.5-cli-bookworm

COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && apt-get update \
    && apt-get install -y --no-install-recommends git unzip postgresql-client \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pdo_pgsql pdo_sqlite redis intl zip bcmath pcntl gd opcache

ARG UID=1000
ARG GID=1000
RUN groupadd -g "$GID" app 2>/dev/null || true \
    && useradd -m -u "$UID" -g "$GID" -s /bin/bash app 2>/dev/null || true

# Pre-create cache dirs so the named volumes mounted there inherit app ownership.
RUN mkdir -p /home/app/.npm /home/app/.cache/composer && chown -R app:app /home/app

USER app
WORKDIR /var/www/html
EXPOSE 8000 5173

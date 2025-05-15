# Use an argument to specify the base WordPress image tag.
# This allows you to easily change the PHP version or variant (e.g., apache, fpm)
# by passing it as a build argument in docker-compose.yml.
ARG WORDPRESS_IMAGE_TAG=latest

# Start from the official WordPress image with the specified tag
FROM wordpress:${WORDPRESS_IMAGE_TAG}

# Switch to root user to install packages
USER root

# Update package lists and install system dependencies:
# - git: Required by Composer for cloning VCS repositories or determining versions.
# - unzip: Required by Composer for extracting .zip archives if PHP's built-in zip extension is insufficient or for specific formats.
# --no-install-recommends: Avoids installing unnecessary recommended packages.
# Clean up apt cache afterwards to keep image size smaller.
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    --no-install-recommends && \
    rm -rf /var/lib/apt/lists/*

# Copy Composer binary from the official Composer image (multi-stage build).
# This is a clean way to get Composer without running its installer script.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Optional: You can set the working directory if you want,
# but the base WordPress image usually sets it to /var/www/html.
# WORKDIR /var/www/html

# Revert to the default wordpress user if needed (the base image often runs as 'www-data' or similar for web server processes)
# The user for php-cli (and thus composer) might be root when you exec in,
# but web server processes will run as the less privileged user defined in the base image.
# This line might not be necessary depending on the base image and your needs.
# USER www-data

# The CMD is inherited from the base 'wordpress' image (e.g., "apache2-foreground")
# Dockerfile
# Defines the custom WordPress image for local development and can be used for production.

# Use an argument to specify the base WordPress image tag.
# This allows you to easily change the PHP version or variant (e.g., apache, fpm)
# by passing it as a build argument in docker-compose.yml or docker-compose.prod.yml.
ARG WORDPRESS_IMAGE_TAG=latest # Default: latest official WordPress image (e.g., php8.2-apache)

# Start from the official WordPress image with the specified tag
FROM wordpress:${WORDPRESS_IMAGE_TAG}

# Switch to root user to install packages and copy files
USER root

# Update package lists and install system dependencies:
# - git: Often required by Composer for cloning VCS repositories or determining versions.
# - unzip: May be required by Composer for extracting certain .zip archives.
# --no-install-recommends: Avoids installing unnecessary recommended packages.
# Clean up apt cache afterwards to keep image size smaller.
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    --no-install-recommends && \
    rm -rf /var/lib/apt/lists/*

# Copy Composer binary from the official Composer image (multi-stage build).
# Using a specific stable version tag for composer image (e.g., '2' for latest v2, or '2.8.8' if specific)
# is recommended for reproducibility over 'latest'.
# 'composer:2' will usually give the latest stable v2 release.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Ensure composer is executable
RUN chmod +x /usr/bin/composer

# The CMD and ENTRYPOINT are inherited from the base 'wordpress' image.
# We are not overriding them here to keep the local development simple,
# and server-side composer installs will be handled by a script executed via CI/CD.
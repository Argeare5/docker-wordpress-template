# WordPress Development Template with Docker, CI/CD, and Live Reload

This project provides a template for modern WordPress application development using Docker for the local environment, BrowserSync for Live Reload, and GitHub Actions for Continuous Integration and Deployment (CI/CD) to a DigitalOcean Droplet (or any other Linux server with Docker).

## Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Getting Started (Local Development)](#getting-started-local-development)
    - [1. Clone the Repository](#1-clone-the-repository)
    - [2. Setup Local `.env` File](#2-setup-local-env-file)
    - [3. Build and Run Docker Containers](#3-build-and-run-docker-containers)
    - [4. Accessing Local Services](#4-accessing-local-services)
    - [5. Working with Composer](#5-working-with-composer)
    - [6. Running Linters (PHP_CodeSniffer)](#6-running-linters-php_codesniffer)
    - [7. Live Reload with BrowserSync](#7-live-reload-with-browsersync)
- [Project Structure](#project-structure)
- [Environment Variables (`.env`)](#environment-variables-env)
    - [Local `.env`](#local-env)
    - [Server `.env` (via GitHub Secrets)](#server-env-via-github-secrets)
- [Server Setup & Deployment (CI/CD)](#server-setup--deployment-cicd)
    - [Server Requirements](#server-requirements)
    - [Required GitHub Secrets for CI/CD](#required-github-secrets-for-cicd)
    - [CI/CD Workflow](#cicd-workflow)
- [Developing Custom Themes and Plugins](#developing-custom-themes-and-plugins)
- [Troubleshooting](#troubleshooting)
- [Further Customization / Advanced Topics](#further-customization--advanced-topics)
- [Security Reminders](#security-reminders)

## Overview

This template provides:
- An isolated local development environment powered by Docker (WordPress, MySQL, phpMyAdmin).
- A custom WordPress Docker image with Composer pre-installed.
- Live Reload and CSS hot-swapping using BrowserSync.
- Integration with PHP_CodeSniffer for WordPress Coding Standards.
- A ready-to-use CI/CD pipeline with GitHub Actions for automatic deployment to a server upon push to the `main` branch.
- PHP dependency management via Composer (scoped to `wp-content`).
- Example configuration for increasing PHP upload limits for the production environment.

## Prerequisites

Before you begin, ensure you have the following tools installed on your host machine:
- **Docker Desktop** (for macOS or Windows) or Docker Engine + Docker Compose (for Linux). [Docker Official Website](https://www.docker.com/products/docker-desktop/)
- **Node.js and npm**: Required for installing and running BrowserSync. [Node.js Official Website](https://nodejs.org/)
- **Git**: For cloning the repository and version control. [Git Official Website](https://git-scm.com/)
- **An IDE or Text Editor**: PhpStorm is recommended for its PHP integration and tools, but any editor will work.

## Getting Started (Local Development)

### 1. Clone the Repository
Clone this repository to your local machine:
```bash
git clone <YOUR_REPOSITORY_URL>
cd <repository-folder-name>
```

### 2. Setup Local `.env` File
In the project root, you'll find an `.env.example` file. Copy it to a new file named `.env`:
```bash
cp .env.example .env
```

Open the .env file and customize the variable values (passwords, ports, etc.) to suit your local setup. See the "Environment Variables" section below for details on each variable.

### 3. Build and Run Docker Containers
This command will build the custom WordPress Docker image (defined in `Dockerfile`) if it doesn't exist or if the `Dockerfile` has changed, and then it will start all services (WordPress, MySQL, phpMyAdmin) in detached mode:
```bash
docker compose up -d --build
```
For subsequent starts, if the `Dockerfile` or `docker-compose.yml` configuration hasn't changed, you can typically just use `docker compose up -d` to start the containers.

### 4. Accessing Local Services
- **WordPress Site:** `http://localhost:${WORDPRESS_HOST_PORT}` (the default port is `8000` if `WORDPRESS_HOST_PORT` is not specified in your local `.env`). On the first run, you'll need to go through the standard WordPress installation process.
- **phpMyAdmin:** `http://localhost:${PHPMYADMIN_HOST_PORT}` (the default port is `8081`). Use the MySQL credentials (`MYSQL_USER`, `MYSQL_PASSWORD` or `MYSQL_ROOT_PASSWORD`) from your local `.env` file to log in.
- **MySQL Database:** The database is accessible to the WordPress container at host `db` on port `3306`. If you've exposed the MySQL port on your host machine (via `MYSQL_HOST_PORT` in `.env`), you can connect to it from your host using an SQL client.

### 5. Working with Composer
Composer is installed globally within the `wordpress` Docker image. All Composer commands related to `wp-content` dependencies (e.g., WordPress plugins, themes managed via Composer) should be run inside the `wordpress` container, within the `/var/www/html/wp-content/` directory.

1.  Exec into the `wordpress` container:
    ```bash
    docker compose exec wordpress bash
    ```
2.  Navigate to the `wp-content` directory:
    ```bash
    # Inside the container
    cd /var/www/html/wp-content
    ```
3.  Run Composer commands as needed:
    ```bash
    # Example: Install dependencies defined in wp-content/composer.json
    composer install --no-progress --no-suggest

    # Example: Require a new WordPress plugin from WPackagist
    composer require wpackagist-plugin/plugin-slug --no-progress --no-suggest

    # Example: Update a specific package
    composer update wpackagist-plugin/plugin-slug --with-all-dependencies
    ```

### 6. Running Linters (PHP_CodeSniffer & PHPStan)
This project is configured with tools to help maintain code quality and standards:

#### a) PHP_CodeSniffer (for Coding Standards)
PHP_CodeSniffer, along with WordPress Coding Standards (WPCS), is set up.
- **Configuration:** `wp-content/phpcs.xml.dist`
- **Composer Script:** `lint:cs` (defined in `wp-content/composer.json`)

To run the code standards check:
1.  Exec into the `wordpress` container and navigate to `/var/www/html/wp-content` (if not already there).
2.  Run the script:
    ```bash
    # Inside the container, in /var/www/html/wp-content
    composer lint:cs
    ```
To automatically fix some of the reported coding standard issues:
```bash
# Inside the container, in /var/www/html/wp-content
composer fix:cs
```

#### b) PHPStan (for Static Analysis)
PHPStan is set up using the `lipemat/phpstan-wordpress` extension to help find potential bugs in your code.
- **Configuration:** `wp-content/phpstan.neon.dist`
- **Composer Script:** `lint:stan` (defined in `wp-content/composer.json`)

To run static analysis:
1.  Ensure `lipemat/phpstan-wordpress` (and its dependencies like `phpstan/phpstan`) are installed via `composer update` or `composer require` as previously discussed.
2.  Make sure you have a `wp-content/phpstan.neon.dist` configuration file. A basic example was provided earlier, but you should tailor it using the `lipemat/phpstan-wordpress` documentation (often involving an `includes:` line pointing to `vendor/lipemat/phpstan-wordpress/extension.neon`).
3.  Exec into the `wordpress` container and navigate to `/var/www/html/wp-content` (if not already there).
4.  Run the script:
    ```bash
    # Inside the container, in /var/www/html/wp-content
    composer lint:stan
    ```

**IDE Integration:**
You can also integrate both PHP_CodeSniffer and PHPStan with your IDE (e.g., PhpStorm) for real-time linting and analysis by configuring them to use the PHP interpreter and tools from the Docker container.

### 7. Live Reload with BrowserSync
BrowserSync is used for automatic browser reloading and CSS injection during local development.

1.  Ensure Node.js and npm are installed on your **host machine**.
2.  Install the project's Node.js dependencies (which include `browser-sync` and `dotenv`):
    ```bash
    # In the project root on your host machine
    npm install
    ```
3.  Ensure your WordPress Docker containers are running (`docker compose up -d`).
4.  Start BrowserSync:
    ```bash
    # In the project root on your host machine
    npm run watch
    ```
5.  Open the URL provided by BrowserSync in your browser (usually `http://localhost:3000`). **Use this BrowserSync URL for development** to see live updates.
    BrowserSync monitors files within the `wp-content/` directory (as configured in `bs-config.js`) and will automatically:
    - Inject CSS changes "on the fly" without a full page reload.
    - Reload the page on PHP or JS file changes.
      The WordPress site URL that BrowserSync proxies is taken from the `WORDPRESS_HOST_PORT` in your local `.env` file.

## Project Structure

A brief overview of key files and directories in this template:

- **`.github/`**: Contains GitHub Actions workflow configurations.
    - **`workflows/deploy.yml`**: Defines the CI/CD pipeline for deploying to the server.
- **`config/`**: Contains configuration files for services.
    - **`php/uploads.ini`**: Custom PHP settings (e.g., for upload limits), used by the production WordPress container.
- **`scripts/`**: Contains utility scripts.
    - **`server_composer_commands.sh`**: Script executed on the server by the CI/CD pipeline to run Composer install commands inside the WordPress container for `wp-content` and any plugins/themes that have their own `composer.json`.
- **`wp-content/`**: The standard WordPress content directory. This is where you'll do most of your WordPress-specific development.
    - **`themes/`**: Place your custom themes here (e.g., `my-custom-theme`).
    - **`plugins/`**: Place your custom plugins here, or plugins managed by Composer will be installed here.
    - **`mu-plugins/`**: For Must-Use plugins.
    - **`composer.json` / `composer.lock`**: Manages PHP dependencies for `wp-content` (e.g., linters, WordPress plugins/themes from WPackagist).
    - **`phpcs.xml.dist`**: Configuration file for PHP_CodeSniffer (WordPress Coding Standards).
    - **`phpstan.neon.dist`**: Configuration file for PHPStan static analysis.
- **`.env`**: Local environment variables (created from `.env.example`, ignored by Git).
- **`.env.example`**: Template for local environment variables.
- **`docker-compose.yml`**: Docker Compose configuration for the **local development environment**.
- **`docker-compose.prod.yml`**: Docker Compose configuration for the **production/staging server** (this file is deployed by the CI/CD workflow).
- **`Dockerfile`**: Defines the custom WordPress Docker image used locally and on the server, which includes Composer.
- **`bs-config.js`**: Configuration file for BrowserSync (for Live Reload).
- **`package.json` / `package-lock.json`**: Manages Node.js dependencies for the project (e.g., BrowserSync, dotenv).
- **`.gitignore`**: Specifies intentionally untracked files that Git should ignore.
- **`README.md`**: This file – providing guidance on using the template.
- **`LICENSE`**: Contains the MIT License text for the project.

## Environment Variables (`.env`)

This project uses `.env` files to manage environment-specific configurations. An `.env.example` file is provided as a template. You should copy it to `.env` for your local setup and fill in the appropriate values. The `.env` file itself should be listed in your `.gitignore` file and **never committed to version control**, especially if it contains sensitive credentials.

### Local `.env`
This file (created by copying `.env.example` in the project root) configures your **local development environment**.

- **`PROJECT_NAME`**:
    - *Purpose*: A prefix for your local Docker container names (e.g., `my_wp_project`). This helps in identifying containers if you have multiple Docker projects.
    - *Example*: `my_local_wordpress`

- **`MYSQL_ROOT_PASSWORD`**:
    - *Purpose*: The root password for your local MySQL database service.
    - *Example*: `local_strong_mysql_root_password` (use a strong password even locally)

- **`MYSQL_DATABASE`**:
    - *Purpose*: The name of the database that will be created for your local WordPress installation.
    - *Example*: `wordpress_local_db`

- **`MYSQL_USER`**:
    - *Purpose*: The username that WordPress will use to connect to the local MySQL database.
    - *Example*: `wp_local_user`

- **`MYSQL_PASSWORD`**:
    - *Purpose*: The password for the `MYSQL_USER`.
    - *Example*: `local_strong_wp_user_password`

- **`MYSQL_HOST_PORT`**:
    - *Purpose*: (Optional) If you want to access the MySQL database directly from your host machine (e.g., with an SQL client), this variable maps a port on your host to the MySQL container's port 3306.
    - *Example*: `33060` (then connect to `localhost:33060` from host)
    - *Default if unset/empty*: `3306` (in `docker-compose.yml`)

- **`WORDPRESS_HOST_PORT`**:
    - *Purpose*: The port on your host machine through which you will access your local WordPress site in the browser. This is also the port that BrowserSync will proxy.
    - *Example*: `8000` (site accessible at `http://localhost:8000`)
    - *Default if unset/empty*: `8000` (in `docker-compose.yml`)

- **`WORDPRESS_TABLE_PREFIX`**:
    - *Purpose*: The table prefix for your WordPress database tables.
    - *Example*: `wp_local_`
    - *Default if unset/empty*: `wp_` (in `docker-compose.yml`)

- **`WORDPRESS_DEBUG`**:
    - *Purpose*: Set to `1` to enable WordPress debug mode locally, or `0` to disable. Helpful for development.
    - *Example*: `1`
    - *Default if unset/empty*: `1` (in `docker-compose.yml`)

- **`PHPMYADMIN_HOST_PORT`**:
    - *Purpose*: The port on your host machine to access phpMyAdmin for managing your local database.
    - *Example*: `8081` (phpMyAdmin accessible at `http://localhost:8081`)
    - *Default if unset/empty*: `8081` (in `docker-compose.yml`)

### Server `.env` (via GitHub Secrets)
This file is generated on the server (e.g., in `/home/wordpress-template/`) by the CI/CD workflow (`.github/workflows/deploy.yml`) using **GitHub Secrets**. You must configure these secrets in your GitHub repository settings. **This server `.env` file should never be manually created on the server or committed to the repository.**

- **GitHub Secret `PROD_PROJECT_NAME`** (writes `PROD_PROJECT_NAME` to server `.env`):
    - *Purpose*: Project name prefix for server Docker containers.
    - *Example Value in GitHub Secret*: `my_live_site`

- **GitHub Secret `PROD_MYSQL_ROOT_PASSWORD`** (writes `PROD_MYSQL_ROOT_PASSWORD` to server `.env`):
    - *Purpose*: **Strong, unique** root password for the MySQL database on the server.

- **GitHub Secret `PROD_MYSQL_DATABASE`** (writes `PROD_MYSQL_DATABASE` to server `.env`):
    - *Purpose*: Name of the WordPress database on the server.

- **GitHub Secret `PROD_MYSQL_USER`** (writes `PROD_MYSQL_USER` to server `.env`):
    - *Purpose*: Username for WordPress to access the database on the server.

- **GitHub Secret `PROD_MYSQL_PASSWORD`** (writes `PROD_MYSQL_PASSWORD` to server `.env`):
    - *Purpose*: **Strong, unique** password for the WordPress database user on the server.

- **GitHub Secret `PROD_WORDPRESS_HOST_PORT`** (writes `PROD_WORDPRESS_HOST_PORT` to server `.env`):
    - *Purpose*: The port the WordPress container will expose on the server.
    - *Example Value in GitHub Secret*: `80` or `8001`.

- **GitHub Secret `PROD_WORDPRESS_TABLE_PREFIX`** (writes `PROD_WORDPRESS_TABLE_PREFIX` to server `.env`):
    - *Purpose*: WordPress table prefix on the server.
    - *Example Value in GitHub Secret*: `wp_` (Defaults to `wp_` in `docker-compose.prod.yml` if this variable is empty or not provided in the server `.env`).

- **GitHub Secret `PROD_WORDPRESS_DEBUG`** (writes `PROD_WORDPRESS_DEBUG` to server `.env`):
    - *Purpose*: WordPress debug mode on the server. **Must be `0`** for production.
    - *Example Value in GitHub Secret*: `0`.

- **GitHub Secret `PROD_WP_ENV`** (writes `PROD_WP_ENV` to server `.env`):
    - *Purpose*: Sets the WordPress environment type.
    - *Example Value in GitHub Secret*: `production` or `staging`.

- **GitHub Secret `PROD_WORDPRESS_CONFIG_EXTRA`** (writes `PROD_WORDPRESS_CONFIG_EXTRA` to server `.env`):
    - *Purpose*: Sets the WordPress extra configs.
    - *Example Value in GitHub Secret*: `define('FS_METHOD', 'direct');`.

- **GitHub Secrets for WordPress Salts/Keys (e.g., `AUTH_KEY`, `SECURE_AUTH_KEY`, etc.)**:
    - *Purpose*: Unique WordPress security keys and salts. Critical for security.
    - *GitHub Secret Names*: `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`, `AUTH_SALT`, `SECURE_AUTH_SALT`, `LOGGED_IN_SALT`, `NONCE_SALT` (these are the names you create in GitHub Secrets UI, **without** a `PROD_` prefix).
    - *Action*: Generate these from [https://api.wordpress.org/secret-key/1.1/salt/](https://api.wordpress.org/secret-key/1.1/salt/) and store each one as a separate GitHub Secret under its respective name (e.g., the value for `define('AUTH_KEY', 'value');` goes into the GitHub Secret named `AUTH_KEY`). Copy only the value part (what's between the single quotes in the `define()` statement).
    - *How they are used*: The GitHub Actions workflow (`deploy.yml`) is configured to read these secrets (e.g., `${{ secrets.AUTH_KEY }}`) and then write them into the server's `.env` file with a `PROD_` prefix (e.g., as `PROD_AUTH_KEY='value_from_AUTH_KEY_secret'`). This ensures consistency in the server `.env` file while allowing you to name the GitHub Secrets for salts more simply. Your `docker-compose.prod.yml` then expects variables like `${PROD_AUTH_KEY}`.

- **GitHub Secret `PROD_PHPMYADMIN_UPLOAD_LIMIT`** (writes `PROD_PHPMYADMIN_UPLOAD_LIMIT` to server `.env`):
    - *Purpose*: Sets the `UPLOAD_LIMIT` for phpMyAdmin on the server.
    - *Example Value in GitHub Secret*: `1G`, `256M`.
    - *Default if unset/empty in `.env`*: `1G` (in `docker-compose.prod.yml`)

- **GitHub Secret `PROD_DOMAIN_NAME`** (writes `PROD_DOMAIN_NAME` to server `.env`):
    - *Purpose*: Sets the domain for your server.
    - *Example Value in GitHub Secret*: `yourdomain.com`.

- **GitHub Secret `PROD_LETSENCRYPT_EMAIL`** (writes `PROD_LETSENCRYPT_EMAIL` to server `.env`):
    - *Purpose*: Sets the email for your server LETSENCRYPT.
    - *Example Value in GitHub Secret*: `youremail@example.com`.

- **GitHub Secret `PROD_PHPMYADMIN_AUTH_USER`** (writes `PROD_PHPMYADMIN_AUTH_USER` to server `.env`):
    - *Purpose*: Sets the login for proxied phpMyAdmin.
    - *Example Value in GitHub Secret*: `admin`.

- **GitHub Secret `PROD_PHPMYADMIN_AUTH_PASS`** (writes `PROD_PHPMYADMIN_AUTH_PASS` to server `.env`):
    - *Purpose*: Sets the hashed password for proxied phpMyAdmin. (docker run --rm httpd:alpine htpasswd -nb admin 'your password')
    - *Example Value in GitHub Secret*: `hash of password`.

## Server Setup & Deployment (CI/CD)

This template includes a GitHub Actions workflow to automatically deploy your WordPress application to a production/staging server when you push changes to your main repository branch.

### Server Requirements

To use the CI/CD workflow for deployment, your server (e.g., a DigitalOcean Droplet) must meet the following basic requirements:
- **Linux Operating System:** Ubuntu LTS (e.g., 20.04, 22.04) is recommended and assumed for these instructions.
- **Docker Engine:** Must be installed on the server.
- **Docker Compose (plugin version):** The `docker compose` command (V2 syntax, without a hyphen) must be available.
- **SSH Access:** GitHub Actions will connect to your server via SSH. Key-based authentication is required for the deployment user.
- **Firewall (e.g., UFW):** Configured to allow incoming traffic on necessary ports:
    - SSH (typically port 22).
    - HTTP (port 80).
    - HTTPS (port 443).
- **Project Directory:** A directory on the server where the application files will be deployed (e.g., `/home/wordpress-template/` as configured in `SERVER_PROJECT_PATH` secret). The SSH user used by GitHub Actions must have write permissions to this directory.

*(Refer to earlier parts of this conversation or standard server setup guides for detailed instructions on installing Docker, Docker Compose, and configuring UFW if you are setting up a new server from scratch.)*

### Required GitHub Secrets for CI/CD
Navigate to your GitHub repository settings: `Settings` -> `Secrets and variables` -> `Actions` -> `New repository secret`. Create the following secrets. These are essential for the CI/CD workflow to connect to your server and configure the application.

- **`SSH_HOST`**:
    - *Description*: The IP address or hostname of your deployment server.
- **`SSH_USER`**:
    - *Description*: The username for SSH login on your server (e.g., `root`, or a dedicated non-root deploy user with appropriate permissions).
- **`SSH_PRIVATE_KEY`**:
    - *Description*: The private SSH key (generated specifically for CI/CD) that has authorized access to your server for the `SSH_USER`. The corresponding public key must be in the user's `~/.ssh/authorized_keys` file on the server.
- **`SERVER_PROJECT_PATH`**:
    - *Description*: The absolute path on your server where the project files will be deployed.
    - *Example Value*: `/home/wordpress-template/`

- **Project & Database Configuration Secrets (these will be written to the server's `.env` file, prefixed with `PROD_`):**
    - `PROD_PROJECT_NAME` (e.g., `my_live_site`)
    - `PROD_MYSQL_ROOT_PASSWORD` (use a strong, unique password)
    - `PROD_MYSQL_DATABASE`
    - `PROD_MYSQL_USER`
    - `PROD_MYSQL_PASSWORD` (use a strong, unique password for this user)
    - `PROD_WORDPRESS_HOST_PORT` (e.g., `80` or an internal port like `8001` if behind a reverse proxy)
    - `PROD_WORDPRESS_TABLE_PREFIX` (e.g., `wp_`; optional if default `wp_` is fine)
    - `PROD_WORDPRESS_DEBUG` (should be `0` for production)
    - `PROD_WP_ENV` (e.g., `production` or `staging`)
    - `WORDPRESS_CONFIG_EXTRA` (e.g., `define('FS_METHOD', 'direct');\nif (isset(\$_SERVER['HTTP_X_FORWARDED_PROTO']) && \$_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {\n  \$_SERVER['HTTPS'] = 'on';\n}`)

- **WordPress Salts/Keys Secrets (these GitHub Secrets are named *without* a `PROD_` prefix):**
    - *Description*: Unique WordPress security keys and salts. Generate these from [https://api.wordpress.org/secret-key/1.1/salt/](https://api.wordpress.org/secret-key/1.1/salt/). Copy **only the value part** (what's between the single quotes in the `define()` statement from the generator) into each corresponding GitHub Secret.
    - *GitHub Secret Names*:
        - `AUTH_KEY`
        - `SECURE_AUTH_KEY`
        - `LOGGED_IN_KEY`
        - `NONCE_KEY`
        - `AUTH_SALT`
        - `SECURE_AUTH_SALT`
        - `LOGGED_IN_SALT`
        - `NONCE_SALT`
    - *Usage*: The CI/CD workflow reads these secrets and writes them to the server's `.env` file with a `PROD_` prefix (e.g., the GitHub Secret `AUTH_KEY` becomes `PROD_AUTH_KEY='value'` in the server `.env` file).

- **phpMyAdmin Configuration Secrets (optional, if deploying phpMyAdmin; these will be written to the server's `.env` file prefixed with `PROD_`):**
    - `PROD_PHPMYADMIN_UPLOAD_LIMIT` (e.g., `1G` or `256M`)

- **LETSENCRYPT Configuration secrets:**
    - `PROD_DOMAIN_NAME` (e.g., `yourdomain.com`)
    - `PROD_LETSENCRYPT_EMAIL` (e.g., `youremail@example.com`)
    - `PROD_PHPMYADMIN_AUTH_USER` (e.g., `phpMyAdmin login`)
    - `PROD_PHPMYADMIN_AUTH_PASS` (e.g., `phpMyAdmin hashed password`)

### CI/CD Workflow
The CI/CD workflow is defined in the file `.github/workflows/deploy.yml` in your repository.
- **Trigger:** The workflow is typically configured to run automatically on a `push` to a specific branch (e.g., `main` or `master`). You can customize this in the `deploy.yml` file.
- **Key Workflow Steps:**
    1.  **Checkout Code:** The latest version of your code is checked out from the repository branch that triggered the workflow.
    2.  **Create Server `.env` File:** A `.env` file is dynamically generated by the workflow using the GitHub Secrets you configured. This file contains all the production-specific environment variables for your WordPress application and Docker services.
    3.  **Deploy Files to Server:** The workflow uses `rsync` (via a secure SSH connection) to synchronize your project files (including `wp-content/`, the server-specific `docker-compose.prod.yml`, the generated `.env` file, and the `config/php/uploads.ini` file) to the `SERVER_PROJECT_PATH` on your server. The `--delete` option in `rsync` ensures that files removed from the repository are also removed from the server in the target directory (excluding `.git` files).
    4.  **Execute Remote Commands on Server:** After files are deployed, the workflow connects to your server again via SSH and executes the following commands in your `SERVER_PROJECT_PATH`:
        - `docker compose -f docker-compose.prod.yml pull`: Pulls the latest versions of any base Docker images specified in your server's Docker Compose file (e.g., `wordpress:phpX.X-apache`, `mysql:X.X`).
        - `docker compose -f docker-compose.prod.yml up -d --force-recreate --remove-orphans`: Stops, removes, and recreates your Docker containers based on the `docker-compose.prod.yml` and the newly deployed files. `--force-recreate` ensures containers are rebuilt even if only configuration or mounted files changed. `--remove-orphans` cleans up containers for services that are no longer defined.
        - `docker compose -f docker-compose.prod.yml exec -T wordpress_prod composer install --no-interaction --no-progress --no-dev --optimize-autoloader --working-dir=/var/www/html/wp-content`: Runs `composer install` inside the running `wordpress_prod` container to install/update PHP dependencies (defined in `wp-content/composer.json`) for the production environment (excluding dev dependencies).
- **Monitoring:** You can monitor the progress and logs of each deployment in the "Actions" tab of your GitHub repository.

## Developing Custom Themes and Plugins

This template is designed to streamline the development of custom WordPress themes and plugins.

1.  **Themes:**
    - Create your custom theme directories within the `wp-content/themes/` folder in your local project.
    - For example: `wp-content/themes/my-custom-theme/`.
    - Standard WordPress theme development practices apply. Ensure your theme has at least an `index.php` and `style.css` with the correct theme headers.
    - If your theme uses Composer for PHP dependencies, manage them with a `composer.json` file inside your theme's directory, or use the main `wp-content/composer.json`.
    - If your theme requires a frontend build process (SCSS, modern JS), set up your preferred build tools (like Vite or Webpack, as discussed) *inside your theme's directory*. This typically involves a `package.json` and the build tool's configuration file (e.g., `vite.config.js` or `webpack.config.js`) within your theme folder.

2.  **Plugins:**
    - Create your custom plugin directories within the `wp-content/plugins/` folder in your local project.
    - For example: `wp-content/plugins/my-custom-plugin/`.
    - Ensure your main plugin PHP file has a valid WordPress plugin header comment, including at least `Plugin Name:`.
    - If your plugin uses Composer for PHP dependencies, manage them with a `composer.json` file inside your plugin's directory, or use the main `wp-content/composer.json`.
    - For frontend assets requiring a build process, set up build tools (Vite, Webpack, etc.) *inside your plugin's directory*, similar to themes.

3.  **Composer Dependencies for `wp-content`:**
    - You can manage shared PHP libraries or WordPress plugins/themes (from WPackagist or other repositories) using the `composer.json` file located in the `wp-content/` directory.
    - Run Composer commands (`install`, `require`, `update`) inside the `wordpress` Docker container within the `/var/www/html/wp-content/` directory, as described in the "Getting Started" section.
    - Remember to commit your `wp-content/composer.json` and `wp-content/composer.lock` files. The `wp-content/vendor/` directory and any plugins/themes installed by this Composer instance should typically be added to your main `.gitignore` if they are re-installed by the CI/CD pipeline on the server.

4.  **Frontend Assets Build Process (if using Vite/Webpack per theme/plugin):**
    - Navigate to your specific theme or plugin directory in your terminal on the host machine (e.g., `cd wp-content/themes/my-custom-theme`).
    - Run the development script (e.g., `npm run dev` or `npm run start`) to start the Vite/Webpack development server for that specific theme/plugin. This server will handle HMR for its assets.
    - Ensure your theme/plugin's PHP code correctly enqueues assets from the Vite/Webpack dev server URL during development, and from a `dist/` folder (using a manifest file) in production builds.
    - BrowserSync (run via `npm run watch` from the project root) can still be used alongside for general PHP file watching and full-page reloads, complementing the HMR from Vite/Webpack for specific assets.

5.  **Linting:**
    - Use `composer lint:cs` (for PHP_CodeSniffer) and `composer lint:stan` (if PHPStan is configured) inside the `wordpress` container (in `/var/www/html/wp-content/`) to check your code.
    - Configure your IDE (e.g., PhpStorm) to use the Dockerized PHP interpreter and linters for real-time feedback.

6.  **Version Control (Git):**
    - Commit all your custom code for themes and plugins.
    - Commit configuration files (`docker-compose.yml`, `docker-compose.prod.yml`, `Dockerfile`, `bs-config.js`, project `package.json`, `wp-content/composer.json`, `wp-content/composer.lock`, `wp-content/phpcs.xml.dist`, etc.).
    - Ensure your main `.gitignore` file correctly ignores:
        - Local `.env` file.
        - `node_modules/` directory in the project root.
        - `wp-content/vendor/` directory (if PHP dependencies are installed by CI/CD on the server).
        - Any plugins/themes in `wp-content/plugins/` or `wp-content/themes/` that are installed by `wp-content/composer.json` on the server (to avoid conflicts with `rsync --delete`). **However, make sure your custom-developed themes and plugins are NOT ignored.**
    - Example relevant lines for your root `.gitignore`:
      ```gitignore
      # Environment files
      .env
      *.env.local

      # Node dependencies
      /node_modules/

      # WordPress content specific vendor directory
      /wp-content/vendor/

      # Example: Ignore specific Composer-installed plugins/themes if they are not part of your direct development
      # but are pulled in by wp-content/composer.json on the server.
      # /wp-content/plugins/akismet/
      # /wp-content/plugins/contact-form-7/
      # Make sure to NOT ignore your custom themes/plugins:
      # !/wp-content/plugins/my-custom-plugin/
      # !/wp-content/themes/my-custom-theme/
      ```
    The strategy for gitignoring Composer-managed plugins/themes in `wp-content/plugins` and `wp-content/themes` depends on whether you commit them or let CI/CD install them. If CI/CD installs them, they should be gitignored.

## Troubleshooting

This section lists common issues you might encounter and how to resolve them.

- **"Error establishing a database connection" on the server:**
    - **Check Docker container logs:** On your server, navigate to your project directory (e.g., `/home/wordpress-template/`) and run `docker compose -f docker-compose.prod.yml logs db_prod` and `docker compose -f docker-compose.prod.yml logs wordpress_prod`. Look for errors.
    - **MySQL (`db_prod`) not running:** The most common cause for this on a new or small server is insufficient RAM. MySQL 8+ typically needs at least 1GB RAM to start and run reliably with WordPress. If logs show "Killed" messages or MySQL failing to start, consider upgrading your Droplet/server RAM.
    - **Verify server `.env` file:** Ensure the `.env` file in your server's project directory (created by the CI/CD workflow from GitHub Secrets) contains the correct database credentials (`PROD_MYSQL_DATABASE`, `PROD_MYSQL_USER`, `PROD_MYSQL_PASSWORD`) and that these match what MySQL expects.
    - **WordPress Salts/Keys:** Ensure all `PROD_..._KEY` and `PROD_..._SALT` variables in the server `.env` file are populated with unique, complex values from your GitHub Secrets.
    - **Database/User Initialization:** MySQL creates the database and user only on the first run with an empty data volume. If you updated credentials in GitHub Secrets *after* the database was already initialized, you might need to reset the database volume to force re-initialization with the new credentials.
        - **Warning: This deletes all database data!** On the server:
          ```bash
          cd /home/wordpress-template/ # Or your project path
          docker compose -f docker-compose.prod.yml down -v 
          # Then re-run your GitHub Actions workflow to deploy and re-initialize.
          ```

- **Local: File changes not appearing / new custom plugins not visible:**
    - **Check `docker-compose.yml` volume mount:** Ensure your local `docker-compose.yml` correctly mounts `./wp-content:/var/www/html/wp-content`.
    - **Restart Docker containers:** This often resolves file sync issues, especially on macOS/Windows:
      ```bash
      # In your local project root
      docker-compose down
      docker-compose up -d
      ```
    - **Docker Desktop File Sharing Settings:** (macOS/Windows) Verify that your project directory is included in Docker Desktop's "File Sharing" settings (Settings -> Resources -> File Sharing).
    - **Plugin Headers:** Ensure your custom plugin's main PHP file has a correct WordPress plugin header comment, especially `Plugin Name:`.
    - **WordPress/Browser Cache:** Hard refresh (Cmd+Shift+R or Ctrl+Shift+R) the "Plugins" page in your local WordPress admin.
    - **Correct Port:** When developing locally:
        - Access your site directly via the WordPress port (e.g., `http://localhost:8000` as per `WORDPRESS_HOST_PORT` in `.env`).
        - When `npm run watch` is active for BrowserSync, access the site via the BrowserSync proxy port (e.g., `http://localhost:3000`) to see live reloads and CSS injections.

- **BrowserSync not injecting CSS "on the fly" (local):**
    - **View through BrowserSync Port:** Ensure you are viewing the site via the BrowserSync proxy URL (e.g., `http://localhost:3000`).
    - **File Watching:** Check that the specific CSS file you are editing is covered by the `files` patterns in `bs-config.js` (it currently watches `wp-content/**/*.css`).
    - **WordPress Enqueueing:** Verify how your theme's `functions.php` is enqueuing the stylesheet (`wp_enqueue_style`).
    - **Block Themes (`theme.json`):** If using a block theme, most styling is controlled by `theme.json` and Global Styles. Changes to the theme's root `style.css` might have limited visual effect or be overridden. For `theme.json` changes to trigger a reload, add it to the `files` array in `bs-config.js`. BrowserSync will then perform a full page reload.

- **GitHub Actions workflow failures:**
    - **Check Workflow Logs:** Carefully review the logs for the failing step in the "Actions" tab of your GitHub repository.
    - **GitHub Secrets:** Double-check that all required secrets (`SSH_HOST`, `SSH_USER`, `SSH_PRIVATE_KEY`, `SERVER_PROJECT_PATH`, and all `PROD_...` variables) are correctly named and populated in your repository's Actions secrets settings.
    - **SSH Connection Issues:** Verify the SSH private key, host, and user. Ensure the server's firewall (UFW) allows SSH connections from GitHub Actions runners (usually, allowing from `Anywhere` is necessary for SSH key-based auth for Actions, but ensure your key is strong).
    - **Permissions on Server:** Ensure the `SERVER_PROJECT_PATH` directory on the server is writable by the `SSH_USER` that GitHub Actions uses.
    - **Server Disk Space:** Ensure your server has enough free disk space.
    - **Errors in Remote Commands:** Check the script block in the `Execute remote commands` step for any syntax errors or commands failing on the server.

- **Composer issues (local or server):**
    - **Run in correct directory:** Ensure Composer commands are run from `wp-content/` (or `/var/www/html/wp-content/` inside the container).
    - **Memory limits:** PHP might run out of memory for complex Composer operations. The WordPress Docker image usually has a reasonable memory limit for PHP CLI.
    - **Network issues:** If Composer can't download packages, check network connectivity from the environment where Composer is running (e.g., from inside the Docker container for server-side `composer install`).
    - **`composer.lock` issues:** If you suspect a corrupted or out-of-sync `composer.lock` file, you can try deleting it (after backing it up) and running `composer install` again (which will regenerate it based on `composer.json`).

## Further Customization / Advanced Topics

This template provides a solid foundation. Here are some areas you might want to explore for further customization or more advanced setups:

- **Database and File Backups:**
    - Implement a robust and regular backup strategy for:
        - **MySQL Database:** Use tools like `mysqldump` (can be run via `docker compose exec db_prod mysqldump ...`) scheduled with `cron`, or use DigitalOcean's snapshot/backup features for the Droplet. Store backups securely, preferably off-server.
        - **`wp-content/uploads` Directory:** This directory contains user-uploaded media. It should be backed up regularly. If it's not part of your Git repository, `rsync` or other backup tools can be used.
    - Consider backup frequency, retention policies, and testing your restore process.

- **Advanced Frontend Build Workflows (Vite/Webpack):**
    - If your themes or plugins require more complex JavaScript and CSS processing (e.g., SCSS/SASS, TypeScript, PostCSS, JS module bundling, React/Vue components), you can integrate tools like Vite or Webpack.
    - This is typically done by setting up a Node.js environment *within each specific theme or plugin directory* that needs it (i.e., its own `package.json`, `vite.config.js` or `webpack.config.js`).
    - The build tool's development server (e.g., Vite dev server on `localhost:5173` or `webpack-dev-server` on `localhost:8080`) would serve assets with HMR.
    - Your WordPress theme/plugin PHP code would need to be adapted to enqueue assets from the dev server during development and from a `dist/` build directory (using a manifest file) in production.
    - BrowserSync (run from the project root) can still be used alongside these tools to handle full-page reloads for PHP file changes, while Vite/Webpack handles HMR for the assets they manage.

- **More Complex CI/CD Scenarios:**
    - **Building Frontend Assets in CI:** If you use Vite/Webpack, you can add a step to your GitHub Actions workflow (`deploy.yml`) to run `npm install && npm run build` for your theme/plugin. The CI would then deploy the compiled static assets instead of the source files.
    - **Automated Testing:** Integrate PHPUnit tests or other automated tests into your CI pipeline.
    - **Staging/Production Environments:** Create separate workflows or use different branches/secrets for deploying to a staging environment before deploying to production.
    - **Docker Image Registry:** For more robust deployments, your CI/CD pipeline could build a custom Docker image (containing WordPress, your `wp-content` with Composer dependencies and built frontend assets) and push it to a Docker registry (like Docker Hub or GitHub Container Registry - GHCR). The server would then pull this pre-built image instead of just syncing files and running `composer install`.

- **Optimizing Docker Images:**
    - Use multi-stage builds in your `Dockerfile` to keep the final image size smaller (e.g., use a build stage for Composer install or asset compilation, then copy only necessary artifacts to the final production image).
    - Ensure your production WordPress image doesn't contain unnecessary development tools.

## Security Reminders

Security is crucial for any web application. Here are some important reminders for your WordPress project:

- **Strong Credentials:**
    - Always use strong, unique passwords for your MySQL database users (root and WordPress user), WordPress admin accounts, SSH access, and any other services.
    - Store sensitive credentials like production database passwords and API keys as encrypted GitHub Secrets for your CI/CD workflow. Do not commit them directly to your repository.
    - Regularly rotate passwords and API keys where appropriate.

- **WordPress Security Keys and Salts:**
    - Ensure that your `wp-config.php` (or the `.env` file that populates it on the server) uses unique and complex WordPress security keys and salts. Generate these from [https://api.wordpress.org/secret-key/1.1/salt/](https://api.wordpress.org/secret-key/1.1/salt/) for your production environment.

- **Keep Software Updated:**
    - Regularly update WordPress core to the latest version.
    - Keep all your themes and plugins updated to their latest versions to patch known vulnerabilities.
    - Keep your server's operating system and installed packages (like Docker, PHP, MySQL if run on host) updated with security patches.

- **Secure Server Configuration:**
    - **Firewall (UFW):** Ensure your server's firewall is active and only allows traffic on necessary ports (SSH, HTTP, HTTPS). Restrict access to sensitive ports (like phpMyAdmin's custom port) to specific trusted IP addresses if possible.
    - **SSH Security:**
        - Use SSH key-based authentication instead of passwords for server access.
        - Disable root login via SSH if you have a dedicated sudo user for administration (`PermitRootLogin no` in `sshd_config`).
        - Consider changing the default SSH port (though this is more security through obscurity).
        - Use tools like `fail2ban` to monitor SSH logs and block IPs with suspicious activity.
    - **User Privileges:** Avoid working as `root` continuously on your server for day-to-day management. Use a dedicated user with `sudo` privileges for administrative tasks. (Note: This template's CI/CD was configured to use `root` for deployment as per earlier discussions; for enhanced security in a real production setup, consider a dedicated non-root deploy user with specific, limited permissions).

- **HTTPS (SSL/TLS):**
    - Always serve your live WordPress site over HTTPS.
    - Use a reverse proxy like Nginx to handle SSL termination. Obtain free SSL certificates from Let's Encrypt using Certbot.
    - Configure HSTS (HTTP Strict Transport Security) headers.

- **phpMyAdmin Security (if deployed to production):**
    - If you deploy phpMyAdmin to your production server, ensure it is heavily secured:
        - Access it via HTTPS.
        - Place it on a non-standard, hard-to-guess URL path.
        - Protect access with strong HTTP Basic Authentication via your reverse proxy (Nginx/Apache).
        - Restrict access to its port via the firewall to only trusted IP addresses.
        - Consider removing it from the production server when not actively needed.

- **Regular Backups:**
    - Implement a strategy for regular automated backups of your WordPress database and files (especially `wp-content/uploads`).
    - Store backups securely, preferably in an off-server location.
    - Periodically test your backup restoration process.

- **Monitor Logs:**
    - Regularly check server logs, WordPress logs (if debug logging is enabled temporarily for troubleshooting), and Docker container logs for any suspicious activity or errors.

- **WordPress Specific Security:**
    - Use security plugins (e.g., Wordfence, Sucuri) if appropriate for your needs, but understand their resource impact.
    - Limit login attempts.
    - Use Two-Factor Authentication (2FA) for WordPress admin accounts.
    - Be mindful of the themes and plugins you install; use reputable sources.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

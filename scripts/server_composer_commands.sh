#!/bin/bash
# scripts/server_composer_commands.sh
# This script is executed on the remote server by the CI/CD pipeline,
# but it triggers commands *inside* the running WordPress Docker container.
# Its purpose is to install composer dependencies for the main wp-content directory,
# and then for any plugins and themes that have their own composer.json.

set -e # Exit immediately if a command (-e) or pipeline (pipefail) exits with a non-zero status.
# set -o pipefail # Causes a pipeline to return the exit status of the last command in the pipe that returned a non-zero status.

# These paths are defined as they are *inside* the WordPress Docker container
WP_CONTENT_DIR_IN_CONTAINER="/var/www/html/wp-content"
PLUGINS_DIR_IN_CONTAINER="$WP_CONTENT_DIR_IN_CONTAINER/plugins"
THEMES_DIR_IN_CONTAINER="$WP_CONTENT_DIR_IN_CONTAINER/themes"

# The docker-compose.prod.yml file is expected to be in the current directory on the server
# where this script (server_composer_commands.sh) is initially called from by the ssh-action.
# However, WORDPRESS_CONTAINER_ID is found by the ssh-action script and this script is then copied into the container.
# This script will be copied into the container and run there. So, it can directly find the container ID if needed,
# but for the final version of deploy.yml, WORDPRESS_CONTAINER_ID is passed by ssh-action.
# For this script, we assume it's run via `docker exec WORDPRESS_CONTAINER_ID bash /path/to/this/script/in/container`
# So, WORDPRESS_CONTAINER_ID is not needed to be found *within* this script.

echo "INFO (server_composer_commands.sh): Starting Composer installations..."

# Function to run composer install in a given directory inside the container
run_composer_in_dir() {
  local project_dir_in_container="$1"
  local project_type_name="$2" # e.g., "main wp-content", "plugin", or "theme"

  echo "INFO (server_composer_commands.sh): Checking for composer.json in $project_type_name directory: $project_dir_in_container"
  # Check if composer.json exists
  if [ -f "$project_dir_in_container/composer.json" ]; then
    echo "INFO (server_composer_commands.sh): composer.json found in $project_dir_in_container. Running composer install..."
    # Run composer install within the specified working directory
    composer install \
      --no-interaction \
      --no-progress \
      --no-dev \
      --optimize-autoloader \
      --working-dir="$project_dir_in_container"

    if [ $? -ne 0 ]; then
      echo "ERROR (server_composer_commands.sh): Composer install failed for $project_dir_in_container" >&2
      # Consider exiting with error if a sub-project failure should stop the whole script:
      # exit 1
    else
      echo "INFO (server_composer_commands.sh): Composer install successful for $project_dir_in_container"
    fi
  else
    echo "INFO (server_composer_commands.sh): No composer.json found in $project_dir_in_container. Skipping."
  fi
}

# Run for main wp-content directory
run_composer_in_dir "$WP_CONTENT_DIR_IN_CONTAINER" "main wp-content"

# Process plugins
echo "INFO (server_composer_commands.sh): Processing Plugins in $PLUGINS_DIR_IN_CONTAINER..."
# Loop through first-level subdirectories in plugins directory
# Ensure we only process directories
if [ -d "$PLUGINS_DIR_IN_CONTAINER" ]; then
    for project_candidate_dir in "$PLUGINS_DIR_IN_CONTAINER"/* ; do
        if [ -d "$project_candidate_dir" ]; then # Check if it's a directory
            run_composer_in_dir "$project_candidate_dir" "plugin $(basename "$project_candidate_dir")"
        fi
    done
else
    echo "WARN (server_composer_commands.sh): Plugins directory $PLUGINS_DIR_IN_CONTAINER not found."
fi


# Process themes
echo "INFO (server_composer_commands.sh): Processing Themes in $THEMES_DIR_IN_CONTAINER..."
if [ -d "$THEMES_DIR_IN_CONTAINER" ]; then
    for project_candidate_dir in "$THEMES_DIR_IN_CONTAINER"/* ; do
        if [ -d "$project_candidate_dir" ]; then # Check if it's a directory
            run_composer_in_dir "$project_candidate_dir" "theme $(basename "$project_candidate_dir")"
        fi
    done
else
    echo "WARN (server_composer_commands.sh): Themes directory $THEMES_DIR_IN_CONTAINER not found."
fi

echo "INFO (server_composer_commands.sh): All composer install tasks finished."
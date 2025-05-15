// This file should be in the root of your project, next to docker-compose.yml

// Load environment variables from .env file into process.env
require('dotenv').config();

// Use the WORDPRESS_HOST_PORT from .env, or default to '8000' if not set
const wordPressHostPort = process.env.WORDPRESS_HOST_PORT || '8000';
const wordPressProxyUrl = `localhost:${wordPressHostPort}`;

module.exports = {
  // Proxy your WordPress site running in Docker
  proxy: wordPressProxyUrl,

  // Port BrowserSync will use for its own server
  port: 3000,

  // Files to watch. BrowserSync will try to inject CSS changes,
  // and reload the page for other file types.
  files: [
    "wp-content/**/*.css",  // Watch all .css files within wp-content and its subdirectories
    "wp-content/**/*.php",  // Watch all .php files for full reload
    "wp-content/**/*.js",    // Watch all .js files (will cause a full reload by default)
    "wp-content/**/*.html"    // Watch all .html files (will cause a full reload by default)
  ],

  // Attempt to inject CSS changes without a full page reload.
  injectChanges: true,

  // Disable BrowserSync notifications in the browser
  notify: false,

  // Don't automatically open a new browser window when BrowserSync starts
  open: false

  // Optional: Add a small delay before reloading
  // reloadDelay: 500,

  // Optional: Watch options for more control if needed (e.g., for Docker on some systems)
  // watchOptions: {
  //   usePolling: true, // Try this if file watching doesn't work reliably in Docker
  //   interval: 100 // Polling interval
  // }
};
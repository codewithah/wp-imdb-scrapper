<?php
/**
 * Plugin Name: IMDb Scraper
 * Description: Made for veronalabs.
 * Version: 0.0.1
 * Author: codewithah
 * Text Domain: imdb-scraper
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IMDB_SCRAPER_VERSION', '0.0.1');
define('IMDB_SCRAPER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMDB_SCRAPER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the main IMDb_Scraper class
require_once IMDB_SCRAPER_PLUGIN_DIR . 'includes/class-imdb-scraper.php';

// Initialize the plugin
function run_imdb_scraper() {
    $plugin = new IMDb_Scraper();
    $plugin->run();
}

run_imdb_scraper();
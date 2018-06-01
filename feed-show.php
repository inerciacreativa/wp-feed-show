<?php
/**
 * Plugin Name: ic Feed Show
 * Plugin URI:  https://github.com/inerciacreativa/wp-feed-show
 * Version:     2.0.2
 * Text Domain: ic-feed-show
 * Domain Path: /languages
 * Description: Widget para mostrar feeds RSS.
 * Author:      Jose Cuesta
 * Author URI:  https://inerciacreativa.com/
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if (!defined('ABSPATH')) {
    exit;
}

ic\Plugin\FeedShow\FeedShow::create(__FILE__, WP_PLUGIN_DIR);

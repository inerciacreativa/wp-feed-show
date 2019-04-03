<?php
/**
 * Plugin Name: ic Feed Show
 * Plugin URI:  https://github.com/inerciacreativa/wp-feed-show
 * Version:     3.0.0
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

if (!class_exists(ic\Framework\Framework::class)) {
	trigger_error('ic Framework not found', E_USER_ERROR);
}

if (!class_exists(ic\Plugin\RewriteControl\RewriteControl::class)) {
	if (file_exists(__DIR__ . '/vendor/autoload.php')) {
		include_once __DIR__ . '/vendor/autoload.php';
	} else {
		trigger_error('Could not load RewriteControl class', E_USER_ERROR);
	}
}

ic\Plugin\FeedShow\FeedShow::create(__FILE__);

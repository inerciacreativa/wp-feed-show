<?php
/**
 * Plugin Name: ic Feed Show
 * Plugin URI:  https://github.com/inerciacreativa/wp-feed-show
 * Version:     5.0.1
 * Text Domain: ic-feed-show
 * Domain Path: /languages
 * Description: Widget para mostrar feeds RSS.
 * Author:      Jose Cuesta
 * Author URI:  https://inerciacreativa.com/
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 */

use ic\Framework\Framework;
use ic\Plugin\FeedShow\FeedShow;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists(Framework::class)) {
	throw new RuntimeException(sprintf('Could not find %s class.', Framework::class));
}

if (!class_exists(FeedShow::class)) {
	$autoload = __DIR__ . '/vendor/autoload.php';

	if (file_exists($autoload)) {
		/** @noinspection PhpIncludeInspection */
		include_once $autoload;
	} else {
		throw new RuntimeException(sprintf('Could not load %s class.', FeedShow::class));
	}
}

FeedShow::create(__FILE__);

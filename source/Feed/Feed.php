<?php

namespace ic\Plugin\FeedShow\Feed;

use RuntimeException;
use SimplePie;
use SimplePie_Cache;
use WP_Feed_Cache;
use WP_Feed_Cache_Transient;
use WP_SimplePie_File;
use WP_SimplePie_Sanitize_KSES;

/**
 * Class Feed
 *
 * @package ic\Plugin\FeedShow\FeedShow\Feed
 */
class Feed
{

	/**
	 * @var string
	 */
	protected static $userAgent = 'ic Feed Show/2.0';

	/**
	 * @var array
	 */
	protected static $forbiddenTags = [
		'base',
		'blink',
		'body',
		'doctype',
		'font',
		'form',
		'frame',
		'frameset',
		'html',
		'input',
		'marquee',
		'meta',
		'noscript',
		'script',
		'style',
	];

	/**
	 * @var bool
	 */
	protected static $loaded = false;

	/**
	 * @param string $url
	 * @param int    $items
	 * @param int    $cache
	 *
	 * @return Collection
	 * @throws RuntimeException
	 */
	public static function fetch(string $url, int $items, int $cache): Collection
	{
		$cache      *= HOUR_IN_SECONDS;
		$collection = new Collection($url, $items, $cache);

		if ($collection->isEmpty() || $collection->hasExpired()) {
			$instance = new static();

			try {
				$feed = $instance->retrieve($url, 0);

				foreach ($feed->get_items(0, $items) as $item) {
					$collection->add($item);
				}

				$collection->save();
			} catch (RuntimeException $exception) {
				throw new RuntimeException($exception->getMessage());
			}
		}

		return $collection;
	}

	/**
	 * @param string $url
	 *
	 * @return bool
	 * @throws RuntimeException
	 */
	public static function check(string $url): bool
	{
		$instance = new static();

		try {
			$instance->retrieve($url, 0);
		} catch (RuntimeException $exception) {
			throw new RuntimeException($exception->getMessage());
		}

		return true;
	}

	/**
	 * Feed constructor.
	 */
	public function __construct()
	{
		if (!self::$loaded) {
			self::load();

			self::$loaded = true;
		}
	}

	/**
	 * @param string $url
	 * @param int    $cache
	 * @param int    $timeout
	 *
	 * @return SimplePie
	 *
	 * @throws RuntimeException
	 */
	protected function retrieve(string $url, int $cache = 3600, int $timeout = 5): SimplePie
	{
		$feed = self::feed($url, $cache, $timeout);

		if ($feed->error()) {
			throw new RuntimeException($feed->error());
		}

		if ($feed->get_item_quantity() === 0) {
			throw new RuntimeException(__('An error has occurred, which probably means the feed is down. Try again later.'));
		}

		return $feed;
	}

	/**
	 * @param string $url
	 * @param int    $cache
	 * @param int    $timeout
	 *
	 * @return SimplePie
	 */
	protected static function feed(string $url, int $cache = 3600, int $timeout = 5): SimplePie
	{
		$feed = new SimplePie();

		$feed->set_sanitize_class('WP_SimplePie_Sanitize_KSES');
		$feed->sanitize = new WP_SimplePie_Sanitize_KSES();

		if (method_exists('SimplePie_Cache', 'register')) {
			SimplePie_Cache::register('wp_transient', WP_Feed_Cache_Transient::class);
			$feed->set_cache_location('wp_transient');
		} else {
			$feed->set_cache_class(WP_Feed_Cache::class);
		}

		$feed->set_file_class(WP_SimplePie_File::class);

		$feed->set_feed_url($url);
		$feed->set_cache_duration($cache);
		$feed->set_timeout($timeout);
		$feed->set_output_encoding(get_option('blog_charset'));
		$feed->set_useragent(self::$userAgent);
		$feed->strip_htmltags(self::$forbiddenTags);

		$feed->init();
		$feed->handle_content_type();

		return $feed;
	}

	/**
	 * Load the required classes.
	 */
	protected static function load(): void
	{
		if (!class_exists(SimplePie::class, false)) {
			require_once ABSPATH . WPINC . '/class-simplepie.php';
		}

		if (!method_exists('SimplePie_Cache', 'register')) {
			require_once ABSPATH . WPINC . '/class-wp-feed-cache.php';
		}

		require_once ABSPATH . WPINC . '/class-wp-feed-cache-transient.php';
		require_once ABSPATH . WPINC . '/class-wp-simplepie-file.php';
		require_once ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php';
	}

}

<?php

namespace ic\Plugin\FeedShow\Feed;

use ic\Framework\Support\Cache;
use ic\Framework\Support\Repository;

/**
 * Class Feed
 *
 * @package ic\Plugin\FeedShow\FeedShow\Feed
 */
class Feed extends Repository
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
	 * @throws \RuntimeException
	 * @return static
	 */
	public static function fetch(string $url, int $items, int $cache = 3600)
	{
		$feed = new static();

		try {
			$rss = $feed->retrieve($url, $cache);

			foreach ($rss->get_items(0, $items) as $item) {
				$feed[] = new Item($item);
			}

			$rss->__destruct();
			unset($rss);
		} catch (\RuntimeException $exception) {
			throw new \RuntimeException($exception->getMessage());
		}

		return $feed;
	}

	/**
	 * @param string $url
	 *
	 * @throws \RuntimeException
	 * @return bool
	 */
	public static function check(string $url): bool
	{
		$feed = new static();

		try {
			$rss = $feed->retrieve($url, 0);
			$rss->__destruct();
			unset($rss);
		} catch (\RuntimeException $exception) {
			throw new \RuntimeException($exception->getMessage());
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
	 * @return \SimplePie
	 * @throws \Exception
	 */
	protected function retrieve(string $url, int $cache = 3600, int $timeout = 5): \SimplePie
	{
		$id = $this->id($url);
		if (($cache > 0) && ($rss = Cache::get($id))) {
			return $rss;
		}

		$cache += random_int(0, 60);

		$rss = self::rss($url, $cache, $timeout);

		if ($rss->error()) {
			$error = $rss->error();

			$rss->__destruct();
			unset($rss);

			throw new \RuntimeException($error);
		}

		if ($rss->get_item_quantity() === 0) {
			$rss->__destruct();
			unset($rss);

			throw new \RuntimeException(__('An error has occurred, which probably means the feed is down. Try again later.'));
		}

		Cache::set($id, $rss, $cache);

		return $rss;
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	protected function id(string $url): string
	{
		$id = str_replace(['http://', 'https://', '.', '/'], ['', '', '_', '_'], $url);

		return 'ic_feed-'. rtrim($id, '-');
	}

	/**
	 * @param string $url
	 * @param int    $cache
	 * @param int    $timeout
	 *
	 * @return \SimplePie
	 */
	protected static function rss(string $url, int $cache = 3600, int $timeout = 5): \SimplePie
	{
		$rss = new \SimplePie();

		$rss->set_useragent(self::$userAgent);
		$rss->set_cache_class(\WP_Feed_Cache::class);
		$rss->set_file_class(\WP_SimplePie_File::class);

		$rss->set_feed_url($url);
		$rss->set_cache_duration($cache);
		$rss->set_timeout($timeout);
		$rss->set_output_encoding(get_option('blog_charset'));

		$rss->strip_htmltags(self::$forbiddenTags);

		$rss->init();
		$rss->handle_content_type();

		return $rss;
	}

	/**
	 * Load the required classes.
	 */
	protected static function load(): void
	{
		if (!class_exists(\SimplePie::class, false)) {
			require_once ABSPATH . WPINC . '/class-simplepie.php';
		}

		require_once ABSPATH . WPINC . '/class-wp-feed-cache.php';
		require_once ABSPATH . WPINC . '/class-wp-feed-cache-transient.php';
		require_once ABSPATH . WPINC . '/class-wp-simplepie-file.php';
		require_once ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php';
	}

}
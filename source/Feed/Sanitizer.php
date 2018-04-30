<?php

namespace ic\Plugin\FeedShow\Feed;

/**
 * Class FeedSanitizer
 *
 * @package ic\Plugin\FeedShow\FeedShow\Feed
 */
class Sanitizer extends \WP_SimplePie_Sanitize_KSES
{

	public function __construct()
	{
		$this->strip_htmltags = [
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

		parent::__construct();
	}
}
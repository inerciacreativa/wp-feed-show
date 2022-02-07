<?php

namespace ic\Plugin\FeedShow\Feed;

use ic\Framework\Html\Tag;
use ic\Framework\Support\Str;

/**
 * Class Item
 *
 * @package ic\Plugin\FeedShow\Feed
 */
class Item
{

	/**
	 * @var string
	 */
	protected $link;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $author;

	/**
	 * @var int
	 */
	protected $date;

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @var int
	 */
	protected $image;

	/**
	 * Item constructor.
	 *
	 * @param array $properties
	 */
	public function __construct(array $properties)
	{
		foreach ($properties as $name => $value) {
			if (($value !== null) && property_exists($this, $name)) {
				$this->$name = $value;
			}
		}
	}

	/**
	 * @return string
	 */
	public function link(): string
	{
		return apply_filters('ic_feed_show_link', $this->link);
	}

	/**
	 * @return string|null
	 */
	public function title(): ?string
	{
		return apply_filters('ic_feed_show_title', $this->title);
	}

	/**
	 * @return string|null
	 */
	public function author(): ?string
	{
		return apply_filters('ic_feed_show_author', $this->author);
	}

	/**
	 * @param string $format
	 *
	 * @return string|int|null
	 */
	public function date(string $format = '')
	{
		if ($this->date === null) {
			return apply_filters('ic_feed_show_date', null, null, $format);
		}

		if ($format === 'U') {
			return apply_filters('ic_feed_show_date', $this->date, $this->date, 'U');
		}

		if ($format === '') {
			$format = (string) get_option('date_format');
		}

		return apply_filters('ic_feed_show_date', date_i18n($format, $this->date), $this->date, $format);
	}

	/**
	 * @return string|null
	 */
	public function content(): ?string
	{
		return apply_filters('ic_feed_show_content', $this->content);
	}

	/**
	 * @param int $words
	 *
	 * @return string|null
	 */
	public function excerpt(int $words = 55): ?string
	{
		if ($this->content === null) {
			return apply_filters('ic_feed_show_excerpt', null);
		}

		$summary = Str::stripTags($this->content, ['figure']);
		$summary = Str::whitespace($summary);
		$summary = Str::words($summary, $words, ' [&hellip;]');

		if (Str::endsWith($summary, '[...]')) {
			$summary = Str::replaceLast($summary, '[...]', '[&hellip;]');
		}

		return apply_filters('ic_feed_show_excerpt', esc_html($summary));
	}

	/**
	 * @param string $size
	 * @param array  $attributes
	 *
	 * @return string|null
	 */
	public function image(string $size = 'thumbnail', array $attributes = []): ?string
	{
		if ($this->image === null) {
			return apply_filters('ic_feed_show_image', null, $size, $attributes);
		}

		$image = wp_get_attachment_image($this->image, $size, false, $attributes);
		/** @noinspection NullPointerExceptionInspection */
		$image = Tag::parse($image)->attributes($attributes);

		if (!isset($image['alt'])) {
			$image['alt'] = '';
		}

		return (string) apply_filters('ic_feed_show_image', $image, $size, $attributes);
	}

	/**
	 * @return bool
	 */
	public function has_image(): bool
	{
		return $this->image !== null;
	}

}

<?php

namespace ic\Plugin\FeedShow\Feed;

use ic\Framework\Support\Arr;
use ic\Framework\Support\Str;

/**
 * Class Item
 *
 * @package ic\Plugin\FeedShow\Feed
 */
class Item
{

	/**
	 * @var \SimplePie_Item
	 */
	protected $item;

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
	protected $content;

	/**
	 * @var string
	 */
	protected $date;

	/**
	 * @var string
	 */
	protected $author;

	/**
	 * @var string
	 */
	protected $image;

	/**
	 * FeedItem constructor.
	 *
	 * @param \SimplePie_Item $item
	 */
	public function __construct(\SimplePie_Item $item)
	{
		$this->item = $item;

		$this->setLink($item);
		$this->setTitle($item->get_title());
		$this->setAuthor($item->get_author());
		$this->setDate($item->get_date('U'));
		$this->setContent($item->get_content());
	}

	/**
	 * @param \SimplePie_Item $item
	 */
	protected function setLink(\SimplePie_Item $item): void
	{
		// FeedBurner
		$data = $item->get_item_tags('http://rssnamespace.org/feedburner/ext/1.0', 'origLink');

		if (\is_array($data)) {
			// Original link is in <feedburner:origLink>
			$link = Arr::get($data, '0.data', false);
		} else {
			$link = $item->get_link();
		}

		while (stristr($link, 'http') !== $link) {
			$link = substr($link, 1);
		}

		$this->link = esc_url(strip_tags($link));
	}

	/**
	 * @param string $title
	 */
	protected function setTitle($title): void
	{
		if (!empty($title)) {
			$title = esc_html(trim(strip_tags($title)));
		}

		$this->title = empty($title) ? __('Untitled') : $title;
	}

	/**
	 * @param \SimplePie_Author|null $author
	 */
	protected function setAuthor($author): void
	{
		if (\is_object($author)) {
			$this->author = esc_html(strip_tags($author->get_name()));
		}
	}

	/**
	 * @param string|null $date
	 */
	protected function setDate($date): void
	{
		$this->date = $date;
	}

	/**
	 * @param string $content
	 */
	protected function setContent($content): void
	{
		$this->content = Str::fromEntities($content);
	}

	/**
	 * @return Image
	 */
	protected function getImage(): Image
	{
		if ($this->image === null) {
			$this->image = new Image($this->link, $this);
		}

		return $this->image;
	}

	/**
	 * @return string
	 */
	public function link(): string
	{
		return apply_filters('ic_feed_show_link', $this->link);
	}

	/**
	 * @return string
	 */
	public function title(): string
	{
		return apply_filters('ic_feed_show_title', $this->title);
	}

	/**
	 * @return string
	 */
	public function author(): string
	{
		return apply_filters('ic_feed_show_author', $this->author);
	}

	/**
	 * @param string $format
	 *
	 * @return string
	 */
	public function date(string $format = ''): string
	{
		if (empty($this->date)) {
			return apply_filters('ic_feed_show_date', '', '', $format);
		}

		if (empty($format)) {
			$format = (string) get_option('date_format');
		}

		return apply_filters('ic_feed_show_date', date_i18n($format, $this->date), $this->date, $format);
	}

	/**
	 * @return string
	 */
	public function content(): string
	{
		return apply_filters('ic_feed_show_content', $this->content);
	}

	/**
	 * @param int $words
	 *
	 * @return string
	 */
	public function excerpt(int $words = 55): string
	{
		if (empty($this->content)) {
			return apply_filters('ic_feed_show_excerpt', '');
		}

		$summary = esc_attr(wp_trim_words($this->content, $words, ' [&hellip;]'));

		if (Str::endsWith($summary, '[...]')) {
			$summary = Str::replaceLast($summary, '[...]', '[&hellip;]');
		}

		return apply_filters('ic_feed_show_excerpt', esc_html($summary));
	}

	/**
	 * @return string
	 */
	public function enclosure(): string
	{
		$enclosure = $this->item->get_enclosure();

		if (($enclosure instanceof \SimplePie_Enclosure) && Str::startsWith((string) $enclosure->get_type(), 'image')) {
			return apply_filters('ic_feed_show_enclosure', $enclosure->get_link());
		}

		return apply_filters('ic_feed_show_enclosure', '');
	}

	/**
	 * @param string $size
	 * @param array  $attributes
	 *
	 * @return string
	 */
	public function image(string $size = 'thumbnail', array $attributes = []): string
	{
		return apply_filters('ic_feed_show_image', $this->getImage()
		                                                ->fetch($size, $attributes), $size);
	}

	/**
	 * @return bool
	 */
	public function has_image(): bool
	{
		return $this->getImage()->has();
	}

}
<?php

namespace ic\Plugin\FeedShow\Feed;

use ic\Framework\Image\Image;
use ic\Framework\Image\ImageSearch;
use ic\Framework\Support\Arr;
use ic\Framework\Support\Str;
use SimplePie_Author;
use SimplePie_Enclosure;
use SimplePie_Item;

class Converter
{

	public const IMAGE = '_ic_feed_show_image';

	/**
	 * @var SimplePie_Item
	 */
	protected $item;

	/**
	 * @var string
	 */
	protected $link;

	/**
	 * Converter constructor.
	 *
	 * @param SimplePie_Item $item
	 */
	public function __construct(SimplePie_Item $item)
	{
		$this->item = $item;
		$this->link = $this->getLink($item);
	}

	/**
	 * @return string
	 */
	public function link(): string
	{
		return $this->link;
	}

	/**
	 * @return array
	 */
	public function item(): array
	{
		$enclosure = $this->getEnclosure($this->item->get_enclosures());

		$item = [
			'link'    => $this->link,
			'title'   => $this->getTitle($this->item->get_title()),
			'author'  => $this->getAuthor($this->item->get_author()),
			'date'    => $this->getDate($this->item->get_date('U')),
			'content' => $this->getContent($this->item->get_content()),
		];

		$item['image'] = $this->getImage($item['link'], $enclosure, $item['content']);

		return $item;
	}

	/**
	 * @param SimplePie_Item $item
	 *
	 * @return string
	 */
	protected function getLink(SimplePie_Item $item): string
	{
		// FeedBurner
		$data = $item->get_item_tags('http://rssnamespace.org/feedburner/ext/1.0', 'origLink');

		if (is_array($data)) {
			// Original link is in <feedburner:origLink>
			$link = Arr::get($data, '0.data', false);
		} else {
			$link = $item->get_link();
		}

		$link = strip_tags($link);

		while (stristr($link, 'http') !== $link) {
			$link = substr($link, 1);
		}

		return esc_url($link);
	}

	/**
	 * @param array|null $enclosures
	 *
	 * @return string|null
	 */
	protected function getEnclosure(array $enclosures = null): ?string
	{
		if ($enclosures === null) {
			return null;
		}

		foreach ($enclosures as $enclosure) {
			if (($enclosure instanceof SimplePie_Enclosure) && ($enclosure->get_type() !== null) && Str::startsWith($enclosure->get_type(), 'image')) {
				return $enclosure->get_link();
			}
		}

		return null;
	}

	/**
	 * @param string|null $title
	 *
	 * @return string|null
	 */
	protected function getTitle(string $title = null): ?string
	{
		if (!empty($title)) {
			$title = esc_html(trim(strip_tags($title)));
		}

		return empty($title) ? null : $title;
	}

	/**
	 * @param SimplePie_Author|null $author
	 *
	 * @return string|null
	 */
	protected function getAuthor(SimplePie_Author $author = null): ?string
	{
		if ($author) {
			return esc_html(strip_tags($author->get_name()));
		}

		return null;
	}

	/**
	 * @param string|null $date
	 *
	 * @return int|null
	 */
	protected function getDate(string $date = null): ?int
	{
		if ($date === null) {
			return null;
		}

		return (int) $date;
	}

	/**
	 * @param string $content
	 *
	 * @return string|null
	 */
	protected function getContent(string $content = null): ?string
	{
		if ($content === null) {
			return null;
		}

		return Str::fromEntities($content);
	}

	/**
	 * @param string      $link
	 * @param string|null $enclosure
	 * @param string|null $content
	 *
	 * @return int|null
	 */
	protected function getImage(string $link, string $enclosure = null, string $content = null): ?int
	{
		if ($image = self::getLocalImage($link)) {
			return $image;
		}

		return self::getExternalImage($link, $enclosure, $content);
	}

	/**
	 * @param string $link
	 *
	 * @return int|null
	 */
	protected static function getLocalImage(string $link): ?int
	{
		$image = get_posts([
			'meta_key'    => self::IMAGE,
			'meta_value'  => $link,
			'post_type'   => 'attachment',
			'post_status' => 'any',
			'numberposts' => 1,
		]);

		if (!empty($image)) {
			$image = reset($image);

			return $image->ID;
		}

		return null;
	}

	/**
	 * @param string      $link
	 * @param string|null $enclosure
	 * @param string|null $content
	 *
	 * @return int|null
	 */
	protected static function getExternalImage(string $link, string $enclosure = null, string $content = null): ?int
	{
		$image = null;

		if ($enclosure) {
			$image = new Image($enclosure);
		} else if ($content) {
			$images = ImageSearch::make($content, true);

			if ($images->count()) {
				$image = $images->sortBySize()->first();
			}
		}

		if ($image && $image->download() && $image->isLocal()) {
			update_post_meta($image->getId(), self::IMAGE, $link);

			return $image->getId();
		}

		return null;
	}

}
<?php

namespace ic\Plugin\FeedShow\Feed;

use ic\Framework\Html\Tag;
use ic\Framework\Image\Image as ImageObject;
use ic\Framework\Image\ImageSearch;

/**
 * Class Image
 *
 * @package ic\Plugin\FeedShow\FeedShow\Feed
 */
class Image
{

	protected const META = '_ic_feed_show_image';

	/**
	 * @var int|null
	 */
	protected $id;

	/**
	 * FeedImage constructor.
	 *
	 * @param string $url
	 * @param Item   $item
	 */
	public function __construct(string $url, Item $item)
	{
		$image = $this->get($url, self::META);

		if ($image) {
			$this->id = $image->ID;
		} else if ($image = $this->search($item)) {
			$this->id = $this->set($image, $url);
		}
	}

	/**
	 * @param string $size
	 * @param array  $attributes
	 *
	 * @return string
	 */
	public function fetch(string $size = 'thumbnail', array $attributes = []): string
	{
		if ($this->id) {
			$image = wp_get_attachment_image($this->id, $size);
			$image = Tag::parse($image)->attributes($attributes);

			if (!isset($image['alt'])) {
				$image['alt'] = '';
			}

			return $image;
		}

		return '';
	}

	/**
	 * @return bool
	 */
	public function has(): bool
	{
		return (bool) $this->id;
	}

	/**
	 * @param string $url
	 * @param string $meta
	 *
	 * @return \WP_Post|null
	 */
	protected function get(string $url, string $meta): ?\WP_Post
	{
		$image = get_posts([
			'meta_key'    => $meta,
			'meta_value'  => $url,
			'post_type'   => 'attachment',
			'post_status' => 'any',
			'numberposts' => 1,
		]);

		return empty($image) ? null : reset($image);
	}

	/**
	 * @param ImageObject $image
	 * @param string      $url
	 *
	 * @return int|null
	 */
	protected function set(ImageObject $image, string $url): ?int
	{
		$image->download();

		if ($image->isLocal()) {
			update_post_meta($image->getId(), self::META, $url);

			return $image->getId();
		}

		return null;
	}

	/**
	 * @param Item $item
	 *
	 * @return ImageObject|null
	 */
	protected function search(Item $item): ?ImageObject
	{
		if ($enclosure = $item->enclosure()) {
			return new ImageObject($enclosure);
		}

		$images = ImageSearch::make($item->content());

		if ($images->count()) {
			return $images->sortBySize()->first();
		}

		return null;
	}

}
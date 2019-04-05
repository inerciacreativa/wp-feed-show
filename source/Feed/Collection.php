<?php

namespace ic\Plugin\FeedShow\Feed;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use SimplePie_Item;

/**
 * Class Collection
 *
 * @package ic\Plugin\FeedShow\Feed
 */
class Collection implements Countable, IteratorAggregate
{

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var int
	 */
	protected $max;

	/**
	 * @var int
	 */
	protected $expiration;

	/**
	 * @var bool
	 */
	protected $expired = false;

	/**
	 * @var bool
	 */
	protected $modified = false;

	/**
	 * @var array
	 */
	protected $items = [];

	/**
	 * Collection constructor.
	 *
	 * @param string $url
	 * @param int    $max
	 * @param int    $expiration
	 */
	public function __construct(string $url, int $max, int $expiration)
	{
		$this->url        = $url;
		$this->max        = $max;
		$this->expiration = $expiration;

		$this->load();
	}

	/**
	 * @param SimplePie_Item $item
	 *
	 * @return bool
	 */
	public function add(SimplePie_Item $item): bool
	{
		$converter = new Converter($item);
		$link      = $converter->link();

		if (!empty($link) && !array_key_exists($link, $this->items)) {
			$this->items[$link] = new Item($converter->item());

			if ($this->count() > 1) {
				uasort($this->items, [$this, 'sort']);
			}

			$this->modified = true;
		}

		while ($this->count() > $this->max) {
			array_pop($this->items);
		}

		return $this->modified;
	}

	/**
	 * @param Item $a
	 * @param Item $b
	 *
	 * @return int
	 */
	protected function sort(Item $a, Item $b): int
	{
		if ($a->date('U') === $b->date('U')) {
			return 0;
		}

		return $a->date('U') < $b->date('U') ? 1 : -1;
	}

	/**
	 * @return int
	 */
	public function count(): int
	{
		return count($this->items);
	}

	/**
	 * @return bool
	 */
	public function hasExpired(): bool
	{
		return $this->expired;
	}

	/**
	 * @inheritdoc
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->items);
	}

	/**
	 *
	 */
	public function save(): void
	{
		if ($this->expired || $this->modified) {
			update_option($this->id('items'), $this->items);
			update_option($this->id('timeout'), time() + $this->expiration);
		}
	}

	/**
	 *
	 */
	protected function load(): void
	{
		$this->items = get_option($this->id('items'), []);

		$timeout = get_option($this->id('timeout'));
		if ($timeout === false || ($timeout !== false && $timeout < time())) {
			$this->expired = true;
		}
	}

	/**
	 * @param string $suffix
	 *
	 * @return string
	 */
	protected function id(string $suffix): string
	{
		if ($this->id === null) {
			$id = preg_replace('(^https?://)', '', $this->url);
			$id = rtrim(str_replace(['.', '/'], '_', $id), '_');

			$this->id = "ic_feed-$id";
		}

		return $this->id . '-' . $suffix;
	}

}
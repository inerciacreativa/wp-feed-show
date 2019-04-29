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
	 *
	 */
	protected function load(): void
	{
		$this->items = get_option($this->id('items'), []);
		if (!is_array($this->items)) {
			$this->items = [];
		}

		$timeout = get_option($this->id('timeout'));
		if ($timeout !== false && $timeout < time()) {
			$this->expired = true;
		}
	}

	/**
	 *
	 */
	public function save(): void
	{
		if ($this->expired || $this->modified) {
			$this->removeExpiredItems();

			update_option($this->id('items'), $this->items);
			update_option($this->id('timeout'), time() + $this->expiration);

			$this->expired  = false;
			$this->modified = false;
		}
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

		if (empty($link) || array_key_exists($link, $this->items)) {
			return false;
		}

		$this->items[$link] = new Item($converter->item());

		$this->sortByDateDesc();

		return $this->modified = true;
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
	public function isEmpty(): bool
	{
		return $this->count() < 1;
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

	/**
	 * @return bool
	 */
	protected function sortByDateDesc(): bool
	{
		if ($this->count() < 2) {
			return false;
		}

		$keys = array_keys($this->items);

		uasort($this->items, static function (Item $a, Item $b) {
			if ($a->date('U') === $b->date('U')) {
				return 0;
			}

			return $a->date('U') < $b->date('U') ? 1 : -1;
		});

		return $keys !== array_keys($this->items);
	}

	/**
	 * @return bool
	 */
	protected function removeExpiredItems(): bool
	{
		if ($this->isEmpty() || $this->count() <= $this->max) {
			return false;
		}

		while ($this->count() > $this->max) {
			array_pop($this->items);
		}

		return $this->modified = true;
	}

}
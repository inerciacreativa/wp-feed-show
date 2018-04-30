<?php

namespace ic\Plugin\FeedShow;

use ic\Framework\Plugin\Plugin;

/**
 * Class FeedShow
 *
 * @package ic\Plugin\FeedShow
 */
class FeedShow extends Plugin
{

	/**
	 * @inheritdoc
	 */
	public function initialize(): void
	{
		Widget::create($this);
	}

}
<?php

namespace ic\Plugin\FeedShow;

use ic\Plugin\FeedShow\Feed\Feed;
use ic\Framework\Plugin\PluginWidget;
use ic\Framework\Widget\WidgetForm;
use ic\Framework\Html\Tag;
use ic\Framework\Support\Arr;
use ic\Framework\Support\Template;

class Widget extends PluginWidget
{

	/**
	 * @inheritdoc
	 */
	public function description(): string
	{
		return __('Entries from any RSS or Atom feed.', $this->id());
	}

	/**
	 * @inheritdoc
	 *
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	protected function frontend(array $instance, Tag $widget, Tag $title): void
	{
		if (empty($instance['feed'])) {
			return;
		}

		try {
			$feed = Feed::fetch($instance['feed'], Arr::get($instance, 'items', 4), Arr::get($instance, 'cache', 1));
		} catch (\RuntimeException $exception) {
			return;
		}

		if ($instance['title_link'] && !empty($instance['title_url'])) {
			$title->content(Tag::a(['href' => $instance['title_url']], $title->content()), true);
		}

		$template = $instance['template_file'] . '.' . $instance['template_type'];
		$content  = Template::render($template, ['feed' => $feed], $this->plugin->getAbsolutePath());

		$widget->content($title);
		$widget->content($content);

		echo $widget;
	}

	/**
	 * @inheritdoc
	 */
	protected function sanitize(array $instance): array
	{
		$instance = array_merge([
			'feed'          => '',
			'title_link'    => false,
			'title_url'     => '',
			'items'         => 4,
			'cache'         => 1,
			'error'         => false,
			'template_type' => 'php',
			'template_file' => 'templates/feed-show',
		], $instance);

		$instance['feed'] = esc_url_raw(strip_tags($instance['feed']));

		if (!empty($instance['feed'])) {
			try {
				Feed::check($instance['feed']);
			} catch (\RuntimeException $exception) {
				$instance['error'] = $exception->getMessage();
			}
		}

		return $instance;
	}

	/**
	 * @inheritdoc
	 */
	protected function backend(array $instance, WidgetForm $form): array
	{
		$error = '';
		if (Arr::get($instance, 'error', false)) {
			$error = Tag::p(['class' => 'widget-error'], Tag::strong($instance['error']));
		}

		return [
			Tag::p($form->url('feed', '', [
				'label' => __('RSS feed URL', $this->id()),
				'class' => 'widefat',
			])),
			$error,
			Tag::p($form->checkbox('title_link', 1, 0, ['label' => __('Make the title a link', $this->id())])),
			Tag::p($form->url('title_url', '', [
				'label' => __('Link for title', $this->id()),
				'class' => 'widefat',
			])),
			Tag::p($form->number('items', 5, [
				'label' => __('Items to display', $this->id()),
				'min'   => 1,
				'max'   => 20,
				'class' => 'tiny-text',
			])),
			Tag::p($form->number('cache', 4, [
				'label' => __('Time to cache', $this->id()),
				'min'   => 0,
				'max'   => 24,
				'class' => 'tiny-text',
			])),
			Tag::fieldset([], [
				Tag::legend('Template'),
				Tag::p($form->choices('template_type', Template::types())),
				Tag::p($form->text('template_file', 'templates/feed-show', [
					'label' => __('Filename', $this->id()),
					'class' => 'widefat',
				])),
			]),
		];
	}

}
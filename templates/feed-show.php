<?php
/** @var \ic\Plugin\FeedShow\Feed\Feed $feed */
/** @var \ic\Plugin\FeedShow\Feed\Item $item */
foreach ($feed as $item):
	?>

    <div class="feed-item">
        <a class="feed-link" href="<?php echo $item->link(); ?>">
			<?php if ($item->has_image()): ?>
                <span class="feed-image">
                    <?php echo $item->image(); ?>
                </span>
			<?php endif; ?>
            <h3 class="feed-title"><?php echo $item->title(); ?></h3>
        </a>
        <div class="feed-summary">
			<?php echo $item->excerpt(); ?>
        </div>
    </div>

<?php
endforeach;
?>
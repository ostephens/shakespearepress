<?php
/**
 * @package WordPress
 * @subpackage Clean Home SP
 */
?>
	<div id="sidebar">
	<?php if ( !dynamic_sidebar( 'primary-widget-area' ) ) : ?>

		<div class="block">
			<h3><?php _e( 'Tags', 'cleanhome-sp' ); ?></h3>
				<ul>
					<?php wp_tag_cloud(); ?>
				</ul>
		</div>
		
	<?php endif; ?>
	</div>
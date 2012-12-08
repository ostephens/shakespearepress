<?php
/**
 * @package WordPress
 * @subpackage Clean Home SP
 */
?>
<?php get_header(); ?>

	<div class="content">
	
		<?php
		/**
		 * Modified to always display posts in the context of Acts and Scenes
		 *
		 * First check if there is a 'acts' context, and either read them into an array, or assume all if no context already set
		 * Secondly do the same for 'scenes' context
		 */
		// Setup arrays to hold act and scene tags
		$acts = array();
		$scenes = array();
		$other_tags = array();
		// First check if there are any tags in query otherwise get all tags
		if (strlen(get_query_var('tag')) > 0) {
			$tags = preg_split("/,/",get_query_var('tag'));
			// Find all the tags that start 'acts'
		} else {
			$tags = get_tags();
		}
		// Now populate acts and scenes array
		foreach ($tags as $tag) {
			if (substr($tag, 0, 4) === 'act-') {
				array_push($acts,$tag);
			} elseif (substr($tag, 0, 4) === 'scene-') {
				array_push($scenes,$tag);
			} else {
				array_push($other_tags,$tag);
			}
		}
		$other_tags_string = implode ("+", $other_tags);
		// Make sure acts and scenes in right order
		asort($acts);
		asort($scenes);
		/**
		 * Remove tags from the query - we'll set these back later
		 *
		 */
		set_query_var('tag','');

		/**
		 * Run through each act in context, so content relevant to each scene is grouped together
		 */
		$total_acts = 0; //set a counter for total number of acts

		foreach ($acts as $act) {
			$act_postcount = 0;
			$actnumber = get_term_by('slug', $act, 'tag');
			foreach ($scenes as $scene) :
				$scenenumber = get_term_by('slug', $scene, 'tag');
				$posts = query_posts($query_string . '&order=asc' . '&orderby=ID' . '&tag=' . $act . '&tag=' . $scene . '&tag=' . $other_tags_string );
				$act_postcount += $wp_query->post_count; //Add number of posts retrieved to cumulative count for casestudy
				wp_reset_query();
		}
		if ($act_postcount === 0 ) {
			continue;
		} else {
			$total_acts++;
		}
		echo '<div id="'.$act.'" class="act-container">';
		echo '<h2 class="act-number">'.$actnumber->name.'</h2>';
		/**
		 * Run through each scene in context, so content relevant to each act is grouped together
		 */
		foreach ($scenes as $scene) :
			$scenenumber = get_term_by('slug', $scene, 'tag');
		/**
		 * Build the query to retrieve posts relevant to current act and scene, 
		 * preserving all other existing variables from query_string
		 * Order ascending by post ID to get posts content in correct order
		 */

		$posts = query_posts($query_string . '&order=asc' . '&orderby=ID' . '&tag=' . $act . '&tag=' . $scene . '&tag=' . $other_tags_string );
		?>
		
		<?php if ( have_posts() ) : ?>
			<div class="scene-container">
			<div <?php post_class(); ?> id="post-<?php the_ID(); ?>">
			<h3 class="scene-title"><?php echo $scenenumber->name ?></h1>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php the_content( __('Read the rest of this entry &raquo;', 'cleanhome') ); ?>
				<?php /*
				<small class="post-meta"><span class="post-date"><b><?php _e( 'Posted:', 'cleanhome' ); ?></b> <?php the_time( get_option( 'date_format' ) ); ?></span> <span class="author-link">| <b><?php _e( 'Author:', 'cleanhome' ); ?></b> <?php the_author_posts_link(); ?></span> <span class="meta-sep"><?php _e( '|', 'cleanhome'); ?></span> <span class="cat-links"><b><?php _e( 'Filed under:', 'cleanhome' ); ?></b> <?php the_category( ', ' ); ?></span> <span class="tag-links"><?php the_tags( ' | <b>Tags:</b> ', ', ', '' ); ?></span> <span class="edit-link"><?php edit_post_link( __( 'Edit this', 'cleanhome' ), ' |  <b>Modify:</b> ' ); ?></span> <span class="meta-sep"><?php _e( '|', 'cleanhome'); ?></span> <span class="comments-link"><?php comments_popup_link( __( 'Leave a comment &#187;', 'cleanhome' ), __( '<strong>1</strong> Comment &#187;', 'cleanhome' ), __( '<strong>%</strong> Comments &#187;', 'cleanhome' ) ); ?></span></small>
				*/ ?>
				<?php wp_link_pages( array( 'before' => '<p>' . __('Page:', 'cleanhome') .' ', 'after' => '</p>', 'next_or_number' => 'number' ) ); ?>
			<?php endwhile; ?>
			</div>
			</div>

			<div class="navigation">
				<div class="alignleft"><?php next_posts_link( __( '&larr; Older Entries', 'cleanhome' ) ); ?></div>
				<div class="alignright"><?php previous_posts_link( __( 'Newer Entries &rarr;', 'cleanhome' ) ); ?></div>
			</div>

		<?php else : ?>
		<?php endif; ?>
		<?php
		wp_reset_query();
	endforeach;
	echo '</div>'; //close act-container div
endforeach;
if ($total_ucs === 0) {
	?>
	<h2 class="center"><?php _e( 'Not found', 'cleanhome' ); ?></h2>
	<p class="center"><?php _e( "Sorry, but you are looking for something that isn't here.", 'cleanhome' ); ?></p>
	<?php 
}
$posts = query_posts($query_string);
?>

</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>

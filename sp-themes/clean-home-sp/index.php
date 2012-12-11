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
		$all_acts = array();
		$all_scenes = array();
		$all_other_tags = array();
		$current_acts = array();
		$current_scenes = array();
		$current_other_tags = array();
		$current_tags = array();
		// First check if there are any tags in query otherwise get all tags
		if (strlen(get_query_var('tag')) > 0) {
			$tag_slugs = preg_split("/\+/",get_query_var('tag'));
			foreach($tag_slugs as $slug){
				array_push($current_tags,get_term_by('slug', $slug, 'post_tag'));
			}
		}
		foreach ($current_tags as $t) {
			if (substr($t->slug, 0, 4) === 'act-') {
				array_push($current_acts,$t);
			} elseif (substr($t->slug, 0, 6) === 'scene-') {
				array_push($current_scenes,$t);
			} else {
				array_push($current_other_tags,$t);
			}
		}
		$all_tags = get_tags();
		// Now populate acts and scenes array
		foreach ($all_tags as $t) {
			if (substr($t->slug, 0, 4) === 'act-') {
				array_push($all_acts,$t);
			} elseif (substr($t->slug, 0, 6) === 'scene-') {
				array_push($all_scenes,$t);
			} else {
				array_push($all_other_tags,$t);
			}
		}
		if (count($current_acts) > 0) {
			$acts = $current_acts;
		} else {
			$acts = $all_acts;
		}
		if (count($current_scenes) > 0) {
			$scenes = $current_scenes;
		} else {
			$scenes = $all_scenes;
		}
		$other_tags = '';
		if (count($current_other_tags) > 0) {
			foreach($current_other_tags as $t) {
				$other_tags .= '+'.$t->slug;
			}
		}
		/**
		 * Remove tags from the query - we'll set these back later
		 *
		 */
		//set_query_var('tag','');

		/**
		 * Run through each act in context, so content relevant to each scene is grouped together
		 */
		$total_acts = 0; //set a counter for total number of acts

		foreach ($acts as $act) {
			$act_postcount = 0;
			$actnumber = $act->name;
			$acttag = $act->slug;
			foreach ($scenes as $scene) {
				$scenenumber = $scene->name;
				$scenetag = $scene->slug;
				$posts = query_posts($query_string . '&order=asc' . '&orderby=ID' . '&tag=' . $acttag . '+' . $scenetag  . $other_tags . '&posts_per_page=-1' );
				$act_postcount += $wp_query->post_count; //Add number of posts retrieved to cumulative count for casestudy
				wp_reset_query();
			}
			if ($act_postcount === 0 ) {
				continue;
			} else {
				$total_acts++;
			}
			echo '<div id="'.$acttag.'" class="act-container">';
			//echo '<h2 class="act-number">'.$actnumber.'</h2>';
			/**
			 * Run through each scene in context, so content relevant to each act is grouped together
			 */
			foreach ($scenes as $scene) {
				$scenenumber = $scene->name;
				$scenetag = $scene->slug;
				/**
				 * Build the query to retrieve posts relevant to current act and scene, 
				 * preserving all other existing variables from query_string
				 * Order ascending by post ID to get posts content in correct order
				 */

				$posts = query_posts($query_string . '&order=asc' . '&orderby=ID' . '&tag=' . $acttag . '+' . $scenetag . $other_tags . '&posts_per_page=-1');
				?>
		
				<?php if ( have_posts() ) : ?>
					<div id="<?php echo $scenetag ?>" class="scene-container">
					<div <?php post_class(); ?> id="post-<?php the_ID(); ?>">
					<h3 class="scene-title"><?php echo $actnumber.": ".$scenenumber ?></h1>
					<?php while ( have_posts() ) : the_post(); ?>
						<?php the_content( __('Read the rest of this entry &raquo;', 'cleanhome') ); ?>
						<div class="post-meta"><span class="edit-link"><?php edit_post_link( __( 'Edit this', 'cleanhome' ), ' |  <b>Modify:</b> ' ); ?></span> <span class="meta-sep"><?php _e( '|', 'cleanhome'); ?></span> <span class="comments-link"><?php comments_popup_link( __( 'Comment/Annotate &#187;', 'cleanhome' ), __( '<strong>1</strong> Comment &#187;', 'cleanhome' ), __( '<strong>%</strong> Comments &#187;', 'cleanhome' ) ); ?></span></div>
						<?php /* wp_link_pages( array( 'before' => '<p>' . __('Page:', 'cleanhome') .' ', 'after' => '</p>', 'next_or_number' => 'number' ) ); */ ?>
					<?php endwhile; ?>
					</div>
					</div>
				<?php else : ?>
				<?php endif; ?>
				<?php
				wp_reset_query();
			}
	echo '</div>'; //close act-container div
}
if ($total_acts === 0) {
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

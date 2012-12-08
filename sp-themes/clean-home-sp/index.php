<?php
/**
 * @package WordPress
 * @subpackage Clean Home
 */
?>
<?php get_header(); ?>

	<div class="content">
	
		<?php
		/**
		 * Modified to always display posts in the context of Case Studies and Sections for the Discovery Case Studies
		 *
		 * First check if there is a 'casestudies' context, and either read them into an array, or assume all if no context already set
		 * Secondly do the same for 'section' context
		 */

			if (strlen(get_query_var('casestudies')) > 0) {
				$ucs = preg_split("/,/",get_query_var('casestudies')); 
			}
			else {
				$ucs = array('archives-hub','aim25','bluk','cul','oclcwc','oup','rijksmuseum','usussex','vanda','wellcome'); 
			}	
			if (strlen(get_query_var('sections')) > 0) {
				$sections = preg_split("/,/",get_query_var('sections'));
			}
			else {
				$sections = array('context','drivers','open-licensing','terms','models','identifiers','relationships','reuse','clear-apis','data-formats','sustainable','supported','use-apis','measure','lessons-learned','future');
			}
		/**
		 * Remove section and casestudies variables from the query - we'll set these back later
		 *
		 */
			set_query_var('sections','');
			set_query_var('casestudies','');

		/**
		 * Run through each casestudies in context, so content relevant to each usecase is grouped together
		 */
		$total_ucs = 0; //set a counter for total number of case studies retrieved

			foreach ($ucs as $uc) :
				$uc_postcount = 0;
				$ucterm = get_term_by('slug', $uc, 'casestudies');
				foreach ($sections as $section) :
					$sectionterm = get_term_by('slug', $section, 'sections');
					$posts = query_posts($query_string . '&order=asc' . '&orderby=ID' . '&casestudies=' . $uc . '&sections=' . $section );
					$uc_postcount += $wp_query->post_count; //Add number of posts retrieved to cumulative count for casestudy
					wp_reset_query();
				endforeach;
				if ($uc_postcount === 0 ) {
					continue;
				} else {
					$total_ucs++;
				}
				echo '<div id="'.$uc.'" class="usecase-container">';
				echo '<h2 class="usecase-title">'.$ucterm->name.'</h2>';
				if ( ! empty( $ucterm->description ) ) {
					echo '<div class="usecase-description">' . $ucterm->description . '</div>';
				}
			/**
			 * Run through each section in context, so content relevant to each usecase is grouped together
			 */
				foreach ($sections as $section) :
					$sectionterm = get_term_by('slug', $section, 'sections');
			/**
			 * Build the query to retrieve posts relevant to current usecase and section, 
			 * preserving all other existing variables from query_string
			 * Order ascending by post ID to get subsection content in correct order
			 */

					$posts = query_posts($query_string . '&order=asc' . '&orderby=ID' . '&casestudies=' . $uc . '&sections=' . $section );
					?>
					
					<?php if ( have_posts() ) : ?>
						<div class="section-container">
						<div <?php post_class(); ?> id="post-<?php the_ID(); ?>">
						<h1 class="section-title"><?php echo $sectionterm->name ?></h1>
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
				echo '</div>'; //close usecase-container div
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

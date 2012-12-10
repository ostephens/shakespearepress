<?php
/*
 * Functions for the ShakespearePress plugin
 * 
 */

/*
 * TO DO: Add pages per character
 */
function createShakespeare() {
	$user_name = 'wshakespeare';
	$user_id = username_exists( $user_name );
	if ( !$user_id ) {
		$random_password = wp_generate_password( $length=8, $include_standard_special_chars=false );
		$user_id = wp_create_user( $user_name, $random_password );
		wp_update_user( array ('ID' => $user_id, 'user_firstname' => 'William', 'user_lastname' => 'Shakespeare'));
	} 
	
}

function detailsofPlay() {
	$play_url = get_option('shakespearepress-playurl');
	if ($play_url['url']) {
		if( !class_exists( 'WP_Http' ) ) {
		    include_once( ABSPATH . WPINC. '/class-http.php' );
		}
		$request = new WP_Http;
		$result = $request->request( $play_url['url'] );
		$xml = $result['body'];
		// Create DOMDocument and load the xml to parse
		$doc = new DOMDocument();
		$doc->loadXML($xml);

		// Create DOMXPath object so we can use XPath queries
		$xpath = new DOMXPath($doc);
		$xpath->registerNameSpace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$xpath_playname = "//Play/@name";
		$play_name = $xpath->evaluate($xpath_playname,$doc)->item(0)->nodeValue;
		$acts = $doc->getElementsByTagName("Act");
		$total_acts = $acts->length;
		$play_opts = array( 'name'=>$play_name, 'total_acts'=>$total_acts );
		return $play_opts;
	} else {
		return false;
	}
	
}

function populatePlay($act) {
	$play_url = get_option('shakespearepress-playurl');
	if( !class_exists( 'WP_Http' ) ) {
	    include_once( ABSPATH . WPINC. '/class-http.php' );
	}
	$request = new WP_Http;
	$result = $request->request( $play_url );
	$xml = $result['body'];
	// Create DOMDocument and load the xml to parse
	$doc = new DOMDocument();
	$doc->loadXML($xml);

	// Create DOMXPath object so we can use XPath queries
	$xpath = new DOMXPath($doc);
	$xpath->registerNameSpace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');
	$acts = $doc->getElementsByTagName("Act");

	$xpath_scene = "./Scene";
	$xpath_paras = "./Paragraphs";
	$xpath_character = "./CharID/text()";
	$xpath_paranum = "./ParagraphNum/text()";
	$xpath_text = "./PlainText/Line/text()";

	foreach($acts as $act) {
		$act_no = $act->attributes->getNamedItem("number")->value;
		if ($act_no <> $act) { 
			continue;
		}
		$scenes = $xpath->evaluate($xpath_scene,$act);
		foreach($scenes as $scene) {
			$scene_no = $scene->attributes->getNamedItem("number")->value;
			$paras = $xpath->evaluate($xpath_paras,$scene);
			foreach($paras as $para) {
				$content = "";
				if ($xpath->evaluate($xpath_character,$para)->item(0)->nodeValue != "xxx") {
 					$speaking = strtoupper($xpath->evaluate($xpath_character,$para)->item(0)->nodeValue);
					$content .= '<div itemscope itemtype="http://schema.org/Person">';
					$content .= '<strong><span itemprop="name">'.$speaking.'</span></strong>';
					$content .= '</div>';
				} else {
					$speaking = "";
				}
				$para_no = $xpath->evaluate($xpath_paranum,$para)->item(0)->nodeValue;
				$lines = $xpath->evaluate($xpath_text,$para);
				$content .= "<p>";
				foreach($lines as $line) {
					$content .= $line->nodeValue."<br />";
				}
				$content .= "</p>";
				$title = "Act ".$act_no.", Scene ".$scene_no.", Paragraph ".$para_no;
				$name = $act_no."-".$scene_no."-".$para_no;
				postPara($title,$name,$content,$act_no,$scene_no,$speaking);
			}
		}
	}	

}

function postPara($title,$name,$content,$act_no,$scene_no,$speaking) {
	set_time_limit(0);
	$author = username_exists( 'wshakespeare' );
	$new_post = array(
			'post_title' => $title,
			'post_content' => convert_chars($content),
			'post_name' => $name,
			'post_author' => $author,
			'post_status' => 'publish',
			'tags_input'      => array("Act ".$act_no,"Scene ".$scene_no, $speaking)
		    //Default field values will do for the rest - so we don't need to worry about these - see
			//http://codex.wordpress.org/Function_Reference/wp_insert_post
	);
	
	$post_id = wp_insert_post($new_post);

	if (is_object($post_id)) {
		//error - what to do?
		return false;
	}
	elseif ($post_id == 0) {
		//error - what to do?
		return false;
	}
	else {
		//add custom fields here if required e.g.
		//add_post_meta($post_id, 'object_title', $title);
	}
	return $post_id;
}

function createCharacterpage($character = "BEATRICE") {
	$author = username_exists( 'wshakespeare' );
	$name = strtolower($character);
	$title = ucfirst($name);
	$content = "";
	// Get content from Designing Shakespeare
	$dscontent = dsData($name); // Do we need more than name? Play?
	$content .= $dscontent;
	// Get content from Will's World Registry
	$wwcontent = wwData($name); // Do we need more than name? Play?
	//$content .= $wwcontent;
	$new_post = array(
			'post_title' => $title,
			'post_content' => convert_chars($content),
			'post_name' => $name,
			'post_author' => $author,
			'post_status' => 'publish',
			'post_type' => 'page'
		    //Default field values will do for the rest - so we don't need to worry about these - see
			//http://codex.wordpress.org/Function_Reference/wp_insert_post
	);
	
	$post_id = wp_insert_post($new_post);

	if (is_object($post_id)) {
		//error - what to do?
		return false;
	}
	elseif ($post_id == 0) {
		//error - what to do?
		return false;
	}
	else {
		//add custom fields here if required e.g.
		//add_post_meta($post_id, 'object_title', $title);
	}
	return $post_id;
}

// Function to retrieve scraped data from Designing Shakespeare database
function dsData($name){
	//Going to return some html at the end
	$html = "";
	if( !class_exists( 'WP_Http' ) ) {
	    include_once( ABSPATH . WPINC. '/class-http.php' );
	}
	$request = new WP_Http;
	$ds_url = "https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=jsondict&name=designing_shakespeare_cast_lists&query=select%20*%20from%20%60swdata%60%20where%20%60role%60%20like%20%22%25". $name ."%25%22";
	$result = $request->request( $ds_url );
	$json = $result['body'];
	$performances = json_decode($json);
	$html .= "<div id='design'>";
	$html .= ucfirst($name)." has been played by the actors listed below. The data is taken from the 'Designing Shakespeare' project which focuses on performances in London, UK. Details of the relevant performance are given here, and linked to the full record at the Designing Shakespeare site which usually includes images of the production.";
	$html .= "<ul>";
	foreach($performances as $performance) {
		$html .= "<li>".$performance->actor." (<a href='".$performance->performance_uri."'>".$performance->performance."</a>)</li>";
	}
	$html .= "</ul>";
	$html .= "</div>";
	return $html;
}

function wwData($name) {
	//Going to return some html at the end
	$html = "";
	if( !class_exists( 'WP_Http' ) ) {
	    include_once( ABSPATH . WPINC. '/class-http.php' );
	}
	$request = new WP_Http;
	$ww_url = "";
}

function shakespearepress_plugin_menu() {
	add_options_page('ShakespearePress settings page', 'ShakespearePress settings', 'manage_options', __FILE__, 'shakespearepress_settings_page');
}

function shakespearepress_register_settings() {
	// first option will contain play URL
	register_setting('shakespearepress-settings', 'shakespearepress-playurl');
	// second option will contain name of the play and the number of acts
	register_setting('shakespearepress-settings', 'shakespearepress-playoptions');
	// third option will contain an array of charater names
	register_setting('shakespearepress-settings', 'shakespearepress-characters');
	
	add_settings_section('shakespearepress-play', 'Import Play', 'shakespearepress_playsection_text', 'shakespearepress');
	// Need to work out fields below - play, acts, chars - just need enough to display Next buttons
	add_settings_field('playurl', 'Play to import', 'play_choice', 'shakespearepress', 'shakespearepress-play');
	add_settings_field('playname', 'Play name', 'play_name', 'shakespearepress', 'shakespearepress-play');
	add_settings_field('totalacts', 'Number of Acts', 'total_acts', 'shakespearepress', 'shakespearepress-play');
	add_settings_field('currentact', 'Current Act', 'current_act', 'shakespearepress', 'shakespearepress-play');
}

function shakespearepress_playsection_text(){
	//Just HTML to go at top of options page
	echo '<p>The play\'s the thing</p>';
}

function play_choice(){
	// Only do output here if play url not already populated in settings
	// Need to check naming here and 
	$play_url = get_option('shakespearepress-playurl');
	if (strlen($play_url['url']) > 0) {
		//may need to put hidden field in form?
		echo "Already selected play ".$play_url['url'];
		echo "<input type=\"hidden\" name=\"shakespearepress-playurl[url]\" value=\"{$play_url['url']}\">";
	} else {
		?>
		<select name="shakespearepress-playurl[url]">
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Much_Ado_about_Nothing.xml">Much Ado About Nothing</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/static/plays/King_Lear.xml">King Lear</option>
		</select>
		<?php		
	}
}
function play_name() {
	
	// If play_url is set, we can retrieve the play_name and total_acts from the xml
	// So here check for play_name - if doesn't exist then grab the xml and get the play name
	// If play_url not set, do nothing
	$play_url_opts = get_option('shakespearepress-playurl');
	$play_options = get_option('shakespearepress-playoptions');
	if (strlen($play_options['name']) > 0) {
		$play_name = $play_options['name'];
	} elseif ($play_url_opts['url']) {
		$play_options = detailsofPlay();
		// add_option('shakespearepress-playoptions',$play_options);
		$play_name = $play_options['name'];
	} else {
		// Do nothing
	}
	echo $play_name;
	echo "<input type=\"hidden\" name=\"shakespearepress-playoptions[name]\" value=\"{$play_name}\">";
}
function total_acts(){
	// Check for total_acts - if doesn't exist then grab the xml and get the total acts. Should have already been set by play_name?
	// if play_url not set, do nothing
	$play_url_opts = get_option('shakespearepress-playurl');
	$play_options = get_option('shakespearepress-playoptions');
	if ($play_options['total_acts']) {
		$total_acts = $play_options['total_acts'];
	} elseif ($play_url_opts['url']) {
		$play_options = detailsofPlay();
		$total_acts = $play_options['total_acts'];
	} else {
		// Do nothing
	}
	echo "This play has ".$total_acts." Acts";
	echo "<input type=\"hidden\" name=\"shakespearepress-playoptions[total_acts]\" value=\"{$total_acts}\">";
}

function current_act() {
	$play_options = get_option('shakespearepress-playoptions');
	if($play_options['current_act'] == 0 && strlen($play_url['url']) > 0 || !$play_options['current_act']) {
		// fetch first act and add one
		$current_act = 1;
		populatePlay($current_act);
		echo "Fetched Act ".$current_act;
		echo "Click Next to fetch ".$current_act + 1;
	} elseif ($play_options['current_act'] < $play_options['total_acts']) {
		$current_act = $play_options['current_act'] + 1;
		populatePlay($current_act);
		echo "Fetched Act ".$current_act;
		echo "Click Next to fetch ".$current_act + 1;
	} else {
		echo "All Acts have been retrieved";
		$current_act = $play_options['current_act'];
	}
	echo "<input type=\"hidden\" name=\"shakespearepress-playoptions[current_act]\" value=\"{$current_act}\">";
}

// write out the plugin options form. Form field name must match option name.
// add other options here as necessary
// just a placeholder in case

function shakespearepress_settings_page() {
  
	if (!current_user_can('manage_options'))  {
	  wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	// Get settings so we know what has been set and only offer to set things not yet done
/*
	if( isset($_POST[ 'playurl' ]) ) {
		// suppress if play already set?
		populatePlay($_POST[ 'playurl' ]);
	}

	if( isset($_GET[ 'createCPs' ])) {
		createCharacterPage();
	}
*/	
	?>
	<div>
		<h2><?php _e('ShakespearePress setup', 'shakespearepress-plugin') ?></h2>
		
		<form action="options.php" method="post">
			<?php settings_fields('shakespearepress-settings'); ?>
			<?php do_settings_sections('shakespearepress'); ?>
			<p class="submit"><input name="Submit" type="submit" value="<?php esc_attr_e('Next'); ?>" />
		</form>
	</div>
	<?php
}

?>
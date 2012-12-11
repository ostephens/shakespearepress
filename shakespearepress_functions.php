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

function detailsofCharacters() {
	$char_array = array();
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
		$chars = $doc->getElementsByTagName("CharID");
		foreach ($chars as $char) {
			if($char->nodeValue != "xxx") {
				array_push($char_array,strtolower($char->nodeValue));
			}
		}
		$char_array = array_unique($char_array);
		return $char_array;
	} else {
		return false;
	}
	
}

function populatePlay($a) {
	set_time_limit(0);
	$play_url = get_option('shakespearepress-playurl');
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
	$acts = $doc->getElementsByTagName("Act");

	$xpath_scene = "./Scene";
	$xpath_paras = "./Paragraphs";
	$xpath_character = "./CharID/text()";
	$xpath_paranum = "./ParagraphNum/text()";
	$xpath_text = "./PlainText/Line/text()";

	foreach($acts as $act) {
		$act_no = $act->attributes->getNamedItem("number")->value;
		if ((int)$act_no <> (int)$a) { 
			error_log('continuing');
			continue;
		}
		
		$scenes = $xpath->evaluate($xpath_scene,$act);
		foreach($scenes as $scene) {
			$scene_no = $scene->attributes->getNamedItem("number")->value;
			$paras = $xpath->evaluate($xpath_paras,$scene);
			foreach($paras as $para) {
				$content = "";
				$para_no = $xpath->evaluate($xpath_paranum,$para)->item(0)->nodeValue;
				$title = "Act ".$act_no.", Scene ".$scene_no.", Paragraph ".$para_no;
				$name = $act_no."-".$scene_no."-".$para_no;
				$check_args=array(
				  'name' => $name,
				  'post_type' => 'post',
				  'post_status' => 'publish',
				  'numberposts' => 1
				);
				$check = get_posts($check_args);
				if( $check ) {
					continue;
				}
				if ($xpath->evaluate($xpath_character,$para)->item(0)->nodeValue != "xxx") {
 					$speaking_arr = explode("-",strtoupper($xpath->evaluate($xpath_character,$para)->item(0)->nodeValue));
					$speaking = $speaking_arr[0];
					$content .= '<div itemscope itemtype="http://schema.org/Person">';
					$content .= '<strong><span itemprop="name">'.$speaking.'</span></strong>';
					$content .= '</div>';
				} else {
					$speaking = "";
				}
				$lines = $xpath->evaluate($xpath_text,$para);
				$content .= "<p>";
				foreach($lines as $line) {
					$content .= $line->nodeValue."<br />";
				}
				$content .= "</p>";
				error_log($name);
				postPara($title,$name,$content,$act_no,$scene_no,$speaking);
			}
		}
	}	

}

function postPara($title,$name,$content,$act_no,$scene_no,$speaking) {
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
	if (get_page_by_title($title) <> NULL) return;
	$content = "";
	// Get WW Registry data
	$wwcontent = wwData($name);
	$content .= $wwcontent;
	// Get content from Designing Shakespeare
	$dscontent = dsData($name); // Do we need more than name? Play?
	$content .= $dscontent;
	if ($wwcontent || $dscontent) {
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
	return;
}

// Function to retrieve scraped data from Designing Shakespeare database
function dsData($name){
	//Going to return some html at the end
	$html = "";
	if( !class_exists( 'WP_Http' ) ) {
	    include_once( ABSPATH . WPINC. '/class-http.php' );
	}
	$request = new WP_Http;
	$ds_query = urlencode($name);
	$ds_url = "https://api.scraperwiki.com/api/1.0/datastore/sqlite?format=jsondict&name=designing_shakespeare_cast_lists&query=select%20*%20from%20%60swdata%60%20where%20%60role%60%20like%20%22%25". $ds_query ."%25%22";
	$result = $request->request( $ds_url );
	$json = $result['body'];
	$performances = json_decode($json);
	if(count($performances) == 0) {
		return false;
	}
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
	$play = get_option('shakespearepress-playoptions');
	$play_name = $play['name'];
	if( !class_exists( 'WP_Http' ) ) {
	    include_once( ABSPATH . WPINC. '/class-http.php' );
	}
	$request = new WP_Http;
	$ww_query = urlencode($name." AND \"".$play_name."\"");
	$ww_url = "http://wwsrv.edina.ac.uk/solr/metadata/select?q=".$ww_query."&wt=phps";
	$result = $request->request( $ww_url );
	$result_array = unserialize($result['body']);
	$num_results = $result_array["response"]["numFound"];
	if ($num_results == 0) {
		// No results
		return false;
	} else {
		$html .= "<div id='ww'>";
		$html .= "<p>The following resources have been found for this character in the Will's World Registry</p>";
		$items = $result_array["response"]["docs"];
		foreach ($items as $item) {
			error_log("Looping through WWR results");
			$title = html_entity_decode($item["dc.title"][0]);
			$desc = html_entity_decode($item["dc.description"][0]);
			$source = html_entity_decode($item["dc.source"][0]);
			$html .= "<p><strong>Title</strong>: <a href=\"".$source."\">".$title."</a> (Click to view)"."<br /><strong>Description</strong>: ".$desc."<br />";
			$credits = $item["ww.credit"];
			$html .= "(credits: ";
			foreach ($credits as $credit) {
				$html .= html_entity_decode($credit).", ";
			}
			$html = trim($html,", ");
			$html .= ")</p>";
		}
		$html .= "</div>";
	}
	return $html;
}

function shakespearepress_plugin_menu() {
	add_options_page('ShakespearePress settings page', 'ShakespearePress settings', 'manage_options', __FILE__, 'shakespearepress_settings_page');
}

function shakespearepress_register_settings() {
	// first option will contain play URL
	register_setting('shakespearepress-settings', 'shakespearepress-playurl');
	// second option will contain name of the play and the number of acts
	register_setting('shakespearepress-settings', 'shakespearepress-playoptions');
	
	add_settings_section('shakespearepress-play', 'Import Play', 'shakespearepress_playsection_text', 'shakespearepress');
	
	add_settings_field('playurl', 'Play to import', 'play_choice', 'shakespearepress', 'shakespearepress-play');
	add_settings_field('playname', 'Play name', 'play_name', 'shakespearepress', 'shakespearepress-play');
	add_settings_field('totalacts', 'Number of Acts', 'total_acts', 'shakespearepress', 'shakespearepress-play');
	add_settings_field('currentact', 'Current Act', 'current_act', 'shakespearepress', 'shakespearepress-play');
	add_settings_field('chars', 'Characters in the play', 'all_characters', 'shakespearepress', 'shakespearepress-play');
}

function shakespearepress_playsection_text(){
	//Just HTML to go at top of options page
	echo '<p>The play\'s the thing</p>';
}

function shakespearepress_charsection_test(){
	echo '<p>The Cast of Players</p>';
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
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Alls_Well_That_Ends_Well.xml">All's Well That Ends Well</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Antony_and_Cleopatra.xml">Antony and Cleopatra</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/As_You_Like_It.xml">As You Like It</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Comedy_of_Errors.xml">Comedy of Errors</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Coriolanus.xml">Coriolanus</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Cymbeline.xml">Cymbeline</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Hamlet.xml">Hamlet</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Henry_IV_Part_I.xml">Henry IV, Part I</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Henry_IV_Part_II.xml">Henry IV, Part II</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Henry_V.xml">Henry V</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Henry_VIII.xml">Henry VIII</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Henry_VI_Part_I.xml">Henry VI, Part I</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Henry_VI_Part_II.xml">Henry VI, Part II</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Henry_VI_Part_III.xml">Henry VI, Part III</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Julius_Caesar.xml">Julius Caesar</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/King_John.xml">King John</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/King_Lear.xml">King Lear</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Lovers_Complaint.xml">Lover's Complaint</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Loves_Labours_Lost.xml">Love's Labour's Lost</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Macbeth.xml">Macbeth</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Measure_for_Measure.xml">Measure for Measure</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Merchant_of_Venice.xml">Merchant of Venice</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Merry_Wives_of_Windsor.xml">Merry Wives of Windsor</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Midsummer_Nights_Dream.xml">Midsummer Night's Dream</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Much_Ado_about_Nothing.xml">Much Ado about Nothing</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Othello.xml">Othello</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Passionate_Pilgrim.xml">Passionate Pilgrim</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Pericles.xml">Pericles</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Phoenix_and_the_Turtle.xml">Phoenix and the Turtle</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Rape_of_Lucrece.xml">Rape of Lucrece</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Richard_II.xml">Richard II</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Richard_III.xml">Richard III</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Romeo_and_Juliet.xml">Romeo and Juliet</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Sonnets.xml">Sonnets</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Taming_of_the_Shrew.xml">Taming of the Shrew</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Tempest.xml">Tempest</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/The_Winters_Tale.xml">The Winter's Tale</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Timon_of_Athens.xml">Timon of Athens</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Titus_Andronicus.xml">Titus Andronicus</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Troilus_and_Cressida.xml">Troilus and Cressida</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Twelfth_Night.xml">Twelfth Night</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Two_Gentlemen_of_Verona.xml">Two Gentlemen of Verona</option>
			<option value="http://wwsrv.edina.ac.uk/wworld/plays/Venus_and_Adonis.xml">Venus and Adonis</option>
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
		echo "Play not yet selected. Please click <strong>Next</strong>.";
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
		echo "Play not yet selected. Please click <strong>Next</strong>.";
		return;
	}
	echo "This play has ".$total_acts." Acts";
	echo "<input type=\"hidden\" name=\"shakespearepress-playoptions[total_acts]\" value=\"{$total_acts}\">";
}
function current_act() {
	$play_options = get_option('shakespearepress-playoptions');
	$play_url = get_option('shakespearepress-playurl');
	if($play_options['current_act'] == 0 && strlen($play_url['url']) > 0 || !$play_options['current_act'] && strlen($play_url['url']) > 0 ) {
		// fetch first act and add one
		$current_act = 1;
		$next_act = 1;
		echo "Click Next to fetch Act ".$next_act;
	} elseif ($play_options['current_act'] < $play_options['total_acts']) {
		$current_act = $play_options['current_act'] + 1;
		$next_act = $current_act + 1;
		populatePlay($current_act);
		echo "Fetched Act ".$current_act." Click Next to fetch Act ".$next_act;
	} elseif (strlen($play_url['url']) == 0) {
		echo "Play not yet selected. Please click <strong>Next</strong>.";
	} elseif (!$play_options['current_act']) {
		$current_act = 0;
		echo "No Acts retrieved yet";
	} else {
		echo "All Acts have been retrieved";
		$current_act = $play_options['current_act'];
	}
	echo "<input type=\"hidden\" name=\"shakespearepress-playoptions[current_act]\" value=\"{$current_act}\">";
}
function all_characters() {
	$play_url = get_option('shakespearepress-playurl');
	if (!$play_url) {
		echo "Play not yet selected. Please click <strong>Next</strong>.";
		return;
	}
	$play_options = get_option('shakespearepress-playoptions');
	if (!$play_options) {
		echo "Waiting to set options. Please click <strong>Next</strong>.";
		return;
	}
	$play_name = $play_options['name'];
	$characters = $play_options['characters'];
	$total_acts = $play_options['total_acts'];
	$current_acts = $play_options['current_acts'];
	if ($total_acts > 0 && $current_acts > 0 && $current_acts <> $total_acts) {
		"Waiting to populate play before generating character pages. Please click <strong>Next</strong>.";
		return;
	}
	$char_list = $characters;
	if(strlen($characters) == 0 && strlen($play_url['url']) > 0 && strlen($play_name) > 0 || !$characters && strlen($play_url['url']) > 0 && strlen($play_name) > 0) {
		$characters = detailsofCharacters();
		foreach($characters as $character) {
			createCharacterpage($character);
			$char_list .= $character.",";
		}
	} elseif (strlen($play_url['url']) == 0) {
		echo "Play not yet selected";
	}
	echo "Characters: ".$char_list;
	echo "<input type=\"hidden\" name=\"shakespearepress-playoptions[characters]\" value=\"{$char_list}\">";
}
// write out the plugin options form. Form field name must match option name.
// add other options here as necessary
// just a placeholder in case

function shakespearepress_settings_page() {
  
	if (!current_user_can('manage_options'))  {
	  wp_die( __('You do not have sufficient permissions to access this page.') );
	}
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
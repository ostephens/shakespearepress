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

function populatePlay($play_url = "http://wwsrv.edina.ac.uk/wworld/plays/Much_Ado_about_Nothing.xml") {
// Using default of Much ado about nothing from http://wwsrv.edina.ac.uk/wworld/plays/Much_Ado_about_Nothing.xml  
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
				if ($act_no <> 1) { 
					return;
				}
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
	$dscontent = dsData($name);
	$content .= $dscontent;
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
		$html .= "<li>".$performance->actor." (<a href='".$performance->play_uri."'>".$performance->performance."</a>)</li>";
	}
	$html .= "</ul>";
	$html .= "</div>";
	
}

function shakespearepress_plugin_menu() {
	add_options_page('Shakespeare Pres settings page', 'Shakespeare Press settings', 'manage_options', __FILE__, 'shakespearepress_settings_page');
}

function shakespearepress_register_settings() {
	// register settings - array, not individual
	register_setting('shakespearepress-settings-group', 'shakespearepress_settings_values');
}

// write out the plugin options form. Form field name must match option name.
// add other options here as necessary
// just a placeholder in case

function shakespearepress_settings_page() {
  
	if (!current_user_can('manage_options'))  {
	  wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	if( isset($_POST[ 'playurl' ]) ) {
		// suppress if play already set?
		populatePlay($_POST[ 'playurl' ]);
	}
	
	if( isset($_GET[ 'createCPs' ])) {
		createCharacterPage();
	}

	?>
	<div>
		<h2><?php _e('shakespearepress plugin options', 'shakespearepress-plugin') ?></h2>
		<form method="post" action="">
			<select name="playurl">
				<option value="http://wwsrv.edina.ac.uk/wworld/plays/Much_Ado_about_Nothing.xml">Much Ado About Nothing (Act 1 only)</option>
				<option value="http://wwsrv.edina.ac.uk/wworld/static/plays/King_Lear.xml">King Lear (Act 1 only)</option>
			</select>
			<p class="submit"><input type="submit" class="button-primary" value=<?php _e('Save changes', 'shakespearepress-plugin') ?> /></p>
		</form>
	</div>
	<?php
}

?>
<?php
/*
 * Functions for the ShakespearePress plugin
 * 
 */

/*
 * TO DO: create a post per paragraph in play
 */
function createShakespeare() {
	// Create a User ID for William Shakespeare;
}

function createCTax() {
	// Create custom taxonomies	
}

function populatePlay($play_url = "http://wwsrv.edina.ac.uk/wworld/plays/Much_Ado_about_Nothing.xml") {
// Using default of Much ado about nothing from http://wwsrv.edina.ac.uk/wworld/plays/Much_Ado_about_Nothing.xml  

	$xml = file_get_contents($play_url);
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
				$speaking = $xpath->evaluate($xpath_character,$para)->nodeValue;
				$content .= "<strong>".$speaking."</strong>";
				$para_no = $xpath->evaluate($xpath_paranum,$para)->nodeValue;
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

function postPara($title,$name,$content,$act_no,$scene_no,$speaking)
	$act_tag = "Act ".$act_no';
	$scene_tag = "Scene ".$scene_no;
	$speak_tag = $speaking;
	$tags = "'".$act_tag.",".$scene_tag.",".$speak_tag."'";
	$new_post = array(
			'post_title' => $title,
			'post_content' => convert_chars($content),
			'post_name' => $name,
			'tags_input'      => $tags
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
		add_post_meta($post_id, 'object_title', $title);
		// add_post_meta($post_id, 'object_maker', $maker);
		// add_post_meta($post_id, 'object_date', $date);
		add_post_meta($post_id, 'object_provider', $provider);
		//other custom fields here if required
	}
	return $post_id;
}

?>
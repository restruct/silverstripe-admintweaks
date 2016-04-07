<?php

class currentyear implements Shortcodable {
	
	public function singular_name(){
		return _t('MED.ReferringInfoShortcode','Actueel jaartal');
	}
	
	// Shortcode stuff
	public static function shortcode_attribute_fields(){
//		return FieldList::create(
//			DropdownField::create(
//				'Style', 
//				'Gallery Style', 
//				array('Carousel' => 'Carousel', 'Lightbox' => 'Lightbox')
//			)
//		);
	}
	
	// handles [year]
	public static function parse_shortcode($arguments, $content, $parser, $shortcode){
		return date("Y");
	}
	
}

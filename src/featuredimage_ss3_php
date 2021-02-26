<?php

class featuredimage implements Shortcodable {
	
	public function singular_name(){
		return _t('MED.FeaturedImageShortcode','Pagina afbeelding van huidige pagina invoegen');
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
	
	// handles [referringinfo]
	public static function parse_shortcode($arguments, $content, $parser, $shortcode){
		$ctrl = Controller::curr();
//		if($ctrl->FeaturedImageID && $ctrl->FeaturedImages()->first()->exists()){
		if($ctrl->FeaturedImage()){
			return '<div class="image">'.$ctrl->FeaturedImage()->CroppedImage(900,500)->forTemplate().'</div>';
		}
		
		return '';
	}
	
}

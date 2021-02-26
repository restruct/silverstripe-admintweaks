<?php

class GeneralHelpers {
	
	// safely get nested properties (returns null if not exists at some point)
	public static function safelyGetProperty($object, $prop_arr){
		// convert to array if string
		if(is_string($prop_arr)){ $prop_arr = explode('->', $prop_arr); }
		// get property to test for
		$prop = array_shift($prop_arr);
		// return null if not property exists
		if(!is_object($object) || !property_exists($object, $prop)) { return null; }
		// else, if final property, return value
		else if (count($prop_arr)==0){ return $object->{$prop}; }
		// else, call self recursively on existing property
		else { return self::safelyGetProperty($object->{$prop}, $prop_arr); }
	}
	
}

class TreeHelpers {
	
	/**
	 * Recursively add child objects to the tree.
	 * @param type $page
	 * @param type $options
	 * @param type $level
	 * @return type 
	 */
//	public static function compileTree($sourceObject = 'SiteTree', $keyField = 'ID', $labelField = 'Title') {
//		
//		try {
//			$obj = singleton($sourceObject);
//		} catch (ReflectionException $e) {
//			throw new InvalidArgumentException(sprintf('The \'%s\' class specified as the $sourceObject does not exist.', $sourceObject), $e->getCode(), $e);
//		}
//		
//		if (!$obj->hasExtension('Hierarchy')) throw new InvalidArgumentException (sprintf('The $sourceObject class (\'%s\') must implement the Hierarchy extension.', $sourceObject));
//		if (!$obj->hasField($keyField)) throw new InvalidArgumentException (sprintf('The %s class has no \'%s\' field.', $sourceObject, $keyField));
//		if (!$obj->hasField($labelField)) throw new InvalidArgumentException (sprintf('The %s class has no \'%s\' field.', $sourceObject, $labelField));
//		
//	}
	
//	public static function hierarchicalTree($pages, $level = 0) {
//		$treeArray = array();
//		foreach($pages as $page){
//			$treeArray["{$page->ID}"] = str_repeat(' ', $level) . '├ ' . $page->MenuTitle;
//			// recursively add children
////			$treeArray = array_merge($treeArray, self::hierarchicalTree($page->Children(), $level+1));
////				$options = $this->compile_source($child, $options, $level++);
//		}
//		return $treeArray;
////		foreach ($page->Children() as $child) {
////			if ( !$this->filterCallback || call_user_func($this->filterCallback, $child) ) {
////				$options[$child->{$this->keyField}] = str_repeat(' ', $level) . '├' . $child->{$this->labelField};
////				$options = $this->compile_source($child, $options, $level++);
////			}
////		}
////		return $options;
//	}
	
}

class DirectorTweaks extends Director {
	
	public static function forceNonWWW(){
		if(!Director::isDev() && !Director::isTest() && strpos($_SERVER['HTTP_HOST'], 'www') === 0) {
			$destURL = str_replace(Director::protocol() . 'www.', Director::protocol(), 
				Director::absoluteURL($_SERVER['REQUEST_URI']));

			self::force_redirect($destURL);
		}
	}
	
}
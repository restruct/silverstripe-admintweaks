<?php

// HTMLEDITOR CONFIG

// HTMLEDITOR CONFIG
// Also see: https://docs.silverstripe.org/en/3.2/developer_guides/forms/field_types/htmleditorfield/

// remove all cruft that may come with pasted content
HtmlEditorConfig::get('cms')
//	->disablePlugins('emotions')
//	->enablePlugins('spellchecker')
//	->enablePlugins('visualblocks')
	->setOptions(array(
		'theme_advanced_blockformats' => 'p,h2,h3,h4,h5',
		'paste_auto_cleanup_on_paste' => 'true', 
		'paste_remove_styles' => 'true', 
		'paste_strip_class_attributes' => 'all', 
		'paste_remove_spans' => 'true',
		'browser_spellcheck' => 'true',
		'inline_styles' => 'false',
	)
);

// Reconstruct editor toolbar with less buttons
HtmlEditorConfig::get('cms')->setButtonsForLine(1,
	'image, shortcodable, link, unlink, anchor, ',
	'bold, italic, removeformat, styleselect, formatselect, ',
	'bullist, numlist, outdent, indent, ',
	'blockquote, hr, visualchars, ', // undo & redo are built in browsers context menus now
	'code, fullscreen' 
	// pastetext, pasteword (not working with auto cleaning)
	// spellchecker (deprecated, use browser spellchecker instead (see right-click menu in textfield))
);
HtmlEditorConfig::get('cms')->setButtonsForLine(2, ''); //tablecontrols
HtmlEditorConfig::get('cms')->setButtonsForLine(3,''); // Delete line 3

//
// custom styles dropdown (prevent 'auto-discovered-in-editor.css' mess)
//

// you may pass styling for the class to appear in the dropdown
//$fontreset = array('font-weight' => 'normal', 'font-family' => 'Arial', 'font-size' => '12px', 'color' => 'black');
$fontreset = array();

$formats = array(
	// Define the styles that will be available in TinyMCE's dropdown style menu
	// * Use 'selector' to specify which elements a style can be applied to
	// * See Headings example below for explanation of different settings
	// * Using 'classes' allows a class to be combined with others while 'attributes'=>'style' removes other classes before applying
		
	// Headings
//	array(
//		'title' => 'Headings' // Set a title to be displayed above the following options in the dropdown menu
//	),
//	array(
//		// Applied to the current heading element (inline not allowed)
//		'title' => 'Ruled', // Title used in TinyMCE dropdown menu
//		'attributes' => array('class'=>'ruled'), // CSS class of .ruled will be applied to element, other classes on this element will be removed
//		'selector' => 'h1,h2,h3,h4,h5,h6' // Only allow class to be applied to these elements
//	),
//	array(
//		// Applied to selected text using a span tag, or applied to an inline element if no text is highlighted (inline allowed)
//		'title' => 'Sub title',
//		'classes' => 'sub-title', // CSS class of .sub-title will be toggled on or off, will not remove or affect other classes on this element
//		'inline' => 'span', // Allow applying this style inline to selected text by using a span tag
//		'selector' => 'i,em,b,strong,a' // If no text is selected but cursor is within one of these elements, apply class to element
//	),
	
	// Paragraphs
	array(
		'title' => 'Paragraphs'
	),
//	array(
//		'title' => 'Intro paragraph (bigger)',
//		'attributes' => array('class'=>'intro'),
//		'selector' => 'p',
//		'styles' => $fontreset,
//	),
	array(
		'title' => '24-hour cancelation policy',
		'attributes' => array('class'=>'hour24'),
		'selector' => 'p',
		'styles' => $fontreset,
	),
//	array(
//		'title' => '24-hour cancelation policy',
//		'attributes' => array('class'=>'hour24'),
//		'selector' => 'p',
//		'styles' => $fontreset,
//	),
	array(
		'title' => 'Location pin (white)',
		'attributes' => array('class'=>'locate'),
		'selector' => 'p',
		'styles' => $fontreset,
	),
	
	// Links/styling items
	array(
		'title' => 'Button links'
	),
	array(
		'title' => 'Button link (blue)',
		'attributes' => array('class'=>'blueBtn'),
		'selector' => 'a',
		'styles' => $fontreset,
	),
	array(
		'title' => 'Button link (green)',
		'attributes' => array('class'=>'greenBtn'),
		'selector' => 'a',
		'styles' => $fontreset,
	),
	array(
		'title' => 'Button link (grey)',
		'attributes' => array('class'=>'greyBtn'),
		'selector' => 'a',
		'styles' => $fontreset,
	),
	
	// Lists
	array(
		'title' => 'Lists'
	),
	array(
		'title' => 'Arrow list',
		'attributes' => array('class'=>'arrowlinks'),
		'selector' => 'ul',
		//'styles' => $fontreset,
	),
	
//	//Layout
//	array(
//		'title' => 'Layout'
//	),
//	array(
//		// Wrap selected content in a h3 with class of .split-3
//		'title' => 'Uitklap item (onderaan)',
//		'attributes' => array('class'=>'accordion'),
//		'block' => 'h3',
//		'styles' => $fontreset,
////		'block' => 'div',
////		'wrapper' => 1
////		'selector' => 'p'
//	),
	
	// Images
	array(
		'title' => 'Beelden',
	),
	array(
		'title' => 'Right',
		'attributes' => array('class'=>'right'),
		'selector' => 'img,div',
		'styles' => $fontreset,
	),
	array(
		'title' => 'Left',
		'attributes' => array('class'=>'left'),
		'selector' => 'img,div',
		'styles' => $fontreset,
	),
	array(
		'title' => 'Centered',
		'attributes' => array('class'=>'center'),
		'selector' => 'img,div',
		'styles' => $fontreset,
	),
	array(
		'title' => 'full width',
		'attributes' => array('class'=>'fullwidth'),
		'selector' => 'img,div',
		'styles' => $fontreset,
	),
	
);

//Set the dropdown menu options
HtmlEditorConfig::get('cms')->setOption('style_formats',$formats);



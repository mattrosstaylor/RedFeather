<?php

/***********************
   Basic Configuration
 ***********************/

// Text to use in the site header.
$CONF['repository_name'] = 'Repository Name Here';
$CONF['repository_tagline'] = 'You can add a tagline for your repository here';

// Colour scheme for the repository.
$CONF['theme'] = array(
	'linkcolor'=>'#1F1FAC', // colour used for hyperlinks, banner trim and the coloured section of the header 
	'bannercolor'=>'#D0D0F0', // colour used for the header and footer
	'text1'=>'black', // main text colour
	'text2'=>'#606060', // annotation colour
	'font'=>'sans-serif', // font to use for the site
	'background'=>'', // page background colour
);
	
// Optional descriptive text for the top of the browse page.
$CONF['browse_html'] = '<h1>Browse Page Html</h1><p style="padding-bottom:20px">This appears at the top of the browse page. It can either be plain text, or <span style="font-weight:bold">formatted html</span>.</p>';

// Optional header section to allow navigation from RedFeather back to a parent site.
//$CONF['return_link'] = array('text'=>'return to site >', 'href'=>'http://www.example.com');

// Default values for a new resource
$CONF['default_metadata'] = array(
	'title'=>'',
	'description'=>'', 
	'creators'=>array(''),
	'emails'=>array(''),
	'license'=>''
);

// Array of username/password combinations that are allowed to access the resource manager
// the passwords can either be plain text, or MD5 encoded
//$CONF['credentials'] = array('admin'=>'password');
$CONF['credentials'] = array('admin'=>'5f4dcc3b5aa765d61d8327deb882cf99');

?>

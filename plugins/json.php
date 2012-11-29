<?php

/***************
   JSON export
 ***************/

$CONF['toolbar']['browse'][] = 'browse_json';

// public function for JSON
function page_json() {
        global $DATA;

	header("Content-type: application/json");

	print json_encode($DATA);
}	

// toolbar item for browse page
function generate_toolbar_item_browse_json()
{
	global $CONF;
	return '<a href="'.$CONF['script_filename'].'?page=json"><img src="http://icons.iconarchive.com/icons/untergunter/leaf-mimes/16/text-xml-icon.png"/> JSON</a>';
}

?>

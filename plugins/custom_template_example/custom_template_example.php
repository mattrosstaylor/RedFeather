<?php

/******************************
   Impeccable custom template
 ******************************/


// set the basic repository information
$CONF['repository_name'] = 'Impeccable';
$CONF['repository_tagline'] = 'RedFeather Reskinned';
$CONF['theme']['text2'] = '#9a9'; // TODO this should need to be overwritten - refactor this style

// add static content
$CONF['javascript'][] = 'javascript_custom';

// the resource manager was originally on the footer, move it to the browse toolbar
$CONF['toolbar']['browse'][] = 'footer_resource_manager';


$FUNCTION_OVERRIDE['render_template'] = 'render_template_custom';
$FUNCTION_OVERRIDE['generate_message_list'] = 'generate_message_list_custom';

// Dimensions for various elements for the site.
$CONF['element_size'] = array(
	'preview_width'=>520, // width of the resource preview in px
	'preview_height'=>520, // height of the resource preview in px
	'metadata_width'=>520,
	'manager_width'=>500 // width of the resource manager workflow
);

// custom jquery function to adjust the redfeather content to match the existing html
function javascript_custom()
{
	return <<<EOT
$(document).ready(function() {
	$('.rf_resource').addClass('post');
	$('#rf_page_resource_manager').addClass('post');
	$('#rf_page_edit').addClass('post');
	$('#rf_page_view').addClass('post');
	$('#rf_metadata').addClass('entry');
	$('<div class="entry"></div>').appendTo('.rf_resource');
	$('<div class="entry"></div>').appendTo('#rf_page_resource_manager > form');
	$('<div class="entry"></div>').appendTo('#rf_page_edit > form');
	$('.rf_content').find('h1').replaceWith(function () {
		return "<h2 class='post title'>" + $(this).html() + "</h2>";
	});
});
EOT;
}


// custom message list	
function generate_message_list_custom()
{
	$html = '';
	$messages = call('get_messages');
	if (count($messages) > 0)
	{
		$html .= '<div class="post"><h2 class="title">Messages</h2><div class="entry"><ul id="rf_error_list">';
		foreach ($messages as $m)
			$html .= '<li>'.$m.'</li>';
		$html .= '</ul></div></div>';
	}
	return $html;
}

// custom template
function render_template_custom()
{
	global $CONF, $TITLE, $BODY;

	// get the compulsory extra components
	$head_elements = call('generate_head_elements');
	$message_list = call('generate_message_list');

	print <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!--
Design by Free CSS Templates
http://www.freecsstemplates.org
Released for free under a Creative Commons Attribution 2.5 License

Name       : Impeccable   
Description: A two-column, fixed-width design with dark color scheme.
Version    : 1.0
Released   : 20101129

-->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="keywords" content="" />
<meta name="description" content="" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>$TITLE</title>
<link href="plugins/custom_template_example/style.css" rel="stylesheet" type="text/css" media="screen" />
$head_elements
</head>
<body>
<div id="wrapper">
<div id="page">
	<div id="page-bgtop">
		<div id="page-bgbtm">
			<div id="content">
				$message_list $BODY
				<div style="clear: both;">&nbsp;</div>
			</div>
			<!-- end #content -->
			<div id="sidebar">
				<div id="logo">
					<h1><a href="#">{$CONF['repository_name']}</a></h1>
					<p>{$CONF['repository_tagline']}</p>
				</div>
				<div id="menu">
					<ul>
						<li class="current_page_item"><a href="index.php">Home</a></li>
						<li><a href="#">Blog</a></li>
						<li><a href="#">Photos</a></li>
						<li><a href="#">About</a></li>
						<li><a href="#">Links</a></li>
						<li><a href="#">Contact</a></li>
					</ul>
				</div>
				<ul>
					<li>
						<h2>What is this?</h2>
						<p>This is an entirely reskinned version of RedFeather with a custom template, stylesheet and resource layout.  It is implemented as a plugin and shows off the potential capabilities of the RedFeather platform.</p>
					</li>	
				</ul>
			</div>
			<!-- end #sidebar -->
			
			<div id="footer">
				<p>Copyright (c) 2010. All rights reserved. Design by <a href="http://www.freecsstemplates.org/">FCT</a>.</p>
			</div>
		</div>
	</div>
	<!-- end #page -->
</div>
<!-- end #footer -->
</body>
</html>
EOT;
}

?>

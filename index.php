<?php
ini_set('display_errors', 1);ini_set('log_errors', 1);error_reporting(E_ALL);

// global variable for configuration
$CONF = array(); 

/****************************	
   RedFeather Configuration
 ****************************/	

	// Text to use in the site header.
	$CONF['repository_name'] = 'RedFeather';
	$CONF['repository_tagline'] = 'Lightweight Resource Exhibition and Discovery';

	// Colour scheme for the repository.
	$CONF['theme'] = array(
		'linkcolor'=>'#AC1F1F', // colour used for hyperlinks, banner trim and the coloured section of the header 
		'bannercolor'=>'#F0D0D0', // colour used for the header and footer
		'text1'=>'black', // main text colour
		'text2'=>'#606060', // annotation colour
		'font'=>'sans-serif', // font to use for the site
		'background'=>'', // page background colour
	);

	// Optional header section to allow navigation from RedFeather back to a parent site.
	//$CONF['return_link'] = array('text'=>'return to site >', 'href'=>'http://www.example.com');

	// Default values for a new resource
	$CONF['default_metadata'] = array(
		//'title'=>'',
		//'description'=>'', 
		'creators'=>array(''),
		'emails'=>array(''),
		'license'=>''
	);

	// Array of username/password combinations that are allowed to access the resource manager
	$CONF['users'] = array('admin'=>'password');


/**************************
   Advanced Configuration
 **************************/

// Field definitions
$CONF['fields'] = array(
	'title',
	'description',
	'creators',
	'date',
	'license',
	'download',
);

// Toolbars
$CONF['toolbars'] = array(
	'footer' => array('credit', 'resource_manager'),
	'browse' => array('search', 'rss', 'rdf'),
	'resource' => array('metadata', 'comments'),
	'resource_manager' => array()
);

// List of available licenses for RedFeather
$CONF['licenses'] = array(
	''=>'unspecified',
	'by'=>'Attribution',
	'by-sa'=>'Attribution-ShareAlike',
	'by-nd'=>'Attribution-NoDerivs',
	'by-nc'=>'Attribution-NonCommercial',
	'by-nc-sa'=>'Attribution-NonCommerical-ShareAlike',
	'by-nc-nd'=>'Attribution-NonCommerical-NoDerivs',
);

// Dimensions for various elements for the site.
$CONF['element_size'] = array(
	'preview_width'=>680, // width of the resource preview in px
	'preview_height'=>550, // height of the resource preview in px
	'metadata_width'=>300, // width of the resource metadata column in px
	'metadata_gap'=>15, // size of the gap between the resource preview and metadata column in px
	'manager_width'=>500 // width of the resource manager workflow
);

// Sets the default page for RedFeather
$CONF['default_page'] = 'page_browse';

// The file used for storing the resource metadata
$CONF['metadata_filename'] = 'resourcedata';
// The name of the plugins folder
$CONF['plugin_dir'] = ".";

// The filename for this script
$CONF['script_filename'] = array_pop(explode('/', $_SERVER['SCRIPT_NAME']));
// The full url of the directory RedFeather is installed in.
$CONF['base_url'] = 'http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], "/")+1);
// The full url of the RedFeather script
$CONF['script_url'] = $CONF['base_url'].$CONF['script_filename'];

// global variable to allow function overriding
$FUNCTION_OVERRIDE = array(
//	'page_browse'=>'page_browse_new',
);


/*********************
   Utility Functions
 *********************/

// global variable to store resource data
$DATA = array();
// global variable for page title
$TITLE = $CONF['repository_name'];
// global variable to buffer main body html
$BODY = '';
// global variable to buffer CSS
$CSS = '';
// global variable to buffer Javascript
$JS = '';

// Calls a function within RedFeather to provide a simple plugin architecture.
// To maintain compatibility with PHP 4.0, functions should only take a single parameter - which is passed through to the target.
// When a named function is called, the FUNCTION_OVERRIDE is first checked to see if an override function has been assigned.
// If it has, that function is called, otherwise it will call the function directly.
function call($function, $param=null)
{
	global $FUNCTION_OVERRIDE;
	if (isset($FUNCTION_OVERRIDE[$function]))
		return call_user_func($FUNCTION_OVERRIDE[$function], $param);
	else if (function_exists($function))
		return call_user_func($function, $param);
	else call_optional('fourohfour');
}

// as above but doesn't give an error if a non-existent function is called
function call_optional($function, $param=null)
{
	global $FUNCTION_OVERRIDE;
	if (isset($FUNCTION_OVERRIDE[$function]))
		return call_user_func($FUNCTION_OVERRIDE[$function], $param);
	else if (function_exists($function))
		return call_user_func($function, $param);
	else return;
}

// function to provide simple authentication functionality
function authenticate() {
	global $CONF, $BODY, $FUNCTION_OVERRIDE;
	// check the session for an authenticated user and return to the parent function if valid.

return;
	if(isset($_SESSION['current_user']))
	{
		return;
	}

	// If this is a post requesting to log in, check username and password against authorised credentials.
	if (isset($_POST['username']) && isset($_POST['password']) 
		&& isset($CONF['users'][$_POST['username']]) 
		&& $CONF['users'][$_POST['username']]==$_POST['password']) 
	{
		$_SESSION['current_user']=$_POST['username'];
		return;
	}
	

	// if the user is unauthenticated and not making a signing post, render login screen.	
	$BODY .=
		'<div id="content"><form method="post" action="'.$CONF['script_filename'].'?'.$_SERVER['QUERY_STRING'].'">
			Username: <input type="text" name="username" />
			Password: <input type="password" name="password" />
			<input type="submit" value="Login" />
		</form></div>';

	call('render_template');
	exit;
}

// page to receive POST requests
function page_post() {
	global $CONF, $DATA;
	// check request type
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		header('HTTP/1.1 405 Method Not Allowed');
		return;
	}
	
	// ensure that the user is logged in
	call('authenticate');

	// attempt to call the post subroutine	
	if (isset($_POST['ACTION']))
	{
		// unset the ACTION value in the post array
		$action = $_POST['ACTION'];
		unset($_POST['ACTION']);
		call('post_'.$action);
	}
	else
		header('HTTP/1.1 400 Bad Request');
}

// generates a named toolbar
function generate_toolbar($toolbar)
{
	global $CONF;

	$html ='<ul class="toolbar_'.$toolbar.'">';

	foreach($CONF['toolbars'][$toolbar] as $tool)
		$html .= '<li>'.call('generate_toolbar_item_'.$toolbar.'_'.$tool).'</li>';

	return $html .= '</ul>';
}

// function to get a list of all the complete resources (i.e. with matching file/metadata)
function get_resource_list()
{
	global $DATA;
	$list = array();
	$files = call('get_file_list');

	foreach ($DATA as $filename => $data)
		if (in_array($filename, $files)) array_push($list, $filename);
	return $list;
}

function add_message($message)
{
	if (!isset($_SESSION['messages']))
		$_SESSION['messages'] = array();
	$_SESSION['messages'][] = $message;
}

function get_messages()
{
	if (!isset($_SESSION['messages']))
		return array();
	$messages = $_SESSION['messages'];
	unset($_SESSION['messages']);
	return $messages;
}

/***************************************************
   Helper functions - these cannot be overwritten
 ***************************************************/

// helper function to html entity encode a string
function _E_($s)
{
	return htmlentities($s);
}

// helper function to get a field
function _F_($data, $field)
{
	if (isset($data[$field]))
		return $data[$field];
	else
		return '';
}

// helper function to get a field html entity encoded
function _EF_($data, $field)
{
	return _E_(_F_($data, $field));
}


/**************
   Base pages
 **************/

// render using template
function render_template()
{
	global $CONF, $BODY, $TITLE, $CSS, $JS;


	$message_html = '';
	$messages = call('get_messages');
	
	if (count($messages) > 0)
	{
		$message_html .= '<ul>';
		foreach ($messages as $m)
			$message_html .= '<li>'.$m.'</li>';
		$message_html .= '</ul>';
	}

	// render the top part of the html, including title, jquery, stylesheet, local javascript and page header
	print 
		'<!DOCTYPE HTML>
		<html><head>
			<title>'.$TITLE.'</title>
			<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
			<script src="'.$CONF['script_filename'].'?page=javascript" type="text/javascript"></script>
			<link rel="stylesheet" type="text/css" href="'.$CONF['script_filename'].'?page=css"/>
		</head>
		<body>
			'.$message_html.'
			<div id="header"><div class="center">
				<h1><a href="'.$CONF['script_filename'].'">
					'.$CONF['repository_name'].'
				</a></h1>';

	// add the optional 'return link' as documented in the RedFeather configuration section
	if (isset($CONF['return_link']))
		print '<a style="float:right;" href="'.$CONF['return_link']['href'].'">'.$CONF['return_link']['text'].'</a>';

	print '
				<h2>'.$CONF['repository_tagline'].'</h2>
			</div></div>
			<div class="center">
			'.$BODY.'
			</div>
			<div id="footer">
				<div class="center">'.call('generate_toolbar','footer').'</div>
		</html>';

}

// toolbar item for footer
function generate_toolbar_item_footer_credit()
{
	return 'Powered by <a href="http://redfeather.ecs.soton.ac.uk">RedFeather</a>'; 
}

// toolbar item for footer
function generate_toolbar_item_footer_resource_manager()
{
	global $CONF;
	return '<a href="'.$CONF['script_filename'].'?page=resource_manager">Resource Manager</a>';
}

// output a 404 page
function fourohfour()
{
	global $BODY, $TITLE;
	$TITLE = '404 - '.$TITLE;
	$BODY .= '<div id="content"><h1>404</h1><p>That page doesn\'t exist.</p></div>';
	header('Status: 404 Not Found');	
	call('render_template');
}

// Public function for CSS
function page_css()
{
	global $CONF, $CSS;
	$page_width = $CONF['element_size']['preview_width']+$CONF['element_size']['metadata_gap']+$CONF['element_size']['metadata_width'];

	// base CSS
	$base_css = <<<EOT
/* http://meyerweb.com/eric/tools/css/reset/ 
   v2.0 | 20110126
   License: none (public domain)
*/

html, body, div, span, applet, object, iframe,
h1, h2, h3, h4, h5, h6, p, blockquote, pre,
a, abbr, acronym, address, big, cite, code,
del, dfn, em, img, ins, kbd, q, s, samp,
small, strike, strong, sub, sup, tt, var,
b, u, i, center,
dl, dt, dd, ol, ul, li,
fieldset, form, label, legend,
table, caption, tbody, tfoot, thead, tr, th, td,
article, aside, canvas, details, embed, 
figure, figcaption, footer, header, hgroup, 
menu, nav, output, ruby, section, summary,
time, mark, audio, video {
	margin: 0;
	padding: 0;
	border: 0;
	font-size: 100%;
	font: inherit;
	vertical-align: baseline;
}
/* HTML5 display-role reset for older browsers */
article, aside, details, figcaption, figure, 
footer, header, hgroup, menu, nav, section {
	display: block;
}
body {
	line-height: 1;
}
ol, ul {
	list-style: none;
}
blockquote, q {
	quotes: none;
}
blockquote:before, blockquote:after,
q:before, q:after {
	content: '';
	content: none;
}
table {
	border-collapse: collapse;
	border-spacing: 0;
}

/* RedFeather CSS */

body { 
	font-family: {$CONF['theme']['font']};
	font-size: 14px;
	color: {$CONF['theme']['text1']};
	background: {$CONF['theme']['background']};
	line-height: 1.15;
	text-align: center;
}
.center {
	width: {$page_width}px;
	margin: auto;
	text-align: left;
}
h1 { 
	font-size: 20px; 
	font-weight:bold;
}
p {
	text-align: justify;
}
p, h1 {
	margin-bottom: 3px;
}
a {
	color: {$CONF['theme']['linkcolor']};
}
a:link, a:visited {
	text-decoration: none;
}
a:hover, a:active {
	text-decoration: underline;
}
#header {
	background: {$CONF['theme']['bannercolor']};
	padding: 12px;
	border-bottom: 1px solid {$CONF['theme']['linkcolor']};
}
#header h1 {
	font-size: 28px;
	margin-bottom: 0;
}
.#header h1 > a {
	text-decoration: none;
}
#header h2 {
	font-size: 14px;
	font-style: italic;
	color: {$CONF['theme']['text1']};
}
#footer {
	padding: 6px; 
	background: {$CONF['theme']['bannercolor']};
	border-top: 1px solid {$CONF['theme']['linkcolor']};
	border-bottom: 1px solid {$CONF['theme']['linkcolor']};
}
.toolbar_footer > li {
	display: inline;
	padding: 0 5px;
	border-right: 1px solid {$CONF['theme']['text1']};
}
.toolbar_footer > li:first-child {
	padding-left: 0;
}
.toolbar_footer > li:last-child {
	padding-right:0;
	border-right: 0;
}
#content {
	padding: 6px 0 6px 0;
}
.clearer {
	clear: both;
}
EOT;
	header('Content-type: text/css');
	print $base_css.$CSS;
}

// Public function for getting js
function page_javascript()
{
	global $JS;
	header('Content-type: text/javascript');
	print $JS;
}
 

/****************************************************
   Functions to interact with the local file system
*****************************************************/
// returns a list of all files within the RedFeather resource scope (i.e. that can be annotated)
function get_file_list()
{
	global $CONF;
	$file_list = array();
	$dir = "./";
	foreach (scandir($dir) as $file)
	{
		// exclude directories, hidden files, the RedFeather file and the metadata file
		if( is_dir($dir.$file) || !call('check_file_allowed', $file)) continue;
		array_push($file_list, $file);
	}
	return $file_list;
}

// checks whether a file is acceptable
function check_file_allowed($filename)
{
	global $CONF;
	if (preg_match("/^\./", $filename) || preg_match("/\.php/", $filename)  || $filename == $CONF['metadata_filename'])
		return false;
	else
		return true;
}


// returns a absolute hyperlink to a given file
function get_file_link($filename)
{
	global $CONF;
	return $CONF['base_url'].rawurlencode($filename);
}

// returns the data a file was last edited
function get_file_date($filename)
{
	return date ('d F Y H:i:s', filemtime($filename));
}

// returns the image size information from a file (replicates the behaviour of the standard php function
function get_image_size($filename)
{
	return getimagesize($filename);
}

// loads the resource metadata from the filesystem in the global variable $CONF
function load_data()
{
	global $CONF, $DATA;
	$DATA = unserialize(file_get_contents($CONF['metadata_filename']));
	if(!is_array($DATA) )
		$DATA= array();
}
// saves the resource metadata to the filesystem
function save_data()
{
	global $CONF, $DATA;
	// save the array as serialized PHP
	$fh = fopen($CONF['metadata_filename'], 'w');
	fwrite($fh,serialize($DATA));
	fclose($fh);
}


/**************************
   Browse/Resource Module
***************************/

$CSS .= <<<EOT
.resource {
	margin-bottom: 12px;
}
.toolbar_browse {
	margin-bottom: 10px;
}
.toolbar_browse > li {
	padding-right: 10px;
	display: inline-block;
}
.toolbar_browse > li > a > img {
	vertical-align: text-bottom;
}
#preview {
	width: {$CONF['element_size']['preview_width']}px;
	text-align: center;
	float: left;
}
#preview iframe {
	width: {$CONF['element_size']['preview_width']}px;
	height: {$CONF['element_size']['preview_height']}px;
	display: block;
}
#preview .message {
	text-align: justify;
	display: none;
}
#preview.message_inserted iframe {
	margin-top: -{$CONF['element_size']['preview_height']}px;
}
#preview.message_inserted .message {
	height: {$CONF['element_size']['preview_height']}px;
	display: block;
}
.metadata {
	width: {$CONF['element_size']['metadata_width']}px;
	float: right;
	margin-left: {$CONF['element_size']['metadata_gap']}px;
	word-wrap: break-word;
}
.metadata_table {
	margin-bottom: 6px;
	margin-left: 6px;
	font-size: 12px;
}
.metadata_table tr>:last-child {
	word-break: break-all;
}
.metadata_table tr>:first-child {
	color: {$CONF['theme']['text2']};
	padding-right: 12px;
}
EOT;

$JS .= <<<EOT
function filter(){
	var filter = $("#filter").val();
 	if(filter == ""){
		$(".resource").show();  
		return;
	}
	$(".resource").hide();
	$(".resource:contains("+$("#filter").val()+")").show();
}

function preview_fallback() {
	var d = document.getElementById('preview');
	if (d != null)
		d.className = d.className + ' message_inserted';
}

window.setTimeout('preview_fallback()', 10000);
EOT;

// Browse page for RedFeather.
// Lists all the resources that have been annotated and provides a facility to search.
function page_browse()
{
	global $CONF, $DATA, $BODY;

	$BODY .= '<div id="content">'.call('generate_toolbar', 'browse');
	
	// div for resource list
	$BODY .= '<div class="browse_list">';

	// get the list of all files within the RedFeather scope
	foreach(call('get_resource_list') as $filename)
	{
		$data = $DATA[$filename];
		// render the resource using the "generate_metadata_table" function
		$url = $CONF['script_filename'].'?file='.rawurlencode($filename);
		$BODY .= 
			'<div class="resource">
				<h1><a href="'.$url.'">'._EF_($data,'title').'</a></h1>
				<p>'.nl2br(_EF_($data,'description')).'</p>
				'.call('generate_metadata_table', $data).'
			</div>';
	}
	$BODY .= '</div></div>';

	call('render_template');
}


// toolbar item for browse page
function generate_toolbar_item_browse_search()
{
	return 'Search these resources: <input id="filter" onkeyup="filter()"type="text" value="" />';
}

// toolbar item for browse page
function generate_toolbar_item_browse_rss()
{
	global $CONF;
	return '<a href="'.$CONF['script_filename'].'?page=rss"><img src="http://icons.iconarchive.com/icons/danleech/simple/16/rss-icon.png"/> RSS</a>';
}

// toolbar item for browse page
function generate_toolbar_item_browse_rdf()
{

	global $CONF;
	return '<a href="'.$CONF['script_filename'].'?page=rdf"><img src="http://icons.iconarchive.com/icons/milosz-wlazlo/boomy/16/database-icon.png"/> RDF+XML</a>';
}

// View the resource preview, metadata and social networking plugin
function page_resource()
{
	global $CONF, $DATA, $BODY, $TITLE;

	$filename = '';	
	// check that the file requested actually exists
	if (isset($_REQUEST['file']))
		$filename = rawurldecode($_REQUEST['file']);

 	if (!in_array($filename, call('get_resource_list')))
	{
		call('fourohfour');
		return;
	}

	$data = $DATA[$filename];

	$TITLE = _EF_($data,'title').' - '.$TITLE;
	$BODY .=
		'<div id="content">
			<div id="preview">'.call('generate_preview', _F_($data,'filename')).'</div>
			<div class="metadata">
			'.call('generate_toolbar', 'resource').'
			</div>
			<div class="clearer"></div>
		</div>';

	call('render_template');
}

// toolbar item for resource page
function generate_toolbar_item_resource_metadata()
{
	global $CONF, $DATA;
	$data = $DATA[rawurldecode($_REQUEST['file'])];
	
	return '<h1>'._EF_($data,'title').'</h1>
		<p>'._EF_($data,'description').'</p>
		'.call('generate_metadata_table', $data);
}

// toolbar item for resource page
function generate_toolbar_item_resource_comments()
{
	global $CONF, $DATA;
	$data = $DATA[rawurldecode($_REQUEST['file'])];

	$this_url = $CONF['script_url'].'?file='._EF_($data,'filename');
	return call('generate_comment_widget', $this_url);
}

// return the Facebook comment widget
function generate_comment_widget($this_url)
{
	global $CONF;

	return '
		<div id="fb-root"></div>
		<script>
			(function(d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				js = d.createElement(s); js.id = id;
				js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1";
				fjs.parentNode.insertBefore(js, fjs);
			}(document, "script", "facebook-jssdk"));
		</script>
		<div class="fb-comments" data-href="'.$this_url.'" data-num-posts="2" data-width="'.$CONF['element_size']['metadata_width'].'"></div>';
}

/* Return the preview widget for a given resource at the dimensions specified.
	If the resource is determined to be an image, it renders as a simple <img> element.
	Otherwise, it will be rendered using the googledocs previewer.
	Due to a bug in the googledocs API, the service can sometimes silently fail and return an empty iframe.
	Since there is no way to detect this when it occurs, and is a fatal bug in terms of preview functionality, a workaround has been devised where an error message is hidden underneath the preview widget.  If the preview widget fails it will be visible through the empty iframe. */
function generate_preview($filename)
{
	global $CONF;
	$width = $CONF['element_size']['preview_width'];
	$height = $CONF['element_size']['preview_height'];

	// get absolute url for file
	$file_url = call('get_file_link', $filename);

	// attempt to determine the image dimensions of the resource
	$image_size = call('get_image_size', $filename);
	// if the function succeed, assume the resource is an image
	if ($image_size)
	{
		// stretch the image to fit preview area, depending on aspect ratio
		if ($image_size[0]/$image_size[1] < $width/$height)
			return '<img src="'._E_($file_url).'" height="'.$height.'">';
		else	
			return '<img src="'._E_($file_url).'" width="'.$width.'">';
	}
	// if the function failed, attempt to render using googledocs previewer
	else
	{
		// create error message in case the widget fails to load
		$error_fallback = '
			<div class="message"><h1>Google docs viewer failed to initialise.</h1><p>This is due to a bug in the viewer which occurs when your Google session expires.</p><p>You can restore functionality by logging back into any Google service.</p></div>';
	
		// place the error message directly underneath the widget

	//	return $error_fallback;
		//return '<iframe src="http://docs.google.com/viewer?embedded=tru2e&url='._E_($file_url).'"></iframe>'.$error_fallback;
		return $error_fallback.'<iframe src="http://docs.google.com/viewer?embedded=true&url='._E_(urlencode($file_url)).'"></iframe>';
	}
}

// returns the metadata table for the resource data specified
function generate_metadata_table($data)
{
	global $CONF;

	$table = '<table class="metadata_table"><tbody>';
	
	//  fields
	foreach ($CONF['fields'] as $fieldname)
		$table .= call_optional("generate_output_field_$fieldname", $data);

	$table .= '</tbody></table>';
	return $table;
}

// field definition for metadata table
function generate_output_field_creators($data)
{
	$html = '';
	$creators = _F_($data,'creators');
	$emails = _F_($data,'emails');
	// check that the creator field exists and not an empty placeholder
	if ($creators && trim($creators[0]) != '')
	{
		// table header should be creator/creators depending on size of array
		$html .= '<tr><td>Creator' .((sizeof($creators)>1) ? 's': '').':</td><td>';
		// loop through each creator name and create a mailto link for them if required
		for ($i = 0; $i < sizeof($creators); $i++)
			if (trim($emails[$i]) == '')
				$html .= _E_($creators[$i]).'<br/>';
			else
				$html .= '<a href="mailto:'._E_($emails[$i]).'">'._E_($creators[$i]).'</a><br/>';
		$html .= '</td></tr>';
	}

	return $html;
}

// field definition for metadata table
function generate_output_field_date($data)
{
	return '<tr><td>Updated:</td><td>'.call('get_file_date', _F_($data,'filename')).'</td></tr>';
}

// field definition for metadata table
function generate_output_field_license($data)
{
	global $CONF;
	return '<tr><td>License:</td><td>'.$CONF['licenses'][_F_($data, 'license')].'</td></tr>';
}

// field definition for metadata table
function generate_output_field_download($data)
{
	return '<tr><td>Download:</td><td><a target="_blank" href="'.call('get_file_link', _F_($data,'filename')).'">'._EF_($data, 'filename').'</a></td></tr>';
}


/***************************
   Resource Manager Module
 ***************************/

$CSS .= <<<EOT
.resource_manager table {
	margin-left: 20px;
}
.resource_manager form {
	margin-bottom: 12px;
}
.resource_manager table td {
	padding: 0 15px 0 0;
}
tbody tr:first-child > td > .up { 
	visibility: hidden;
}
tbody tr:last-child > td > .down { 
	visibility: hidden;
}
.end_field {
	margin-bottom: 10px;
}
.number {
	color: {$CONF['theme']['text2']};
}
.updown {
	font-size: 20px;
	font-weight: bold;
}
.manageable label {
	display: inline-block;
	color: {$CONF['theme']['text2']};
	text-align:right;
	width: 100px;
	padding-right:10px;
}
.manageable > input, .manageable > textarea, .manageable > select {
	width: {$CONF['element_size']['manager_width']}px;
	vertical-align: middle;
}
.manageable input, .manageable textarea, .manageable select {
	font: inherit;
	border: solid 1px {$CONF['theme']['bannercolor']};
}

.multifield {
	display: inline-block;
	width: {$CONF['element_size']['manager_width']}px;
	vertical-align: middle;
}
.new_multifield {
	display: none;
}
EOT;

$JS .= <<<EOT
$(document).ready(function() {
	$('.up').click(function() {
		var item = $(this).parent().parent();
		var other = item.prev('.sortable');
		if (other.html() == null) return false;
		item.detach().insertBefore(other);
		var this_num = item.find('.number').html();
		item.find('.number').html(other.find('.number').html());
		other.find('.number').html(this_num);
		return false;
	});
	$('.down').click(function() {
		var item = $(this).parent().parent();
		var other = item.next('.sortable');
		if (other.html() == null) return false;
		item.detach().insertAfter(other);
		var this_num = item.find('.number').html();
		item.find('.number').html(other.find('.number').html());
		other.find('.number').html(this_num);
		return false;
	});
});
function multifield_add(field) {
	var multifield = $("#"+field);
	var add_link = $("#add"+field);
	var new_item = $('#new_'+field).text();
	multifield.append('<div>'+new_item+'<a href="#" onclick="javascript:$(this).parent().remove();return false;">remove</a></div>');
	add_link.remove().appendTo(multifield);
}
function post_delete_form(filename) {
	if (confirm(filename +"\\nDelete this file?")) {
		$("#delete_file_field").val(filename);
		$("#delete_file").submit();
		return true;
	}
	return false;
}
EOT;

function page_resource_manager()
{
	global $CONF, $DATA, $BODY, $TITLE;

	call('authenticate');
	
	$TITLE = 'Resource Manager - '.$TITLE;
	$BODY .= '<div id="content" class="resource_manager"><h1>Resource Manager</h1>';
	$BODY .= call('generate_toolbar', 'resource_manager');
	
	$num = 1;
	$BODY .= '<form action="'.$CONF['script_filename'].'?page=post" method="POST">';
	$BODY .= '<table><tbody>';

	// iterate through all the files currently present in the filesystem	
	foreach (call('get_resource_list') as $filename)
	{
		$data = $DATA[$filename];
		$BODY .= '<tr class="sortable">';
		$BODY .= '<td class="number">'.$num++.'.</td>';
		$BODY .= '<td>'._EF_($data, 'title').'</td>';
		$BODY .= '<td class="updown"><a href="#" class="up">&uarr;</a><a href="#" class="down">&darr;</a></td>';
		$BODY .= '<td><a href="'._E_(call('get_file_link',$filename)).'" target="_blank">'.$filename.'</td>';
		$BODY .= '<td><a href="'.$CONF['script_filename'].'?page=edit&file='.rawurlencode($filename).'">edit</a></td>';
		$BODY .= '<td><a href="'.$CONF['script_filename'].'?file='.rawurlencode($filename).'">view</a></td>';
		$BODY .= '<td><a href="#" onclick="javascript:post_delete_form(\''.$filename.'\');">delete</a></td>';
		$BODY .= '<input type="hidden" name="ordering[]" value="'._E_($filename).'"/>';
		$BODY .= '</tr>';
	}

	$BODY .= '</tbody></table>';
	// add save button
	$BODY .= '<input type="hidden" name="ACTION" value="reorder_resources"/>';
	$BODY .= '<input type="submit" value="Save order"/>';
	$BODY .= '</form>';

	// get a list of any unannotated files
	$new_files = array();
	foreach (call('get_file_list') as $filename)
		if (!isset($DATA[$filename]))
			$new_files[] = $filename;

	// generate the table for the new files 
	if (count($new_files) > 0)
	{
		$BODY .= '<h1>Unannotated files</h1>';
		$BODY .= '<table><tbody>';
		// render list of new files
		foreach ($new_files as $filename)
		{
			$BODY .= '<tr>';
			$BODY .= '<td><a href="'._E_(call('get_file_link',$filename)).'" target="_blank">'.$filename.'</td>';
			$BODY .= '<td><a href="'.$CONF['script_filename'].'?page=edit&file='.rawurlencode($filename).'">edit</a></td>';
			$BODY .= '<td><a href="#" onclick="javascript:post_delete_form(\''.$filename.'\');">delete</a></td>';
			$BODY .= '</tr>';    
		}

		$BODY .= '</tbody></table>';
	}

	// hidden form for deletion
	$BODY .= '<form id="delete_file" action="'.$CONF['script_filename'].'?page=post" method="POST"><input type="hidden" name="ACTION" value="delete"/><input id="delete_file_field" type="hidden" name="filename"></form>';
	

	// new deposit box
	$BODY .= '<h1>New deposit</h1>';
	$BODY .= '<form method="post" action="'.$CONF['script_filename'].'?page=post" enctype="multipart/form-data">';
	$BODY .= '<input type="file" name="file" />';
	$BODY .= '<input type="hidden" name="ACTION" value="upload"/>';
	$BODY .= '<input type="submit" value="Upload"/>';
	$BODY .= '<input type="checkbox" name="overwrite" />Overwrite existing file<br>';
	$BODY .= '</form>';

	$BODY .= '</div></div>';
	call('render_template');
}

function post_reorder_resources()
{
	global $DATA;
	
	$resource_list = call('get_resource_list');

	$new_data = array();

	// check for resources that might have been added due to a race condition - these are added to the top of the list
	foreach($resource_list as $filename)
		if (!in_array($filename, $_POST['ordering']))
		{
			$new_data[$filename] = $DATA[$filename];
			call('add_message', $DATA[$filename]['title'].' was added.');
		}

	// copy resource data to new array if they exist
	foreach ($_POST['ordering'] as $filename)
	{
		if (in_array($filename, $resource_list))
			$new_data[$filename] = $DATA[$filename];
		else
			call('add_message', $filename.' no longer exists.');
	}

	$DATA = $new_data;
	call('save_data');

	// redirect to the resource page
	header('Location:'.$CONF['script_url'].'?page=resource_manager');
}

function get_upload_error_message($code)
{
	switch ($code) { 
		case UPLOAD_ERR_INI_SIZE: 
			return 'The uploaded file exceeds the upload_max_filesize directive in php.ini'; 
		case UPLOAD_ERR_FORM_SIZE: 
			return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; 
		case UPLOAD_ERR_PARTIAL: 
			return 'The uploaded file was only partially uploaded'; 
		case UPLOAD_ERR_NO_FILE: 
			return 'No file was uploaded';
		case UPLOAD_ERR_NO_TMP_DIR: 
			return 'Missing a temporary folder'; 
		case UPLOAD_ERR_CANT_WRITE: 
			return 'Failed to write file to disk';
		case UPLOAD_ERR_EXTENSION:
			return 'File upload stopped by extension'; 
		default:
			return 'Unknown upload error';
	} 
} 

function post_upload()
{
	$filename = $_FILES['file']['name'];

	$filename = utf8_decode($filename);

	if ($_FILES['file']['error'] > 0)
	{
		call('add_message', call('get_upload_error_message', $_FILES['file']['error']));
		header('Location:'.$CONF['script_url'].'?page=resource_manager');
		return;
	}

	if (!check_file_allowed($filename))
	{
		call('add_message', 'Invalid file type');
		header('Location:'.$CONF['script_url'].'?page=resource_manager');
		return;
	}

	// check whether the file already exists
	$file_list = call("get_file_list");

	if (in_array($filename, $file_list) && !$_POST['overwrite'])
	{
		call('add_message', 'File already exists');
		header('Location:'.$CONF['script_url'].'?page=resource_manager');
		return;
	}

	if (!copy($_FILES['file']['tmp_name'], $filename))
	{
		call('add_message', 'File write error');
		header('Location:'.$CONF['script_url'].'?page=resource_manager');
		return;
	}
	
	header('Location:'.$CONF['script_url'].'?page=edit&file='.rawurlencode($filename));
}

function post_delete()
{
	global $DATA;

	$filename = $_POST['filename'];

	// check that this file can really be deleted
	if (in_array($filename, call('get_file_list')))
	{
		if (!unlink($filename))
		{
			call('add_message', 'File delete error');
			header('Location:'.$CONF['script_url'].'?page=resource_manager');
			return;
		}

		if (isset($DATA[$filename]))
		{
			unset($DATA[$filename]);
			call('save_data');
		}

		call('add_message', $filename.' deleted.');
	}
	else
		call('add_message', $filename.' does not exist.');

	header('Location:'.$CONF['script_url'].'?page=resource_manager');
}

function page_edit()
{
	global $CONF, $DATA, $BODY, $TITLE;

	call('authenticate');

	$filename = '';	
	$data = '';

	// check that the file requested actually exists
	if (isset($_REQUEST['file']))
		$filename = rawurldecode($_REQUEST['file']);

 	if (isset($DATA[$filename]))
		$data = $DATA[$filename];
	else
	{
		// if this is a new file add it with the default metadata
		if (in_array($filename, call('get_file_list')))
		{
			$data = $CONF['default_metadata'];
			$data['filename'] = $filename;
		}
		else
		{
			call('fourohfour');
			return;
		}
	}

	$BODY .= '<div id="content">';
	$BODY .= '<form action="'.$CONF['script_filename'].'?page=post" method="POST">';
	$BODY .= '<div class="manageable">'.call('generate_manageable_item', $data).'</div>';
	$BODY .= '<input type="hidden" name="ACTION" value="save_resource">';
	$BODY .= '<input type="submit" value="Save"/>';
	$BODY .= '</form>';
	$BODY .= '</div>';
	call('render_template');
}

// public function to save data from the resource manager to the local file system
function post_save_resource()
{
	global $CONF, $DATA;

	// get the filename
	$filename = $_POST['filename'];

	// replace the data if it already exists, otherwise prepend it to the array as the top item
	if (isset($DATA[$filename]))
		$DATA[$filename] = $_POST;
	else
		$DATA = array_merge(array($filename => $_POST), $DATA);

	call('save_data');

	// redirect to the resource page
	header('Location:'.$CONF['script_url'].'?file='.rawurlencode($filename));
}

// returns the html for a single item on the resource workflow
function generate_manageable_item($data)
{
	global $CONF;

	// render the basic fields
	$item_html = '
		<h1><a href="'._E_(call('get_file_link', _F_($data,'filename'))).'" target="_blank">'._EF_($data,'filename').'</a></h1>
		<input type="hidden" name="filename" value="'._EF_($data,'filename').'" />';
		
	// optional fields
	foreach ($CONF['fields'] as $fieldname)
		$item_html .= call_optional("generate_input_field_$fieldname", $data).'<div class="clearer end_field"></div>';

	return $item_html;
}

// helper function for implementing multifields
function generate_multifield_input_widget($params)
{
	$data = $params[0];
	$fieldname = $params[1];
	
	$html = '<div class="multifield multifield_'.$fieldname.'" id="'.$fieldname.'">';

	$field = _F_($data,$fieldname);
	// check if there are entires currently set for this resource
	if ($field)
		// loop through the elements and create the table rows
		for ($i = 0; $i < sizeof($field); $i++)
		{
		$html .= '<div>
				'.call('generate_multifield_input_'.$fieldname, array($data, $i)).'
				<a href="#" onclick="javascript:$(this).parent().remove();return false;">remove</a>
			</div>';
		}
	// add the new item button
	$html .= '<a id="add'.$fieldname.'" href="#" onclick="javascript:multifield_add(\''.$fieldname.'\');return false;">add</a>';
	$html .= '</div>';
	return $html;
}

// field definition for manageable item
function generate_input_field_title($data)
{
	return '<label>Title</label><input name="title" value="'._EF_($data,'title').'" autocomplete="off" />';
}

// field definition for manageable item
function generate_input_field_description($data)
{
	return '<label>Description</label><textarea name="description" autocomplete="off" rows="8">'._EF_($data,'description').'</textarea>';
}

// field definition for manageable item
function generate_input_field_creators($data)
{
	return '<label>Creators</label>'.call('generate_multifield_input_widget', array($data,'creators')).'
		<div class="new_multifield" id="new_creators">
			'._E_('<input name="creators[]" autocomplete="off" /><input name="emails[]" autocomplete="off" />').'
		</div>';
}

function generate_multifield_input_creators($params)
{
	$data = $params[0];
	$i = $params[1];

	$creators = _F_($data, 'creators');
	$emails = _F_($data, 'emails');

	return '<input name="creators[]" value="'._E_($creators[$i]).'" autocomplete="off" /><input name="emails[]" value="'._E_($emails[$i]).'" autocomplete="off" />';
}

// field definition for manageable item
function generate_input_field_license($data)
{
	global $CONF;

	// add license dropdown box
	$license_options = '';
	foreach ($CONF['licenses'] as $key => $value)	
	{
		if (_F_($data, 'license') == $key)
			$selected = 'selected';
		else
			$selected = '';

		$license_options .= '<option value="'.$key.'" '.$selected.' autocomplete="off">'.$value.'</option>';
	}

	return '<label>License</label><select name="license" autocomplete="off">'.$license_options.'</select>';
}

/*********************
   RSS Export Module
 *********************/

// Public function for the RSS feed
function page_rss() {
	global $CONF, $DATA;
        
//	header('Content-type: application/rss+xml');

	print 
'<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/"><channel>
	<title>'.$CONF['repository_name'].'</title>
	<link>'.$CONF['script_url'].'</link>
	<atom:link rel="self" href="'.$CONF['script_url'].'?page=rss" type="application/rss+xml" xmlns:atom="http://www.w3.org/2005/Atom"></atom:link>
	<description>'.$CONF['repository_tagline'].'</description>
	<language>en</language>
';
	// loop through all files which are public accessible
	foreach(call('get_resource_list') as $filename)
	{
		$data = $DATA[$filename];
                print '	<item>
';
		//  fields
		foreach ($CONF['fields'] as $fieldname)
			print call_optional("generate_rss_field_$fieldname", $data);
		print '	</item>
';
        }

	print '</channel></rss>';
}

// field definition for rss
function generate_rss_field_title($data)
{
	return '		<title>'._EF_($data,'title').'</title>
';
}

// field definition for rss
function generate_rss_field_description($data)
{
	return '		<description>'._EF_($data,'description').'</description>
';
}

// field definition for rss
function generate_rss_field_date($data)
{
	return '		<pubDate>'.call('get_file_date', _F_($data,'filename')).'</pubDate>
';
}

// field definition for rss
function generate_rss_field_download($data)
{
	global $CONF;
	$resource_url = _E_($CONF['script_url'].'?file='.rawurlencode(_F_($data, 'filename')));
	return '		<link>'.$resource_url.'</link>
		<guid>'.$resource_url.'</guid>
';
}


/*********************
   RDF Export Plugin
 *********************/

// public function for RDF
function page_rdf() {
        global $CONF, $DATA;

	if (isset($_REQUEST['file']))
		$resource_list = array($_REQUEST['file']);
	else
		$resource_list = call('get_resource_list');

//	header("Content-type: application/rdf+xml");
	print 
'<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:bibo="http://purl.org/ontology/bibo/" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dct="http://purl.org/dc/terms/">
';

	// loop through all files which are public accessible
        foreach($resource_list as $filename)
	{
		$data = $DATA[$filename];
		$resource_uri = _E_($CONF['script_url'].'?file='.$filename);

		//  fields
		foreach ($CONF['fields'] as $fieldname)
			print call_optional("generate_rdf_field_$fieldname", array($data, $resource_uri));
	}

	print '</rdf:RDF>';
}

// field definition for RDF
function generate_rdf_field_title($params)
{
	$data = $params[0];
	$resource_uri = $params[1];

	return 
'	<rdf:Description rdf:about="'.$resource_uri.'">
		<dc:title>"'._EF_($data,'title').'"</dc:title>
	</rdf:Description>
';
}

// field definition for RDF
function generate_rdf_field_description($params)
{
	$data = $params[0];
	$resource_uri = $params[1];

	return 
'	<rdf:Description rdf:about="'.$resource_uri.'">
		<dc:description>'._EF_($data,'description').'</dc:description>
	</rdf:Description>
';
}

// field definition for RDF
function generate_rdf_field_creators($params)
{
	global $CONF;
	$data = $params[0];
	$resource_uri = $params[1];

	$xml = '';
	$creators = _F_($data,'creators');
	if ($creators)
			foreach($creators as $creator)
			{
				$creator_uri = $CONF['script_url'].'?page=creators#'._E_($creator);
				$xml .=
'	<rdf:Description rdf:about="'.$resource_uri.'">
		<dct:creator rdf:resource="'.$creator_uri.'"/>
	</rdf:Description>
';

				$xml .=
'	<rdf:Description rdf:about="'.$creator_uri.'">
		<foaf:name>'._E_($creator).'</foaf:name>
		<foaf:type rdf:resource="http://xmlns.com/foaf/0.1/Person"/>
	</rdf:Description>
';
			}
	return $xml;
}

// field definition for RDF
function generate_rdf_field_date($params)
{
	$data = $params[0];
	$resource_uri = $params[1];

	return 
'	<rdf:Description rdf:about="'.$resource_uri.'">
		<dct:date>'.call_optional('get_file_date',_F_($data,'filename')).'</dct:date>
	</rdf:Description>
';
}

// field definition for RDF
function generate_rdf_field_download($params)
{
	global $CONF;
	$data = $params[0];
	$resource_uri = $params[1];

	$file_url = _E_($CONF['base_url']._F_($data,'filename'));

	return 
'	<rdf:Description rdf:about="'.$resource_uri.'">
		<dct:hasPart rdf:resource="'.$file_url.'"/>
		<rdf:type rdf:resource="http://purl.org/ontology/bibo/Document"/>
	</rdf:Description>
';
}

// public function for unique people
// allows them to be assigned a URI
function page_creators() {
	global $CONF, $BODY, $TITLE;

	$TITLE = 'Contributors - '.$TITLE;
	$BODY .= '<div id="content"><h1>Contributors</h1><ul>';
	foreach (get_unique_creators() as $creator)
	{
		$BODY .= '<li><a href="#'._E_($creator).'">'._E_($creator).'</a></li>';
	}
	$BODY .= '</ul></div>';
	call('render_template');
}

// get a list of all the unique creators
function get_unique_creators() {
	global $CONF, $DATA;

	$list = array();

	foreach (call('get_resource_list') as $filename)
		if (isset($DATA[$filename]['creators']))
			foreach($DATA[$filename]['creators'] as $creator)	
				array_push($list, $creator);

	natcasesort($list);
	return array_unique($list);
}


/******************************
   Entry Point for RedFeather
 ******************************/
session_set_cookie_params(0, $CONF['script_url']);
session_start();

// If a plugin directory exists, open it and include any php files it contains.
// Some variables and functions could be overwritten at this point, depending on the plugins installed.
if(is_dir($CONF['plugin_dir']))
	if ($dh = opendir($CONF['plugin_dir']))
	{ 
		while (($file = readdir($dh)) !== false) 
			if(is_file($CONF['plugin_dir'].'/'.$file) && preg_match('/\.php$/', $file) && $file != $CONF['script_filename'])
				include($CONF['plugin_dir'].'/'.$file);
		closedir($dh);
	}

// Load the resource metadata
call('load_data');

// Loads the required page according to the get parameters.
// publically accessible functions should be prefixed with "page_".
// If a "file" parameter has been specified in isolation, load the resource page.
// If no parameter has been specified, use the default.
if(isset($_REQUEST['page']))
	call('page_'.$_REQUEST['page']);
else if (isset($_REQUEST['file']))
	call('page_resource');
else
	call($CONF['default_page']);

/*******
   End
 *******/

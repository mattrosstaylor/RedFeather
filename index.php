<?php
ini_set('display_errors', 1);ini_set('log_errors', 1);error_reporting(E_ALL);

// Global variable for storing all aspects of RedFeather's current state.
$VAR = array(); 
// Global variable to act as buffer for all program output
$CSS = '';
$JS = '';
$BODY = '';

/*	
	RedFeather Configuration
*/	
	// Text to use in the site header.
	$VAR['header_text'] = array(
		'Red', // coloured section of main header
		'Feather', // plain section of main header
		'Lightweight Resource Exhibition and Discovery', // site tagline
	);
	// Colour scheme for the repository.
	$VAR['theme'] = array(
		'linkcolor'=>'#AC1F1F', // colour used for hyperlinks, banner trim and the coloured section of the header 
		'bannercolor'=>'#F0D0D0', // colour used for the header and footer
		'text1'=>'black', // main text colour
		'text2'=>'#606060', // annotation colour
		'font'=>'sans-serif', // font to use for the site
		'background'=>'', // page background colour
	);

	// Optional header section to allow navigation from RedFeather back to a parent site.
	//$VAR['return_link'] = array('text'=>'return to site >', 'href'=>'http://www.example.com');

	// Default values for a new resource
	$VAR['default_metadata'] = array(
		'title'=>'',
		'description'=>'', 
		'creators'=>array(''),
		'emails'=>array(''),
		'license'=>''
	);

	// Array of username/password combinations that are allowed to access the resource manager
	$VAR['users'] = array('admin'=>'shoes');

/* 
	End of RedFeather configuration 
*/

// Field definitions
$VAR['fields'] = array(
	'title',
	'description',
	'creators',
	'date',
	'license',
	'download',
);

// Toolbars
$VAR['toolbars'] = array(
	'footer' => array('credit', 'resource_manager'),
	'browse' => array('search', 'rss', 'rdf'),
	'resource' => array('metadata', 'comments'),
);

// List of available licenses for RedFeather
$VAR['licenses'] = array(
	''=>'unspecified',
	'by'=>'Attribution',
	'by-sa'=>'Attribution-ShareAlike',
	'by-nd'=>'Attribution-NoDerivs',
	'by-nc'=>'Attribution-NonCommercial',
	'by-nc-sa'=>'Attribution-NonCommerical-ShareAlike',
	'by-nc-nd'=>'Attribution-NonCommerical-NoDerivs',
);

// Dimensions for various elements for the site.
$VAR['element_size'] = array(
	'preview_width'=>680, // width of the resource preview in px
	'preview_height'=>550, // height of the resource preview in px
	'metadata_width'=>300, // width of the resource metadata column in px
	'metadata_gap'=>15, // size of the gap between the resource preview and metadata column in px
	'manager_width'=>600 // width of the resource manager workflow
);

// Sets the default page for RedFeather
$VAR['default_page'] = 'page_browse';

// The filename for this script
$VAR['script_filename'] = array_pop(explode("/", $_SERVER["SCRIPT_NAME"]));
// The full url of the directory RedFeather is installed in.
$VAR['base_url'] = 'http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], "/")+1);
// The full url of the RedFeather script
$VAR['script_url'] = $VAR['base_url'].$VAR['script_filename'];
// The file used for storing the resource metadata
$VAR['metadata_filename'] = "resourcedata";
// The name of the plugins folder
$VAR['plugin_dir'] = "plugins";

// Maps function names to functions, this allows you to override any RedFeather function.
$function_map = array(
	// 'page_browse'=>'page_browse_new',
);

// Calls a function within RedFeather to provide a simple plugin architecture.
// To maintain compatibility with PHP 4.0, functions should only take a single parameter - which is passed through to the target.
// When a named function is called, the function_map is first checked to see if an override function has been assigned.
// If it has, that function is called, otherwise it will call the function directly.
function call($function, $param=null)
{
	global $function_map;
	if (isset($function_map[$function]))
		return call_user_func($function_map[$function], $param);
	else return call_user_func($function, $param);
}

// as above but doesn't give an error if a non-existent function is called
function call_optional($function, $param=null)
{
	global $function_map;
	if (isset($function_map[$function]))
		return call_user_func($function_map[$function], $param);
	else if (function_exists($function))
		return call_user_func($function, $param);
	else return;
}

// If a plugin directory exists, open it and include any php files it contains.
// Some variables and functions could be overwritten at this point, depending on the plugins installed.
if(is_dir($VAR['plugin_dir']))
	if ($dh = opendir($VAR['plugin_dir']))
	{ 
		while (($file = readdir($dh)) !== false) 
			if(is_file($VAR['plugin_dir'].'/'.$file) && preg_match('/\.php$/', $file))
				include($VAR['plugin_dir'].'/'.$file);
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
	call($VAR['default_page']);

/*
	Functions to interact with the local file system.
*/
// returns a list of all files within the RedFeather resource scope (i.e. that can be annotated)
function get_file_list()
{
	global $VAR;
	$file_list = array();
	$dir = "./";
	foreach (scandir($dir) as $file)
	{
		// exclude directories, hidden files, the RedFeather file and the metadata file
		if( is_dir($dir.$file) || preg_match("/^\./", $file) || $file == $VAR['script_filename'] || $file == $VAR['metadata_filename']) continue;
		array_push($file_list, $file);
	}
	return $file_list;
}


// returns a absolute hyperlink to a given file
function get_file_link($filename)
{
	global $VAR;
	return $VAR['base_url'].$filename;
}

// returns the data a file was last edited
function get_file_date($filename)
{
	return date ("d F Y H:i:s", filemtime($filename));
}

// returns the image size information from a file (replicates the behaviour of the standard php function
function get_image_size($filename)
{
	return getimagesize($filename);
}

// loads the resource metadata from the filesystem in the global variable $VAR
function load_data()
{
	global $VAR;
	$VAR['data'] = unserialize(file_get_contents($VAR['metadata_filename']));
	if(!is_array($VAR['data']) )
		$VAR['data']= array();
}

function save_data()
{
	global $VAR;
	// save the array as serialized PHP
	$fh = fopen($VAR['metadata_filename'], 'w');
	fwrite($fh,serialize($VAR['data']));
	fclose($fh);
}

/* public function to save data from the resource manager to the local file system
	Only available for POST requests
	$_POST['resource_count'] contains the full number of resources being saved.
	Each resource being saved has an explicit number associated with it in order to group its component metadata fields together.
	These fields are indexed in the post array using a concatentation of the fieldname and the resource number.
	Thus, the filename of the 3rd resource is $_POST['filename3'] and the description of the 2nd is $_POST['description2'].
	Arrays are treated in exactly the same way as single values and can be saved without issue.
	Files that are designated as "missing" will have their metadata retained even if they are not part of the main POST.
	The ordering array contains the ids of all the submitted resources in the order they should be saved.
 */
function page_save_all()
{
	global $VAR;
	// check request type
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		header('HTTP/1.1 405 Method Not Allowed');
		return;
	}
	
	// ensure that the user is logged in
	call('authenticate');

	// keep a copy of the old data. This is used to retain resource metadata in the event that a file is missing..
	$old_data = $VAR['data'];
	$VAR['data'] = array();
	
	// loop once for each resource that is being saved
	foreach ($_POST["ordering"] as $i)
	{
		// get the filename, this is used as the main index for the resource.  If no filename is posted, ignore this resource.
		$filename = $_POST["filename$i"];
		if ($filename == NULL) continue;

		// if the resource is marked as missing, retrieve the data from the old array
		if (isset($_POST['missing']) && in_array($i, $_POST['missing']))
		{
			 $VAR['data'][$filename] = $old_data[$filename];
			continue;
		}

		// scan through each parameter in the post array
		foreach ($_POST as $key => $value)
			// if parameter is of the form fieldname.number - it is a field and should be added to data array in the form $VAR['data']['example.doc']['title'] = "Example document"
			if (preg_match("/(.*)($i\$)/", $key, $matches))
				$VAR['data'][$filename][$matches[1]] = $value;
	}
	
	call('save_data');

	// redirect to the resource manager
	header('Location:'.$VAR['script_url'].'?page=manage_resources');
}

/*
	End of functions to interact with the local file system.
*/


// generates a named toolbar
function generate_toolbar($toolbar)
{
	global $VAR;

	$html ="<ul class='toolbar_$toolbar'>";

	foreach($VAR['toolbars'][$toolbar] as $tool)
		$html .= "<li>".call("generate_toolbar_item_".$toolbar."_".$tool)."</li>";

	return $html .= "</ul>";
}

// function to get a list of all the complete resources (i.e. with matching file/metadata)
function get_resource_list()
{
	global $VAR;
	$list = array();
	$files = call('get_file_list');

	foreach ($VAR['data'] as $filename => $data)
		if (in_array($filename, $files)) array_push($list, $filename);

	return $list;
} 

// function to provide simple authentication functionality
function authenticate() {
	global $VAR, $BODY, $function_map;

	// check the session for an authenticated user and return to the parent function if valid.
	session_set_cookie_params(0, $VAR['script_url']);
	session_start();
	if(isset($_SESSION['current_user']))
	{
		return;
	}

	// If this is a post requesting to log in, check username and password against authorised credentials.
	if (isset($_POST['username']) && isset($_POST['password']) 
		&& isset($VAR['users'][$_POST['username']]) 
		&& $VAR['users'][$_POST['username']]==$_POST['password']) 
	{
		$_SESSION['current_user']=$_POST['username'];
		return;
	}
	

	// if the user is unauthenticated and not making a signing post, render login screen.	

	$BODY .=
		'<div id="content"><form method="post" action="'.$VAR['script_filename'].'?'.$_SERVER['QUERY_STRING'].'">
			Username: <input type="text" name="username" />
			Password: <input type="password" name="password" />
			<input type="submit" value="Login" />
		</form></div>';

	call('render_template');

	exit;
}

// render using template
function render_template()
{
	global $VAR, $BODY, $CSS, $JS;

	// get the main title of the page
	$VAR['page_title'] = $VAR['header_text'][0].$VAR['header_text'][1];


	$page_width = $VAR['element_size']['preview_width']+$VAR['element_size']['metadata_gap']+$VAR['element_size']['metadata_width'];

	// add default CSS
	$base_css = <<<EOT
body { 
	font-family: {$VAR['theme']['font']};
	font-size: 14px;
	color: {$VAR['theme']['text1']};
	background: {$VAR['theme']['background']};
	line-height: 1.15;
}
.center {
	width: {$page_width}px;
	margin: auto;
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
	color: {$VAR['theme']['linkcolor']};
}
a:link, a:visited {
	text-decoration: none;
}
a:hover, a:active {
	text-decoration: underline;
}
#header {
	background: {$VAR['theme']['bannercolor']};
	padding: 12px;
	border-bottom: 1px solid {$VAR['theme']['linkcolor']};
}
#header h1 {
	font-size: 28px;
	margin-bottom: 0;
}
.#header h1 > a {
	color:inherit;
	text-decoration: none;
}
titlespan {
	color: {$VAR['theme']['linkcolor']};
}
#header h2 {
	font-size: 14px;
	font-style: italic;
	color: {$VAR['theme']['text2']};
}
#footer {
	padding: 6px; 
	background: {$VAR['theme']['bannercolor']};
	border-top: 1px solid {$VAR['theme']['linkcolor']};
	border-bottom: 1px solid {$VAR['theme']['linkcolor']};
}
.toolbar_footer > li {
	display: inline;
	padding: 0 5px;
	border-right: 1px solid {$VAR['theme']['text1']};
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

	// render the top part of the html, including title, jquery, stylesheet, local javascript and page header
	print 
		'<html><head>
			<title>'.$VAR['page_title'].'</title>
			<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
			<link rel="stylesheet" href="http://meyerweb.com/eric/tools/css/reset/reset.css" type="text/css" />
			<style type="text/css">'.$base_css.$CSS.'</style>
			<script type="text/javascript">'.$JS.'</script>
		</head>
		<body>
			<div id="header"><div class="center">
				<h1><a href="'.$VAR['script_url'].'">
					<span class="titlespan">'.$VAR['header_text'][0].'</span>'.$VAR['header_text'][1].'
				</a></h1>';

	// add the optional 'return link' as documented in the RedFeather configuration section
	if (isset($VAR['return_link']))
		print '<a style="float:right;" href="'.$VAR['return_link']['href'].'">'.$VAR['return_link']['text'].'</a>';

	print '
				<h2>'.$VAR['header_text'][2].'</h2>
			</div></div>
			<div class="center">
			'.$BODY.'
			</div>
			<div id="footer">
				<div class="center">'.call('generate_toolbar','footer').'</div>
		</html>';

}

function generate_toolbar_item_footer_credit()
{
	return 'Powered by <a href="http://redfeather.ecs.soton.ac.uk">RedFeather</a>'; 
}

function generate_toolbar_item_footer_resource_manager()
{
	global $VAR;
	return '<a href="'.$VAR['script_url'].'?page=manage_resources">Manage Resources</a>';
}

/*
	Basic Field Definitions
*/
function generate_field_output_creators($data)
{
	$html = '';
	// check that the creator field exists and not an empty placeholder
	if (isset($data['creators']) && trim($data['creators'][0]) != '')
	{
		// table header should be creator/creators depending on size of array
		$html .= '<tr><td>Creator' .((sizeof($data['creators'])>1) ? 's': '').':</td><td>';
		// loop through each creator name and create a mailto link for them if required
		for ($i = 0; $i < sizeof($data['creators']); $i++)
			if (trim($data['emails'][$i]) == '')
				$html .= $data['creators'][$i].'<br/>';
			else
				$html .= '<a href="mailto:'.$data['emails'][$i].'">'.$data['creators'][$i].'</a><br/>';
		$html .= '</td></tr>';
	}

	return $html;
}

function generate_field_output_date($data)
{
	return '<tr><td>Updated:</td><td>'.call('get_file_date', $data['filename']).'</td></tr>';
}

function generate_field_output_license($data)
{
	global $VAR;
	return '<tr><td>License:</td><td>'.$VAR['licenses'][$data['license']].'</td></tr>';
}

function generate_field_output_download($data)
{
	return '<tr><td>Download:</td><td><a target="_blank" href="'.call('get_file_link', $data['filename']).'">'.$data['filename'].'</a></td></tr>';
}

function generate_field_input_title($params)
{
	$data = $params[0];
	$num = $params[1];

	return "<tr><td>Title</td><td><input name='title$num' value='".$data['title']."' autocomplete='off' /></td></tr>";
}

function generate_field_input_description($params)
{
	$data = $params[0];
	$num = $params[1];

	return "<tr><td>Description</td><td><textarea name='description$num' autocomplete='off' rows='8'>".$data['description']."</textarea></td></tr>";
}

function generate_field_input_creators($params)
{
	$data = $params[0];
	$num = $params[1];

	$html = "
		<tr>
			<td>Creators</td>
			<td>
				<table id='creators$num' class='creators'>
					<tr>
						<th>Name</th>
						<th>Email</th>
					</tr>";

	// check if there are creators currently set for this resource
	if (isset($data['creators']))
		// loop through the creators and create the creator/email table rows
		for ($i = 0; $i < sizeof($data['creators']); $i++)
		{
			$html .= "
				<tr>
					<td><input name='creators".$num."[]' value='".$data['creators'][$i]."' autocomplete='off' /></td>
					<td><input name='emails".$num."[]' value='".$data['emails'][$i]."' autocomplete='off' /></td>
					<td><a href='#' onclick='javascript:$(this).parent().parent().remove(); return false;'>remove</a></td>
				</tr>";

		}
	// add the new creator button
	$html .= "
					<tr id='addcreator$num'>
						<td><a creator$num' href='#' onclick='javascript:add_creator$num();return false;'>add new creator</a></td>
					</tr>
				</table>
			</td>
		</tr>";

	global $JS;

	// add the javascript function for the creator widget
	// this is ridiculously inefficient since it is unneccessarily repeated once per resource but I won't fix it right now
	$JS .= <<<EOT
function add_creator$num() {
	var creators = $("#creators$num");
	var addcreator = $("#addcreator$num");
	creators.append('<tr><td><input name="creators{$num}[]" autocomplete="off" /></td><td><input name="emails{$num}[]" autocomplete="off" /></td><td><a href="#" onclick="javascript:$(this).parent().parent().remove(); return false;">remove</a></td></tr>');
	addcreator.remove().appendTo(creators);
}
EOT;

	return $html;
}

function generate_field_input_license($params)
{
	global $VAR;
	$data = $params[0];
	$num = $params[1];

	// add license dropdown box
	$license_options = "";
	foreach ($VAR['licenses'] as $key => $value)	
	{
		if ($data['license'] == $key)
			$selected = 'selected';
		else
			$selected = '';

		$license_options .= "<option value='$key' $selected autocomplete='off'>$value</option>";
	}

	return "<tr><td class='table_left'>Licence</td><td><select name='license$num' autocomplete='off'>$license_options</select></td></tr>";
}

/*
	Public functions.
*/ 

// Browse page for RedFeather.
// Lists all the resources that have been annotated and provides a facility to search.
function page_browse()
{
	global $VAR, $BODY, $CSS;

	$CSS .= <<<EOT
.resource {
	margin-bottom: 12px;
}
.toolbar_browse {
	margin-bottom: 10px;
}
.toolbar_browse > li {
	padding-right: 10px;
	display: inline;
}
.toolbar_browse > li > a > img {
	vertical-align: text-bottom;
}
EOT;

	$BODY .= '<div id="content">'.call('generate_toolbar', 'browse');
	
	// div for resource list
	$BODY .= '<div class="browse_list">';

	// get the list of all files within the RedFeather scope
	foreach(call('get_resource_list') as $filename)
	{
		// retrieve the data and render the resource using the "generate_metadata_table" function
		$data = $VAR['data'][$filename];
		$url = $VAR['script_url']."?file=$filename";
		$BODY .= 
			"<div class='resource'>
				<h1><a href='$url'>{$data['title']}</a></h1>
				<p>{$data['description']}</p>
				".call('generate_metadata_table', $data)."
			</div>";
	}
	$BODY .= '</div></div>';

	call('render_template');
}


function generate_toolbar_item_browse_search()
{
	global $VAR;

	return 
		'Search these resources: <input id="filter" onkeyup="filter()"type="text" value="" />
		<script type="text/javascript">
			function filter(){
				var filter = $("#filter").val();
 				if(filter == ""){
					$(".resource").show();  
					return;
				}
				$(".resource").hide();
				$(".resource:contains("+$("#filter").val()+")").show();
			}
		</script>';
}

function generate_toolbar_item_browse_rss()
{
	global $VAR;
	return '<a href="'.$VAR['script_url'].'?page=rss"><img src="http://icons.iconarchive.com/icons/danleech/simple/16/rss-icon.png"/> RSS</a>';
}

function generate_toolbar_item_browse_rdf()
{

	global $VAR;
	return '<a href="'.$VAR['script_url'].'?page=rdf"><img src="http://icons.iconarchive.com/icons/milosz-wlazlo/boomy/16/database-icon.png"/> RDF+XML</a>';
}

// View the resource preview, metadata and social networking plugin
function page_resource()
{
	global $VAR, $BODY, $CSS;
	
	// check that the file requested actually exists
	if (!isset($_REQUEST['file']) || !isset($VAR['data'][$_REQUEST['file']]))
	{
		$BODY .= 'Invalid resource.';
		call('render_bottom');
		return;
	}

	// get the resource metadata and compute urls
	$data = $VAR['data'][$_REQUEST['file']];

	$CSS .= <<<EOT
#preview {
	width: {$VAR['element_size']['preview_width']}px;
	max-height: {$VAR['element_size']['preview_height']}px;
	overflow: hidden;
	text-align: center;
}
#preview iframe {
	width: {$VAR['element_size']['preview_width']}px;
	height: {$VAR['element_size']['preview_height']}px;
}
#preview .message {
	display: none;
	text-align: justify;
}
#preview.message_inserted iframe {
	margin-top: -{$VAR['element_size']['preview_height']}px;
}
#preview.message_inserted .message {
	height: {$VAR['element_size']['preview_height']}px;
	display: block;
}
EOT;

	$BODY .=
		'<div id="content">
			<div class="metadata">
			'.call('generate_toolbar', 'resource').'
			</div>
			<div id="preview">'.call('generate_preview', array($data['filename'], $VAR['element_size']['preview_width'], $VAR['element_size']['preview_height'])).'</div>
			<div class="clearer"></div>
		</div>';

	call('render_template');
}

// toolbar
function generate_toolbar_item_resource_metadata()
{
	global $VAR;
	$data = $VAR['data'][$_REQUEST['file']];
	
	return '<h1>'.$data['title'].'</h1>
		<p>'.$data['description'].'</p>
		'.call('generate_metadata_table', $data);
}

function generate_toolbar_item_resource_comments()
{
	global $VAR;
	$data = $VAR['data'][$_REQUEST['file']];

	$this_url = $VAR["script_url"].'?file='.$data['filename'];
	return call('generate_comment_widget', $this_url);
}

// return the Facebook comment widget
function generate_comment_widget($this_url)
{
	global $VAR;

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
		<div class="fb-comments" data-href="'.$this_url.'" data-num-posts="2" data-width="'.$VAR['element_size']['metadata_width'].'"></div>';
}

/* Return the preview widget for a given resource at the dimensions specified.
	If the resource is determined to be an image, it renders as a simple <img> element.
	Otherwise, it will be rendered using the googledocs previewer.
	Due to a bug in the googledocs API, the service can sometimes silently fail and return an empty iframe.
	Since there is no way to detect this when it occurs, and is a fatal bug in terms of preview functionality, a workaround has been devised where an error message is hidden underneath the preview widget.  If the preview widget fails it will be visible through the empty iframe. */
function generate_preview($params)
{
	global $VAR;
	$filename = $params[0];
	$width = $params[1];
	$height = $params[2];

	// get absolute url for file
	$file_url = call('get_file_link', $filename);

	// attempt to determine the image dimensions of the resource
	$image_size = call('get_image_size', $filename);
	// if the function succeed, assume the resource is an image
	if ($image_size)
	{
		// stretch the image to fit preview area, depending on aspect ratio
		if ($width-$image_size[0] < $height-$image_size[1])
			return "<img src='$file_url' width='$width'>";
		else	
			return "<img src='$file_url' height='$height'>";
	}
	// if the function failed, attempt to render using googledocs previewer
	else
	{
		global $JS;

		$JS .= <<<EOT
function preview_fallback() {
	var d = document.getElementById('preview');
	d.className = d.className + ' message_inserted';
}
window.setTimeout('preview_fallback()', 10000);
EOT;
		// create error message in case the widget fails to load
		$error_fallback = "
			<div class='message'><h1>Google docs viewer failed to initialise.</h1><p>This is due to a bug in the viewer which occurs when your Google session expires.</p><p>You can restore functionality by logging back into any Google service.</p></div>";
	
		// place the error message directly underneath the widget
		return $error_fallback.'<iframe src="http://docs.google.com/viewer?embedded=true&url='.urlencode($file_url).'"></iframe>';
	}
}

// returns the metadata table for the resource data specified
function generate_metadata_table($data)
{
	global $VAR, $CSS;

	// add custom CSS
	$CSS .= <<<EOT
.metadata {
	width: {$VAR['element_size']['metadata_width']}px;
	float: right;
	margin-left: {$VAR['element_size']['metadata_gap']}px;
	padding:0;
}
.metadata_table {
	margin-bottom: 6px;
	margin-left: 6px;
	font-size: 12px;
}
tr>:first-child {
	color: {$VAR['theme']['text2']};
	padding-right: 12px;
}
EOT;

	$table = '<table class="metadata_table"><tbody>';
	
	//  fields
	foreach ($VAR['fields'] as $fieldname)
	{
		$table .= call_optional("generate_field_output_$fieldname", $data);
	}

	$table .= '</tbody></table>';
	return $table;
}

/* Public function for the RedFeather resource manager
	Provides an interface to annotate all the files accessible to RedFeather.
	User must be authenticated to access this page.
	New files are added to the top of the list.
	Files which have metadata, but are missing from the filesystem are listed as such and provided with a link allowing them to be deleted if required. */
function page_manage_resources()
{
	global $VAR, $BODY, $CSS, $JS;

	call('authenticate');
	
	$CSS .= <<<EOT
.new_resource {
	border-left: 1px dashed {$VAR['theme']['linkcolor']};
	padding-left: 6px;
	margin-bottom: 6px; 
}
.manageable {
	margin-top: 15px;
}
.manageable td {
	padding-bottom:12px;
	vertical-align: middle;
}
tr>:first-child {
	color: {$VAR['theme']['text2']};
	padding-right: 12px;
}
.manageable tr>:nth-child(2) {
	width: {$VAR['element_size']['manager_width']}px;
}
.manageable input, .manageable textarea, .manageable select {
	font: inherit;
	width: 100%;
}
.creators th {
	color: {$VAR['theme']['text2']};
}
.creators td {
	width: 45%;
	padding-bottom: 6px;
}
.creators tr >:nth-child(3) {
	width: 10%;
	text-align: right;
}
EOT;

	// counter for the number of new files detected
	$new_file_count = 0;
	// counter for the total number of files
	$num = 0;
	// list for files that were present in the filesystem	
	$files_found_list = array();
	// buffer for copying manageable resources
	$resource_html = "";

	$BODY .= "<div id='content'><h1>Manage Resources</h1><form action='".$VAR['script_filename']."?page=save_all' method='POST'>";
	
	// iterate through all the files currently present in the filesystem	
	foreach (call('get_file_list') as $filename)
	{
		// numbered field used for ordering
		$order_marker = "<input type='hidden' name='ordering[]' value='$num'/>";

		// if the metadata exists for the file, render the workflow item and add it to the list of found files
		if (isset($VAR['data'][$filename])) {
			array_push($files_found_list, $filename);
		}
		else
		{
			// if the file exists but the metadata doesn't, render a new workflow item with the default metadata
			$data = $VAR['default_metadata'];
			$data['filename'] = $filename;
			$resource_html .= "<div class='manageable new_resource' id='resource$num'>".call('generate_manageable_item', array($data, $num))."$order_marker</div>";
			$new_file_count++;
			$num++;
		}
	}

	
	// loop through all the metadata entries
	foreach ($VAR['data'] as $key => $value) {

		// numbered field used for ordering
		$order_marker = "<input type='hidden' name='ordering[]' value='$num'/>";

		// something
		if (in_array($key, $files_found_list))
			$resource_html .= "<div class='manageable' id='resource$num'>".call('generate_manageable_item', array($VAR['data'][$key], $num))."$order_marker</div>";
		else
			$resource_html .= "<div class='manageable' id='resource$num'><h1>$key</h1><p>Resource not found <a href='#' onclick='javascript:$(\"#resource$num\").remove();return false;'>delete metadata</a></p><input type='hidden' name='filename$num' value='$key'/><input type='hidden' name='missing[]' value='$num'/>$order_marker</div>";
		$num++;
	}

	if ($new_file_count) $BODY .= "<p>$new_file_count new files found.</p>";

	$BODY .= $resource_html;

	// add save button
	$BODY .= "<input type='submit' value='Save'/>";
	$BODY .= "</form></div>";

	// adding the javascript for up/down arrows
	$JS .= <<<EOT
$(document).ready(function() {
	$('<div style="text-size:8px;"><a href="#" class="up">up</a>/<a href="#" class="down">down</a></div>').insertAfter('.manageable > h1');
	$('.up').click(function() {
		var item = $(this).parent().parent();
		var other = item.prev('.manageable');
		if (other.html() == null) return false;
		item.detach().insertBefore(other);
		return false;
	});
	$('.down').click(function() {
		var item = $(this).parent().parent();
		var other = item.next('.manageable');
		if (other.html() == null) return false;
		item.detach().insertAfter(other);
		return false;
	});
});
EOT;
	call('render_template');
}

// returns the html for a single item on the resource workflow
function generate_manageable_item($params)
{
	global $VAR;

	$data = $params[0];
	$num = $params[1];

	// render the basic fields
	$item_html = "
		<h1><a href='".call('get_file_link', $data['filename'])."' target='_blank'>".$data['filename']."</a></h1>
		<input type='hidden' name='filename$num' value='".$data['filename']."' />
		<table>";
		
	// optional fields
	foreach ($VAR['fields'] as $fieldname)
	{
		$item_html .= call_optional("generate_field_input_$fieldname", array($data, $num));
	}
	$item_html .= "</table>";

	return $item_html;
}

// Public function for the RSS feed
function page_rss() {
	global $VAR;
        
	header("Content-type: application/rss+xml");

	print 
'<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/"><channel>
	<title>'.$VAR['header_text'][0].$VAR['header_text'][1].'</title>
	<link>'.$VAR['script_url'].'</link>
	<atom:link rel="self" href="'.$VAR['script_url'].'?page=rss" type="application/rss+xml" xmlns:atom="http://www.w3.org/2005/Atom"></atom:link>
	<description>'.$VAR['header_text'][2].'</description>
	<language>en</language>
';
	// loop through all files which are public accessible
	foreach(call('get_resource_list') as $filename)
	{
		$data = $VAR['data'][$filename];
                print '	<item>
';
		//  fields
		foreach ($VAR['fields'] as $fieldname)
		{
			print call_optional("generate_field_rss_$fieldname", $data);
		}
		print '	</item>
';
        }

	print '</channel></rss>';
}

/*
	Field definitions for RSS output
*/
function generate_field_rss_title($data)
{
	return '		<title>'.htmlentities($data['title']).'</title>
';
}

function generate_field_rss_description($data)
{
	return '		<description>'.htmlentities($data['description']).'</description>
';
}

function generate_field_rss_date($data)
{
	return '		<pubDate>'.call('get_file_date', $data['filename']).'</pubDate>
';
}

function generate_field_rss_download($data)
{
	global $VAR;
	$resource_url = htmlentities($VAR['script_url'].'?file='.$data['filename']);
	return '		<link>'.$resource_url.'</link>
		<guid>'.$resource_url.'</guid>
';
}

// public function for RDF
function page_rdf() {
        global $VAR;

	if (isset($_REQUEST['file']))
		$resource_list = array($_REQUEST['file']);
	else
		$resource_list = call('get_resource_list');

	header("Content-type: application/rdf+xml");
	print 
'<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:bibo="http://purl.org/ontology/bibo/" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dct="http://purl.org/dc/terms/">
';

	// loop through all files which are public accessible
        foreach($resource_list as $filename)
	{
		$data = $VAR['data'][$filename];
		$resource_uri = htmlentities($VAR['script_url'].'?file='.$filename);

		//  fields
		foreach ($VAR['fields'] as $fieldname)
			print call_optional("generate_field_rdf_$fieldname", array($data, $resource_uri));
	}

	print '</rdf:RDF>';
}

/*
	Field definitions for RDF output
*/
function generate_field_rdf_title($params)
{
	$data = $params[0];
	$resource_uri = $params[1];

	return 
"	<rdf:Description rdf:about='$resource_uri'>
		<dc:title>".$data['title']."</dc:title>
	</rdf:Description>
";
}

function generate_field_rdf_description($params)
{
	$data = $params[0];
	$resource_uri = $params[1];

	return 
"	<rdf:Description rdf:about='$resource_uri'>
		<dc:description>".$data['description']."</dc:description>
	</rdf:Description>
";
}

function generate_field_rdf_creators($params)
{
	global $VAR;
	$data = $params[0];
	$resource_uri = $params[1];

	$xml = '';
	if (isset($data["creators"]))
			foreach($data['creators'] as $creator)
			{
				$creator_uri = $VAR['script_url']."?page=creators#".urlencode($creator);
				$xml .=
"	<rdf:Description rdf:about='$resource_uri'>
		<dct:creator rdf:resource='$creator_uri'/>
	</rdf:Description>
";

				$xml .=
"	<rdf:Description rdf:about='$creator_uri'>
		<foaf:name>$creator</foaf:name>
		<foaf:type rdf:resource='http://xmlns.com/foaf/0.1/Person'/>
	</rdf:Description>
";
			}
	return $xml;
}

function generate_field_rdf_date($params)
{
	$data = $params[0];
	$resource_uri = $params[1];

	return 
"	<rdf:Description rdf:about='$resource_uri'>
		<dct:date>".call_optional('get_file_date',$data['filename'])."</dct:date>
	</rdf:Description>
";
}

function generate_field_rdf_download($params)
{
	global $VAR;
	$data = $params[0];
	$resource_uri = $params[1];

	$file_url = htmlentities($VAR['base_url'].$data['filename']);

	return 
"	<rdf:Description rdf:about='$resource_uri'>
		<dct:hasPart rdf:resource='$file_url'/>
		<rdf:type rdf:resource='http://purl.org/ontology/bibo/Document'/>
	</rdf:Description>
";
}

// public function for unique people
function page_creators() {
	global $VAR, $BODY;

	$BODY .= "<div id='content'><h1>Contributors.</h1><ul>";
	foreach (get_unique_creators() as $creator)
	{
		$BODY .= "<li><a href='#".urlencode($creator)."'>$creator</a></li>";
	}
	$BODY .= "</ul></div>";
	call("render_template");

}

// get a list of all the unique creators
function get_unique_creators() {
	global $VAR;

	$list = array();

	foreach (call('get_resource_list') as $filename)
		if (isset($VAR['data'][$filename]['creators']))
			foreach($VAR['data'][$filename]['creators'] as $creator)	
				array_push($list, $creator);

	natcasesort($list);
	return array_unique($list);
}

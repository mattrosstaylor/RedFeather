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
		'title'=>'',
		'description'=>'', 
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
	'manager_width'=>600 // width of the resource manager workflow
);

// Sets the default page for RedFeather
$CONF['default_page'] = 'page_browse';

// The filename for this script
$CONF['script_filename'] = array_pop(explode("/", $_SERVER["SCRIPT_NAME"]));
// The full url of the directory RedFeather is installed in.
$CONF['base_url'] = 'http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], "/")+1);
// The full url of the RedFeather script
$CONF['script_url'] = $CONF['base_url'].$CONF['script_filename'];
// The file used for storing the resource metadata
$CONF['metadata_filename'] = "resourcedata";
// The name of the plugins folder
$CONF['plugin_dir'] = "plugins";


/******************************
   Entry Point for RedFeather
 ******************************/

// If a plugin directory exists, open it and include any php files it contains.
// Some variables and functions could be overwritten at this point, depending on the plugins installed.
if(is_dir($CONF['plugin_dir']))
	if ($dh = opendir($CONF['plugin_dir']))
	{ 
		while (($file = readdir($dh)) !== false) 
			if(is_file($CONF['plugin_dir'].'/'.$file) && preg_match('/\.php$/', $file))
				include($CONF['plugin_dir'].'/'.$file);
		closedir($dh);
	}

// global variable to store resource data
$DATA = array();
// global variable to buffer main body html
$BODY = '';
// global variable to buffer CSS
$CSS = '';
// global variable to buffer Javascript
$JS = '';

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


/*********************
   Utility Functions
 *********************/

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

// Maps function names to functions, this allows you to override any RedFeather function.
$function_map = array(
	// 'page_browse'=>'page_browse_new',
);

// function to provide simple authentication functionality
function authenticate() {
	global $CONF, $BODY, $function_map;

	// check the session for an authenticated user and return to the parent function if valid.
	session_set_cookie_params(0, $CONF['script_url']);
	session_start();
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

// generates a named toolbar
function generate_toolbar($toolbar)
{
	global $CONF;

	$html ="<ul class='toolbar_$toolbar'>";

	foreach($CONF['toolbars'][$toolbar] as $tool)
		$html .= "<li>".call("generate_toolbar_item_".$toolbar."_".$tool)."</li>";

	return $html .= "</ul>";
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
 
// render using template
function render_template()
{
	global $CONF, $BODY, $CSS, $JS;

	$page_width = $CONF['element_size']['preview_width']+$CONF['element_size']['metadata_gap']+$CONF['element_size']['metadata_width'];

	// add default CSS
	$base_css = <<<EOT
body { 
	font-family: {$CONF['theme']['font']};
	font-size: 14px;
	color: {$CONF['theme']['text1']};
	background: {$CONF['theme']['background']};
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

	// render the top part of the html, including title, jquery, stylesheet, local javascript and page header
	print 
		'<html><head>
			<title>'.$CONF['repository_name'].'</title>
			<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
			<link rel="stylesheet" href="http://meyerweb.com/eric/tools/css/reset/reset.css" type="text/css" />
			<style type="text/css">'.$base_css.$CSS.'</style>
			<script type="text/javascript">'.$JS.'</script>
		</head>
		<body>
			<div id="header"><div class="center">
				<h1><a href="'.$CONF['script_url'].'">
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
	return '<a href="'.$CONF['script_url'].'?page=resource_manager">Resource Manager</a>';
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
		if( is_dir($dir.$file) || preg_match("/^\./", $file) || $file == $CONF['script_filename'] || $file == $CONF['metadata_filename']) continue;
		array_push($file_list, $file);
	}
	return $file_list;
}


// returns a absolute hyperlink to a given file
function get_file_link($filename)
{
	global $CONF;
	return $CONF['base_url'].$filename;
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

// Browse page for RedFeather.
// Lists all the resources that have been annotated and provides a facility to search.
function page_browse()
{
	global $CONF, $DATA, $BODY, $CSS;

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
		$data = $DATA[$filename];
		$url = $CONF['script_url']."?file=$filename";
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


// toolbar item for browse page
function generate_toolbar_item_browse_search()
{
	global $CONF;

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

// toolbar item for browse page
function generate_toolbar_item_browse_rss()
{
	global $CONF;
	return '<a href="'.$CONF['script_url'].'?page=rss"><img src="http://icons.iconarchive.com/icons/danleech/simple/16/rss-icon.png"/> RSS</a>';
}

// toolbar item for browse page
function generate_toolbar_item_browse_rdf()
{

	global $CONF;
	return '<a href="'.$CONF['script_url'].'?page=rdf"><img src="http://icons.iconarchive.com/icons/milosz-wlazlo/boomy/16/database-icon.png"/> RDF+XML</a>';
}

// View the resource preview, metadata and social networking plugin
function page_resource()
{
	global $CONF, $DATA, $BODY, $CSS;
	
	// check that the file requested actually exists
	if (!isset($_REQUEST['file']) || !isset($DATA[$_REQUEST['file']]))
	{
		$BODY .= 'Invalid resource.';
		call('render_template');
		return;
	}

	// get the resource metadata and compute urls
	$data = $DATA[$_REQUEST['file']];

	$CSS .= <<<EOT
#preview {
	width: {$CONF['element_size']['preview_width']}px;
	max-height: {$CONF['element_size']['preview_height']}px;
	overflow: hidden;
	text-align: center;
}
#preview iframe {
	width: {$CONF['element_size']['preview_width']}px;
	height: {$CONF['element_size']['preview_height']}px;
}
#preview .message {
	display: none;
	text-align: justify;
}
#preview.message_inserted iframe {
	margin-top: -{$CONF['element_size']['preview_height']}px;
}
#preview.message_inserted .message {
	height: {$CONF['element_size']['preview_height']}px;
	display: block;
}
EOT;

	$BODY .=
		'<div id="content">
			<div class="metadata">
			'.call('generate_toolbar', 'resource').'
			</div>
			<div id="preview">'.call('generate_preview', array($data['filename'], $CONF['element_size']['preview_width'], $CONF['element_size']['preview_height'])).'</div>
			<div class="clearer"></div>
		</div>';

	call('render_template');
}

// toolbar item for resource page
function generate_toolbar_item_resource_metadata()
{
	global $CONF, $DATA;
	$data = $DATA[$_REQUEST['file']];
	
	return '<h1>'.$data['title'].'</h1>
		<p>'.$data['description'].'</p>
		'.call('generate_metadata_table', $data);
}

// toolbar item for resource page
function generate_toolbar_item_resource_comments()
{
	global $CONF, $DATA;
	$data = $DATA[$_REQUEST['file']];

	$this_url = $CONF["script_url"].'?file='.$data['filename'];
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
function generate_preview($params)
{
	global $CONF;
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
	global $CONF, $CSS;

	// add custom CSS
	$CSS .= <<<EOT
.metadata {
	width: {$CONF['element_size']['metadata_width']}px;
	float: right;
	margin-left: {$CONF['element_size']['metadata_gap']}px;
	padding:0;
}
.metadata_table {
	margin-bottom: 6px;
	margin-left: 6px;
	font-size: 12px;
}
tr>:first-child {
	color: {$CONF['theme']['text2']};
	padding-right: 12px;
}
EOT;

	$table = '<table class="metadata_table"><tbody>';
	
	//  fields
	foreach ($CONF['fields'] as $fieldname)
		$table .= call_optional("generate_field_output_$fieldname", $data);

	$table .= '</tbody></table>';
	return $table;
}


// field definition for metadata table
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

// field definition for metadata table
function generate_field_output_date($data)
{
	return '<tr><td>Updated:</td><td>'.call('get_file_date', $data['filename']).'</td></tr>';
}

// field definition for metadata table
function generate_field_output_license($data)
{
	global $CONF;
	return '<tr><td>License:</td><td>'.$CONF['licenses'][$data['license']].'</td></tr>';
}

// field definition for metadata table
function generate_field_output_download($data)
{
	return '<tr><td>Download:</td><td><a target="_blank" href="'.call('get_file_link', $data['filename']).'">'.$data['filename'].'</a></td></tr>';
}


/***************************
   Resource Manager Module
 ***************************

/* Public function for the RedFeather resource manager
	Provides an interface to annotate all the files accessible to RedFeather.
	User must be authenticated to access this page.
	New files are added to the top of the list.
	Files which have metadata, but are missing from the filesystem are listed as such and provided with a link allowing them to be deleted if required. */
function page_resource_manager()
{
	global $CONF, $DATA, $BODY, $CSS, $JS;

	call('authenticate');
	
	$CSS .= <<<EOT
.new_resource {
	border-left: 1px dashed {$CONF['theme']['linkcolor']};
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
	color: {$CONF['theme']['text2']};
	padding-right: 12px;
}
.manageable tr>:nth-child(2) {
	width: {$CONF['element_size']['manager_width']}px;
}
.manageable input, .manageable textarea, .manageable select {
	font: inherit;
	width: 100%;
}
.creators th {
	color: {$CONF['theme']['text2']};
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

	$BODY .= "<div id='content'><h1>Resource Manager</h1><form action='".$CONF['script_filename']."?page=save_all' method='POST'>";
	
	// iterate through all the files currently present in the filesystem	
	foreach (call('get_file_list') as $filename)
	{
		// numbered field used for ordering
		$order_marker = "<input type='hidden' name='ordering[]' value='$num'/>";

		// if the metadata exists for the file, render the workflow item and add it to the list of found files
		if (isset($DATA[$filename])) {
			array_push($files_found_list, $filename);
		}
		else
		{
			// if the file exists but the metadata doesn't, render a new workflow item with the default metadata
			$data = $CONF['default_metadata'];
			$data['filename'] = $filename;
			$resource_html .= "<div class='manageable new_resource' id='resource$num'>".call('generate_manageable_item', array($data, $num))."$order_marker</div>";
			$new_file_count++;
			$num++;
		}
	}

	
	// loop through all the metadata entries
	foreach ($DATA as $key => $value) {

		// numbered field used for ordering
		$order_marker = "<input type='hidden' name='ordering[]' value='$num'/>";

		// something
		if (in_array($key, $files_found_list))
			$resource_html .= "<div class='manageable' id='resource$num'>".call('generate_manageable_item', array($DATA[$key], $num))."$order_marker</div>";
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
	global $CONF;

	$data = $params[0];
	$num = $params[1];

	// render the basic fields
	$item_html = "
		<h1><a href='".call('get_file_link', $data['filename'])."' target='_blank'>".$data['filename']."</a></h1>
		<input type='hidden' name='filename$num' value='".$data['filename']."' />
		<table>";
		
	// optional fields
	foreach ($CONF['fields'] as $fieldname)
		$item_html .= call_optional("generate_field_input_$fieldname", array($data, $num));

	$item_html .= "</table>";

	return $item_html;
}

// field definition for manageable item
function generate_field_input_title($params)
{
	$data = $params[0];
	$num = $params[1];

	return "<tr><td>Title</td><td><input name='title$num' value='".$data['title']."' autocomplete='off' /></td></tr>";
}

// field definition for manageable item
function generate_field_input_description($params)
{
	$data = $params[0];
	$num = $params[1];

	return "<tr><td>Description</td><td><textarea name='description$num' autocomplete='off' rows='8'>".$data['description']."</textarea></td></tr>";
}

// field definition for manageable item
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

// field definition for manageable item
function generate_field_input_license($params)
{
	global $CONF;
	$data = $params[0];
	$num = $params[1];

	// add license dropdown box
	$license_options = "";
	foreach ($CONF['licenses'] as $key => $value)	
	{
		if ($data['license'] == $key)
			$selected = 'selected';
		else
			$selected = '';

		$license_options .= "<option value='$key' $selected autocomplete='off'>$value</option>";
	}

	return "<tr><td class='table_left'>Licence</td><td><select name='license$num' autocomplete='off'>$license_options</select></td></tr>";
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
	global $CONF;
	// check request type
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		header('HTTP/1.1 405 Method Not Allowed');
		return;
	}
	
	// ensure that the user is logged in
	call('authenticate');

	// keep a copy of the old data. This is used to retain resource metadata in the event that a file is missing..
	$old_data = $DATA;
	$DATA = array();
	
	// loop once for each resource that is being saved
	foreach ($_POST["ordering"] as $i)
	{
		// get the filename, this is used as the main index for the resource.  If no filename is posted, ignore this resource.
		$filename = $_POST["filename$i"];
		if ($filename == NULL) continue;

		// if the resource is marked as missing, retrieve the data from the old array
		if (isset($_POST['missing']) && in_array($i, $_POST['missing']))
		{
			 $DATA[$filename] = $old_data[$filename];
			continue;
		}

		// scan through each parameter in the post array
		foreach ($_POST as $key => $value)
			// if parameter is of the form fieldname.number - it is a field and should be added to data array in the form $DATA['example.doc']['title'] = "Example document"
			if (preg_match("/(.*)($i\$)/", $key, $matches))
				$DATA[$filename][$matches[1]] = $value;
	}
	
	call('save_data');

	// redirect to the resource manager
	header('Location:'.$CONF['script_url'].'?page=resource_manager');
}


/*********************
   RSS Export Module
 *********************/

// Public function for the RSS feed
function page_rss() {
	global $CONF, $DATA;
        
	header("Content-type: application/rss+xml");

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
			print call_optional("generate_field_rss_$fieldname", $data);
		print '	</item>
';
        }

	print '</channel></rss>';
}

// field definition for rss
function generate_field_rss_title($data)
{
	return '		<title>'.htmlentities($data['title']).'</title>
';
}

// field definition for rss
function generate_field_rss_description($data)
{
	return '		<description>'.htmlentities($data['description']).'</description>
';
}

// field definition for rss
function generate_field_rss_date($data)
{
	return '		<pubDate>'.call('get_file_date', $data['filename']).'</pubDate>
';
}

// field definition for rss
function generate_field_rss_download($data)
{
	global $CONF;
	$resource_url = htmlentities($CONF['script_url'].'?file='.$data['filename']);
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

	header("Content-type: application/rdf+xml");
	print 
'<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:bibo="http://purl.org/ontology/bibo/" xmlns:foaf="http://xmlns.com/foaf/0.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dct="http://purl.org/dc/terms/">
';

	// loop through all files which are public accessible
        foreach($resource_list as $filename)
	{
		$data = $DATA[$filename];
		$resource_uri = htmlentities($CONF['script_url'].'?file='.$filename);

		//  fields
		foreach ($CONF['fields'] as $fieldname)
			print call_optional("generate_field_rdf_$fieldname", array($data, $resource_uri));
	}

	print '</rdf:RDF>';
}

// field definition for RDF
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

// field definition for RDF
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

// field definition for RDF
function generate_field_rdf_creators($params)
{
	global $CONF;
	$data = $params[0];
	$resource_uri = $params[1];

	$xml = '';
	if (isset($data["creators"]))
			foreach($data['creators'] as $creator)
			{
				$creator_uri = $CONF['script_url']."?page=creators#".urlencode($creator);
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

// field definition for RDF
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

// field definition for RDF
function generate_field_rdf_download($params)
{
	global $CONF;
	$data = $params[0];
	$resource_uri = $params[1];

	$file_url = htmlentities($CONF['base_url'].$data['filename']);

	return 
"	<rdf:Description rdf:about='$resource_uri'>
		<dct:hasPart rdf:resource='$file_url'/>
		<rdf:type rdf:resource='http://purl.org/ontology/bibo/Document'/>
	</rdf:Description>
";
}

// public function for unique people
// allows them to be assigned a URI
function page_creators() {
	global $CONF, $BODY;

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
	global $CONF, $DATA;

	$list = array();

	foreach (call('get_resource_list') as $filename)
		if (isset($DATA[$filename]['creators']))
			foreach($DATA[$filename]['creators'] as $creator)	
				array_push($list, $creator);

	natcasesort($list);
	return array_unique($list);
}

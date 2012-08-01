<?php
ini_set('display_errors', 1);ini_set('log_errors', 1);error_reporting(E_ALL);

// global variables 
$VAR = array();
$PAGE = '';

// use variables
$VAR['users'] = array('admin'=>'shoes');
$VAR['header_text'] = array('Red','Feather','Lightweight Resource Exhibition and Discovery');
$VAR['theme'] = array(
	'color1'=>'#AC1F1F',
	'color2'=>'#F0D0D0',
	'text1'=>'black',
	'text2'=>'#606060',
	'font'=>'sans-serif',
	'background'=>'',
);
$VAR['default_metadata'] = array('title'=>'','description'=>'', 'creators'=>array(''),'emails'=>array(''), 'license'=>'');

//$VAR['header_text'] = array('Cyan','Feather','Lightweight Resource Exhibition and Discovery');$VAR['theme'] = array('color1'=>'#1F1FAC', 'color2'=>'#D0D0F0','text1' => 'black', 'text2'=>'#606060', 'header_logo' => 'http://thumbs.photo.net/photo/8498980-sm.jpg', 'font'=>'serif', 'background'=>'');
//$VAR['default_metadata'] = array('title'=>'','description'=>'', 'creators'=>array('Matt R Taylor'),'emails'=>array('mrt@ecs.soton.ac.uk'), 'license'=>'by-nd');

// set system variables
$VAR['rf_file'] = array_pop(explode("/", $_SERVER["SCRIPT_NAME"]));
$VAR['base_url'] = 'http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], "/")+1);
$VAR['rf_url'] = $VAR['base_url'].$VAR['rf_file'];
$VAR['size'] = array('preview_width'=>680, 'preview_height'=>550, 'metadata_gap'=>15, 'metadata_width'=>300, 'manager_width'=>600);

$VAR['metadata_file'] = "rf_data.php";
$VAR['plugin_dir'] = "rf_plugins";

// function storage
$functions = array();
$function_map = array();

// add core page flows
call_back_list('browse', array( 'load_data', 'render_top','render_browse','render_bottom'));
call_back_list('resource', array( 'load_data', 'render_top','render_resource','render_bottom'));
call_back_list('manage_resources', array( 'authenticate','load_data', 'render_top','render_manage_list','render_bottom'));
call_back_list('save_resources', array('authenticate','load_data','save_data'));
call_back_list("rss", array( 'load_data', 'render_rss' ) );
call_back_list("rdf", array( 'load_data', 'render_rdf' ) );

// set the default page to browse
if(!isset($_REQUEST['page'])) $_REQUEST['page'] = "browse";

// load the plugins
if(is_dir($VAR['plugin_dir']))
	if ($dh = opendir($VAR['plugin_dir']))
	{ 
		while (($file = readdir($dh)) !== false) 
		{
			if(is_file($VAR['plugin_dir'].'/'.$file) && preg_match('/\.php$/', $file))
				include($VAR['plugin_dir'].'/'.$file);
		}
		closedir($dh);
	}

// load the specified page
if(isset($_REQUEST['page']))
	call($_REQUEST['page']);
else
	call('resource');

// output the page html
print preg_replace('/\s+/', ' ',$PAGE);


// FUNCTIONS FROM HERE ON DOWN
function call($function_name)
{
	global $functions, $function_map;
	foreach( $functions[$function_name] as $function )
		if (isset($function_map[$function]))
			call_user_func($function_map[$function]);
		else call_user_func($function);
}

function call_back_list($function_name, $list=NULL)
{
	global $functions;
	if($list == NULL)
	{
		if(isset($functions[$function_name]))
			return $functions[$function_name];
		return array();
	}
	$functions[$function_name] = $list;
}

function get_file_list()
{
	global $VAR;
	$file_list = array();
	$dir = "./";
	foreach (scandir($dir) as $file)
	{
		if(is_dir($dir.$file)) continue;
		if($file == $VAR['rf_file']) continue;
		if($file == $VAR['metadata_file']) continue;
		if(preg_match("/^\./", $file)) continue;
		array_push($file_list, $file);
	}
	return $file_list;
}

function get_file_link($filename)
{
	global $VAR;
	return $VAR['base_url'].$filename;
}

function get_file_date($filename)
{
	return date ("d F Y H:i:s.", filemtime($filename));
}

function get_image_size($filename)
{
	return getimagesize($filename);
}


function load_data()
{
	global $VAR;
	$VAR['data'] = unserialize(file_get_contents($VAR['metadata_file']));
	if(!is_array($VAR['data']) )
		$VAR['data']= array();

}

function save_data()
{
	global $VAR;
	$old_data = $VAR['data'];
	$VAR['data'] = array();
	for ($i = 0; $i < $_REQUEST['resource_count']; $i++)
	{
		$filename = $_REQUEST["filename$i"];
		if ($filename == NULL) continue;

		foreach ($_REQUEST as $key => $value)
			if (preg_match("/(.*)($i\$)/", $key, $matches))
				$VAR['data'][$filename][$matches[1]] = $value;
	}

	if (isset($_REQUEST['missing']))
		foreach ($_REQUEST['missing'] as $missed)
			$VAR['data'][$missed] = $old_data[$missed];

	$fh = fopen($VAR['metadata_file'], 'w');
	fwrite($fh,serialize($VAR['data']));
	fclose($fh); 
	header('Location:'.$VAR['rf_url'].'?page=manage_resources');
}

function generate_stylesheet()
{
	global $VAR;
	$text1 = $VAR['theme']['text1'];
	$text2 = $VAR['theme']['text2'];
	$color1 = $VAR['theme']['color1'];
	$color2 = $VAR['theme']['color2'];
	$background = $VAR['theme']['background'];
	$font = $VAR['theme']['font'];
	$manager_width = $VAR['size']['manager_width']."px";
	$preview_width = $VAR['size']['preview_width']."px";
	$preview_height = $VAR['size']['preview_height']."px";
	$metadata_width = $VAR['size']['metadata_width']."px";
	$metadata_gap = $VAR['size']['metadata_gap']."px";
	$page_width = $VAR['size']['preview_width']+$VAR['size']['metadata_gap']+$VAR['size']['metadata_width']."px";
	return "
body { 
	font-family: $font;
	font-size: 14px;
	color: $text1;
	background: $background;
	line-height: 1.15;
}
.center {
	width: $page_width;
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
	color: $color1;
}
a:link, a:visited {
	text-decoration: none;
}
a:hover, a:active {
	text-decoration: underline;
}
#header {
	background: $color2;
	padding: 12px;
	border-bottom: 1px solid $color1;
}
#header h1 {
	font-size: 28px;
	margin-bottom: 0;
}
#header h2 {
	font-size: 14px;
	font-style: italic;
	color: $text2;
}
#header a {
	color:inherit;
	text-decoration: none;
}
.titlespan {
	color: $color1;
}
#footer {
	padding: 6px; 
	background: $color2;
	border-top: 1px solid $color1;
	border-bottom: 1px solid $color1;
}
#content {
	padding: 6px 0 6px 0;
}
.new_resources {
	border-left: 1px dashed $color1;
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
	color: $text2;
	padding-right: 12px;
}
.manageable tr>:nth-child(2) {
	width: $manager_width;
}
.manageable input, .manageable textarea, .manageable select {
	font: inherit;
	width: 100%;
}
.creators th {
	color: $text2;
}
.creators td {
	width: 45%;
	padding-bottom: 6px;
}
.creators tr >:nth-child(3) {
	width: 10%;
	text-align: right;
}
.metadata {
	width: $metadata_width;
	float: right;
	margin-left: $metadata_gap;
	padding:0;
}
.metadata_table {
	margin-bottom: 6px;
	margin-left: 6px;
	font-size: 12px;
}
#preview {
	width: $preview_width;
	max-height: $preview_height;
	overflow: hidden;
	text-align: center;
}
#preview iframe {
	width: $preview_width;
	height: $preview_height;
}
#preview .message {
	display: none;
	text-align: justify;
}
#preview.message_inserted iframe {
	margin-top: -$preview_height;
}
#preview.message_inserted .message {
	height: $preview_height;
	display: block;
}
.clearer {
	clear: both;
}
.resource {
	margin-bottom: 12px;
}
.resource .field_name {
	color: $text2;
}
.browse_tools {
	vertical-align: center;
	margin-bottom: 6px;
}
";

}

function render_top()
{
	global $VAR, $PAGE;
	$VAR['page_title'] = $VAR['header_text'][0].$VAR['header_text'][1];
	$PAGE .= 
'<html><head>
	<title>'.$VAR['page_title'].'</title>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
	<link rel="stylesheet" href="http://meyerweb.com/eric/tools/css/reset/reset.css" type="text/css" />
	<style type="text/css">'.generate_stylesheet().'</style>
</head><body>
<div id="header"><div class="center">
	<a href="'.$VAR['rf_url'].'">
		<h1><span class="titlespan">'.$VAR['header_text'][0].'</span>'.$VAR['header_text'][1].'</h1>
		<h2>'.$VAR['header_text'][2].'</h2>
	</a>

	<script type="text/javascript">
		window.setTimeout("preview_fallback()", 3000);
		function preview_fallback() {
			var d = document.getElementById("preview");
			d.className = d.className + " message_inserted";
		}

		function add_creator($num) {
			var creators = $("#creators" +$num);
			var addcreator = $("#addcreator" +$num);
			creators.append("<tr><td><input name=\'creators" +$num +"[]\' autocomplete=\'off\' /></td><td><input name=\'emails" +$num +"[]\' autocomplete=\'off\' /></td><td><a href=\'#\' onclick=\'javascript:$(this).parent().parent().remove(); return false;\'>remove</a></td></tr>");
			addcreator.remove().appendTo(creators);
		}
	</script>
</div></div>
<div class="center">';
}

function render_bottom()
{
	global $VAR, $PAGE;
	$PAGE .= '</div><div id="footer"><div class="center">Powered by <a href="http://redfeather.ecs.soton.ac.uk">RedFeather</a> | <a href="'.$VAR['rf_url'].'?page=manage_resources">Manage Resources</a></div></div></html>';
}

function render_browse()
{
	global $VAR, $PAGE;

	$licenses = get_licenses();

	$PAGE .=
'<div id="content"><div class="browse_tools">
	Search these resources: <input id="filter" onkeyup="filter()"type="text" value="" />
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
	</script>
	<a href="'.$VAR['rf_url'].'?page=rss"><img src="http://icons.iconarchive.com/icons/danleech/simple/16/rss-icon.png"/> RSS</a>
	<a href="'.$VAR['rf_url'].'?page=rdf"><img src="http://icons.iconarchive.com/icons/milosz-wlazlo/boomy/16/database-icon.png"/> RDF+XML</a>
</div>';

	$PAGE .= '<div class="browse_list">';
	foreach(get_file_list() as $filename)
	{
		$data = $VAR['data'][$filename];
		$url = $VAR['rf_url']."?page=resource&file=$filename";
		$PAGE .= 
"<div class='resource'>
	<h1><a href='$url'>{$data['title']}</a></h1>
	<p>{$data['description']}</p>
	".generate_metadata_table($data)."
</div>";
	}
	$PAGE .= '</div></div>';
}

function render_resource()
{
	global $VAR, $PAGE;
	$data = $VAR['data'][$_REQUEST['file']];
	$this_url = $VAR["rf_url"].'?page=resource&file='.$data['filename'];
	$file_url = get_file_link($data['filename']); 

	$PAGE .=
'<div id="content">
	<div class="metadata">
		<h1>'.$data['title'].'</h1>
		<p>'.$data['description'].'</p>
		'.generate_metadata_table($data).'
		<div id="fb-root"></div><script>
			(function(d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				js = d.createElement(s); js.id = id;
				js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1";
				fjs.parentNode.insertBefore(js, fjs);
			}(document, "script", "facebook-jssdk"));</script>
			<div class="fb-comments" data-href="'.$this_url.'" data-num-posts="2" data-width="'.$VAR['size']['metadata_width'].'"></div>
	</div>
	<div id="preview">'.generate_preview($data['filename'], $VAR['size']['preview_width'], $VAR['size']['preview_height']).'</div>
	<div class="clearer"></div>
</div>';
}

function generate_preview($filename, $width , $height)
{
	global $VAR;
	$file_url = get_file_link($filename);
	$image_size = get_image_size($filename);
	if ($image_size)
	{
		if ($width-$image_size[0] < $height-$image_size[1])
			return "<img src='$file_url' width='$width'>";
		else	
			return "<img src='$file_url' height='$height'>";
	}
	else
	{
		if (isDomainAvailable('http://docs.google.com/viewer') == false)
		{
			return "<div class='message'><h1>Preview unavailable.</h1><p>The Google docs viewer appears to be inaccessible.</p></div>";
		}
		$error_fallback = "<div class='message'><h1>Google docs viewer failed to initialise.</h1><p>This is due to a bug in the viewer which occurs when your Google session expires.</p><p>You can restore functionality by logging back into any Google service.</p></div>";
		return $error_fallback.'<iframe src="http://docs.google.com/viewer?embedded=true&url='.urlencode($file_url).'"></iframe>';
	}
}

function isDomainAvailable($domain)
{
	// this code is currently not php 4 compliant.
	return true;

	//check, if a valid url is provided
	if(!filter_var($domain, FILTER_VALIDATE_URL))
	{
		return false;
	}

	//initialize curl
	$curlInit = curl_init($domain);
	curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
	curl_setopt($curlInit,CURLOPT_HEADER,true);
 	curl_setopt($curlInit,CURLOPT_NOBODY,true);
	curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

	//get answer
	$response = curl_exec($curlInit);
	curl_close($curlInit);

	if ($response) return true;

	return false;
}

function generate_metadata_table($data)
{
	global $VAR;
	$licenses = get_licenses();
	$table = '<table class="metadata_table"><tbody>';

	if (isset($data['creators']))
	{
		$table .= '<tr><td>Creator' .((sizeof($data['creators'])>1) ? 's': '').':</td><td>';
		for ($i = 0; $i < sizeof($data['creators']); $i++)
			$table .= '<a href="mailto:'.$data['emails'][$i].'">'.$data['creators'][$i].'</a><br/>';
		$table .= '</td></tr>';
	}
	$table .='
		<tr><td>Updated:</td><td>'.get_file_date($data['filename']).'</td></tr>
		<tr><td>License:</td><td>'.$licenses[$data['license']].'</td></tr>
		<tr><td>Download:</td><td><a target="_blank" href="'.get_file_link($data['filename']).'">'.$data['filename'].'</a></td></tr>
	</tbody></table>';
	return $table;
}

function get_licenses()
{
	$cc = array();
	$cc[''] = 'unspecified';
	$cc['by'] = 'Attribution';
	$cc['by-sa'] = 'Attribution-ShareAlike';
	$cc['by-nd'] = 'Attribution-NoDerivs';
	$cc['by-nc'] = 'Attribution-NonCommercial';
	$cc['by-nc-sa'] = 'Attribution-NonCommerical-ShareAlike';
	$cc['by-nc-nd'] = 'Attribution-NonCommerical-NoDerivs';
	return $cc;
}

function authenticate() {
	//global $VAR, $function_map, $_SESSION, $_POST;
	global $VAR, $PAGE, $function_map;
	session_set_cookie_params(0, $VAR['rf_url']);
	session_start();
	if(isset($_SESSION['current_user']))
	{
		return 0;
	}
	if (isset($_POST['username']) && isset($_POST['password']) 
		&& isset($VAR['users'][$_POST['username']]) 
		&& $VAR['users'][$_POST['username']]==$_POST['password']) 
	{
		$_SESSION['current_user']=$_POST['username'];
		return;
	}
	
	
	call_user_func('render_top');

	$PAGE .=
'<form method="post" action="'.$VAR['rf_file'].'?'.$_SERVER['QUERY_STRING'].'">
	Username: <input type="text" name="username" />
	Password: <input type="password" name="password" />
	<input type="submit" value="Login" />
</form>';
	call_user_func('render_bottom');

	print $PAGE;
	exit;
}

function render_manage_list()
{
	global $VAR, $PAGE;
	$PAGE .= '<div id="content"><h1>Manage Resources</h1>';


	$new_file_count = 0;
	$num = 0;
	$manage_resources_html = '';
	$new_resources_html = '';
	$files_found_list = array();
	$PAGE .= "<form action='".$VAR['rf_file']."?page=save_resources' method='POST'>\n";
	
	foreach (get_file_list() as $filename)
	{
		if (isset($VAR['data'][$filename])) {
			$data = $VAR['data'][$filename];
			array_push($files_found_list, $filename);
			$manage_resources_html .= "<div class='manageable' id='resource$num'>".generate_manageable_item($data, $num)."</div>";
		}
		else
		{
			//the default data for the workflow
			$data = $VAR['default_metadata'];
			$data['filename'] = $filename;
			$new_resources_html .= "<div class='manageable' id='resource$num'>".generate_manageable_item($data, $num)."</div>";
			$new_file_count++;
		}
		$num++;
	}
		
	
	// check whether any files are missing
	$missing_resources_html = '';
	$missing_num = 0;

	foreach ($VAR['data'] as $key => $value) {
		if (! in_array($key, $files_found_list))
		{
			$missing_resources_html .= "<div class='manageable' id='missing$missing_num'><p>Resource not found: $key <a href='#' onclick='javascript:$(\"#missing$missing_num\").remove();return false;'>delete metadata</a></p><input type='hidden' name='missing[]' value='$key'/></div>";
			$missing_num++;
		}
	}
	
	$PAGE .= $missing_resources_html;
	if ($new_file_count) $PAGE .= "<div class='new_resources'><p>$new_file_count new files found.</p>".$new_resources_html."</div>";


	$PAGE .= "<div>$manage_resources_html</div>";
	$PAGE .= "<input type='hidden' name='resource_count' value='$num'/>";
	$PAGE .= "<input type='submit' value='Save'/>";
	$PAGE .= "</form></div>";
}


function generate_manageable_item($data, $num)
{
	global $VAR;
	$item_html = "
<h1><a href='".get_file_link($data['filename'])."' target='_blank'>".$data['filename']."</a></h1>
<input type='hidden' name='filename$num' value='".$data['filename']."' />
<table>
	<tr><td>Title</td><td><input name='title$num' value='".$data['title']."' autocomplete='off' /></td></tr>
	<tr><td>Description</td><td><textarea name='description$num' autocomplete='off' rows='8'>".$data['description']."</textarea></td></tr>
	<tr><td>Creators</td><td>
		<table id='creators$num' class='creators'><tr><th>Name</th><th>Email</th><th/></tr>";

	if (isset($data['creators']))
	for ($i = 0; $i < sizeof($data['creators']); $i++)
	{
		$item_html .= "
			<tr>
				<td><input name='creators".$num."[]' value='".$data['creators'][$i]."' autocomplete='off' /></td>
				<td><input name='emails".$num."[]' value='".$data['emails'][$i]."' autocomplete='off' /></td>
				<td><a href='#' onclick='javascript:$(this).parent().parent().remove(); return false;'>remove</a></td>
			</tr>";

	}
	$item_html .= "
	<tr id='addcreator$num'>
		<td><a creator$num' href='#' onclick='javascript:add_creator($num);return false;'>add new creator</a></td>
	</tr>
</table>
";

	$license_options = "";
	foreach (get_licenses() as $key => $value)	
	{
		if ($data['license'] == $key)
			$selected = 'selected';
		else
			$selected = '';

		$license_options .= "<option value='$key' $selected autocomplete='off'>$value</option>";
	}

	$item_html .= "<tr><td class='table_left'>Licence</td><td><select name='license$num' autocomplete='off'>$license_options</select></td></tr>";
	$item_html .= "</table>";

	return $item_html;
}


function render_rss() {
        global $VAR;
        
        header("Content-type: application/rss+xml");

        echo '<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/"><channel>
    <title>RedFeather RSS</title>
    <link>'.$VAR['rf_url'].'</link>
    <atom:link rel="self" href="'.$VAR['rf_url'].'?page=rss" type="application/rss+xml" xmlns:atom="http://www.w3.org/2005/Atom"></atom:link>
    <description></description>
    <language>en</language>
';
        foreach(get_file_list() as $filename)
	{
		$data = $VAR['data'][$filename];
               
		if(!$data['title']) { continue; }
                $resource_url = htmlentities($VAR['rf_url'].'?page=resource&file='.$filename);
                print '<item><pubDate>';
                print get_file_date($filename);
                print '</pubDate>
  <title>'.htmlentities($data['title']).'</title>
  <link>'.$resource_url.'</link>
  <guid>'.$resource_url.'</guid>
  <description>'.htmlentities($data['description']).'</description>
</item>';
        }

        print '</channel></rss>';
}

function render_rdf() {
        global $VAR;
	print 'Coming soon...';        
}


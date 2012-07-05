<?php
ini_set('display_errors', 1);ini_set('log_errors', 1);error_reporting(E_ALL);

// global variables 
$variables = array('page'=>'');

// use variables
$variables['users'] = array('admin'=>'shoes');
$variables['header_text'] = array('Red','Feather','Lightweight Resource Exhibition and Discovery');
$variables['theme'] = array(
	'color1'=>'#AC1F1F',
	'color2'=>'#F0D0D0',
	'text1'=>'black',
	'text2'=>'#606060',
	'font'=>'sans-serif',
	'background'=>'',
);
$variables['default_metadata'] = array('title'=>'','description'=>'', 'creator'=>'','email'=>'', 'license'=>'');
$variables['default_metadata'] = array('title'=>'','description'=>'', 'creator'=>'Matt R Taylor','email'=>'mrt@ecs.soton.ac.uk', 'license'=>'by-nd');

//$variables['header_text'] = array('Green','Feather','Now with a custom name and colour scheme');$variables['theme'] = array('color1'=>'#1FAC1F', 'color2'=>'#D0F0D0','text1' => '#3F5F3F', 'text2'=>'#90A090', 'header_logo'=>'http://gallerywall.co.uk/shop/images/Green_Peacock_Feather.jpg', 'font'=>'serif', 'background'=>'');

$variables['header_text'] = array('Cyan','Feather','Lightweight Resource Exhibition and Discovery');$variables['theme'] = array('color1'=>'#1F1FAC', 'color2'=>'#D0D0F0','text1' => 'black', 'text2'=>'#606060', 'header_logo' => 'http://thumbs.photo.net/photo/8498980-sm.jpg', 'font'=>'serif', 'background'=>'');


//$variables['header_text'] = array('Derp','Feather','Herp herp derp derp derp!!');$variables['theme'] = array('color1'=>'cyan', 'color2'=>'magenta','text1' => 'yellow', 'text2'=>'#55FF55', 'header_logo' => 'http://images.sodahead.com/blogs/000200043/blogs_turkey_4946_822901_poll_xlarge.jpeg', 'background'=>'#daa', 'font'=>'"sans-serif');


// set system variables
$variables['rf_file'] = array_pop(explode("/", $_SERVER["SCRIPT_NAME"]));
$variables['base_url'] = 'http://'.$_SERVER['HTTP_HOST'].substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], "/")+1);
$variables['rf_url'] = $variables['base_url'].$variables['rf_file'];

$variables['metadata_file'] = "rf_data.php";
$variables['plugin_dir'] = "rf_plugins";

// ensures that the metadata file exists
touch($variables['metadata_file']);

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
if(is_dir($variables['plugin_dir']))
	if ($dh = opendir($variables['plugin_dir']))
	{ 
		while (($file = readdir($dh)) !== false) 
		{
			if(is_file($variables['plugin_dir'].'/'.$file) && preg_match('/\.php$/', $file))
				include($variables['plugin_dir'].'/'.$file);
		}
		closedir($dh);
	}

// load the specified page
if(isset($_REQUEST['page']))
	call($_REQUEST['page']);
else
	call('resource');

// output the page html
print $variables['page'];


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

function load_data()
{
	global $variables;
	$variables['data'] = unserialize(file_get_contents($variables['metadata_file']));
	if(!is_array($variables['data']) )
		$variables['data']= array();

}

function save_data()
{
	global $variables;
	$old_data = $variables['data'];
	$variables['data'] = array();
	for ($i = 0; $i < $_REQUEST['resource_count']; $i++)
	{
		$filename = $_REQUEST["filename$i"];
		if ($filename == NULL) continue;

		foreach ($_REQUEST as $key => $value)
			if (preg_match("/(.*)($i\$)/", $key, $matches))
				$variables["data"][$filename][$matches[1]] = $value;
	}

	if (isset($_REQUEST['missing']))
		foreach ($_REQUEST['missing'] as $missed)
			$variables['data'][$missed] = $old_data[$missed];

	$fh = fopen($variables['metadata_file'], 'w');
	fwrite($fh,serialize($variables['data']));
	fclose($fh); 
	header('Location:'.$variables['rf_url'].'?page=manage_resources');
}

function stylesheet()
{
	global $variables;
	$text1 = $variables['theme']['text1'];
	$text2 = $variables['theme']['text2'];
	$color1 = $variables['theme']['color1'];
	$color2 = $variables['theme']['color2'];
	$background = $variables['theme']['background'];
	$font = $variables['theme']['font'];
	$preview_width = '675px';
	$preview_height = '520px';
	return "
body { 
	font-family: $font;
	font-size: 14px;
	text-align: justify;
	color: $text1;
	background: $background;
	line-height: 1.15;
}
.center {
	width:1000px;
	margin: auto;
}
h1 { 
	font-size: 20px; 
	font-weight:bold;
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
.manageable input, .manageable textarea, .manageable select {
	font: inherit;
	width: 500px;
}
#metadata {
	width: 325px;
	margin-left:6px;
	float: right;
	padding: 0;
}
.metadata_table {
	margin-bottom: 6px;
	margin-left: 6px;
	font-size: 12px;
}
#preview {
	max-width: $preview_width;
	max-height: $preview_height;
	overflow: hidden;
	text-align: center;
}
#preview img {
	max-width: inherit;
	max-height: inherit;
}
#preview iframe {
	width: $preview_width;
	height: $preview_height;;
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
	global $variables;
	$variables['page_title'] = $variables['header_text'][0].$variables['header_text'][1];
	$variables['page'] .= 
'<html><head>
	<title>'.$variables['page_title'].'</title>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
	<link rel="stylesheet" href="http://meyerweb.com/eric/tools/css/reset/reset.css" type="text/css" />
	<style type="text/css">'.stylesheet().'</style>
</head>
<body>';
	$variables['page'] .=
'
<div id="header"><div class="center">
	<a href="'.$variables['rf_url'].'">
		<h1><span class="titlespan">'.$variables['header_text'][0].'</span>'.$variables['header_text'][1].'</h1>
		<h2>'.$variables['header_text'][2].'</h2>
	</a>
	</div>
</div>
<div class="center">';
}

function render_bottom()
{
	global $variables;
	$variables['page'] .= '</div><div id="footer"><div class="center">Powered by <a href="http://redfeather.ecs.soton.ac.uk">RedFeather</a> | <a href="'.$variables['rf_url'].'?page=manage_resources">Manage Resources</a></div></div></html>';
}

function render_browse()
{
	global $variables;

	$licenses = get_licenses();

	$variables['page'] .= '<div id="content"><div class="browse_tools">
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
	<a href="'.$variables['rf_url'].'?page=rss"><img src="http://icons.iconarchive.com/icons/danleech/simple/16/rss-icon.png"/> RSS</a>
	<a href="'.$variables['rf_url'].'?page=rdf"><img src="http://icons.iconarchive.com/icons/milosz-wlazlo/boomy/16/database-icon.png"/> RDF+XML</a>
</div>
	';

	$variables['page'] .= '<div class="browse_list">';
	foreach($variables['data'] as $filename => $data)
	{
		$url = $variables['rf_url']."?page=resource&file=$filename";
		$variables['page'] .= "<div class='resource'>";
		$variables['page'] .= "<h1 class='resource_title'><a href='$url'>{$data['title']}</a></h1>";
		$variables['page'] .= "<p class='description'>{$data['description']}</p>";
		$variables['page'] .= make_metadata_table($data);
		$variables['page'] .= "</div>";
	}
	$variables['page'] .= '</div></div>';
}

function render_resource()
{
	global $variables;
	$data = $variables['data'][$_REQUEST['file']];	
	$this_url = $variables["rf_url"].'?page=resource&file='.$_REQUEST['file'];
	$file_url = $variables['base_url'].$_REQUEST['file'];

	$variables['page'] .= '<div id="content">';
	
	
	$variables['page'] .= '<div id="metadata">';

	$variables['page'] .= '<h1>'.$data['title'].'</h1>';
	$variables['page'] .= '<p>'.$data['description'].'</p>';

	$variables['page'] .= make_metadata_table($data);

	$variables['page'] .= '<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, "script", "facebook-jssdk"));</script>
<div class="fb-comments" data-href="'.$this_url.'" data-num-posts="2" data-width="325"></div>';

	$variables['page'] .= '</div>';

	$variables['page'] .= '<div id="preview">'.make_preview($_REQUEST['file']).'</div>';
	$variables['page'] .= '<div class="clearer"></div></div>';
}

function make_preview($filename)
{
	global $variables;
	$file_url = $variables['base_url'].$filename;
	if (getimagesize($filename))
		return '<img src="'.$file_url.'">';
	else 
		return  '<iframe src="http://docs.google.com/viewer?embedded=true&url='.urlencode($file_url).'"></iframe>';
}

function make_metadata_table($data)
{
	global $variables;
	$licenses = get_licenses();

	$variables['page'] .= '<table class="metadata_table"><tbody>';
	$variables['page'] .= '<tr><td>Creator:</td><td>'.$data['creator'].' &lt;<a href="mailto:'.$data['email'].'">'.$data['email'].'</a>&gt;</td></tr>';
	$variables['page'] .= '<tr><td>Updated:</td><td>'.date ("d F Y H:i:s.", filemtime($data['filename'])).'</td></tr>';
	$variables['page'] .= '<tr><td>License:</td><td>'.$licenses[$data['license']].'</td></tr>';
	$variables['page'] .= '<tr><td>Download:</td><td><a target="_blank" href="'.$variables['base_url'].$data['filename'].'">'.$data['filename'].'</a></td></tr>';
	$variables['page'] .= '</tbody></table>';


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
	//global $variables, $function_map, $_SESSION, $_POST;
	global $variables, $function_map;
	session_set_cookie_params(0, $variables['rf_url']);
	session_start();
	if(isset($_SESSION['current_user']))
	{
		return 0;
	}
	if (isset($_POST['username']) && isset($_POST['password']) 
		&& isset($variables['users'][$_POST['username']]) 
		&& $variables['users'][$_POST['username']]==$_POST['password']) 
	{
		$_SESSION['current_user']=$_POST['username'];
		return;
	}
	
	
	call_user_func('render_top');

	$variables['page'] .= '<form method="post" action="'.$variables['rf_file'].'?'.$_SERVER['QUERY_STRING'].'">
	Username: <input type="text" name="username" />
	Password: <input type="password" name="password" />
	<input type="submit" value="Login" />
	</form>';
	call_user_func('render_bottom');

	print $variables['page'];
	exit;
}

function render_manage_list()
{
	global $variables;
	$variables['page'] .= '<div id="content"><h1>Manage Resources</h1>';

	$dir = "./";

	$new_file_count = 0;
	$num = 0;
	$manage_resources_html = '';
	$new_resources_html = '';
	$files_found_list = array();
		
	$variables['page'] .= "<form action='".$variables['rf_file']."?page=save_resources' method='POST'>\n";
	foreach (scandir($dir) as $file)
	{
		if(is_dir($dir.$file)) continue;
		if($file == $variables['rf_file']) continue;
		if($file == $variables['metadata_file']) continue;
		if(preg_match("/^\./", $file)) continue;

		if (isset($variables['data'][$file])) {
			$data = $variables['data'][$file];
			array_push($files_found_list, $file);
			$manage_resources_html .= "<div class='manageable' id='resource$num'>".render_managed($data, $num)."</div>";
		}
		else
		{
			//the default data for the workflow
			$data = $variables['default_metadata'];
			$data['filename'] = $file;
			$new_resources_html .= "<div class='manageable' id='resource$num'>".render_managed($data, $num)."</div>";
			$new_file_count++;
		}
		$num++;
	}
		
	
	// check whether any files are missing
	$missing_resources_html = '';
	$missing_num = 0;

	foreach ($variables['data'] as $key => $value) {
		if (! in_array($key, $files_found_list))
		{
			$missing_resources_html .= "<div class='manageable' id='missing$missing_num'><p>Resource not found: $key <a href='#' onclick='javascript:$(\"#missing$missing_num\").remove();'>delete metadata</a></p><input type='hidden' name='missing[]' value='$key'/></div>";
			$missing_num++;
		}
	}
	
	$variables['page'] .= $missing_resources_html;
	if ($new_file_count) $variables['page'] .= "<div class='new_resources'><p>$new_file_count new files found.</p>".$new_resources_html."</div>";


	$variables['page'] .= "<div>$manage_resources_html</div>";
	$variables['page'] .= "<input type='hidden' name='resource_count' value='$num'/>";
	$variables['page'] .= "<input type='submit' value='Save'/>";
	$variables['page'] .= "</form></div>";
}


function render_managed($data, $num)
{
	global $variables;
	$item_html = "<h1><a href='".$data['filename']."' target='_blank'>".$data['filename']."</a></h1><input type='hidden' name='filename$num' value='".$data['filename']."' />";
	$item_html .= "<table><tbody>";
	$item_html .= "<tr><td>Title</td><td><input name='title$num' value='".$data['title']."' autocomplete='off' /></td></tr>";
	$item_html .= "<tr><td>Description</td><td><textarea name='description$num' autocomplete='off' rows='8'>".$data['description']."</textarea></td></tr>";
	$item_html .= "<tr><td>Creator</td><td><input name='creator$num' value='".$data['creator']."' autocomplete='off' /></td></tr>";
	$item_html .= "<tr><td>Email</td><td><input name='email$num' value='".$data['email']."' autocomplete='off' /></td></tr>";

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
	$item_html .= "</tbody></table>";

	return $item_html;
}


function render_rss() {
        global $variables;
        
        header("Content-type: application/rss+xml");

        echo '<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/"><channel>
    <title>RedFeather RSS</title>
    <link>'.$variables['rf_url'].'</link>
    <atom:link rel="self" href="'.$variables['rf_url'].'?page=rss" type="application/rss+xml" xmlns:atom="http://www.w3.org/2005/Atom"></atom:link>
    <description></description>
    <language>en</language>
';
        foreach($variables['data'] as $file => $data)
        {
                if(!$data['title']) { continue; }
                $resource_url = htmlentities($variables['rf_url'].'?page=resource&file='.$file);
                print '<item><pubDate>';
                $mtime = "";
                if(is_file($file)){
                        $mtime = filemtime($file);
                }
                print date ("d M Y H:i:s O", $mtime);
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
        global $variables;
	print 'Coming soon...';        
}


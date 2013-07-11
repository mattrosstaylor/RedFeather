<?php

/***************
   RESTful URLs

normally URLs look like this:
/redfeather/index.php?page=foo to /redfeather/foo
and 
/redfeather/index.php?file=bar to /redfeather/file/bar


either put this in apache config

<Location /url/path/to/redfeather>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-l
RewriteRule .* index.php [L,QSA]
</Location>

or this in .htaccess

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-l
RewriteRule .* index.php [L,QSA]

(thanks to FatFreePHP for the technique)

 ***************/

$FUNCTION_OVERRIDE["url"] = "restful_url";
$FUNCTION_OVERRIDE["page_url"] = "restful_page_url";
$FUNCTION_OVERRIDE["file_url"] = "restful_file_url";
$FUNCTION_OVERRIDE["handle_request"] = "restful_handle_request";

function restful_url()
{
	global $CONF;

	$url = $CONF['base_url'];
	return $url;
}

function restful_page_url( $page, $opts="" )
{
	global $CONF;

	$url = $CONF['base_url'].$page;
	if( $opts != "" ) { $url .= "?$opts"; }
	return $url;
}
	
// return the link to a page about a file (can be overridden to allow REST)
function restful_file_url( $file, $opts="" )
{
	global $CONF;
	
	$url = $CONF['base_url'].'file/'.$file;
	if( $opts != "" ) { $url .= "?$opts"; }
	return $url;
}


function restful_handle_request()
{
	global $CONF;
	global $_REQUEST;

	$prefix_len = strrpos($_SERVER['SCRIPT_NAME'], "/")+1;
	$path = substr( $_SERVER["REQUEST_URI"], $prefix_len );
	if( preg_match( "/^file\/([^\/?]+)/", $path, $r ) )
	{
		$_REQUEST["file"] = $r[1];
	}
	elseif( preg_match( "/^([^\/?]+)/", $path, $r ) )
	{
		$_REQUEST["page"] = $r[1];
	}
	handle_request();		
}

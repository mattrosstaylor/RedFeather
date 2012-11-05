<?php

// Dimensions for various elements for the site.
$CONF['element_size'] = array(
	'preview_width'=>750, // width of the resource preview in px
	'preview_height'=>600, // height of the resource preview in px
	'manager_width'=>500 // width of the resource manager workflow
);
$CONF['css'][] = 'css_resource_vertical';

$FUNCTION_OVERRIDE['generate_resource'] = 'generate_resource_vertical';

function css_resource_vertical()
{
	return <<<EOT
#rf_metadata {
	float:none;
	width:100%;
}
EOT;
}

// generates the resource itself
function generate_resource_vertical($data)
{
	global $CONF;
	return '<h1>'._EF_($data,'title').'</h1>
		'.call('generate_preview', _F_($data,'filename')).'
		<div id="rf_metadata">
			<p>'.nl2br(_EF_($data,'description')).'</p>
			'.call('generate_metadata_table', $data)
			.call('generate_comment_widget', $CONF['element_size']['preview_width']).'
		</div>
		<div class="rf_clearer"></div>';
}

?>

<?php


array_push($CONF['fields'], 'keywords');	
array_push($CONF['toolbars']['browse'], 'tagcloud');

$CONF['tagcloud_minsize'] = 20;
$CONF['tagcloud_maxsize'] = 72;


$CSS .= <<<EOT
.keyword:hover {
	text-decoration: underline;
}
EOT;

// field definition for metadata table
function generate_output_field_keywords($data)
{
	$html = '';
	$keywords = _F_($data,'keywords');
	// check that the keyword field exists and not an empty placeholder
	if ($keywords && trim($keywords[0]) != '')
	{
		$html .= '<tr><td>Keyword' .((sizeof($keywords)>1) ? 's': '').':</td><td>';
		// loop through each keyword
		for ($i = 0; $i < sizeof($keywords); $i++)
			$html .= '<span class="keyword">'._E_($keywords[$i]).'</span>'.($i < sizeof($keywords)-1 ? ', ' : '');
		$html .= '</td></tr>';
	}

	return $html;
}    

// field definition for metadata table
function generate_input_field_keywords($data)
{
	return '<label>Keywords</label>'.call('generate_multifield_input_widget', array($data,'keywords')).'
		<div class="new_multifield" id="new_keywords">
			'._E_('<input name="keywords[]" autocomplete="off" />').'
		</div>';
}

// input field for resource manager
function generate_multifield_input_keywords($params)
{
	$data = $params[0];
	$i = $params[1];

	$keywords = _F_($data, 'keywords');
		return '<input name="keywords[]" value="'._E_($keywords[$i]).'" autocomplete="off" />';
}


// toolbar item for browse page
function generate_toolbar_item_browse_tagcloud()
{
	global $CONF;
	return '<a href="'.$CONF['script_filename'].'?page=tagcloud"><img src="http://icons.iconarchive.com/icons/fatcow/farm-fresh/16/tag-yellow-icon.png"/> TagCloud</a>';
}


// public page for tagcloud
function page_tagcloud()
{
	global $TITLE, $BODY, $DATA, $CONF;
	$TITLE = 'TagCloud - '.$TITLE;
	$BODY .= '<div id="content">';
	$BODY .= '<h1>TagCloud</h1>';
	$BODY .= '</div>';

	$count = array();

	// collate the keywords
	foreach ($DATA as $filename => $data)
		if (isset($data['keywords']))
			foreach ($data['keywords'] as $keyword)
				if (isset($count[$keyword]))
					$count[$keyword]++;
				else
					$count[$keyword] = 1;

	// get the max/min counts and unique list
	$min_count = 99999;
	$max_count = 0;
	$list = array();

	foreach ($count as $keyword => $c)
	{
		if ($c < $min_count)
			$min_count = $c;
		if ($c > $max_count)
			$max_count = $c;
		array_push($list, $keyword); 
	}
	sort($list);


	// special hack to prevent divide by zero
	if ($max_count-$min_count == 0)
		$min_count--;

	$min_size = $CONF['tagcloud_minsize'];
	$max_size = $CONF['tagcloud_maxsize'];

	// output the keyword list at the correct size
	foreach ($list as $keyword)
	{
		$size = (($count[$keyword]-$min_count) / ($max_count-$min_count)) * ($max_size - $min_size) + $min_size;
		$BODY .= '<span class="keyword" style="font-size: '.$size.'px">'.$keyword.' </span>';
	}

	call("render_template");
}
?>

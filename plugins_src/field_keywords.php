<?php

array_push($CONF['fields'], 'keywords');	

// field definition for metadata table
function generate_output_field_keywords($data)
{
/*	$html = '';
	$keywords = _F_($data,'keywords');
	// check that the keyword field exists and not an empty placeholder
	if ($keywords && trim($keywords[0]) != '')
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

	return $html; */
}    

// field definition for manageable item
function generate_input_field_keywords($params)
{
	$data = $params[0];
	$num = $params[1];

	return '<label>Keywords</label>'.call('generate_multifield_input_widget', array($data,$num,'keywords')).'
		<div class="new_multifield" id="new_keywords'.$num.'">
			'._E_('<input name="keywords'.$num.'[]" autocomplete="off" />').'
		</div>';
}

function generate_multifield_input_keywords($params)
{
	$data = $params[0];
	$num = $params[1];
	$i = $params[2];

	$keywords = _F_($data, 'keywords');
	return '<input name="keywords'.$num.'[]" value="'._E_($keywords[$i]).'" autocomplete="off" />';
}
?>

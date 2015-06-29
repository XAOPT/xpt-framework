<?php
function get_fields_list($fields = array(), $prefix = '')
{
	$output = array();

	if (!empty($fields))
	foreach ($fields as $f)
	{
		if (!isset($f['parent']) || ($f['parent'] == '' && $f['type'] != 'array'))
		{
			$value = '';
			if ($f['type'] == 'checks')
			{
				$output[$f['sysname']] = (int)zReq::getVar($prefix.$f['sysname'], 'BOOL', 'POST', '');
			}
			else
			{
				$temp = zReq::getVar($prefix.$f['sysname'], 'SQL', 'POST', '');
				if ($temp != '')
					$output[$f['sysname']] = $temp;
			}
		}
		else if ($f['type'] == 'array')
		{
			$output[$f['sysname']] = array();

			foreach ($fields as $ch)
			{
				if ($ch['parent'] == $f['sysname'])
				{
					foreach ($_POST as $post => $value)
					{
						if (preg_match('/'.$ch['sysname'].'_(\d+)$/', $post, $matches))
						{

							$index = $matches[1];

							$output[$f['sysname']][$index][$ch['sysname']] = ($f['type'] == 'checks')?zReq::getVar($ch['sysname'].'_'.$index, 'BOOL', 'POST', ''):zReq::getVar($ch['sysname'].'_'.$index, 'SQL', 'POST', '');
						}
					}
				}
			}

			// делаем из хеша обычный массив
			$temp = array();

			foreach ($output[$f['sysname']] as $o) {$temp[] = $o;}

			$output[$f['sysname']] = $temp;
		}
	}

	return $output;
}

function get_and_validate_fields()
{
	$data = array(
		'field_title' => zReq::getVar('field_title', 'SQL', 'POST', ''),
		'sysname'     => zReq::getVar('sysname', 'SQL', 'POST', ''),
		'type'        => zReq::getVar('type', 'SQL', 'POST', 'text'),
		'value'       => zReq::getVar('field_value', 'SQL_ARRAY', 'POST', ''),
		'default'     => zReq::getVar('default', 'SQL', 'POST', ''),
		'parent'      => zReq::getVar('parent', 'SQL', 'POST', '')
	);

	// убираем пустые значения для селекта
	if (!empty($data['value']))
	{
		$temp = array();
		foreach ($data['value'] as $v)
		{
			if (!empty($v))
				$temp[] = $v;
		}
		$data['value'] = implode(',', $temp);
	}
	else {
		$data['value'] = '';
	}

	if (!$data['field_title'] || !$data['sysname'])
		return false;

	return $data;
}


function show_field_add_form($options_string)
{
	$temp = explode('||', $options_string);

	$options = array();
	for ($i=0; $i < count($temp); $i+=2) {
		$options[$temp[$i]] = $temp[$i+1];
	}

	$output = '
		<form action="'.$options['url'].'" method="post" class="table">
		';

	if (isset($options['hidden']))
		$output .= $options['hidden'];

	$output .= '
		<h4>{:add_parametr}</h4>
		<dl class="dl-horizontal">
				<dt>{:title}</dt>
				<dd><input class="form-control input-sm" name="field_title" type="text"></dd>

				<dt>{:sysname}</dt>
				<dd><input class="form-control input-sm" name="sysname" type="text"></dd>

			<div id="field_type_select">
				<dt>{:type}</dt>
				<dd>
					<select class="form-control input-sm" name="type">
						<option value="text">text</option>
						<option value="textarea">textarea</option>
						<option value="checks">checks</option>
						<option value="select">select</option>
						<option value="separator">separator</option>
						<option value="array">array</option>
					</select>
				</dd>
			</div>
	';

	if (isset($options['parents']) && !empty($options['parents']))
	{
		$output .= '
			<div class="parent_holder">
				<dt>{:parent}</dt>
				<dd><select class="form-control input-sm" name="parent">
					<option value="">-</option>
		';

		$parents = explode(',', $options['parents']);

		foreach ($parents as $p)
		{
			$output .= "<option value='{$p}'>{$p}</option>";
		}

		$output .= '
				</select></dd>
			</div>
		';
	}

	$output .= '
			<div class="by_default_holder">
				<dt>{:by_default}</dt>
				<dd><input class="form-control input-sm" name="default" type="text"></dd>
			</div>
				<dt></dt>
				<dd><input class="btn btn-mini btn-primary" type="submit" value="{:add}"></dd>
		</dl>
		</form>
	';

	return $output;
}


function show_field_input($value = array(), $options = array())
{
	if (empty($value))
		return;

	switch($value['type'])
	{
		case 'text':
			echo "<input class='form-control input-sm' name='{$value['sysname']}' type='text' value='";
			if (isset($options[$value['sysname']]))
				echo $options[$value['sysname']];
			else
				echo $value['default'];
			echo "'>";
			break;
		case 'textarea':
			echo "<textarea class='form-control' name='{$value['sysname']}'>";
			if (isset($options[$value['sysname']]))
				echo $options[$value['sysname']];
			else
				echo $value['default'];
			echo '</textarea>';
			break;
		case 'checks':
			echo "<input type='checkbox' name='{$value['sysname']}'";
			if (
				( isset($options[$value['sysname']])  && $options[$value['sysname']] > 0 ) ||
				(!isset($options[$value['sysname']]) && $value['default'])
			)
				echo 'CHECKED';

			echo '>';
			break;
		case 'select':
			echo "<select class='form-control input-sm' name='{$value['sysname']}'>";

			$counter = 0;
			foreach ($value['value'] as $k=>$v)
			{
				echo '<option value="'.$v.'" ';

				if (
					  (isset($options[$value['sysname']]['value']) && $v == $options[$value['sysname']]['value']) ||
					  (isset($options[$value['sysname']]) && $v == $options[$value['sysname']]) ||
					  (!isset($options[$value['sysname']]) && $v == $value['default']) ||
					  ((isset($options[$value['sysname']]['value']) && $k == $options[$value['sysname']]['value']))
					){
					echo 'SELECTED';}

				echo ">{$v}</option>";
				$counter++;
			}

			echo '</select>';
			break;
		default:
			echo '';
			break;
	}
}

/*function show_field_add_form2($options_string)
{
	$temp = explode('||', $options_string);

	$options = array();
	for ($i=0; $i < count($temp); $i+=2) {
		$options[$temp[$i]] = $temp[$i+1];
	}

	$output = '
		<form action="'.$options['url'].'" method="post">
		';

	if (isset($options['hidden']))
		$output .= $options['hidden'];

	$output .= '
		<table id="field_form">
			<tr>
				<th>{:title}</th>
				<th>{:sysname}</th>
				<th>{:type}</th>
				<th>{:value}</th>
				<th>{:by_default}</th>
				<th></th>
			</tr>
			<tr>
				<td><input name="field_title" type="text"></td>
				<td><input name="sysname" type="text"></td>
				<td>
					<select name="type">
						<option value="text">text</option>
						<option value="textarea">textarea</option>
						<option value="checks">checks</option>
						<option value="select">select</option>
						<option value="separator">separator</option>
					</select>
				</td>
				<td id="field_value">-</td>
				<td><input name="default" type="text"></td>
				<td><input class="btn btn-mini btn-primary" type="submit" value="{:add}"></td>
			</tr>
		</table>
		</form>

		<script>
		$(document).ready(function()
		{

			$("select[name=\'type\']").change(function() {
				val = $(this).val();
				if (val=="select")
					$("#field_value").html("<textarea name=\'field_value\'></textarea>");
				else
					$("#field_value").html("-");
			});
		});
		</script>
	';

	return $output;
}*/
?>
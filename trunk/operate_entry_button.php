<?php

// needs $submit, $items, $name_pattern, $user

echo $back_url;
$back_button = $OUTPUT->single_button($back_url, get_string('back_button', 'apply'));


if ($submit) {
	//
	echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

	echo '<input type="radio" name="example" value="サンプル" />サンプル';
	echo '<input type="radio" name="example" value="サンプル" checked />サンプル';
	echo '<br />';
	echo '<input type="checkbox" name="example" value="サンプル">サンプル';

	$inputvalue = 'value="'.get_string('operate_entry_button', 'apply').'"';
	$submit_button = '<input name="oprate_values" type="submit" '.$inputvalue.' />';
	$reset_button  = '<input type="reset" value="'.get_string('clear').'" />';

	//
	echo '<div align="center">';
	echo '<table border="0">';
	echo '<tr>';
	echo '<td>'.$back_button.'</td>';
	echo '<td>&nbsp;&nbsp;&nbsp;</td>';
	echo '<td>'.$reset_button.'</td>';
	echo '<td>&nbsp;&nbsp;&nbsp;</td>';
	echo '<td>'.$submit_button.'</td>';
	echo '</tr>';
	echo '</table>';
	echo '</div>';

	echo $OUTPUT->box_end();
}

//
else {
	echo '<div align="center">';
	echo $back_button;
	echo '</div>';
}

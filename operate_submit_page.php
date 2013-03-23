<?php

// needs $submit, $items, $name_pattern, $user



if ($submit) {
	//
    echo '<form action="operate_submit.php" method="post">';
    echo '<fieldset>';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="operate" value="operate" />';

	//
	$align   = right_to_left() ? 'right' : 'left';
	$student = $DB->get_record('user', array('id'=>$submit->user_id));

	if 		($name_pattern=='firstname') $user_name = $student->firstname;
	else if ($name_pattern=='lastname')  $user_name = $student->lastname;
	else								 $user_name = fullname($student); 

	$title = $user_name.' ('.userdate($submit->time_modified, '%Y/%m/%d %H:%M').')';
	if ($submit_ver==0) $title .= ' '.get_string('title_draft','apply');


	echo $OUTPUT->heading($title, 3);

	//
	if ($err_message!='') {
		echo $OUTPUT->box_start('mform error boxaligncenter boxwidthwide');
		echo $err_message;
		echo $OUTPUT->box_end();
	}

	//
	echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

	foreach ($items as $item) {
		//get the values
		$params = array('submit_id'=>$submit->id, 'item_id'=>$item->id, 'version'=>$submit_ver);
		$value  = $DB->get_record('apply_value', $params);

		echo $OUTPUT->box_start('apply_print_item');
		if ($item->typ!='pagebreak' and $item->label!=APPLY_NODISP_TAG and $item->label!=APPLY_ADMIN_TAG) {
			if (isset($value->value)) {
				apply_print_item_show_value($item, $value->value);
			}
			else {
				apply_print_item_show_value($item, false);
			}
		}
        else if ($item->label==APPLY_ADMIN_TAG) {
			if (isset($value->value)) {
            	apply_print_item_submit($item, $value->value);
			}
			else {
            	apply_print_item_submit($item, false);
			}
        }
		echo $OUTPUT->box_end();
	}
	require('entry_info.php');

	echo $OUTPUT->box_end();

	//

	echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
	$accept_input = '<input type="radio" name="radiobtn_accept"  value="accept" /><strong>'.get_string('accept_entry', 'apply').'</strong>';
	$reject_input = '<input type="radio" name="radiobtn_accept"  value="reject" /><strong>'.get_string('reject_entry', 'apply').'</strong>';
	$execd_input  = '<input type="checkbox" name="checkbox_execd" value="execd" /><strong>'.get_string('execd_entry',  'apply').'</strong>';
	//
/*
	echo $OUTPUT->box_start('apply_print_item');
	echo $accept_input;
	echo '&nbsp;&nbsp;&nbsp;&nbsp;';
	echo $reject_input;
	echo $OUTPUT->box_end();
	echo $OUTPUT->box_start('apply_print_');
	echo $execd_input;
	echo $OUTPUT->box_end();
*/
	echo '<table border="0" class="operation_submit">';
	echo '<tr>';
	echo '<td>'.$accept_input.'</td>';
	echo '<td>&nbsp;&nbsp;&nbsp;</td>';
	echo '<td>'.$reject_input.'</td>';
	echo '</tr>';
	echo '<tr>';
	echo '<td>'.$execd_input.'</td>';
	echo '<td>&nbsp;&nbsp;&nbsp;</td>';
	echo '<td>&nbsp;&nbsp;&nbsp;</td>';
	echo '</tr>';
	echo '</table>';
	echo $OUTPUT->box_end();

	//
	$submit_value  = 'value="'.get_string('operate_submit_button', 'apply').'"';
	$back_value    = 'value="'.get_string('back_button', 'apply').'"';
	$reset_value   = 'value="'.get_string('clear').'"';
	$submit_button = '<input name="operate_values"  type="submit" '.$submit_value.' />';
	$back_button   = '<input name="back_to_entries" type="submit" '.$back_value.' />';
	$reset_button  = '<input type="reset" '.$reset_value.' />';
	//
    echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
    echo '<input type="hidden" name="submit_id"  value="'.$submit->id.'" />';
    echo '<input type="hidden" name="submit_ver" value="'.$submit->version.'" />';

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

	echo '</fieldset>';
    echo '</form>';
}

//
else {
	$back_button = $OUTPUT->single_button($back_url, get_string('back_button', 'apply'));
	//
	echo $OUTPUT->heading(get_string('no_submit_data', 'apply'), 3);
	echo '<div align="center">';
	echo $back_button;
	echo '</div>';
}


<?php

// needs $sumit, $items, $name_pattern, $user

if ($submit) {
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
	echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide apply_item');

	foreach ($items as $item) {
		//get the values
		$params = array('submit_id'=>$submit->id, 'item_id'=>$item->id, 'version'=>$submit_ver);
		$value  = $DB->get_record('apply_value', $params);

		if ($item->typ!='pagebreak' and $item->label!=APPLY_NODISP_TAG) {
			if (isset($value->value)) {
				apply_print_item_show_value($item, $value->value);
			}
			else {
				apply_print_item_show_value($item, false);
			}
		}
	}

	//
	require('entry_info.php');
	//
	require('entry_button.php');

	echo $OUTPUT->box_end();
}

//
else {
	echo $OUTPUT->heading(get_string('no_submit_data', 'apply'), 3);
}


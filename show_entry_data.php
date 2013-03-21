<?php

// needs $sumit, $items, $name_pattern, $user

$align   = right_to_left() ? 'right' : 'left';
$student = $DB->get_record('user', array('id'=>$submit->user_id));

if 		($name_pattern=='firstname') $user_name = $student->firstname;
else if ($name_pattern=='lastname')  $user_name = $student->lastname;
else								 $user_name = fullname($student); 

if (!$submit) {
	echo $OUTPUT->heading(get_string('not_submit_data', 'apply'), 3);
}
else {
	echo $OUTPUT->heading($user_name.' ('.userdate($submit->time_modified, '%Y/%m/%d %H:%M').')', 3);
	echo $OUTPUT->box_start('apply_items');

	$itemnr = 0;
	foreach ($items as $item) {
		//get the values
		$params = array('submit_id'=>$submit->id, 'item_id'=>$item->id);
		$value  = $DB->get_record('apply_value', $params);

		echo $OUTPUT->box_start('apply_item_box_'.$align);
		if ($item->hasvalue==1) {
			$itemnr++;
			echo $OUTPUT->box_start('apply_item_number_'.$align);
			echo $itemnr;
			echo $OUTPUT->box_end();
		}
		if ($item->typ != 'pagebreak') {
			echo $OUTPUT->box_start('box generalbox boxalign_'.$align);
			if (isset($value->value)) {
				apply_print_item_show_value($item, $value->value);
			}
			else {
				apply_print_item_show_value($item, false);
			}
			echo $OUTPUT->box_end();
		}
		echo $OUTPUT->box_end();
	}
	echo $OUTPUT->box_end();
}



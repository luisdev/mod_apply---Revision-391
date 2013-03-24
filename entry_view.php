<?php

// needs  $submit, $items, $name_pattern, $user

if ($submit->id!=$USER->id) {
    require_capability('mod/apply:viewreports', $context);
}

$student = $DB->get_record('user', array('id'=>$submit->user_id));

if 		($name_pattern=='firstname') $user_name = $student->firstname;
else if ($name_pattern=='lastname')  $user_name = $student->lastname;
else								 $user_name = fullname($student); 

$title = $user_name.' ('.userdate($submit->time_modified, '%Y/%m/%d %H:%M').')';
if 		($submit->class==APPLY_CLASS_DRAFT)  $title .= '&nbsp;<font color="#e22">'.get_string('class_draft', 'apply').'</font>';
else if ($submit->class==APPLY_CLASS_CANCEL) $title .= '&nbsp;<font color="#e22">'.get_string('class_cancel','apply').'</font>';
//
if ($this_action!='preview') {
	$preview_img = $OUTPUT->pix_icon('t/preview', get_string('preview'));
	$preview_url = new moodle_url('/mod/apply/preview.php');
	$preview_url->params(array('id'=>$cm->id, 'courseid'=>$courseid, 'action'=>$this_action));
	$preview_url->params(array('submit_id'=>$submit->id, 'submit_ver'=>$submit->version, 'user_id'=>$user_id));
	$title .= '&nbsp;&nbsp;<a href="'.$preview_url->out().'">'.$preview_img.'</a>';
}

echo $OUTPUT->heading(format_text($title), 3);

//
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
foreach ($items as $item) {
	//get the values
	$params = array('submit_id'=>$submit->id, 'item_id'=>$item->id, 'version'=>$submit_ver);
	$value  = $DB->get_record('apply_value', $params);

	if ($item->typ!='pagebreak' and $item->label!=APPLY_NODISP_TAG) {
		echo $OUTPUT->box_start('apply_print_item');
		if (isset($value->value)) {
			apply_print_item_show_value($item, $value->value);
		}
		else {
			apply_print_item_show_value($item, false);
		}
		echo $OUTPUT->box_end();
	}
}
require('entry_info.php');
echo $OUTPUT->box_end();

//require('entry_button.php');


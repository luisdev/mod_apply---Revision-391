<?php

// need $submit, $data, $name_pattern, $courseid, ...

$student = apply_get_user_info($submit->user_id);
if ($student) {
	if 		($name_pattern=='firstname') $user_name = $student->firstname;
	else if ($name_pattern=='lastname')  $user_name = $student->lastname;
	else								 $user_name = fullname($student); 
	//
	$user_url  = $CFG->wwwroot.'/user/view.php?id='.$student->id.'&amp;course='.$courseid;
	$prof_link = '<strong><a href="'.$user_url.'">'.$user_name.'</a></strong>';
	$acked_url = $CFG->wwwroot.'/user/view.php?id='.$submit->acked_user.'&amp;course='.$courseid;
	$entry_url = new moodle_url($url, array('user_id'=>$student->id, 'submit_id'=>$submit->id, 'do_show'=>'show_one_entry'));

	///////////////////////////////////////
	//
	$data[] = $OUTPUT->user_picture($student, array('courseid'=>$courseid));
	$data[] = $prof_link;
	//
	$title = $submit->title;
	if ($title=='') $title = get_string('no_title', 'apply');
	$entry_link = '<a href="'.$entry_url->out().'">'.$title.'</a>';
	$data[] = $entry_link;
	//
	$mod_time = userdate($submit->time_modified, '%Y/%m/%d %H:%M');
	$entry_link = '<a href="'.$entry_url->out().'">'.$mod_time.'</a>';
	$data[] = $entry_link;
	//
	$data[] = $submit->version;
	//
	if 		($submit->class==APPLY_CLASS_DRAFT)  $class = get_string('class_draft',   'apply');
	else if ($submit->class==APPLY_CLASS_NEW)    $class = get_string('class_newpost', 'apply');
	else if ($submit->class==APPLY_CLASS_UPDATE) $class = get_string('class_update',  'apply');
	else if ($submit->class==APPLY_CLASS_CANCEL) $class = get_string('class_cancel',  'apply');
	$data[] = $class;
	//
	if 		($submit->acked==APPLY_ACKED_NOTYET) $acked = get_string('acked_notyet',  'apply');
	else if ($submit->acked==APPLY_ACKED_ACCEPT) $acked = get_string('acked_accept', 'apply');
	else if ($submit->acked==APPLY_ACKED_REJECT) $acked = get_string('acked_reject', 'apply');
	if ($submit->acked!=APPLY_ACKED_NOTYET) {
		$acked = '<a href="'.$acked_url->out().'">'.$acked.'</a>';
	}
	$data[] = $acked;
	//
	if ($submit->execed) $execed = get_string('execed_done',   'apply');
	else 				 $execed = get_string('execed_notyet', 'apply');
	$data[] = $execed;
	//
	$data[] = 'x';

/*
	//link to delete the entry
	if (has_capability('mod/apply:deletesubmissions', $context)) {
		$delete_url_params = array('id'=>$cm->id, 'submit_id'=>$submit->id, 'do_show'=>'show_one_entry');
		$deleteentry_url = new moodle_url($CFG->wwwroot.'/mod/apply/delete_submit.php', $delete_url_params);
		$deleteentry_link = '<a href="'.$deleteentry_url->out().'">'.get_string('delete_entry', 'apply').'</a>';
		$data[] = $deleteentry_link;
	}
*/
}


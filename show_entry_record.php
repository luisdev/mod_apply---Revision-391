<?php

// need $req_own_data, $submit, $data, $name_pattern, $courseid, ...

$student = apply_get_user_info($submit->user_id);
if ($student) {
	if 		($name_pattern=='firstname') $user_name = $student->firstname;
	else if ($name_pattern=='lastname')  $user_name = $student->lastname;
	else								 $user_name = fullname($student); 
	//
	$entry_url 	= new moodle_url($url, array('user_id'=>$student->id, 'submit_id'=>$submit->id, 'do_show'=>'show_one_entry'));
	//
	$user_url  	= $CFG->wwwroot.'/user/view.php?id='.$student->id.'&amp;course='.$courseid;
	$acked_url	= $CFG->wwwroot.'/user/view.php?id='.$submit->acked_user.'&amp;course='.$courseid;
	$execed_url = $CFG->wwwroot.'/user/view.php?id='.$submit->execed_user.'&amp;course='.$courseid;
	$reply_url 	= $CFG->wwwroot.'/user/view.php?id='.$submit->reply_user.'&amp;course='.$courseid;

	///////////////////////////////////////
	//
	if (!$req_own_data) {
		$data[] = $OUTPUT->user_picture($student, array('courseid'=>$courseid));
		$data[] = '<strong><a href="'.$user_url.'">'.$user_name.'</a></strong>';
	}
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
	else if ($submit->acked==APPLY_ACKED_ACCEPT) $acked = get_string('acked_accept',  'apply');
	else if ($submit->acked==APPLY_ACKED_REJECT) $acked = get_string('acked_reject',  'apply');
	if ($submit->acked!=APPLY_ACKED_NOTYET) {
		$acked = '<a href="'.$acked_url.'">'.$acked.'</a>';
	}
	$data[] = $acked;
	//
	if ($submit->execed) $execed = get_string('execed_done',   'apply');
	else 				 $execed = get_string('execed_notyet', 'apply');
	if ($submit->execed) $execed = '<a href="'.$execed_url.'">'.$execed.'</a>';
	$data[] = $execed;
	//

//	if (has_capability('mod/apply:deletesubmissions', $context)) {
	if ($req_own_data) {
		$update_url_params = array('submit_id'=>$submit->id);
		$update_url = new moodle_url($CFG->wwwroot.'/mod/apply/edit.php', $update_url_params);
		$data[] = '<a href="'.$update_url->out().'">'.get_string('update_entry', 'apply').'</a>';
		//
		$cancel_url_params = array('submit_id'=>$submit->id);
		$cancel_url = new moodle_url($CFG->wwwroot.'/mod/apply/edit.php', $cancel_url_params);
		$data[] = '<a href="'.$cancel_url->out().'">'.get_string('cancel_entry', 'apply').'</a>';
		//
		$delete_url_params = array('submit_id'=>$submit->id, 'acked'=>$submit->acked);
		$delete_url = new moodle_url($CFG->wwwroot.'/mod/apply/delete_submit.php', $delete_url_params);
		$data[] = '<a href="'.$delete_url->out().'">'.get_string('delete_entry', 'apply').'</a>';
	}
	else {
		$data[] = get_string('operation_entry', 'apply');
	}
}


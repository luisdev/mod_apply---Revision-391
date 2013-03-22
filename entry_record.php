<?php

// need $req_own_data, $submit, $data, $name_pattern, $courseid, ...

$student = apply_get_user_info($submit->user_id);
if ($student) {
	if 		($name_pattern=='firstname') $user_name = $student->firstname;
	else if ($name_pattern=='lastname')  $user_name = $student->lastname;
	else								 $user_name = fullname($student); 
	//
	$entry_url = new moodle_url($url, array('user_id'=>$student->id, 'submit_id'=>$submit->id, 'do_show'=>'show_one_entry'));
	//
	$user_url  = $CFG->wwwroot.'/user/view.php?id='.$student->id.'&amp;course='.$courseid;
	$acked_url = $CFG->wwwroot.'/user/view.php?id='.$submit->acked_user.'&amp;course='.$courseid;
	$execd_url = $CFG->wwwroot.'/user/view.php?id='.$submit->execd_user.'&amp;course='.$courseid;

	///////////////////////////////////////
	//
	if (!$req_own_data) {
		$data[] = $OUTPUT->user_picture($student, array('courseid'=>$courseid));
		$data[] = '<strong><a href="'.$user_url.'">'.$user_name.'</a></strong>';
	}
	//
	$title = $submit->title;
	if ($title=='') $title = get_string('no_title', 'apply');
	$entry_link = '<strong><a href="'.$entry_url->out().'">'.$title.'</a></strong>';
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
	if ($submit->class==APPLY_CLASS_DRAFT)  $class = '<strong>'.$class.'</strong>';
	$data[] = $class;

	//
	if 		($submit->class==APPLY_CLASS_DRAFT)  $acked = '-';
	else if ($submit->acked==APPLY_ACKED_NOTYET) $acked = get_string('acked_notyet',  'apply');
	else if ($submit->acked==APPLY_ACKED_ACCEPT) $acked = get_string('acked_accept',  'apply');
	else if ($submit->acked==APPLY_ACKED_REJECT) $acked = get_string('acked_reject',  'apply');
	if ($submit->acked!=APPLY_ACKED_NOTYET) {
		$acked = '<a href="'.$acked_url.'">'.$acked.'</a>';
	}
	$data[] = $acked;

	//
	if 		($submit->class==APPLY_CLASS_DRAFT) $execd = '-';
	else if ($submit->execd==APPLY_EXECD_DONE)  $execd = get_string('execd_done',   'apply');
	else 				 				  		$execd = get_string('execd_notyet', 'apply');
	if ($submit->execd==APPLY_EXECD_DONE) $execd = '<a href="'.$execd_url.'">'.$execd.'</a>';
	$data[] = $execd;

	//
	if ($req_own_data) {
		if ($submit->class!=APPLY_CLASS_CANCEL) {
			if ($submit->acked==APPLY_ACKED_ACCEPT) {
				// Update
				$change_label	= get_string('update_entry_button', 'apply');
 				$change_params	= array('submit_id'=>$submit->id);
				$cahnge_action  = 'submit.php';
				// Cancel
				$discard_label	= get_string('cancel_entry_button', 'apply');
				$discard_params	= array('submit_id'=>$submit->id, 'acked'=>$submit->acked);
				$discard_action	= 'delete_submit.php';
			}
			else {
				// Edit
				$change_label	= get_string('edit_entry_button', 'apply');
 				$change_params	= array('submit_id'=>$submit->id);
				$cahnge_action  = 'submit.php';
				// Delete
				$discard_label	= get_string('delete_entry_button', 'apply');
				$discard_params	= array('submit_id'=>$submit->id, 'acked'=>$submit->acked);
				$discard_action	= 'delete_submit.php';
			}
			//
			$change_url  = new moodle_url($CFG->wwwroot.'/mod/apply/'.$change_action, $change_params);
			$discard_url = new moodle_url($CFG->wwwroot.'/mod/apply/'.$discard_action, $discard_params);
			$data[] = '<strong><a href="'.$change_url->out().'">'. $change_label. '</a></strong>';
			$data[] = '<strong><a href="'.$discard_url->out().'">'.$discard_label.'</a></strong>';
		}
	}

	// for admin
	else {
		if ($submit->class!=APPLY_CLASS_CANCEL) {
			$op_url_params = array('submit_id'=>$submit->id, 'acked'=>$submit->acked);
			$op_url = new moodle_url($CFG->wwwroot.'/mod/apply/op.php', $op_url_params);
			$data[] = '<strong><a href="'.$op_url->out().'">'.get_string('operation_entry', 'apply').'</a></strong>';
		}
		else {
			$data[] = '-';
		}
	}
}


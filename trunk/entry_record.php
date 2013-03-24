<?php

// need $req_own_data, $submit, $data, $name_pattern, $courseid, ...


//
if (!$req_own_data or $submit->id!=$USER->id) {
    require_capability('mod/apply:viewreports', $context);
}


$student = apply_get_user_info($submit->user_id);
if ($student) {
	if 		($name_pattern=='firstname') $user_name = $student->firstname;
	else if ($name_pattern=='lastname')  $user_name = $student->lastname;
	else								 $user_name = fullname($student); 
	//
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
	$entry_params = array('user_id'=>$student->id, 'submit_id'=>$submit->id, 'submit_ver'=>$submit->version, 'do_show'=>'view_one_entry');
	$entry_url = new moodle_url($this_url, $entry_params);
	$data[] = '<strong><a href="'.$entry_url->out().'">'.$title.'</a></strong>';
	//
	$data[] = userdate($submit->time_modified, '%Y/%m/%d %H:%M');
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
	if ($req_own_data) {
		if ($submit->version>0 and apply_exist_draft_values($submit->id)) {
			$draft_params = array('user_id'=>$student->id, 'submit_id'=>$submit->id, 'submit_ver'=>0, 'do_show'=>'view_one_entry');
			$draft_url = new moodle_url($this_url, $draft_params);
			$data[] = '<strong><a href="'.$draft_url->out().'">'.get_string('exist', 'apply').'</a></strong>';
		}
		else {
			$data[] = '-';
		}
	}

	//
	if 		($submit->class==APPLY_CLASS_DRAFT)  $acked = '-';
	else if ($submit->acked==APPLY_ACKED_NOTYET) $acked = get_string('acked_notyet',  'apply');
	else if ($submit->acked==APPLY_ACKED_ACCEPT) $acked = get_string('acked_accept',  'apply');
	else if ($submit->acked==APPLY_ACKED_REJECT) $acked = get_string('acked_reject',  'apply');
	if ($submit->acked!=APPLY_ACKED_NOTYET) {
		$acked = '<strong><a href="'.$acked_url.'">'.$acked.'</a></strong>';
	}
	$data[] = $acked;

	//
	if		($submit->class==APPLY_CLASS_DRAFT)  $execd = '-';
	else if ($submit->execd==APPLY_EXECD_DONE)   $execd = get_string('execd_done',   'apply');
	else 				 				  		 $execd = get_string('execd_notyet', 'apply');
	if ($submit->execd!=APPLY_EXECD_NOTYET) {
		$execd = '<strong><a href="'.$execd_url.'">'.$execd.'</a></strong>';
	}
	$data[] = $execd;

	//
	if ($req_own_data) {
		if ($submit->class==APPLY_CLASS_CANCEL and $submit->acked==APPLY_ACKED_ACCEPT) {
			// 解除が受理されたものは，ユーザは変更できない
			$data[] = '-';
			$data[] = '-';
		}
		else {
			if ($submit->acked==APPLY_ACKED_ACCEPT) {
				// Update
				$change_label	= get_string('update_entry_button', 'apply');
				$change_params  = array('id'=>$id, 'submit_id'=>$submit->id, 'submit_ver'=>$submit->version, 'courseid'=>$courseid, 'go_page'=>0);
				$change_action  = 'submit.php';
				// Cancel
				$discard_label	= get_string('cancel_entry_button', 'apply');
				$discard_params	= array('id'=>$id, 'submit_id'=>$submit->id);
				$discard_action	= 'delete_submit.php';
			}
			else {
				// Edit
				$change_label	= get_string('edit_entry_button', 'apply');
				$change_params  = array('id'=>$id, 'submit_id'=>$submit->id, 'submit_ver'=>$submit->version, 'courseid'=>$courseid, 'go_page'=>0);
				$change_action  = 'submit.php';
				
				if ($submit->version<=1) {
					// Delete
					$discard_label	= get_string('delete_entry_button', 'apply');
					$discard_params	= array('id'=>$id, 'submit_id'=>$submit->id);
					$discard_action	= 'delete_submit.php';
				}
				else {
					// Rollback
					$discard_label	= get_string('rollback_entry_button', 'apply');
					$discard_params	= array('id'=>$id, 'submit_id'=>$submit->id);
					$discard_action	= 'delete_submit.php';
				}
			}

			//
			if ($submit->class==APPLY_CLASS_CANCEL) {
				// 解除を申請している場合は，内容を編集・更新できない
				$data[] = '-';
			}
			else {
				$change_url  = new moodle_url($CFG->wwwroot.'/mod/apply/'.$change_action,  $change_params);
				$data[] = '<strong><a href="'.$change_url->out().'">'. $change_label. '</a></strong>';
			}
			//
			$discard_url = new moodle_url($CFG->wwwroot.'/mod/apply/'.$discard_action, $discard_params);
			$data[] = '<strong><a href="'.$discard_url->out().'">'.$discard_label.'</a></strong>';
		}
	}

	// for admin
	else {
		if (($submit->class!=APPLY_CLASS_CANCEL or $submit->acked!=APPLY_ACKED_ACCEPT) and 
			($submit->acked==APPLY_ACKED_NOTYET or $submit->execd==APPLY_EXECD_NOTYET)) {
			$operate_params = array('id'=>$id, 'submit_id'=>$submit->id, 'submit_ver'=>$submit->version, 'courseid'=>$courseid);
			$operate_url = new moodle_url($CFG->wwwroot.'/mod/apply/operate_submit.php', $operate_params);
			$data[] = '<strong><a href="'.$operate_url->out().'">'.get_string('operate_submit', 'apply').'</a></strong>';
		}
		else {
			$data[] = '-';
		}
	}
}


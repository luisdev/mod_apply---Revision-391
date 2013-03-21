<?php

// needs $sumit, $items, $name_pattern, $user

if ($submit) {
	//
	if ($req_own_data and $submit->class!=APPLY_CLASS_CANCEL) {
		// Edit
		if ($submit->acked!=APPLY_ACKED_ACCEPT) {
			$change_params	= array('submit_id'=>$submit->id);
			$change_label	= get_string('edit_entry_button', 'apply');
			$change_url		= new moodle_url($CFG->wwwroot.'/mod/apply/submit.php', $change_params);
		}
		// Update
		else {
			$change_params	= array('submit_id'=>$submit->id);
			$change_label	= get_string('update_entry_button', 'apply');
			$change_url		= new moodle_url($CFG->wwwroot.'/mod/apply/submis.php', $change_params);
		}

		// Cancel
		if ($submit->acked==APPLY_ACKED_ACCEPT) {
			$discard_params = array('submit_id'=>$submit->id);
			$discard_label 	= get_string('edit_entry_button', 'apply');
			$discard_url	= new moodle_url($CFG->wwwroot.'/mod/apply/deete_submit.php', $discard_params);
		}
		// Delete
		else {
			$discard_params = array('submit_id'=>$submit->id, 'acked'=>$submit->acked);
			$discard_label 	= get_string('delete_entry_button', 'apply');
			$discard_url 	= new moodle_url($CFG->wwwroot.'/mod/apply/delete_submit.php', $discard_params);
		}

		//	
		echo '<div align="center">';
		echo '<table border="0">';
		echo '<tr>';
		echo '<td>'.$OUTPUT->continue_button(new moodle_url($url, array('do_show'=>'view'))).'</td>';
		echo '<td>&nbsp;&nbsp;&nbsp;</td>';
		echo '<td>'.$OUTPUT->single_button($change_url,  $change_label). '</td>';
		echo '<td>&nbsp;&nbsp;&nbsp;</td>';
		echo '<td>'.$OUTPUT->single_button($discard_url, $discard_label).'</td>';
		echo '<td>&nbsp;&nbsp;&nbsp;</td>';
		echo '</tr>';
		echo '</table>';
		echo '</div>';
	}

	//
	else if (!$req_own_data and $submit->class!=APPLY_CLASS_CANCEL) {
		echo $OUTPUT->continue_button(new moodle_url($url, array('do_show'=>'show_entries')));
	}
}


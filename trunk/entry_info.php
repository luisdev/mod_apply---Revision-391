<?php

if ($submit) {
	echo $OUTPUT->box_start('boxaligncenter boxwidthwide');
	//
	if ($submit->class!=APPLY_CLASS_DRAFT) {
		if ($submit->acked==APPLY_ACKED_ACCEPT) {
			$info_link = apply_get_user_link($submit->acked_user, $name_pattern);
			$info_str  = get_string('acked_accept', 'apply').' : '.$info_link.' : '.userdate($submit->aked_time);
		}
		else if ($submit->acked==APPLY_ACKED_REJECT) {
			$aked_link = apply_get_user_link($submit->acked_user, $name_pattern);
			$info_str  = get_string('acked_reject', 'apply').' : '.$info_link.' : '.userdate($submit->aked_time);

		}
		else if ($submit->acked==APPLY_ACKED_NOTYET) {
			$info_str = get_string('acked_notyet', 'apply');
		}
	}
	else {
		$info_str = '-';	// draft
	}

	echo '<br />';
	echo '<span class="entry_info">';
	echo get_string('title_ack', 'apply').': '.$info_str;
	echo '</span><br />';

	//
	if ($submit->class!=APPLY_CLASS_DRAFT) {
		if ($submit->execd==APPLY_EXECD_DONE) {
			$info_link = apply_get_user_link($submit->execd_user, $name_pattern);
			$info_str  = get_string('execd_done', 'apply').' : '.$info_link.' : '.userdate($submit->execd_time);
		}
		else {
			$info_str  = get_string('execd_notyet', 'apply');
		}
	}
	else {
		$info_str = '-';	// draft
	}

	echo '<span class="entry_info">';
	echo get_string('title_exec', 'apply').': '.$info_str;
	echo '</span><br />';

	//
	echo $OUTPUT->box_end();
}

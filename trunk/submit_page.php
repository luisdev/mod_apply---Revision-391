<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


if (isset($save_return)) {
	if ($save_return=='failed') {
		echo $OUTPUT->box_start('mform error');
		echo get_string('saving_failed', 'apply');
		echo $OUTPUT->box_end();
	}
	else if ($save_return=='missing') {
		echo $OUTPUT->box_start('mform error');
		echo get_string('saving_failed_because_missing_or_false_values', 'apply');
		echo $OUTPUT->box_end();
	}
}

//print the items
if (is_array($apply_items)) {
	echo $OUTPUT->box_start('apply_form');
	echo '<form action="submit.php" method="post" onsubmit=" ">';
	echo '<fieldset>';
	echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

	//check, if there exists required-elements
	$params = array('apply_id' => $apply->id, 'required' => 1);
	$countreq = $DB->count_records('apply_item', $params);
	if ($countreq > 0) {
		echo '<span class="apply_required_mark">(*)';
		echo get_string('items_are_required', 'apply');
		echo '</span>';
	}
	echo $OUTPUT->box_start('apply_items');

	unset($startitem);
	$select = 'apply_id = ? AND hasvalue = 1 AND position < ?';
	$params = array($apply->id, $start_position);
	$itemnr = $DB->count_records_select('apply_item', $select, $params);
	$last_break_position = 0;
	$align = right_to_left() ? 'right' : 'left';
	

	foreach ($apply_items as $applyitem) {
		if (!isset($startitem)) {
			//avoid showing double pagebreaks
			if ($applyitem->typ == 'pagebreak') {
				continue;
			}
			$startitem = $applyitem;
		}

		if ($applyitem->dependitem > 0) {
			//chech if the conditions are ok
			$fb_compare_value = apply_compare_item_value($applycompletedtmp->id, $applyitem->dependitem, $applyitem->dependvalue, true);
			if (!isset($applycompletedtmp->id) OR !$fb_compare_value) {
				$lastitem = $applyitem;
				$last_break_position = $applyitem->position;
				continue;
			}
		}

		if ($applyitem->dependitem > 0) {
			$dependstyle = ' apply_complete_depend';
		}
		else {
			$dependstyle = '';
		}

		echo $OUTPUT->box_start('apply_item_box_'.$align.$dependstyle);
		$value = '';
		//get the value
		$frmvaluename = $applyitem->typ . '_'. $applyitem->id;
		if (isset($save_return)) {
			$value = isset($formdata->{$frmvaluename}) ? $formdata->{$frmvaluename} : null;
			$value = apply_clean_input_value($applyitem, $value);
		}
		else {
			if (isset($applycompletedtmp->id)) {
				$value = apply_get_item_value($applycompletedtmp->id, $applyitem->id, true);
			}
		}
		if ($applyitem->hasvalue==1) {
			$itemnr++;
			echo $OUTPUT->box_start('apply_item_number_'.$align);
			echo $itemnr;
			echo $OUTPUT->box_end();
		}
		if ($applyitem->typ != 'pagebreak') {
			echo $OUTPUT->box_start('box generalbox boxalign_'.$align);
			apply_print_item_complete($applyitem, $value, $highlightrequired);
			echo $OUTPUT->box_end();
		}

		echo $OUTPUT->box_end();

		$last_break_position = $applyitem->position; //last item-pos (item or pagebreak)
		if ($applyitem->typ == 'pagebreak') {
			break;
		} else {
			$lastitem = $applyitem;
		}
	}
	echo $OUTPUT->box_end();

	//
	echo '<input type="hidden" name="id" value="'.$id.'" />';
	echo '<input type="hidden" name="apply_id" value="'.$apply->id.'" />';
	echo '<input type="hidden" name="last_page" value="'.$go_page.'" />';
	if (isset($applycompletedtmp->id)) {
		$inputvalue = 'value="'.$applycompletedtmp->id.'"';
	}
	else {
		$inputvalue = 'value=""';
	}
	echo '<input type="hidden" name="submit_id" '.$inputvalue.' />';
	echo '<input type="hidden" name="courseid" value="'. $courseid . '" />';
	echo '<input type="hidden" name="prev_values" value="1" />';
	if (isset($startitem)) {
		echo '<input type="hidden" name="start_itempos" value="'.$startitem->position.'" />';
		echo '<input type="hidden" name="last_itempos" value="'.$lastitem->position.'" />';
	}


	if ( $ispagebreak AND $last_break_position > $firstpagebreak->position) {
		$inputvalue = 'value="'.get_string('previous_page', 'apply').'"';
		echo '<input name="go_prev_page" type="submit" '.$inputvalue.' />';
	}
	if ($last_break_position < $maxitemcount) {
		$inputvalue = 'value="'.get_string('next_page', 'apply').'"';
		echo '<input name="go_next_page" type="submit" '.$inputvalue.' />';
	}
	if ($last_break_position >= $maxitemcount) { //last page
		$inputvalue = 'value="'.get_string('save_entries', 'apply').'"';
		echo '<input name="save_values" type="submit" '.$inputvalue.' />';
	}

	echo '</fieldset>';
	echo '</form>';
	echo $OUTPUT->box_end();

	echo $OUTPUT->box_start('apply_submit_cancel');
	if ($courseid) {
		$action = 'action="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'"';
	}
	else {
		if ($course->id == SITEID) {
			$action = 'action="'.$CFG->wwwroot.'"';
		}
		else {
			$action = 'action="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"';
		}
	}

	echo '<form '.$action.' method="post" onsubmit=" ">';
	echo '<fieldset>';
	echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
	echo '<input type="hidden" name="courseid" value="'. $courseid . '" />';
	echo '<button type="submit">'.get_string('cancel').'</button>';
	echo '</fieldset>';
	echo '</form>';
	echo $OUTPUT->box_end();

	$SESSION->apply->is_started = true;
}

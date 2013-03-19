<?php

//print the items
echo $OUTPUT->box_start('apply_form');
{
	echo '<form action="submit.php" method="post" onsubmit=" ">';
	echo '<fieldset>';
	echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

	$params = array('apply_id' => $apply->id, 'required' => 1);
	$countreq = $DB->count_records('apply_item', $params);
	if ($countreq>0) {
		echo '<span class="apply_required_mark">(*)';
		echo get_string('items_are_required', 'apply');
		echo '</span>';
	}
	
	//
	echo $OUTPUT->box_start('apply_items');
	{
		unset($start_item);
		$select = 'apply_id = ? AND hasvalue = 1 AND position < ?';
		$params = array($apply->id, $start_position);
		$itemnr = $DB->count_records_select('apply_item', $select, $params);
		$last_break_position = 0;
		$align = right_to_left() ? 'right' : 'left';

		foreach ($apply_items as $apply_item) {
			if (!isset($start_item)) {
				if ($apply_item->typ=='pagebreak') continue;
				$start_item = $apply_item;
			}
			if ($apply_item->dependitem>0) {
				$compare_value = apply_compare_item_value($submit_id, $apply_item->dependitem, $apply_item->dependvalue, true);
				if (!isset($submit_id) OR !$compare_value) {
					$last_item = $apply_item;
					$last_break_position = $apply_item->position;
					continue;
				}
			}
			if ($apply_item->dependitem>0) {
				$depend_style = ' apply_submit_depend';
			}
			else {
				$depend_style = '';
			}

			//
			echo $OUTPUT->box_start('apply_item_box_'.$align.$depend_style);
			{
				$value = '';
				//get the value
				$frmvaluename = $apply_item->typ . '_'. $apply_item->id;
				if (isset($save_return)) {
					$value = isset($formdata->{$frmvaluename}) ? $formdata->{$frmvaluename} : null;
					$value = apply_clean_input_value($apply_item, $value);
				}
				else {
					if (isset($submit_id)) {
						$value = apply_get_item_value($submit_id, $apply_item->id, true);
					}
				}
				if ($apply_item->hasvalue==1) {
					$itemnr++;
					echo $OUTPUT->box_start('apply_item_number_'.$align);
					echo $itemnr;
					echo $OUTPUT->box_end();
				}
				if ($apply_item->typ != 'pagebreak') {
					echo $OUTPUT->box_start('box generalbox boxalign_'.$align);
					apply_print_item_complete($apply_item, $value, $highlightrequired);
					echo $OUTPUT->box_end();
				}
			}
			echo $OUTPUT->box_end();
		}

		$last_break_position = $apply_item->position; //last item-pos (item or pagebreak)
		if ($apply_item->typ=='pagebreak') {
			break;
		}
		else {
			$last_item = $apply_item;
		}
	}
	echo $OUTPUT->box_end();

	//
	echo '<input type="hidden" name="id" value="'.$id.'" />';
	echo '<input type="hidden" name="apply_id" value="'.$apply->id.'" />';
	echo '<input type="hidden" name="last_page" value="'.$go_page.'" />';

	if (isset($submit_id)) 	$inputvalue = 'value="'.$submit_id.'"';
	else 					$inputvalue = 'value=""';
	//
	echo '<input type="hidden" name="submit_id" '.$inputvalue.' />';
	echo '<input type="hidden" name="courseid" value="'. $courseid . '" />';
	echo '<input type="hidden" name="prev_values" value="1" />';
	if (isset($start_item)) {
		echo '<input type="hidden" name="start_itempos" value="'.$start_item->position.'" />';
		echo '<input type="hidden" name="last_itempos"  value="'.$last_item->position.'" />';
	}
	
	// Button
	echo '<input type="reset" value="'.get_string('clear').'" />';
	echo '&nbsp;&nbsp;&nbsp;&nbsp;';

	if ( $is_pagebreak AND $last_break_position > $first_pagebreak->position) {
		$inputvalue = 'value="'.get_string('previous_page', 'apply').'"';
		echo '<input name="go_prev_page" type="submit" '.$inputvalue.' />';
	}
	if ($last_break_position<$max_item_count) {
		$inputvalue = 'value="'.get_string('next_page', 'apply').'"';
		echo '<input name="go_next_page" type="submit" '.$inputvalue.' />';
	}
	if ($last_break_position>=$max_item_count) { //last page
		$inputvalue = 'value="'.get_string('save_entries', 'apply').'"';
		echo '<input name="save_values" type="submit" '.$inputvalue.' />';
	}

	echo '</fieldset>';
	echo '</form>';
}
echo $OUTPUT->box_end();

//
//
echo $OUTPUT->box_start('apply_submit_cancel');
{
	if ($courseid) {
		$action = 'action="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'"';
	}
	else {
		$action = 'action="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"';
	}

	echo '<form '.$action.' method="post" onsubmit=" ">';
	echo '<fieldset>';
	echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
	echo '<input type="hidden" name="courseid" value="'. $courseid . '" />';
	echo '<button type="submit">'.get_string('cancel').'</button>';
	echo '</fieldset>';
	echo '</form>';
}
echo $OUTPUT->box_end();



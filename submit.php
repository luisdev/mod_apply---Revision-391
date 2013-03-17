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

/**
 * prints the form so the user can fill out the apply
 *
 * @package apply
 * @author  Fumi.Iseki
 * @license GNU Public License
 * @attention modified from mod_feedback that by Andreas Grabs
 */

require_once('../../config.php');
require_once('lib.php');

apply_init_session();

$id 			= required_param('id', PARAM_INT);
$courseid 	  	= optional_param('courseid', 0, PARAM_INT);
$submit_id		= optional_param('submit_id', 0, PARAM_INT);
$prev_values 	= optional_param('prev_values', 0, PARAM_INT);
$go_page 		= optional_param('go_page', -1, PARAM_INT);
$last_page 	  	= optional_param('last_page', false, PARAM_INT);
$start_itempos	= optional_param('start_itempos', 0, PARAM_INT);
$last_itempos  	= optional_param('last_itempos',  0, PARAM_INT);

$highlightrequired = false;

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
	print_error('invalidsesskey');
}


// Page
//if the use hit enter into a textfield so the form should not submit
if ( isset($formdata->sesskey)	  	AND
	!isset($formdata->save_values) 	AND
	!isset($formdata->go_next_page) AND
	!isset($formdata->go_prev_page)) {

	$go_page = $formdata->last_page;
}

if (isset($formdata->save_values)) {
	$save_values = true;
} 
else {
	$save_values = false;
}

// page
if ($go_page<0 AND !$save_values) {
	if (isset($formdata->go_next_page)) {
		$go_page = $last_page + 1;
		$go_next_page = true;
		$go_prev_page = false;
	}
	else if (isset($formdata->go_prev_page)) {
		$go_page = $last_page - 1;
		$go_next_page = false;
		$go_prev_page = true;
	}
	else {
		print_error('missingparameter');
	}
}
else {
	$go_next_page = $go_prev_page = false;
}


//
if (! $cm = get_coursemodule_from_id('apply', $id)) {
	print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
	print_error('coursemisconf');
}
if (! $apply  = $DB->get_record('apply', array('id'=>$cm->instance))) {
	print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);

$apply_submit_cap = false;
if (has_capability('mod/apply:submit', $context)) {
	$apply_submit_cap = true;
}
//
if (!$apply_submit_cap) {
	print_error('error');
}

require_login($course, true, $cm);


/// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$PAGE->navbar->add(get_string('apply:submit', 'apply'));
$urlparams = array('id'=>$cm->id, 'go_page'=>$go_page, 'courseid'=>$course->id);
$PAGE->set_url('/mod/apply/submit.php', $urlparams);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();


//ishidden check.
if ((empty($cm->visible) AND !has_capability('moodle/course:viewhiddenactivities', $context))) {
	notice(get_string("activityiscurrentlyhidden"));
}

//check, if the apply is open (time_open, time_close)
$checktime = time();
$apply_is_closed = ($apply->time_open>$checktime) OR ($apply->time_close<$checktime AND $apply->time_close>0);

if ($apply_is_closed) {
	echo $OUTPUT->box_start('generalbox boxaligncenter');
	{
		echo '<h2><font color="red">';
		echo get_string('apply_is_not_open', 'apply');
		echo '</font></h2>';
		echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
	}
	echo $OUTPUT->box_end();
	echo $OUTPUT->footer();
	exit;
}


//additional check for multiple-submit (prevent browsers back-button).
//the main-check is in view.php
$apply_can_submit = true;
if ($apply->multiple_submit==0) {
	if (apply_get_valid_submit_count($apply->id, $USER->id)>0) {
		$apply_can_submit = false;
	}
}


// can submit
if ($apply_can_submit) {
	//
	if ($prev_values==1) {
		if (!isset($SESSION->apply->is_started) OR !$SESSION->apply->is_started==true) {
			print_error('error', '', $CFG->wwwroot.'/course/view.php?id='.$course->id);
		}

		if (apply_check_values($start_itempos, $last_itempos)) {
			$user_id   = $USER->id;
			$submit_id = apply_save_values($apply->id, $submit_id, $user_id, true);	// save to tmp

			if ($submit_id) {
				if ($user_id>0) {
					add_to_log($course->id, 'apply', 'start_apply', 'view.php?id='.$cm->id, $apply->id, $cm->id, $user_id);
				}
				if (!$go_next_page AND !$go_prev_page) {
					$prev_values = false;
				}
			}
			else {
				$save_return = 'failed';
				if (isset($last_page)) {
					$go_page = $last_page;
				}
				else {
					print_error('missingparameter');
				}
			}
		}
		//
		else {
			$save_return = 'missing';
			$highlightrequired = true;
			if (isset($last_page)) {
				$go_page = $last_page;
			}
			else {
				print_error('missingparameter');
			}
		}
	}

	//saving the items
	if ($save_values AND !$prev_values) {
		//exists there any pagebreak, so there are values in the apply_value_tmp
		$user_id   = $USER->id; 
		$submit_id = apply_save_values($apply->id, $submit_id, $user_id);

		if ($submit_id) {
			$save_return = 'saved';
			add_to_log($course->id, 'apply', 'submit', 'view.php?id='.$cm->id, $apply->id, $cm->id, $user_id);
			apply_send_email($cm, $apply, $course, $user_id);
		}
		else {
			$save_return = 'failed';
		}
	}

	//
	if ($allbreaks = apply_get_all_break_positions($apply->id)) {
		if ($go_page<=0) {
			$start_position = 0;
		}
		else {
			if (!isset($allbreaks[$go_page - 1])) {
				$go_page = count($allbreaks);
			}
			$start_position = $allbreaks[$go_page - 1];
		}
		$ispagebreak = true;
	} 
	else {
		$start_position = 0;
		$newpage = 0;
		$ispagebreak = false;
	}

	//
	//get the apply_items after the last shown pagebreak
	$select = 'apply_id = ? AND position > ?';
	$params = array($apply->id, $start_position);
	$apply_items = $DB->get_records_select('apply_item', $select, $params, 'position');

	//get the first pagebreak
	$params = array('apply_id' => $apply->id, 'typ' => 'pagebreak');
	if ($pagebreaks = $DB->get_records('apply_item', $params, 'position')) {
		$pagebreaks = array_values($pagebreaks);
		$firstpagebreak = $pagebreaks[0];
	}
	else {
		$firstpagebreak = false;
	}
	$maxitemcount = $DB->count_records('apply_item', array('apply_id'=>$apply->id));

	//
	//get the values of completeds before done. Anonymous user can not get these values.
	if ((!isset($SESSION->apply->is_started)) AND (!isset($save_return))) {
		$submits = apply_get_current_submit($apply->id);
		if (!$submits) {
			$applycompleted = apply_get_current_completed($apply->ideid);
			if ($applycompleted) {
				//copy the values to apply_valuetmp create a completedtmp
				$applycompletedtmp = apply_set_tmp_values($applycompleted);
			}
		}
		else {
			$submits = apply_get_current_submit($apply->id);
		}
	}

	///////////////////////////////////////////////////////////////////////////
	/// Print the main part of the page
	$analysisurl = new moodle_url('/mod/apply/analysis.php', array('id'=>$id));
	if ($courseid>0) {
		$analysisurl->param('courseid', $courseid);
	}
	echo $OUTPUT->heading(format_text($apply->name));

	if (!has_capability('mod/apply:viewreports', $context)) {

		$params = array('user_id'=>$USER->id, 'apply_id' => $apply->id);
		echo $OUTPUT->box_start('mdl-align');
		echo '<a href="'.$analysisurl->out().'">';
		echo get_string('completed_applys', 'apply').'</a>';
		echo $OUTPUT->box_end();
	}

	if (isset($save_return) && $save_return=='saved') {
		echo '<p align="center">';
		echo '<b><font color="green">';
		echo get_string('entries_saved', 'apply');
		echo '</font></b>';
		echo '</p>';

		if ($courseid) {
			$url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
		}
		else {
			$url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
		}
		echo $OUTPUT->continue_button($url);
	}

	else {
		require('submit_page.php');
/*
		if (isset($save_return) && $save_return=='failed') {
			echo $OUTPUT->box_start('mform error');
			echo get_string('saving_failed', 'apply');
			echo $OUTPUT->box_end();
		}

		if (isset($save_return) && $save_return=='missing') {
			echo $OUTPUT->box_start('mform error');
			echo get_string('saving_failed_because_missing_or_false_values', 'apply');
			echo $OUTPUT->box_end();
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
			$pply_complete_cancelparams = array($apply->id, $start_position);
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
					$fb_compare_value = apply_compare_item_value($applycompletedtmp->id,
																	$applyitem->dependitem,
																	$applyitem->dependvalue,
																	true);
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
						$value = apply_get_item_value($applycompletedtmp->id,
														 $applyitem->id,
														 true);
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
*/
	}
}

// cannot submit
else {
	echo $OUTPUT->box_start('generalbox boxaligncenter');
	{
		echo '<h2>';
		echo '<font color="red">';
		echo get_string('this_apply_is_already_submitted', 'apply');
		echo '</font>';
		echo '</h2>';
		echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
	}
	echo $OUTPUT->box_end();
}


///////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();

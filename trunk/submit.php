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
$submit_id		= optional_param('submit_id', 0, PARAM_INT);
$course_id 	  	= optional_param('course_id', 0, PARAM_INT);
$preservevalues = optional_param('preservevalues', 0, PARAM_INT);
$gopage 		= optional_param('gopage', -1, PARAM_INT);
$lastpage 	  	= optional_param('lastpage', false, PARAM_INT);
$startitempos	= optional_param('startitempos', 0, PARAM_INT);
$lastitempos  	= optional_param('lastitempos',  0, PARAM_INT);

$highlightrequired = false;

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
	print_error('invalidsesskey');
}


// Page
//if the use hit enter into a textfield so the form should not submit
if ( isset($formdata->sesskey)	AND
	!isset($formdata->savevalues) AND
	!isset($formdata->gonextpage) AND
	!isset($formdata->gopreviouspage)) {

	$gopage = $formdata->lastpage;
}

if (isset($formdata->savevalues)) {
	$savevalues = true;
} 
else {
	$savevalues = false;
}

// page
if ($gopage<0 AND !$savevalues) {
	if (isset($formdata->gonextpage)) {
		$gopage = $lastpage + 1;
		$gonextpage = true;
		$gopreviouspage = false;
	}
	else if (isset($formdata->gopreviouspage)) {
		$gopage = $lastpage - 1;
		$gonextpage = false;
		$gopreviouspage = true;
	}
	else {
		print_error('missingparameter');
	}
}
else {
	$gonextpage = $gopreviouspage = false;
}


//
if (! $cm = get_coursemodule_from_id('apply', $id)) {
	print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
	print_error('coursemisconf');
}
if (! $apply = $DB->get_record('apply', array('id'=>$cm->instance))) {
	print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);

$apply_complete_cap = false;
if (has_capability('mod/apply:complete', $context)) {
	$apply_complete_cap = true;
}
//
if (!$apply_complete_cap) {
	print_error('error');
}

require_login($course, true, $cm);


// ???
//$completion = new completion_info($course);
//$completion->set_module_viewed($cm);


/// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$PAGE->navbar->add(get_string('apply:submit', 'apply'));
$urlparams = array('id'=>$cm->id, 'gopage'=>$gopage, 'course_id'=>$course->id);
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
		echo '<h2><font color="red">';
		echo get_string('apply_is_not_open', 'apply');
		echo '</font></h2>';
		echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
	echo $OUTPUT->box_end();
	echo $OUTPUT->footer();
	exit;
}


//additional check for multiple-submit (prevent browsers back-button).
//the main-check is in view.php
$apply_can_submit = true;
if ($apply->multiple_submit==0) {
	if (apply_is_already_submitted($apply->id, $course_id)) {
		$apply_can_submit = false;
	}
}


// can submit
if ($apply_can_submit) {
	//
	if ($preservevalues==1) {
		if (!isset($SESSION->apply->is_started) OR !$SESSION->apply->is_started==true) {
			print_error('error', '', $CFG->wwwroot.'/course/view.php?id='.$course->id);
		}

		if (apply_check_values($startitempos, $lastitempos)) {
			$user_id   = $USER->id;
			$submit_id = apply_save_values($apply->id, $submit_id, $user_id, true);

			if ($submit_id) {
				if ($user_id>0) {
					add_to_log($course->id, 'apply', 'start_apply', 'view.php?id='.$cm->id, $apply->id, $cm->id, $user_id);
				}
				if (!$gonextpage AND !$gopreviouspage) {
					$preservevalues = false;
				}
			}
			else {
				$savereturn = 'failed';
				if (isset($lastpage)) {
					$gopage = $lastpage;
				}
				else {
					print_error('missingparameter');
				}
			}
		}
		//
		else {
			$savereturn = 'missing';
			$highlightrequired = true;
			if (isset($lastpage)) {
				$gopage = $lastpage;
			} else {
				print_error('missingparameter');
			}
		}
	}

	//saving the items
	if ($savevalues AND !$preservevalues) {
		//exists there any pagebreak, so there are values in the apply_value_tmp
		$user_id   = $USER->id; 
		$submit_id = apply_save_values($apply->id, $submit_id, $user_id);

		if ($submit_id) {
			$savereturn = 'saved';
			add_to_log($course->id, 'apply', 'submit', 'view.php?id='.$cm->id, $apply->id, $cm->id, $user_id);
			apply_send_email($cm, $apply, $course, $user_id);

			// Update completion state
//			$completion = new completion_info($course);
//			if ($completion->is_enabled($cm) && $apply->completionsubmit) {
//				$completion->update_state($cm, COMPLETION_COMPLETE);
//			}
		}
		else {
			$savereturn = 'failed';
		}
	}

	//
	if ($allbreaks = apply_get_all_break_positions($apply->id)) {
		if ($gopage<=0) {
			$startposition = 0;
		}
		else {
			if (!isset($allbreaks[$gopage - 1])) {
				$gopage = count($allbreaks);
			}
			$startposition = $allbreaks[$gopage - 1];
		}
		$ispagebreak = true;
	} 
	else {
		$startposition = 0;
		$newpage = 0;
		$ispagebreak = false;
	}

	//
	//get the applyitems after the last shown pagebreak
	$select = 'apply_id = ? AND position > ?';
	$params = array($apply->id, $startposition);
	$applyitems = $DB->get_records_select('apply_item', $select, $params, 'position');

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
	if ((!isset($SESSION->apply->is_started)) AND (!isset($savereturn))) {
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
	if ($course_id>0) {
		$analysisurl->param('course_id', $course_id);
	}
	echo $OUTPUT->heading(format_text($apply->name));

	if ( has_capability('mod/apply:viewanalysepage', $context) AND 
		!has_capability('mod/apply:viewreports', $context)) {

		$params = array('user_id'=>$USER->id, 'apply_id' => $apply->id);
		echo $OUTPUT->box_start('mdl-align');
		echo '<a href="'.$analysisurl->out().'">';
		echo get_string('completed_applys', 'apply').'</a>';
		echo $OUTPUT->box_end();
	}

	if (isset($savereturn) && $savereturn=='saved') {
		echo '<p align="center">';
		echo '<b><font color="green">';
		echo get_string('entries_saved', 'apply');
		echo '</font></b>';
		echo '</p>';

		if ($course_id) {
			$url = $CFG->wwwroot.'/course/view.php?id='.$course_id;
		}
		else {
			$url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
		}
		echo $OUTPUT->continue_button($url);
	}

	else {
		if (isset($savereturn) && $savereturn=='failed') {
			echo $OUTPUT->box_start('mform error');
			echo get_string('saving_failed', 'apply');
			echo $OUTPUT->box_end();
		}

		if (isset($savereturn) && $savereturn=='missing') {
			echo $OUTPUT->box_start('mform error');
			echo get_string('saving_failed_because_missing_or_false_values', 'apply');
			echo $OUTPUT->box_end();
		}

		//print the items
		if (is_array($applyitems)) {
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
			$params = array($apply->id, $startposition);
			$itemnr = $DB->count_records_select('apply_item', $select, $params);
			$lastbreakposition = 0;
			$align = right_to_left() ? 'right' : 'left';
	

			foreach ($applyitems as $applyitem) {
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
						$lastbreakposition = $applyitem->position;
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
				if (isset($savereturn)) {
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

				$lastbreakposition = $applyitem->position; //last item-pos (item or pagebreak)
				if ($applyitem->typ == 'pagebreak') {
					break;
				} else {
					$lastitem = $applyitem;
				}
			}
			echo $OUTPUT->box_end();
			echo '<input type="hidden" name="id" value="'.$id.'" />';
			echo '<input type="hidden" name="apply_id" value="'.$apply->id.'" />';
			echo '<input type="hidden" name="lastpage" value="'.$gopage.'" />';
			if (isset($applycompletedtmp->id)) {
				$inputvalue = 'value="'.$applycompletedtmp->id.'"';
			}
			else {
				$inputvalue = 'value=""';
			}
			echo '<input type="hidden" name="submit_id" '.$inputvalue.' />';
			echo '<input type="hidden" name="course_id" value="'. $course_id . '" />';
			echo '<input type="hidden" name="preservevalues" value="1" />';
			if (isset($startitem)) {
				echo '<input type="hidden" name="startitempos" value="'.$startitem->position.'" />';
				echo '<input type="hidden" name="lastitempos" value="'.$lastitem->position.'" />';
			}


			if ( $ispagebreak AND $lastbreakposition > $firstpagebreak->position) {
				$inputvalue = 'value="'.get_string('previous_page', 'apply').'"';
				echo '<input name="gopreviouspage" type="submit" '.$inputvalue.' />';
			}
			if ($lastbreakposition < $maxitemcount) {
				$inputvalue = 'value="'.get_string('next_page', 'apply').'"';
				echo '<input name="gonextpage" type="submit" '.$inputvalue.' />';
			}
			if ($lastbreakposition >= $maxitemcount) { //last page
				$inputvalue = 'value="'.get_string('save_entries', 'apply').'"';
				echo '<input name="savevalues" type="submit" '.$inputvalue.' />';
			}

			echo '</fieldset>';
			echo '</form>';
			echo $OUTPUT->box_end();

			echo $OUTPUT->box_start('apply_complete_cancel');
			if ($course_id) {
				$action = 'action="'.$CFG->wwwroot.'/course/view.php?id='.$course_id.'"';
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
			echo '<input type="hidden" name="course_id" value="'. $course_id . '" />';
			echo '<button type="submit">'.get_string('cancel').'</button>';
			echo '</fieldset>';
			echo '</form>';
			echo $OUTPUT->box_end();

			$SESSION->apply->is_started = true;
		}
	}
}



// cannot submit
else {
	echo $OUTPUT->box_start('generalbox boxaligncenter');
		echo '<h2>';
		echo '<font color="red">';
		echo get_string('this_apply_is_already_submitted', 'apply');
		echo '</font>';
		echo '</h2>';
		echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
	echo $OUTPUT->box_end();
}


///////////////////////////////////////////////////////////////////////////
/// Finish the page

echo $OUTPUT->footer();
